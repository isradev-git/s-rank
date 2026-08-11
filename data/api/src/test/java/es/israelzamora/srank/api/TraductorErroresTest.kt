package es.israelzamora.srank.api

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response
import java.io.IOException
import java.net.UnknownHostException

class TraductorErroresTest {

    private fun httpConCuerpo(codigo: Int, cuerpo: String): HttpException =
        HttpException(
            Response.error<Any>(
                codigo,
                cuerpo.toResponseBody("application/json".toMediaType()),
            ),
        )

    @Test
    fun sin_red_se_cuenta_en_castellano_y_sin_codigos() {
        val e = traduceError(UnknownHostException("s-rank.israelzamora.es"))

        assertEquals(ErrorApi.SinRed, e)
        assertEquals("No hay conexión. Comprueba el wifi o los datos.", e.mensaje)
    }

    @Test
    fun cualquier_fallo_de_entrada_salida_es_falta_de_red() {
        assertEquals(ErrorApi.SinRed, traduceError(IOException("socket cerrado")))
    }

    @Test
    fun el_401_es_sesion_caducada() {
        val e = traduceError(httpConCuerpo(401, """{"message":"Unauthenticated."}"""))

        assertEquals(ErrorApi.SesionCaducada, e)
    }

    @Test
    fun el_422_usa_el_mensaje_del_servidor_capitalizado() {
        // Los mensajes de lang/es empiezan en minúscula porque van debajo de
        // un campo. Al pintarlos sueltos hay que capitalizarlos.
        val cuerpo = """{"message":"El correo ya está en uso.",
            "errors":{"email":["el correo ya está en uso"]}}"""

        val e = traduceError(httpConCuerpo(422, cuerpo)) as ErrorApi.Validacion

        assertEquals("El correo ya está en uso", e.porCampo["email"])
        assertEquals("El correo ya está en uso", e.mensaje)
    }

    @Test
    fun el_422_del_login_llega_por_el_campo_email() {
        // Ojo: credenciales malas NO son un 401, son un 422 con este cuerpo.
        // Lo lanza ValidationException en AuthController::login.
        val cuerpo = """{"message":"Credenciales incorrectas.",
            "errors":{"email":["Credenciales incorrectas."]}}"""

        val e = traduceError(httpConCuerpo(422, cuerpo)) as ErrorApi.Validacion

        assertEquals("Credenciales incorrectas.", e.porCampo["email"])
    }

    @Test
    fun el_429_lo_traduce_la_app_porque_el_servidor_no_puede() {
        // El limitador de Laravel responde antes de entrar en la ruta, así que
        // no pasa por lang/es. Es el único texto que pone la app.
        val e = traduceError(httpConCuerpo(429, """{"message":"Too Many Attempts."}"""))

        assertEquals(ErrorApi.DemasiadosIntentos, e)
        assertEquals("Demasiados intentos. Espera un momento y vuelve a probar.", e.mensaje)
        assertTrue("no puede colarse el inglés del servidor", !e.mensaje.contains("Attempts"))
    }

    @Test
    fun un_500_no_ensena_el_numero() {
        val e = traduceError(httpConCuerpo(500, "<html>Server Error</html>"))

        assertEquals(ErrorApi.Desconocido, e)
        assertEquals("No hemos podido conectar. Inténtalo otra vez.", e.mensaje)
        assertTrue("ningún código HTTP en pantalla", !e.mensaje.contains("500"))
    }

    @Test
    fun un_422_con_el_cuerpo_roto_no_revienta() {
        // Si el servidor devolviera HTML por olvidar Accept: application/json,
        // la app tiene que decir algo en castellano, no propagar la excepción.
        val e = traduceError(httpConCuerpo(422, "<html>lo que sea</html>"))

        assertEquals(ErrorApi.Desconocido, e)
    }
}
