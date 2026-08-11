package es.israelzamora.srank.auth.registro

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

data class EstadoRegistro(
    val nombre: String = "",
    val correo: String = "",
    val contrasena: String = "",
    val cargando: Boolean = false,
    val errorNombre: String? = null,
    val errorCorreo: String? = null,
    val errorContrasena: String? = null,
    val errorGeneral: String? = null,
    val registrado: Boolean = false,
)

/** El mínimo que exige el servidor (`min:8` en AuthController::register). */
private const val MINIMO_CONTRASENA = 8

class RegistroViewModel(private val auth: AuthRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoRegistro())
    val estado: StateFlow<EstadoRegistro> = _estado.asStateFlow()

    fun escribeNombre(v: String) = _estado.update { it.copy(nombre = v, errorNombre = null) }
    fun escribeCorreo(v: String) = _estado.update { it.copy(correo = v, errorCorreo = null) }
    fun escribeContrasena(v: String) =
        _estado.update { it.copy(contrasena = v, errorContrasena = null) }

    fun registrar() {
        val actual = _estado.value
        if (actual.cargando) return

        // El registro son 3 por hora y por IP. Gastar uno en algo que el
        // servidor va a rechazar seguro deja al usuario una hora fuera.
        val fallos = EstadoRegistro(
            errorNombre = "Escribe tu nombre.".takeIf { actual.nombre.isBlank() },
            errorCorreo = "Escribe tu correo.".takeIf { actual.correo.isBlank() },
            errorContrasena = "La contraseña necesita $MINIMO_CONTRASENA caracteres como mínimo."
                .takeIf { actual.contrasena.length < MINIMO_CONTRASENA },
        )
        if (fallos.errorNombre != null || fallos.errorCorreo != null ||
            fallos.errorContrasena != null
        ) {
            _estado.update {
                it.copy(
                    errorNombre = fallos.errorNombre,
                    errorCorreo = fallos.errorCorreo,
                    errorContrasena = fallos.errorContrasena,
                )
            }
            return
        }

        _estado.update { it.copy(cargando = true, errorGeneral = null) }

        viewModelScope.launch {
            auth.registrar(actual.nombre, actual.correo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, registrado = true) } }
                .onFailure { fallo -> _estado.update { it.conFallo(fallo) } }
        }
    }

    /**
     * Un 422 trae el campo que falla, así que el mensaje va debajo de ese campo
     * y no en un aviso suelto que obliga a adivinar cuál era.
     */
    private fun EstadoRegistro.conFallo(fallo: Throwable): EstadoRegistro {
        val error = fallo as? ErrorApi ?: ErrorApi.Desconocido
        if (error !is ErrorApi.Validacion) {
            return copy(cargando = false, errorGeneral = error.mensaje)
        }
        return copy(
            cargando = false,
            errorNombre = error.porCampo["name"],
            errorCorreo = error.porCampo["email"],
            errorContrasena = error.porCampo["password"],
            errorGeneral = if (error.porCampo.isEmpty()) error.mensaje else null,
        )
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { RegistroViewModel(auth) }
        }
    }
}
