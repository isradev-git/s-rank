package es.israelzamora.srank.hoy

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.CorreoPeticionDto
import es.israelzamora.srank.api.HoyDto
import es.israelzamora.srank.api.LoginPeticionDto
import es.israelzamora.srank.api.LoginRespuestaDto
import es.israelzamora.srank.api.MensajeDto
import es.israelzamora.srank.api.PerfilDto
import es.israelzamora.srank.api.RegistroPeticionDto
import es.israelzamora.srank.api.ResetPeticionDto
import es.israelzamora.srank.api.UsuarioDto
import es.israelzamora.srank.api.jsonSrank
import es.israelzamora.srank.system.SystemRepositorio
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import java.net.UnknownHostException

private const val RESPUESTA_HOY = """
    {"date":"2026-08-11",
     "progress":{"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
                 "xp_for_next":400,"current_streak":12,"longest_streak":30,
                 "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}},
     "quests":[{"key":"water","label":"Beber 2 litros de agua","target":2000,
                "progress":2000,"xp_reward":10,"is_optional":false,"completed":true}]}
"""

private class ApiHoyFalsa(var respuesta: () -> HoyDto) : ApiSrank {
    var veces = 0
        private set

    override suspend fun hoy(): HoyDto {
        veces++
        return respuesta()
    }

    override suspend fun login(peticion: LoginPeticionDto): LoginRespuestaDto = error("no")
    override suspend fun registro(peticion: RegistroPeticionDto): LoginRespuestaDto = error("no")
    override suspend fun olvideContrasena(peticion: CorreoPeticionDto): MensajeDto = error("no")
    override suspend fun cambiaContrasena(peticion: ResetPeticionDto): MensajeDto = error("no")
    override suspend fun salir(): MensajeDto = error("no")
    override suspend fun usuario(): UsuarioDto = error("no")
    override suspend fun perfil(): PerfilDto = error("no")
}

@OptIn(ExperimentalCoroutinesApi::class)
class HoyViewModelTest {

    private val dispatcher = StandardTestDispatcher()

    @Before fun preparar() = Dispatchers.setMain(dispatcher)

    @After fun limpiar() = Dispatchers.resetMain()

    private fun vmCon(api: ApiHoyFalsa) = HoyViewModel(SystemRepositorio(api))

    @Test
    fun carga_el_progreso_y_las_misiones() = runTest {
        val api = ApiHoyFalsa { jsonSrank.decodeFromString(RESPUESTA_HOY) }
        val vm = vmCon(api)

        vm.carga()
        advanceUntilIdle()

        val estado = vm.estado.value
        assertEquals(4, estado.hoy?.progreso?.nivel)
        assertEquals("martes, 11 de agosto", estado.hoy?.dia)
        assertEquals(1, estado.hoy?.misiones?.size)
        assertNull(estado.error)
        assertEquals(false, estado.cargando)
    }

    @Test
    fun sin_red_lo_dice_en_castellano() = runTest {
        val api = ApiHoyFalsa { throw UnknownHostException("s-rank") }
        val vm = vmCon(api)

        vm.carga()
        advanceUntilIdle()

        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)
        assertNull(vm.estado.value.hoy)
    }

    @Test
    fun reintentar_vuelve_a_pedir() = runTest {
        var fallar = true
        val api = ApiHoyFalsa {
            if (fallar) throw UnknownHostException("s-rank")
            else jsonSrank.decodeFromString(RESPUESTA_HOY)
        }
        val vm = vmCon(api)
        vm.carga()
        advanceUntilIdle()

        fallar = false
        vm.carga()
        advanceUntilIdle()

        assertEquals(2, api.veces)
        assertNull(vm.estado.value.error)
        assertEquals(4, vm.estado.value.hoy?.progreso?.nivel)
    }

    @Test
    fun plegar_la_seccion_no_borra_lo_cargado() = runTest {
        val api = ApiHoyFalsa { jsonSrank.decodeFromString(RESPUESTA_HOY) }
        val vm = vmCon(api)
        vm.carga()
        advanceUntilIdle()

        vm.plegaMisiones()

        assertTrue(!vm.estado.value.misionesDesplegadas)
        assertEquals(4, vm.estado.value.hoy?.progreso?.nivel)
    }
}
