package es.israelzamora.srank.auth

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank

/**
 * El marco de las tres pantallas de cuenta: la línea de prompt y el hueco.
 *
 * `verticalScroll` no es un adorno: con el tamaño de fuente del sistema al
 * máximo, un formulario de tres campos no cabe en un móvil pequeño, y sin
 * scroll el botón queda debajo del borde y la pantalla deja de funcionar.
 */
@Composable
fun MarcoAuth(
    titulo: String,
    modifier: Modifier = Modifier,
    content: @Composable ColumnScope.() -> Unit,
) {
    Column(
        modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
    ) {
        // El `$` es dibujo: se limpia, pero el título hay que conservarlo, o
        // TalkBack se queda sin decir en qué pantalla está el usuario.
        Text(
            text = "$ $titulo",
            style = SRank.texto.titulo,
            color = SRank.color.ambar,
            modifier = Modifier.clearAndSetSemantics { text = AnnotatedString(titulo) },
        )
        content()
    }
}
