package es.israelzamora.srank.session

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class SesionEnMemoriaTest {

    @Test
    fun al_empezar_no_hay_sesion() = runTest {
        val sesion = SesionEnMemoria()

        assertNull(sesion.token.first())
        assertNull(sesion.tokenActual)
    }

    @Test
    fun guardar_deja_el_token_disponible_tambien_de_forma_sincrona() = runTest {
        // El interceptor de OkHttp no puede suspender, así que lee
        // tokenActual. Si esa copia no se actualizara al guardar, la primera
        // petición después de entrar iría sin Authorization.
        val sesion = SesionEnMemoria()

        sesion.guarda(token = "42|abcdef", nombre = "Israel")

        assertEquals("42|abcdef", sesion.token.first())
        assertEquals("42|abcdef", sesion.tokenActual)
        assertEquals("Israel", sesion.nombre.first())
    }

    @Test
    fun limpiar_borra_las_dos_copias() = runTest {
        // Si tokenActual sobreviviera al cierre de sesión, el interceptor
        // seguiría mandando el token de quien acaba de salir.
        val sesion = SesionEnMemoria()
        sesion.guarda(token = "42|abcdef", nombre = "Israel")

        sesion.limpia()

        assertNull(sesion.token.first())
        assertNull(sesion.tokenActual)
        assertNull(sesion.nombre.first())
    }
}
