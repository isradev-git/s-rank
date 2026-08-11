package es.israelzamora.srank.auth.recuperar

import es.israelzamora.srank.auth.ApiFalsa
import es.israelzamora.srank.auth.AuthRepositorio
import es.israelzamora.srank.session.SesionEnMemoria
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class RecuperarViewModelTest {

    private val dispatcher = StandardTestDispatcher()
    private lateinit var api: ApiFalsa
    private lateinit var vm: RecuperarViewModel

    @Before fun preparar() {
        Dispatchers.setMain(dispatcher)
        api = ApiFalsa()
        vm = RecuperarViewModel(AuthRepositorio(api, SesionEnMemoria()))
    }

    @After fun limpiar() = Dispatchers.resetMain()

    @Test
    fun con_un_correo_registrado_avanza_al_paso_dos() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")

        vm.pideCodigo()
        advanceUntilIdle()

        assertEquals(PasoRecuperar.CODIGO, vm.estado.value.paso)
    }

    @Test
    fun con_un_correo_que_no_existe_avanza_exactamente_igual() = runTest {
        // ESTA ES LA REGLA. El servidor responde 200 exista o no la cuenta.
        // Si la pantalla se comportara distinto, este endpoint volvería a ser
        // una lista de qué correos están registrados.
        vm.escribeCorreo("noexiste@ejemplo.es")

        vm.pideCodigo()
        advanceUntilIdle()

        assertEquals(PasoRecuperar.CODIGO, vm.estado.value.paso)
        assertNull(vm.estado.value.error)
    }

    @Test
    fun el_texto_es_el_mismo_en_los_dos_casos() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        val conCuenta = vm.estado.value.aviso

        val otro = RecuperarViewModel(AuthRepositorio(ApiFalsa(), SesionEnMemoria()))
        otro.escribeCorreo("noexiste@ejemplo.es")
        otro.pideCodigo()
        advanceUntilIdle()

        assertEquals(conCuenta, otro.estado.value.aviso)
        assertTrue(conCuenta!!.startsWith("Si ese correo está registrado"))
    }

    @Test
    fun un_fallo_de_red_si_se_cuenta_y_no_avanza() = runTest {
        // Quedarse callado ante un fallo real dejaría al usuario esperando un
        // correo que nunca se pidió. Esto no delata nada: pasa igual exista o
        // no la cuenta.
        api.respuestaOlvide = { throw java.net.UnknownHostException("s-rank") }
        vm.escribeCorreo("existe@ejemplo.es")

        vm.pideCodigo()
        advanceUntilIdle()

        assertEquals(PasoRecuperar.CORREO, vm.estado.value.paso)
        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)
    }

    @Test
    fun un_codigo_mal_lo_dice_y_deja_reintentar() = runTest {
        api.respuestaReset = {
            throw HttpException(
                Response.error<Any>(
                    422,
                    """{"message":"El código no es válido o ha caducado.",
                        "errors":{"code":["El código no es válido o ha caducado."]}}"""
                        .toResponseBody("application/json".toMediaType()),
                ),
            )
        }
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("000000")
        vm.escribeContrasena("micontrasenanueva")

        vm.cambiaContrasena()
        advanceUntilIdle()

        assertEquals("El código no es válido o ha caducado.", vm.estado.value.errorCodigo)
        assertEquals(PasoRecuperar.CODIGO, vm.estado.value.paso)
    }

    @Test
    fun el_codigo_tiene_seis_cifras_y_se_comprueba_antes_de_llamar() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("123")
        vm.escribeContrasena("micontrasenanueva")

        vm.cambiaContrasena()
        advanceUntilIdle()

        assertEquals("El código son 6 cifras.", vm.estado.value.errorCodigo)
    }

    @Test
    fun un_fallo_de_red_tras_un_codigo_invalido_no_deja_los_dos_avisos_a_la_vez() = runTest {
        // Mismo fallo que en registro (tarea 9): si la rama sin Validacion no
        // limpia errorCodigo explicitamente, el aviso del intento anterior se
        // queda pegado en pantalla junto al nuevo.
        api.respuestaReset = {
            throw HttpException(
                Response.error<Any>(
                    422,
                    """{"message":"El código no es válido o ha caducado.",
                        "errors":{"code":["El código no es válido o ha caducado."]}}"""
                        .toResponseBody("application/json".toMediaType()),
                ),
            )
        }
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("000000")
        vm.escribeContrasena("micontrasenanueva")
        vm.cambiaContrasena()
        advanceUntilIdle()
        assertEquals("El código no es válido o ha caducado.", vm.estado.value.errorCodigo)

        api.respuestaReset = { throw java.net.UnknownHostException("s-rank") }
        vm.cambiaContrasena()
        advanceUntilIdle()

        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)
        assertNull(vm.estado.value.errorCodigo)
        assertNull(vm.estado.value.errorContrasena)
    }

    @Test
    fun un_codigo_corto_tras_un_fallo_de_red_no_deja_los_dos_avisos_a_la_vez() = runTest {
        // La otra mitad del mismo fallo: la validacion local tampoco limpiaba
        // el error general si uno anterior se habia quedado puesto.
        api.respuestaReset = { throw java.net.UnknownHostException("s-rank") }
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("123456")
        vm.escribeContrasena("micontrasenanueva")
        vm.cambiaContrasena()
        advanceUntilIdle()
        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)

        vm.escribeCodigo("12")
        vm.cambiaContrasena()
        advanceUntilIdle()

        assertEquals("El código son 6 cifras.", vm.estado.value.errorCodigo)
        assertNull(vm.estado.value.error)
    }

    @Test
    fun cambiar_bien_termina() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("123456")
        vm.escribeContrasena("micontrasenanueva")

        vm.cambiaContrasena()
        advanceUntilIdle()

        assertTrue(vm.estado.value.cambiada)
    }
}
