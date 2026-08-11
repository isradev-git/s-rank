package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * La letra del rango con su marco: E · D · C · B · A · S.
 *
 * El color sube con el rango, pero **sin tocar cian, rojo ni morado**: esos
 * tres son del momento de recompensa y gastarlos en una insignia permanente
 * desactivaría el premio. S se queda en ámbar, que ya es el color de marca.
 */
@Composable
fun InsigniaRango(rango: String, modifier: Modifier = Modifier) {
    val color: Color = when (rango.uppercase()) {
        // ponytail: el rango E va en apagado (2,72:1), por debajo del mínimo
        // que pide la regla de que apagado nunca lleve la única copia de un
        // dato. Se salva porque la insignia nunca va sola —al lado siempre
        // hay «NIVEL n» en texto— y el rango se anuncia entero por TalkBack.
        // El techo: si en el móvil se lee mal, sube a texto.
        "E" -> SRank.color.apagado
        "D" -> SRank.color.texto
        "C" -> SRank.color.azul
        "B" -> SRank.color.verde
        else -> SRank.color.ambar   // A y S
    }

    Row(
        modifier
            .border(1.dp, color)
            .padding(horizontal = 8.dp, vertical = 2.dp)
            .clearAndSetSemantics { text = AnnotatedString("rango ${rango.uppercase()}") },
    ) {
        Text(
            text = rango.uppercase(),
            style = SRank.texto.seccion,
            color = color,
        )
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaInsignia() {
    SRankTheme {
        Row {
            listOf("E", "D", "C", "B", "A", "S").forEach {
                InsigniaRango(it, Modifier.padding(4.dp))
            }
        }
    }
}
