package es.israelzamora.srank.api

import org.junit.Assert.assertEquals
import org.junit.Test
import java.time.LocalDate

class FechasTest {

    @Test
    fun un_instante_de_las_2330_utc_en_madrid_ya_es_el_dia_siguiente() {
        // En verano Madrid va dos horas por delante de UTC. Sin convertir,
        // «hoy» cambiaría a medianoche menos dos horas y las misiones del día
        // aparecerían como las de ayer.
        assertEquals(
            LocalDate.of(2026, 8, 12),
            diaEnMadrid("2026-08-11T23:30:00.000000Z"),
        )
    }

    @Test
    fun antes_de_las_2200_utc_sigue_siendo_el_mismo_dia() {
        assertEquals(
            LocalDate.of(2026, 8, 11),
            diaEnMadrid("2026-08-11T21:59:00.000000Z"),
        )
    }

    @Test
    fun en_invierno_madrid_va_una_hora_por_delante() {
        // Enero: CET, +1. A las 23:30 UTC en Madrid es la 00:30 del día 12.
        assertEquals(
            LocalDate.of(2026, 1, 12),
            diaEnMadrid("2026-01-11T23:30:00.000000Z"),
        )
    }

    @Test
    fun el_dia_se_escribe_en_castellano() {
        assertEquals(
            "martes, 11 de agosto",
            formateaDiaLargo(LocalDate.of(2026, 8, 11)),
        )
    }
}
