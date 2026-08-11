package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * La ventana del Sistema: el momento de recompensa.
 *
 * **El cian de aquí no aparece en ninguna otra pantalla.** Es la única regla de
 * color sin excepciones: en cuanto el cian sale en la interfaz normal deja de
 * significar «premio» y esto se queda en un recuadro más.
 *
 * En la fase 1.1 nada la dispara todavía y solo se ve en este @Preview. Se
 * escribe ahora porque define la identidad de la app: verla antes de cerrar los
 * tokens evita descubrir en 1.2 que obliga a retocarlos.
 */
@Composable
fun VentanaSistema(
    titulo: String,
    lineas: List<String>,
    modifier: Modifier = Modifier,
) {
    Column(
        modifier
            .fillMaxWidth()
            .background(SRank.color.superficie)
            .border(1.dp, SRank.color.cian)
            .padding(16.dp),
    ) {
        Text(
            text = "◆ $titulo".uppercase(),
            style = SRank.texto.seccion,
            color = SRank.color.cian,
        )
        Text(
            text = "─".repeat(24),
            style = SRank.texto.nota,
            color = SRank.color.cian,
        )
        lineas.forEach {
            Text(
                text = it,
                style = SRank.texto.cuerpo,
                color = SRank.color.texto,
                modifier = Modifier.padding(top = 4.dp),
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaVentanaSistema() {
    SRankTheme {
        Column(Modifier.padding(16.dp)) {
            VentanaSistema(
                titulo = "has subido de nivel",
                lineas = listOf("Nivel 4 → 5", "+22 XP", "Logro: Hidratado"),
            )
        }
    }
}
