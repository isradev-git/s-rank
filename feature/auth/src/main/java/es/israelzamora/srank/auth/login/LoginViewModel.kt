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
    val errorGeneral: String? = null,
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
        // usuario fuera antes de haber escrito bien. Este aviso habla de los
        // dos campos a la vez, así que va de general y no bajo uno solo.
        if (actual.correo.isBlank() || actual.contrasena.isBlank()) {
            _estado.update {
                it.copy(error = null, errorGeneral = "Escribe tu correo y tu contraseña.")
            }
            return
        }

        _estado.update { it.copy(cargando = true, error = null, errorGeneral = null) }

        viewModelScope.launch {
            auth.entrar(actual.correo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, entrado = true) } }
                .onFailure { fallo -> _estado.update { it.conFallo(fallo) } }
        }
    }

    /**
     * Mismo patrón que `RegistroViewModel`/`RecuperarViewModel`: `error` es
     * el hueco de campo (bajo la contraseña, vía `supportingText` de
     * `CampoSRank`) y solo lo ocupa un `ErrorApi.Validacion` con campo
     * conocido. Todo lo demás —sin red, 429, un 422 sin campo— es
     * `errorGeneral`, bajo el botón. Las dos ramas dejan las dos banderas
     * explícitas: si no fuera así, un 422 seguido de un 429 dejaría el
     * mensaje de campo del primer intento pegado en pantalla junto al
     * general del segundo (el mismo fallo real que tuvo
     * `RegistroViewModel.conFallo` en la tarea 9).
     */
    private fun EstadoLogin.conFallo(fallo: Throwable): EstadoLogin {
        val error = fallo as? ErrorApi ?: ErrorApi.Desconocido
        return if (error is ErrorApi.Validacion && error.porCampo.isNotEmpty()) {
            copy(cargando = false, error = error.mensaje, errorGeneral = null)
        } else {
            copy(cargando = false, error = null, errorGeneral = error.mensaje)
        }
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { LoginViewModel(auth) }
        }
    }
}
