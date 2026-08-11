package es.israelzamora.srank.ui.componentes

import org.junit.Assert.assertEquals
import org.junit.Test

class ComentarioTest {

    @Test
    fun sin_dato_se_lee_el_texto_solo() {
        assertEquals("lunes, 11 de agosto", leidoComentario("lunes, 11 de agosto", dato = null))
    }

    @Test
    fun con_texto_y_dato_se_leen_los_dos_seguidos() {
        assertEquals("racha de 12 días", leidoComentario("racha de", dato = "12 días"))
    }

    @Test
    fun con_el_texto_vacio_se_lee_solo_el_dato_sin_espacio_de_sobra() {
        // Antes del arreglo `leido` era siempre "$texto $dato", así que con
        // texto vacío esto daba " hoy no hay misiones", con un espacio
        // inicial de sobra que TalkBack habría anunciado igual.
        assertEquals("hoy no hay misiones", leidoComentario("", dato = "hoy no hay misiones"))
    }
}
