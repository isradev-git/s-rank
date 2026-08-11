package es.israelzamora.srank.ui.componentes

import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.tooling.preview.Preview
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Cuántos de los diez bloques están encendidos.
 *
 * Trunca en vez de redondear: 399 de 400 son nueve bloques, no diez. Enseñar
 * la barra llena cuando todavía falta XP sería mentir en la única pantalla
 * donde el número importa.
 */
fun bloquesEncendidos(progreso: Int, total: Int, bloques: Int = 10): Int {
    if (total <= 0 || progreso <= 0) return 0
    val encendidos = (progreso.toLong() * bloques / total).toInt()
    return encendidos.coerceIn(0, bloques)
}

/**
 * `[▓▓▓▓▓▓░░░░]` en línea propia, del spec §5.4.
 *
 * En línea propia y no junto a los números: juntos son unos 24 caracteres, que
 * con la fuente del sistema al máximo no caben en un móvil de 320 dp. La barra
 * sola ocupa unos 187 dp de los 288 disponibles.
 *
 * Los glifos U+2593 y U+2591 están comprobados en JetBrains Mono 2.304.
 *
 * Para TalkBack no se leen veinticuatro caracteres de dibujo: se anuncia el
 * porcentaje, que es lo que la barra significa.
 */
@Composable
fun BarraBloques(
    progreso: Int,
    total: Int,
    modifier: Modifier = Modifier,
    color: Color = SRank.color.ambar,
) {
    val bloques = 10
    val llenos = bloquesEncendidos(progreso, total, bloques)
    val porcentaje = if (total <= 0) 0 else (progreso.toLong() * 100 / total).toInt().coerceIn(0, 100)

    val pintada = buildAnnotatedString {
        withStyle(SpanStyle(color = SRank.color.apagado)) { append("[") }
        withStyle(SpanStyle(color = color)) { append("▓".repeat(llenos)) }
        withStyle(SpanStyle(color = SRank.color.lineas)) { append("░".repeat(bloques - llenos)) }
        withStyle(SpanStyle(color = SRank.color.apagado)) { append("]") }
    }

    Text(
        text = pintada,
        style = SRank.texto.cuerpo,
        modifier = modifier.clearAndSetSemantics {
            text = AnnotatedString("$porcentaje por ciento")
        },
    )
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaBarraBloques() {
    SRankTheme {
        androidx.compose.foundation.layout.Column {
            BarraBloques(progreso = 240, total = 400)
            BarraBloques(progreso = 1250, total = 2000, color = SRank.color.verde)
        }
    }
}
