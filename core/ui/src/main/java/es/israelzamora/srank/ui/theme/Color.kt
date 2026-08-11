package es.israelzamora.srank.ui.theme

import androidx.compose.runtime.Immutable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color
import kotlin.math.pow

/**
 * Los once colores del spec §7. Se llaman por su nombre en castellano porque
 * ColorScheme de Material 3 no tiene hueco para «apagado» ni para «el cian del
 * Sistema», y escribir `tertiary` queriendo decir «cian» es justo cómo el cian
 * acabaría escapándose a la interfaz normal sin que nadie lo note.
 */
@Immutable
data class SRankColors(
    val fondo: Color = Color(0xFF000000),
    val superficie: Color = Color(0xFF0D0D10),
    val lineas: Color = Color(0xFF1F1F23),
    val texto: Color = Color(0xFFE4E4E7),
    val apagado: Color = Color(0xFF52525B),
    val ambar: Color = Color(0xFFF59E0B),   // marca, acción, XP
    val verde: Color = Color(0xFF4ADE80),   // completado
    val azul: Color = Color(0xFF60A5FA),    // información y navegación
    // ponytail: los tres siguientes solo los toca el momento de recompensa.
    // Si aparecen en una pantalla normal, el premio deja de significar nada.
    // El techo es la disciplina: nadie lo comprueba salvo la revisión.
    val rojo: Color = Color(0xFFF87171),    // récord, alerta
    val cian: Color = Color(0xFF22D3EE),    // EXCLUSIVO de las ventanas del Sistema
    val morado: Color = Color(0xFFA78BFA),  // rareza épica
)

val LocalSRankColors = staticCompositionLocalOf { SRankColors() }

/**
 * Luminancia relativa según WCAG 2.1. Se usa desde el test: es la única forma
 * de que cambiar un hexadecimal a ojo salte antes de llegar al móvil.
 */
private fun luminancia(rgb: Int): Double {
    val canales = listOf(16, 8, 0).map { ((rgb shr it) and 0xFF) / 255.0 }
    val lineal = canales.map { c ->
        if (c <= 0.03928) c / 12.92 else ((c + 0.055) / 1.055).pow(2.4)
    }
    return 0.2126 * lineal[0] + 0.7152 * lineal[1] + 0.0722 * lineal[2]
}

/** Razón de contraste entre dos colores, de 1:1 a 21:1. */
fun contraste(a: Int, b: Int): Double {
    val (alta, baja) = listOf(luminancia(a), luminancia(b)).sorted().reversed()
    return (alta + 0.05) / (baja + 0.05)
}
