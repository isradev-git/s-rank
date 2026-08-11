package es.israelzamora.srank.auth.recuperar

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

enum class PasoRecuperar { CORREO, CODIGO }

data class EstadoRecuperar(
    val paso: PasoRecuperar = PasoRecuperar.CORREO,
    val correo: String = "",
    val codigo: String = "",
    val contrasena: String = "",
    val cargando: Boolean = false,
    val aviso: String? = null,
    val error: String? = null,
    val errorCodigo: String? = null,
    val errorContrasena: String? = null,
    val cambiada: Boolean = false,
)

/**
 * El texto que se enseña **siempre**, exista o no la cuenta. No hay ninguna
 * otra rama: si la hubiera, el endpoint volvería a ser una lista de qué
 * correos están registrados.
 */
private const val AVISO_UNICO =
    "Si ese correo está registrado, te hemos enviado un código de 6 cifras. " +
        "Caduca en 30 minutos."

private const val MINIMO_CONTRASENA = 8

class RecuperarViewModel(private val auth: AuthRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoRecuperar())
    val estado: StateFlow<EstadoRecuperar> = _estado.asStateFlow()

    fun escribeCorreo(v: String) = _estado.update { it.copy(correo = v, error = null) }

    fun escribeCodigo(v: String) =
        _estado.update { it.copy(codigo = v.filter(Char::isDigit).take(6), errorCodigo = null) }

    fun escribeContrasena(v: String) =
        _estado.update { it.copy(contrasena = v, errorContrasena = null) }

    /**
     * Avanza al paso 2 **siempre que el servidor conteste**, tenga cuenta ese
     * correo o no. Solo un fallo de red o un 429 dejan el paso donde estaba, y
     * eso pasa igual en los dos casos, así que no dice nada de nadie.
     */
    fun pideCodigo() {
        val actual = _estado.value
        if (actual.cargando) return

        if (actual.correo.isBlank()) {
            _estado.update { it.copy(error = "Escribe tu correo.") }
            return
        }

        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            auth.pideCodigo(actual.correo)
                .onSuccess {
                    _estado.update {
                        it.copy(
                            cargando = false,
                            paso = PasoRecuperar.CODIGO,
                            aviso = AVISO_UNICO,
                        )
                    }
                }
                .onFailure { fallo ->
                    _estado.update {
                        it.copy(
                            cargando = false,
                            error = (fallo as? ErrorApi)?.mensaje ?: ErrorApi.Desconocido.mensaje,
                        )
                    }
                }
        }
    }

    fun cambiaContrasena() {
        val actual = _estado.value
        if (actual.cargando) return

        val errorCodigo = "El código son 6 cifras.".takeIf { actual.codigo.length != 6 }
        val errorContrasena =
            "La contraseña necesita $MINIMO_CONTRASENA caracteres como mínimo."
                .takeIf { actual.contrasena.length < MINIMO_CONTRASENA }

        if (errorCodigo != null || errorContrasena != null) {
            // Las tres banderas quedan explícitas: si no se limpiara `error`,
            // el aviso general de un intento anterior (red, 429...) se
            // quedaría pegado en pantalla junto al error de campo nuevo.
            _estado.update {
                it.copy(errorCodigo = errorCodigo, errorContrasena = errorContrasena, error = null)
            }
            return
        }

        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            auth.cambiaContrasena(actual.correo, actual.codigo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, cambiada = true) } }
                .onFailure { fallo -> _estado.update { it.conFallo(fallo) } }
        }
    }

    /**
     * Un 422 trae el campo que falla (code o password), así que el mensaje va
     * debajo de ese campo. Las dos ramas dejan las tres banderas —errorCodigo,
     * errorContrasena, error— en un estado explícito y completo: si un
     * intento anterior dejó puesto un error de campo (o uno general) y este
     * trae el otro tipo, no pueden convivir los dos a la vez. Es el mismo
     * fallo real que tuvo `RegistroViewModel.conFallo` en la tarea 9.
     */
    private fun EstadoRecuperar.conFallo(fallo: Throwable): EstadoRecuperar {
        val error = fallo as? ErrorApi ?: ErrorApi.Desconocido
        if (error !is ErrorApi.Validacion) {
            return copy(
                cargando = false,
                errorCodigo = null,
                errorContrasena = null,
                error = error.mensaje,
            )
        }
        return copy(
            cargando = false,
            errorCodigo = error.porCampo["code"],
            errorContrasena = error.porCampo["password"],
            error = if (error.porCampo.isEmpty()) error.mensaje else null,
        )
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { RecuperarViewModel(auth) }
        }
    }
}
