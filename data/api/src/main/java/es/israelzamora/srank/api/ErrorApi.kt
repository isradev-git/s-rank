package es.israelzamora.srank.api

import retrofit2.HttpException
import java.io.IOException

/**
 * Los errores tal como se cuentan en pantalla. Se traducen aquí, una sola vez,
 * y las pantallas enseñan el texto ya hecho.
 *
 * Ningún código HTTP sale de este fichero: «No hay conexión», no «Error 503».
 */
sealed class ErrorApi(val mensaje: String) : Exception(mensaje) {

    data object SinRed : ErrorApi("No hay conexión. Comprueba el wifi o los datos.")

    data object SesionCaducada : ErrorApi("Tu sesión ha caducado. Vuelve a entrar.")

    data object DemasiadosIntentos :
        ErrorApi("Demasiados intentos. Espera un momento y vuelve a probar.")

    data object Desconocido : ErrorApi("No hemos podido conectar. Inténtalo otra vez.")

    /** 422 de Laravel: el mensaje ya viene en español desde `lang/es`. */
    class Validacion(
        val porCampo: Map<String, String>,
        mensaje: String,
    ) : ErrorApi(mensaje)
}

/**
 * Traduce lo que suelte la red a algo que se pueda enseñar.
 *
 * El 429 es el único que pone su texto la app: lo emite el limitador de Laravel
 * antes de entrar en la ruta, así que no pasa por `lang/es` y llega en inglés
 * («Too Many Attempts.»).
 */
fun traduceError(t: Throwable): ErrorApi = when {
    t is ErrorApi -> t
    t is IOException -> ErrorApi.SinRed
    t is HttpException -> when (t.code()) {
        401 -> ErrorApi.SesionCaducada
        422 -> leeValidacion(t)
        429 -> ErrorApi.DemasiadosIntentos
        else -> ErrorApi.Desconocido
    }
    else -> ErrorApi.Desconocido
}

private fun leeValidacion(t: HttpException): ErrorApi {
    val cuerpo = t.response()?.errorBody()?.string().orEmpty()

    val dto = runCatching { jsonSrank.decodeFromString<ErrorValidacionDto>(cuerpo) }
        .getOrNull() ?: return ErrorApi.Desconocido

    val porCampo = dto.errors
        .mapNotNull { (campo, mensajes) ->
            mensajes.firstOrNull()?.let { campo to capitaliza(it) }
        }
        .toMap()

    val principal = porCampo.values.firstOrNull()
        ?: dto.message.takeIf { it.isNotBlank() }?.let(::capitaliza)
        ?: return ErrorApi.Desconocido

    return ErrorApi.Validacion(porCampo, principal)
}

/**
 * Los mensajes de `lang/es` empiezan en minúscula porque van debajo de un
 * campo. Al enseñarlos sueltos quedan mal, así que se capitalizan al pintar.
 *
 * ponytail: el brief original recortaba el punto final con `trimEnd('.')`
 * para que el mensaje no acabara en dos puntos dentro de una frase, pero
 * ningún test ejercita esa situación (el único caso con punto final,
 * "Credenciales incorrectas.", espera conservarlo tal cual) y sí hay un test
 * que exige que el punto se mantenga. Se quita el recorte: el techo es que
 * si en el futuro un mensaje de validación con punto final se empotra dentro
 * de otra frase con su propio punto, puede verse ".." y habrá que revisar
 * esto con un test que lo describa primero.
 */
private fun capitaliza(texto: String): String =
    texto.replaceFirstChar { it.uppercase() }
