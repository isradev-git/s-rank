package es.israelzamora.srank.nav

import es.israelzamora.srank.session.Sesion
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

/**
 * Simula el mismo defecto que `SesionDataStore` (ver `Sesion.kt`):
 * `tokenActual` solo se rellena cuando algo colecta `token`. `SesionEnMemoria`,
 * la de los tests normales del resto de la app, no sirve aquí porque su
 * `tokenActual` ya está siempre al día sin necesidad de colectar nada, así que
 * no puede detectar la carrera que el contrato 1 de la tarea 12 pide fijar.
 */
private class SesionFriaHastaColectar(tokenGuardado: String?) : Sesion {
    @Volatile
    override var tokenActual: String? = null
        private set

    override val token: Flow<String?> = flowOf(tokenGuardado).onEach { tokenActual = it }
    override val nombre: Flow<String?> = flowOf(null)

    override suspend fun guarda(token: String, nombre: String) {
        tokenActual = token
    }

    override suspend fun limpia() {
        tokenActual = null
    }
}

class NavRaizTest {

    @Test
    fun espera_al_primer_valor_antes_de_mandar_a_hoy() = runTest {
        val sesion = SesionFriaHastaColectar(tokenGuardado = "42|abcdef")
        assertNull("antes de decidir, tokenActual todavía no se ha colectado", sesion.tokenActual)

        val ruta = decideRutaInicial(sesion)

        assertEquals("hoy", ruta)
        // Si esto falla, la ruta se decidió sin esperar de verdad al primer
        // valor: el interceptor leería tokenActual == null en la primera
        // petición autenticada y la sesión guardada no serviría de nada al
        // arrancar en frío.
        assertEquals(
            "al decidir la ruta ya se ha colectado token, así que tokenActual está relleno",
            "42|abcdef",
            sesion.tokenActual,
        )
    }

    @Test
    fun sin_token_manda_a_login() = runTest {
        val sesion = SesionFriaHastaColectar(tokenGuardado = null)

        val ruta = decideRutaInicial(sesion)

        assertEquals("login", ruta)
    }
}
