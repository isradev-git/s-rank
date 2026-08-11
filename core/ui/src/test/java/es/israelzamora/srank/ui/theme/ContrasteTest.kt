package es.israelzamora.srank.ui.theme

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * WCAG 2.1: 1.4.3 pide 4,5:1 para texto normal. El spec §7 lo hereda.
 *
 * Este test existe porque un color se cambia en un segundo y el daño no se ve
 * hasta que alguien con poca vista abre la app.
 */
class ContrasteTest {

    private val negro = 0x000000

    @Test
    fun los_colores_que_llevan_informacion_pasan_de_45() {
        val exigidos = mapOf(
            "texto" to 0xe4e4e7,
            "ambar" to 0xf59e0b,
            "verde" to 0x4ade80,
            "azul" to 0x60a5fa,
            "rojo" to 0xf87171,
            "cian" to 0x22d3ee,
            "morado" to 0xa78bfa,
        )
        exigidos.forEach { (nombre, color) ->
            val ratio = contraste(color, negro)
            assertTrue(
                "$nombre da %.2f:1 sobre negro y hace falta 4,5:1".format(ratio),
                ratio >= 4.5,
            )
        }
    }

    @Test
    fun apagado_no_llega_a_45_y_por_eso_no_puede_llevar_datos() {
        // Si alguien "arregla" este gris subiéndolo, que se entere aquí y
        // relea §5.3 antes de repartir apagado por las pantallas.
        assertEquals(2.72, contraste(0x52525b, negro), 0.01)
    }

    @Test
    fun lineas_solo_sirve_de_separador_decorativo() {
        // 1,28:1. Un borde de control necesita 3:1 (WCAG 1.4.11), así que
        // los campos y botones usan apagado, no lineas. Ver §5.5.
        assertEquals(1.28, contraste(0x1f1f23, negro), 0.01)
    }

    @Test
    fun el_color_de_rescate_del_borde_si_pasaria_311() {
        // Si en el móvil no se ve dónde se escribe, este es el recambio.
        assertTrue(contraste(0x6b7280, negro) >= 3.0)
    }
}
