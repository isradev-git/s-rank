package es.israelzamora.srank.system

import es.israelzamora.srank.api.jsonSrank
import es.israelzamora.srank.api.HoyDto
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ModelosTest {

    private val respuestaReal = """
        {"date":"2026-08-11",
         "progress":{"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
                     "xp_for_next":400,"current_streak":12,"longest_streak":30,
                     "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}},
         "quests":[
           {"key":"water","label":"Beber 2 litros de agua","target":2000,"progress":2000,
            "xp_reward":10,"is_optional":false,"completed":true},
           {"key":"steps_8000","label":"8.000 pasos","target":8000,"progress":5240,
            "xp_reward":15,"is_optional":true,"completed":false}],
         "suggested_workout":{"reason":"Te faltan 2 entrenos para tu meta de esta semana.",
                              "weekly_done":1,"weekly_goal":3,"template":null}}
    """.trimIndent()

    @Test
    fun traduce_la_respuesta_de_hoy_a_dominio() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()

        assertEquals(4, hoy.progreso.nivel)
        assertEquals("E", hoy.progreso.rango)
        assertEquals(240, hoy.progreso.xpEnNivel)
        assertEquals(400, hoy.progreso.xpParaSiguiente)
        assertEquals(12, hoy.progreso.racha)
        assertEquals(2, hoy.misiones.size)
    }

    @Test
    fun el_dia_se_escribe_en_castellano() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()

        assertEquals("martes, 11 de agosto", hoy.dia)
    }

    @Test
    fun una_mision_terminada_no_ensena_avance_parcial() {
        // «2.000 de 2.000» debajo de una misión ya marcada es ruido.
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()
        val agua = hoy.misiones.first { it.clave == "water" }

        assertTrue(agua.completada)
        assertEquals(null, agua.avance)
    }

    @Test
    fun una_mision_a_medias_ensena_cuanto_lleva_con_separador_de_miles() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()
        val pasos = hoy.misiones.first { it.clave == "steps_8000" }

        assertFalse(pasos.completada)
        assertEquals("5.240 de 8.000", pasos.avance)
    }

    @Test
    fun una_mision_de_objetivo_uno_no_ensena_avance() {
        // «0 de 1» debajo de «Entrenar» no dice nada que no diga la casilla.
        val crudo = """
            {"date":"2026-08-11",
             "progress":{"level":1,"rank":"E","xp_total":0,"xp_into_level":0,
                         "xp_for_next":100,"current_streak":0,"longest_streak":0,
                         "stats":{"strength":0,"endurance":0,"consistency":0,"vitality":0}},
             "quests":[{"key":"train","label":"Entrenar","target":1,"progress":0,
                        "xp_reward":30,"is_optional":false,"completed":false}]}
        """.trimIndent()

        val hoy = jsonSrank.decodeFromString<HoyDto>(crudo).aDominio()

        assertEquals(null, hoy.misiones.first().avance)
    }

    @Test
    fun el_contador_de_la_seccion_cuenta_las_hechas() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()

        assertEquals("1 de 2", hoy.contadorMisiones)
    }
}
