package es.israelzamora.srank.auth.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import es.israelzamora.srank.api.ErrorApi
import es.israelzamora.srank.auth.AuthRepositorio
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class EstadoLogin(
    val correo: String = "",
    val contrasena: String = "",
    val cargando: Boolean = false,
    val error: String? = null,
    val entrado: Boolean = false,
)

class LoginViewModel(private val auth: AuthRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoLogin())
    val estado: StateFlow<EstadoLogin> = _estado.asStateFlow()

    fun escribeCorreo(valor: String) = _estado.update { it.copy(correo = valor, error = null) }

    fun escribeContrasena(valor: String) =
        _estado.update { it.copy(contrasena = valor, error = null) }

    fun entrar() {
        val actual = _estado.value
        if (actual.cargando) return

        // Comprobar aquí no es cortesía: el login son 5 intentos por minuto y
        // por IP, y gastar uno en algo que se sabe que va a fallar deja al
        // usuario fuera antes de haber escrito bien.
        if (actual.correo.isBlank() || actual.contrasena.isBlank()) {
            _estado.update { it.copy(error = "Escribe tu correo y tu contraseña.") }
            return
        }

        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            auth.entrar(actual.correo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, entrado = true) } }
                .onFailure { fallo ->
                    _estado.update {
                        it.copy(
                            cargando = false,
                            error = (fallo as? ErrorApi)?.mensaje
                                ?: ErrorApi.Desconocido.mensaje,
                        )
                    }
                }
        }
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { LoginViewModel(auth) }
        }
    }
}
