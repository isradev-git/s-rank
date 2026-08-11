package es.israelzamora.srank.auth.registro

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
class RegistroViewModelTest {

    private val dispatcher = StandardTestDispatcher()
    private lateinit var api: ApiFalsa
    private lateinit var sesion: SesionEnMemoria
    private lateinit var vm: RegistroViewModel

    @Before fun preparar() {
        Dispatchers.setMain(dispatcher)
        api = ApiFalsa()
        sesion = SesionEnMemoria()
        vm = RegistroViewModel(AuthRepositorio(api, sesion))
    }

    @After fun limpiar() = Dispatchers.resetMain()

    private fun rellenaBien() {
        vm.escribeNombre("Israel")
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("micontrasena")
    }

    @Test
    fun registrarse_bien_deja_la_sesion_puesta() = runTest {
        rellenaBien()

        vm.registrar()
        advanceUntilIdle()

        assertTrue(vm.estado.value.registrado)
        assertEquals("43|ghijkl", sesion.tokenActual)
    }

    @Test
    fun una_contrasena_corta_se_avisa_antes_de_llamar() = runTest {
        // El registro son 3 por hora. Gastar uno en algo que el servidor va a
        // rechazar seguro deja al usuario una hora fuera.
        vm.escribeNombre("Israel")
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("corta")

        vm.registrar()
        advanceUntilIdle()

        assertEquals("La contraseña necesita 8 caracteres como mínimo.",
            vm.estado.value.errorContrasena)
        assertNull(sesion.tokenActual)
    }

    @Test
    fun ocho_caracteres_justos_valen() = runTest {
        vm.escribeNombre("Israel")
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("12345678")

        vm.registrar()
        advanceUntilIdle()

        assertTrue(vm.estado.value.registrado)
    }

    @Test
    fun el_correo_repetido_se_ensena_bajo_su_campo() = runTest {
        api.respuestaRegistro = {
            throw HttpException(
                Response.error<Any>(
                    422,
                    """{"message":"El correo ya está en uso.",
                        "errors":{"email":["el correo ya está en uso"]}}"""
                        .toResponseBody("application/json".toMediaType()),
                ),
            )
        }
        rellenaBien()

        vm.registrar()
        advanceUntilIdle()

        assertEquals("El correo ya está en uso", vm.estado.value.errorCorreo)
        assertNull(vm.estado.value.errorGeneral)
    }

    @Test
    fun sin_nombre_no_se_llama_al_servidor() = runTest {
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.registrar()
        advanceUntilIdle()

        assertEquals("Escribe tu nombre.", vm.estado.value.errorNombre)
    }
}
