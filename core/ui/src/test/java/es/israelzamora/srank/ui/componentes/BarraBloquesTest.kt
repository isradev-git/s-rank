package es.israelzamora.srank.ui.componentes

import org.junit.Assert.assertEquals
import org.junit.Test

class BarraBloquesTest {

    @Test
    fun vacia_cuando_no_hay_progreso() {
        assertEquals(0, bloquesEncendidos(progreso = 0, total = 400))
    }

    @Test
    fun avanza_a_saltos_del_diez_por_ciento() {
        assertEquals(6, bloquesEncendidos(progreso = 240, total = 400))
    }

    @Test
    fun no_se_llena_hasta_haber_llegado_de_verdad() {
        // 399 de 400 es 99,75%. Enseñar la barra llena sería mentir sobre
        // que falta XP para subir de nivel.
        assertEquals(9, bloquesEncendidos(progreso = 399, total = 400))
        assertEquals(10, bloquesEncendidos(progreso = 400, total = 400))
    }

    @Test
    fun nunca_se_pasa_ni_se_queda_corta() {
        assertEquals(10, bloquesEncendidos(progreso = 900, total = 400))
        assertEquals(0, bloquesEncendidos(progreso = -5, total = 400))
    }

    @Test
    fun un_total_de_cero_no_revienta() {
        // El servidor manda target 0 en alguna misión opcional. Dividir aquí
        // sería una excepción en mitad de la pantalla de hoy.
        assertEquals(0, bloquesEncendidos(progreso = 10, total = 0))
    }

    @Test
    fun no_desborda_con_progreso_cerca_del_maximo_de_int() {
        // progreso.toLong() * bloques se queda en Long, pero volver a Int sin
        // acotar antes desborda: 2_147_483_647 * 10 / 1 da -10, que coerceIn
        // deja en 0. Una barra vacía cuando en realidad está llena de sobra.
        assertEquals(10, bloquesEncendidos(progreso = Int.MAX_VALUE, total = 1))
    }
}
