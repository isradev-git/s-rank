package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * `▸ MISIONES DE HOY   [1 de 4] ▾`, plegable.
 *
 * El hueco del medio es `Spacer(weight)`, nunca espacios: con la fuente del
 * sistema al máximo, alinear con caracteres descuadra la línea.
 *
 * Los triángulos no se leen. TalkBack ya sabe anunciar plegado/desplegado, y
 * eso es lo que se le da en stateDescription.
 */
@Composable
fun CabeceraSeccion(
    titulo: String,
    desplegada: Boolean,
    alPulsar: () -> Unit,
    modifier: Modifier = Modifier,
    contador: String? = null,
    color: Color = SRank.color.ambar,
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = modifier
            .clickable(onClick = alPulsar)
            .defaultMinSize(minHeight = 48.dp)
            .padding(vertical = 8.dp)
            .clearAndSetSemantics {
                text = AnnotatedString(
                    if (contador == null) titulo else "$titulo, $contador",
                )
                stateDescription = if (desplegada) "desplegado" else "plegado"
            },
    ) {
        Text(
            text = if (desplegada) "▾ " else "▸ ",
            style = SRank.texto.seccion,
            color = color,
        )
        Text(
            text = titulo.uppercase(),
            style = SRank.texto.seccion,
            color = color,
        )
        Spacer(Modifier.weight(1f))
        if (contador != null) {
            Text(
                text = "[$contador]",
                style = SRank.texto.nota,
                color = SRank.color.texto,
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaCabeceraSeccion() {
    SRankTheme {
        androidx.compose.foundation.layout.Column {
            CabeceraSeccion("misiones de hoy", desplegada = true, alPulsar = {}, contador = "1 de 4")
            CabeceraSeccion("nutrición", desplegada = false, alPulsar = {}, color = SRank.color.verde)
        }
    }
}
