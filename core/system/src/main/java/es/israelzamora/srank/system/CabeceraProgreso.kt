package es.israelzamora.srank.system

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.componentes.BarraBloques
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.componentes.InsigniaRango
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Nivel, rango, barra de XP y racha.
 *
 * El hueco entre el nivel y la insignia es `Spacer(weight)`, no espacios: con
 * la fuente del sistema al máximo se descuadraría.
 */
@Composable
fun CabeceraProgreso(
    progreso: Progreso,
    dia: String,
    modifier: Modifier = Modifier,
) {
    Column(modifier) {
        // La fecha es toda la información, no una etiqueta decorativa: va
        // como dato para leerse a 16,55:1, no a 2,72:1 (spec §5.3).
        Comentario("", dato = dia)
        Spacer(Modifier.height(16.dp))

        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(
                text = "NIVEL ${progreso.nivel}",
                style = SRank.texto.titulo,
                color = SRank.color.texto,
            )
            Spacer(Modifier.weight(1f))
            InsigniaRango(progreso.rango)
        }

        Spacer(Modifier.height(8.dp))
        BarraBloques(progreso = progreso.xpEnNivel, total = progreso.xpParaSiguiente)

        Text(
            text = "${progreso.xpEnNivel} / ${progreso.xpParaSiguiente} XP",
            style = SRank.texto.nota,
            color = SRank.color.texto,
        )

        Spacer(Modifier.height(8.dp))
        Comentario("racha de", dato = "${progreso.racha} días")
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaCabecera() {
    SRankTheme {
        CabeceraProgreso(
            progreso = Progreso(4, "E", 240, 400, 12, 30),
            dia = "martes, 11 de agosto",
            modifier = Modifier.padding(16.dp),
        )
    }
}
