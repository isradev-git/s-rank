package es.israelzamora.srank.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider

/**
 * Material 3 aporta lo aburrido y difícil: campos de texto, foco, teclado,
 * selección, TabRow, Scaffold. Se usa para eso y solo para eso.
 *
 * Su ColorScheme se rellena con los mismos valores para que ningún componente
 * de M3 aporte color propio por debajo. El vocabulario de color de la app es
 * SRank.color, no MaterialTheme.colorScheme.
 */
@Composable
fun SRankTheme(content: @Composable () -> Unit) {
    val colores = SRankColors()
    val tipografia = SRankTypography()

    val esquemaM3 = darkColorScheme(
        primary = colores.ambar,
        onPrimary = colores.fondo,
        secondary = colores.azul,
        onSecondary = colores.fondo,
        background = colores.fondo,
        onBackground = colores.texto,
        surface = colores.superficie,
        onSurface = colores.texto,
        surfaceVariant = colores.superficie,
        onSurfaceVariant = colores.apagado,
        outline = colores.apagado,
        error = colores.rojo,
        onError = colores.fondo,
    )

    CompositionLocalProvider(
        LocalSRankColors provides colores,
        LocalSRankTypography provides tipografia,
    ) {
        MaterialTheme(colorScheme = esquemaM3, content = content)
    }
}

/** Punto de entrada corto: `SRank.color.ambar`, `SRank.texto.cuerpo`. */
object SRank {
    val color: SRankColors
        @Composable get() = LocalSRankColors.current
    val texto: SRankTypography
        @Composable get() = LocalSRankTypography.current
}
