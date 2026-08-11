package es.israelzamora.srank.auth.login

import es.israelzamora.srank.auth.ApiFalsa
import es.israelzamora.srank.auth.AuthRepositorio
import es.israelzamora.srank.session.SesionEnMemoria
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response
import java.net.UnknownHostException

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {

    private val dispatcher = StandardTestDispatcher()
    private lateinit var api: ApiFalsa
    private lateinit var sesion: SesionEnMemoria
    private lateinit var vm: LoginViewModel

    @Before
    fun preparar() {
        Dispatchers.setMain(dispatcher)
        api = ApiFalsa()
        sesion = SesionEnMemoria()
        vm = LoginViewModel(AuthRepositorio(api, sesion))
    }

    @After
    fun limpiar() {
        Dispatchers.resetMain()
    }

    private fun httpError(codigo: Int, cuerpo: String) = HttpException(
        Response.error<Any>(codigo, cuerpo.toResponseBody("application/json".toMediaType())),
    )

    @Test
    fun entrar_bien_guarda_la_sesion_y_avisa() = runTest {
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.entrar()
        advanceUntilIdle()

        assertTrue(vm.estado.value.entrado)
        assertNull(vm.estado.value.error)
        assertEquals("42|abcdef", sesion.token.first())
    }

    @Test
    fun credenciales_malas_llegan_como_422_y_se_ensenan_bajo_el_campo() = runTest {
        // Es un 422, no un 401: AuthController lanza ValidationException.
        api.respuestaLogin = {
            throw httpError(
                422,
                """{"message":"Credenciales incorrectas.",
                    "errors":{"email":["Credenciales incorrectas."]}}""",
            )
        }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("mala")

        vm.entrar()
        advanceUntilIdle()

        assertFalse(vm.estado.value.entrado)
        // "Credenciales incorrectas." con el punto: ErrorApi.kt (tarea 7) decide
        // explícitamente no recortarlo (ver el comentario ponytail sobre
        // capitaliza()), y TraductorErroresTest ya fija ese mismo valor con
        // punto. Ver desviación en el informe.
        assertEquals("Credenciales incorrectas.", vm.estado.value.error)
        assertNull("no se guarda sesión con credenciales malas", sesion.tokenActual)
    }

    @Test
    fun el_429_se_cuenta_en_castellano() = runTest {
        // El límite es 5 por minuto y llega en inglés desde el limitador.
        api.respuestaLogin = { throw httpError(429, """{"message":"Too Many Attempts."}""") }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.entrar()
        advanceUntilIdle()

        assertEquals(
            "Demasiados intentos. Espera un momento y vuelve a probar.",
            vm.estado.value.errorGeneral,
        )
        assertNull(vm.estado.value.error)
    }

    @Test
    fun sin_red_lo_dice_y_deja_reintentar() = runTest {
        api.respuestaLogin = { throw UnknownHostException("s-rank.israelzamora.es") }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.entrar()
        advanceUntilIdle()

        assertEquals(
            "No hay conexión. Comprueba el wifi o los datos.",
            vm.estado.value.errorGeneral,
        )
        assertNull(vm.estado.value.error)
        assertFalse(vm.estado.value.cargando)
    }

    @Test
    fun no_llama_al_servidor_con_los_campos_vacios() = runTest {
        // Gastar uno de los cinco intentos por minuto en algo que se sabe
        // que va a fallar es regalar el límite.
        vm.entrar()
        advanceUntilIdle()

        assertEquals(0, api.vecesLogin)
        assertEquals("Escribe tu correo y tu contraseña.", vm.estado.value.errorGeneral)
        assertNull(vm.estado.value.error)
    }

    @Test
    fun al_escribir_otra_vez_desaparece_el_error_de_campo_anterior() = runTest {
        api.respuestaLogin = {
            throw httpError(
                422,
                """{"message":"Credenciales incorrectas.",
                    "errors":{"email":["Credenciales incorrectas."]}}""",
            )
        }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("mala")
        vm.entrar()
        advanceUntilIdle()

        vm.escribeContrasena("otra")

        assertNull(vm.estado.value.error)
    }

    @Test
    fun un_429_tras_un_422_no_deja_los_dos_avisos_a_la_vez() = runTest {
        // Mismo fallo real que tuvo RegistroViewModel en la tarea 9: si las
        // dos banderas no se dejan explícitas en cada rama, el error de
        // campo del primer intento se queda pegado en pantalla junto al
        // general del segundo.
        api.respuestaLogin = {
            throw httpError(
                422,
                """{"message":"Credenciales incorrectas.",
                    "errors":{"email":["Credenciales incorrectas."]}}""",
            )
        }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("mala")
        vm.entrar()
        advanceUntilIdle()
        assertEquals("Credenciales incorrectas.", vm.estado.value.error)

        api.respuestaLogin = { throw httpError(429, """{"message":"Too Many Attempts."}""") }
        vm.entrar()
        advanceUntilIdle()

        assertEquals(
            "Demasiados intentos. Espera un momento y vuelve a probar.",
            vm.estado.value.errorGeneral,
        )
        assertNull("el error de campo del intento anterior no debe seguir puesto", vm.estado.value.error)
    }
}
