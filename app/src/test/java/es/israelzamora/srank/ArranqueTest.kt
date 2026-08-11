package es.israelzamora.srank

import org.junit.Assert.assertEquals
import org.junit.Test

class ArranqueTest {
    @Test
    fun el_nombre_de_la_app_es_el_de_la_marca() {
        assertEquals("S-RANK", NOMBRE_APP)
    }
}
