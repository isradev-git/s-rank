package es.israelzamora.srank.api

import es.israelzamora.srank.session.SesionEnMemoria
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runCurrent
import kotlinx.coroutines.test.runTest
import okhttp3.MediaType.Companion.toMediaType
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory
import java.io.IOException

class RedTest {

    @Test
    fun pide_envuelve_el_resultado_bueno_en_success() = runTest {
        val resultado = pide { "hola" }

        assertEquals(Result.success("hola"), resultado)
    }

    @Test
    fun pide_traduce_los_fallos_conocidos_a_errorapi() = runTest {
        val resultado = pide<Unit> { throw IOException("socket cerrado") }

        assertEquals(ErrorApi.SinRed, resultado.exceptionOrNull())
    }

    @Test
    fun pide_relanza_la_cancelacion_en_lugar_de_traducirla() = runTest {
        // Si `pide` capturase Throwable a secas, salir de una pantalla con una
        // petición en vuelo se traduciría en "No hemos podido conectar" en vez
        // de morir en silencio como corresponde a una corrutina cancelada.
        var relanzada = false

        try {
            pide<Unit> { throw CancellationException("la pantalla se cerró") }
        } catch (e: CancellationException) {
            relanzada = true
        }

        assertTrue("CancellationException debe relanzarse, no traducirse", relanzada)
    }

    @OptIn(ExperimentalCoroutinesApi::class)
    @Test
    fun avisa_llega_a_quien_ya_esta_escuchando() = runTest {
        // extraBufferCapacity = 1: el interceptor corre en un hilo de OkHttp y
        // no puede suspender para emitir aunque el colector (la raíz de
        // navegación) todavía no haya procesado el aviso anterior.
        val recibido = async { SesionExpirada.avisos.first() }
        runCurrent() // deja que el colector se suscriba antes de avisar

        SesionExpirada.avisa()

        assertEquals(Unit, recibido.await())
    }

    @Test
    fun creaApi_construye_la_interfaz_sin_reventar() {
        val api = creaApi(SesionEnMemoria(), "http://localhost/")

        assertNotNull(api)
    }

    /**
     * `creaApi_construye_la_interfaz_sin_reventar` solo comprueba que el
     * `Retrofit.create()` no da null. No basta: Retrofit por defecto valida
     * cada anotación **la primera vez que se llama a ese método**, no al
     * crear la interfaz, así que un `@Body` mal puesto o una ruta sin `/`
     * inicial en uno de los ocho endpoints de `ApiSrank` podría pasar la
     * suite entera sin que nadie lo llamase — la misma lección que costó
     * meses con `/informe-salud` en el backend (ver CLAUDE.md, «lo que no
     * tiene test no lo cubre nadie»). `validateEagerly(true)` mueve esa
     * validación a la construcción, así que este test la ejercita sobre las
     * ocho de golpe sin tener que llamarlas una a una.
     */
    @Test
    fun con_validacion_eager_las_ocho_anotaciones_de_apisrank_son_validas() {
        val retrofit = Retrofit.Builder()
            .baseUrl("http://localhost/")
            .addConverterFactory(jsonSrank.asConverterFactory("application/json".toMediaType()))
            .validateEagerly(true)
            .build()

        val api = retrofit.create(ApiSrank::class.java)

        assertNotNull(api)
    }
}
