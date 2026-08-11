package es.israelzamora.srank.api

import es.israelzamora.srank.session.Sesion
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.runCurrent
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.withTimeoutOrNull
import okhttp3.Interceptor
import okhttp3.Protocol
import okhttp3.Request
import okhttp3.Response
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test
import java.lang.reflect.Proxy

/**
 * `InterceptorSrank` pasó de `private` a `internal` solo para que este test
 * pueda instanciarlo: sigue sin salir del módulo, así que la visibilidad de
 * cara a otros módulos no cambia.
 */
class InterceptorSrankTest {

    /**
     * Un `Sesion` de mentira escrito a mano (mismo patrón que `SesionEnMemoria`
     * y que `ApiFalsa` en la tarea 8), con un contador de `limpia()` para poder
     * afirmar que el interceptor no la llama cuando no hay token.
     */
    private class SesionFalsa(override var tokenActual: String? = null) : Sesion {
        var vecesLimpia = 0
            private set

        override val token: Flow<String?> = flowOf(tokenActual)
        override val nombre: Flow<String?> = flowOf(null)

        override suspend fun guarda(token: String, nombre: String) {
            tokenActual = token
        }

        override suspend fun limpia() {
            vecesLimpia++
            tokenActual = null
        }
    }

    /**
     * Un `Interceptor.Chain` de mentira, sin red ni `MockWebServer`.
     *
     * OkHttp 5.4.0 amplió esta interfaz a unos 40 métodos (timeouts, DNS,
     * proxy, TLS...) porque ahora un interceptor puede leer y reescribir la
     * configuración del cliente por llamada — comprobado con
     * `javap -p Interceptor$Chain.class`, no es un recuerdo de una versión
     * vieja de OkHttp. `InterceptorSrank` solo usa `request()` y `proceed()`,
     * así que en vez de implementar a mano los otros ~38 (puro ruido que
     * nunca se ejecuta) se usa un proxy dinámico del JDK: cualquier otro
     * método revienta con `UnsupportedOperationException`, que es justo lo
     * que se quiere si el interceptor empezara a usarlos sin que este test se
     * actualizara.
     */
    private class CadenaFalsa(private val peticion: Request, private val respuesta: Response) {
        var recibida: Request? = null
            private set

        val chain: Interceptor.Chain =
            Proxy.newProxyInstance(
                Interceptor.Chain::class.java.classLoader,
                arrayOf(Interceptor.Chain::class.java),
            ) { _, method, args ->
                when (method.name) {
                    "request" -> peticion
                    "proceed" -> {
                        recibida = args[0] as Request
                        respuesta
                    }
                    else -> throw UnsupportedOperationException(
                        "CadenaFalsa: ${method.name}() no debería hacer falta",
                    )
                }
            } as Interceptor.Chain
    }

    private fun peticionSinCabeceras(): Request =
        Request.Builder().url("https://s-rank.israelzamora.es/api/system/today").build()

    private fun respuestaConCodigo(peticion: Request, codigo: Int): Response =
        Response.Builder()
            .request(peticion)
            .protocol(Protocol.HTTP_1_1)
            .code(codigo)
            .message("da igual")
            .build()

    @Test
    fun accept_json_va_en_toda_peticion_aunque_no_haya_token() = runTest {
        val peticion = peticionSinCabeceras()
        val cadena = CadenaFalsa(peticion, respuestaConCodigo(peticion, 200))
        val interceptor = InterceptorSrank(SesionFalsa(tokenActual = null))

        interceptor.intercept(cadena.chain)

        assertEquals("application/json", cadena.recibida?.header("Accept"))
    }

    @Test
    fun con_token_anade_authorization_bearer() = runTest {
        val peticion = peticionSinCabeceras()
        val cadena = CadenaFalsa(peticion, respuestaConCodigo(peticion, 200))
        val interceptor = InterceptorSrank(SesionFalsa(tokenActual = "42|abcdef"))

        interceptor.intercept(cadena.chain)

        assertEquals("Bearer 42|abcdef", cadena.recibida?.header("Authorization"))
    }

    @OptIn(ExperimentalCoroutinesApi::class)
    @Test
    fun sin_token_no_anade_authorization_ni_hace_nada_mas() = runTest {
        // Contrato de la tarea 6: tokenActual == null es "no hay token ahora
        // mismo", nunca "no hay sesión". El interceptor no debe decidir nada
        // por su cuenta: ni limpiar la sesión, ni tratarlo como logout, ni
        // avisar de que la sesión caducó.
        val recibidoAviso = async { withTimeoutOrNull(100) { SesionExpirada.avisos.first() } }
        runCurrent()

        val peticion = peticionSinCabeceras()
        val cadena = CadenaFalsa(peticion, respuestaConCodigo(peticion, 200))
        val sesion = SesionFalsa(tokenActual = null)
        val interceptor = InterceptorSrank(sesion)

        interceptor.intercept(cadena.chain)

        assertNull(cadena.recibida?.header("Authorization"))
        assertEquals(0, sesion.vecesLimpia)
        assertNull("un token nulo no debe avisar de sesión caducada", recibidoAviso.await())
    }

    @OptIn(ExperimentalCoroutinesApi::class)
    @Test
    fun un_401_dispara_un_aviso_de_sesion_expirada() = runTest {
        // El colector se suscribe antes del intercept: con replay = 0 en
        // SharedFlow, un aviso emitido sin colector activo no llega a quien
        // se suscribe después (la lección que costó el primer intento del
        // test de SesionExpirada en RedTest.kt).
        val recibido = async { SesionExpirada.avisos.first() }
        runCurrent()

        val peticion = peticionSinCabeceras()
        val cadena = CadenaFalsa(peticion, respuestaConCodigo(peticion, 401))
        val interceptor = InterceptorSrank(SesionFalsa(tokenActual = "un-token-caducado"))

        interceptor.intercept(cadena.chain)

        assertEquals(Unit, recibido.await())
    }

    @OptIn(ExperimentalCoroutinesApi::class)
    @Test
    fun un_200_no_dispara_ningun_aviso() = runTest {
        val recibido = async { withTimeoutOrNull(100) { SesionExpirada.avisos.first() } }
        runCurrent()

        val peticion = peticionSinCabeceras()
        val cadena = CadenaFalsa(peticion, respuestaConCodigo(peticion, 200))
        val interceptor = InterceptorSrank(SesionFalsa(tokenActual = "un-token-valido"))

        interceptor.intercept(cadena.chain)

        assertNull("un 200 no debe emitir ningún aviso", recibido.await())
    }
}
