package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Fila de misión: `[✓] Beber 2 litros de agua`, con el avance parcial debajo.
 *
 * `Text("[✓] Beber 2 litros")` lo lee TalkBack como «corchete, marca de
 * verificación, corchete». Por eso la fila entera va como un solo nodo: los
 * corchetes, la marca y el `//` son dibujo, no contenido. Un usuario ciego oye
 * «Beber 2 litros de agua, hecha».
 *
 * En 1.1 es de solo lectura, así que no es objetivo táctil y no necesita los
 * 48 dp. La fase 1.2 traerá las opcionales marcables a mano
 * (POST /api/system/quests/{key}/complete): entonces es añadir un onClick
 * nullable y el alto mínimo, no rediseñar.
 */
@Composable
fun FilaMision(
    etiqueta: String,
    completada: Boolean,
    avance: String? = null,
    modifier: Modifier = Modifier,
) {
    val estado = if (completada) "hecha" else "pendiente"

    Column(
        modifier
            .clearAndSetSemantics {
                text = AnnotatedString(if (avance == null) etiqueta else "$etiqueta, $avance")
                stateDescription = estado
            }
            .padding(vertical = 4.dp),
    ) {
        Row {
            Text(
                text = if (completada) "[✓] " else "[ ] ",
                style = SRank.texto.cuerpo,
                color = if (completada) SRank.color.verde else SRank.color.apagado,
            )
            Text(
                text = etiqueta,
                style = SRank.texto.cuerpo,
                // Dentro de una fila el color solo dice estado (spec §6).
                color = if (completada) SRank.color.verde else SRank.color.texto,
            )
        }
        if (avance != null) {
            Comentario(
                texto = "",
                dato = avance,
                modifier = Modifier.padding(PaddingValues(start = 32.dp)),
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaFilaMision() {
    SRankTheme {
        Column {
            FilaMision("Beber 2 litros de agua", completada = true)
            FilaMision("Entrenar", completada = false, avance = "1 de 3 esta semana")
            FilaMision("8.000 pasos", completada = false, avance = "5.240 de 8.000")
        }
    }
}
