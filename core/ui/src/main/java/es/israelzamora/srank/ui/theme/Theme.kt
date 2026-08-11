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
 *
 * ponytail: no hay ni un test de interfaz en todo el módulo —ni Compose UI
 * test, ni Robolectric, ni `androidTest`—, así que ninguna afirmación de
 * accesibilidad de este fichero ni de los `KDoc` de `core/ui` (contraste,
 * `clearAndSetSemantics`, roles, foco) está verificada más que a ojo y por
 * `ContrasteTest`, que solo comprueba color, no lo que realmente anuncia
 * TalkBack. El techo: toda la accesibilidad de esta rama depende de un paso
 * manual en un móvil de verdad (ver la fase 1.1, «verificación a mano»). El
 * recambio, si algún día un fallo de accesibilidad cuesta caro: Compose UI
 * Testing (`ui-test-junit4` + Robolectric o un emulador) sobre los
 * componentes de este módulo, empezando por los que hacen
 * `clearAndSetSemantics`, que es donde ya se ha colado un fallo real (ver el
 * arreglo del `$ hoy` en `PantallaHoy`, que sobrevivió a doce revisiones
 * porque nada lo comprobaba en ejecución).
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
        // Sin esto, PrimaryTabRow pinta la línea que va bajo las pestañas
        // (HorizontalDivider) con el gris malva por defecto de M3, contra el
        // propio comentario de arriba: «ningún componente de M3 aporta color
        // propio por debajo». Se ve en las tres pantallas con pestañas.
        outlineVariant = colores.lineas,
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
