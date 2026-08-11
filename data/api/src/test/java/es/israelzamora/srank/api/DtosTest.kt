package es.israelzamora.srank.api

import org.junit.Assert.assertEquals
import org.junit.Test

class DtosTest {

    @Test
    fun deserializa_una_mision_tal_como_la_manda_el_servidor() {
        val crudo = """
            {"key":"water","label":"Beber 2 litros de agua","target":2000,
             "progress":1250,"xp_reward":10,"is_optional":false,"completed":false}
        """.trimIndent()

        val m = jsonSrank.decodeFromString<MisionDto>(crudo)

        assertEquals("water", m.key)
        assertEquals("Beber 2 litros de agua", m.label)
        assertEquals(2000, m.target)
        assertEquals(1250, m.progress)
        assertEquals(10, m.xpReward)
        assertEquals(false, m.isOptional)
        assertEquals(false, m.completed)
    }

    @Test
    fun un_campo_nuevo_del_servidor_no_tumba_la_app() {
        // El backend puede añadir claves sin publicar una versión nueva de la
        // app. Si eso reventase, cada cambio del servidor dejaría fuera a
        // quien no actualice.
        val conExtra = """
            {"key":"train","label":"Entrenar","target":1,"progress":0,
             "xp_reward":30,"is_optional":false,"completed":false,
             "icono_que_aun_no_existe":"pesa"}
        """.trimIndent()

        val m = jsonSrank.decodeFromString<MisionDto>(conExtra)

        assertEquals("train", m.key)
    }

    @Test
    fun deserializa_el_progreso_entero() {
        val crudo = """
            {"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
             "xp_for_next":400,"current_streak":12,"longest_streak":30,
             "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}}
        """.trimIndent()

        val p = jsonSrank.decodeFromString<ProgresoDto>(crudo)

        assertEquals(4, p.level)
        assertEquals("E", p.rank)
        assertEquals(240, p.xpIntoLevel)
        assertEquals(400, p.xpForNext)
        assertEquals(12, p.currentStreak)
        assertEquals(8, p.stats.consistency)
    }

    @Test
    fun deserializa_la_respuesta_de_login() {
        val crudo = """
            {"access_token":"42|abcdef","token_type":"Bearer",
             "user_name":"Israel","is_admin":false}
        """.trimIndent()

        val r = jsonSrank.decodeFromString<LoginRespuestaDto>(crudo)

        assertEquals("42|abcdef", r.accessToken)
        assertEquals("Israel", r.userName)
        assertEquals(false, r.isAdmin)
    }

    @Test
    fun deserializa_el_perfil_con_los_modulos_como_lista() {
        // config('srank.modules') en el backend es un array indexado de PHP
        // (['entrenamiento', 'nutrición']), no un array asociativo, así que
        // json_encode lo manda como lista JSON, no como objeto.
        // Ver backend/config/srank.php:42.
        val crudo = """
            {"progress":{"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
             "xp_for_next":400,"current_streak":12,"longest_streak":30,
             "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}},
             "modules":["entrenamiento","nutrición"]}
        """.trimIndent()

        val p = jsonSrank.decodeFromString<PerfilDto>(crudo)

        assertEquals(4, p.progress.level)
        assertEquals(listOf("entrenamiento", "nutrición"), p.modules)
    }
}
