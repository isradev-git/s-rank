package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.layout.Row
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * El comentario `// …` del spec §5.3.
 *
 * Si lleva un dato, el color se parte: el marcador va en apagado (2,72:1, que
 * es decoración) y el dato en texto (16,55:1, que se lee). Sigue leyéndose como
 * comentario porque lo dicen el marcador y el tamaño, no el gris.
 *
 * Para TalkBack se anuncia solo el contenido: las dos barras son dibujo.
 */
@Composable
fun Comentario(
    texto: String,
    dato: String? = null,
    modifier: Modifier = Modifier,
) {
    val leido = if (dato == null) texto else "$texto $dato"

    Row(
        modifier.clearAndSetSemantics { text = AnnotatedString(leido) },
    ) {
        Text(
            text = "// ",
            style = SRank.texto.nota,
            color = SRank.color.apagado,
        )
        Text(
            text = texto,
            style = SRank.texto.nota,
            // Sin dato, el comentario entero es secundario y puede ir apagado.
            // Con dato, el texto de delante lo acompaña y también es secundario.
            color = SRank.color.apagado,
        )
        if (dato != null) {
            Text(
                text = " $dato",
                style = SRank.texto.nota,
                color = SRank.color.texto,
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaComentario() {
    SRankTheme {
        androidx.compose.foundation.layout.Column {
            Comentario("lunes, 11 de agosto")
            Comentario("de 8.000 pasos llevas", dato = "5.240")
        }
    }
}
