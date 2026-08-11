package es.israelzamora.srank.ui.componentes

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
 * El título de pantalla `$ …` del spec §5.2.
 *
 * El `$` es dibujo: no aporta información y no hay que saber qué significa.
 * `clearAndSetSemantics` sustituye lo que anuncia TalkBack por el título
 * limpio, sin el signo — si no, TalkBack dice «dólar hoy» en vez de «hoy».
 *
 * Antes de este componente el patrón estaba escrito dos veces (`PantallaHoy`
 * y `MarcoAuth`), y solo una de las dos limpiaba la semántica. Con una quinta
 * pantalla volvería a olvidarse: de ahí subirlo aquí.
 */
@Composable
fun TituloPantalla(texto: String, modifier: Modifier = Modifier) {
    Text(
        text = "$ $texto",
        style = SRank.texto.titulo,
        color = SRank.color.ambar,
        modifier = modifier.clearAndSetSemantics { text = AnnotatedString(texto) },
    )
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaTituloPantalla() {
    SRankTheme {
        TituloPantalla("hoy")
    }
}
