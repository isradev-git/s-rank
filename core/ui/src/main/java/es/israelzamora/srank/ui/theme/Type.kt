package es.israelzamora.srank.ui.theme

import androidx.compose.runtime.Immutable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.Font
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp
import es.israelzamora.srank.ui.R

/**
 * JetBrains Mono va empaquetada. Nunca la monoespaciada del sistema: cada
 * fabricante trae una distinta y la estética se rompe fuera de tu móvil.
 */
val JetBrainsMono = FontFamily(
    Font(R.font.jetbrains_mono_regular, FontWeight.Normal),
    Font(R.font.jetbrains_mono_bold, FontWeight.Bold),
)

/**
 * Los cinco tamaños del spec §7. Todos en sp: la app respeta el tamaño de
 * fuente del sistema, que llega a 2x.
 */
@Immutable
data class SRankTypography(
    val titulo: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Bold, fontSize = 20.sp,
    ),
    val seccion: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Bold, fontSize = 16.sp,
    ),
    val cuerpo: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Normal, fontSize = 13.sp,
    ),
    val nota: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Normal, fontSize = 11.5.sp,
    ),
    // Versales fingidas con mayúsculas y letterSpacing: Compose no tiene
    // versales de verdad y empaquetar una segunda fuente para eso no compensa.
    val etiqueta: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Bold, fontSize = 10.5.sp,
        letterSpacing = 1.sp,
    ),
)

val LocalSRankTypography = staticCompositionLocalOf { SRankTypography() }
