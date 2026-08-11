package es.israelzamora.srank.system

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.layout.Column
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import es.israelzamora.srank.ui.componentes.CabeceraSeccion
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.componentes.FilaMision
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Las misiones del día, de solo lectura en 1.1.
 *
 * La cabecera va en ámbar porque es la sección de misiones (spec §6). Dentro
 * de cada fila el color solo dice estado.
 */
@Composable
fun ListaMisiones(
    misiones: List<Mision>,
    contador: String,
    desplegada: Boolean,
    alPlegar: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Column(modifier) {
        CabeceraSeccion(
            titulo = "misiones de hoy",
            desplegada = desplegada,
            alPulsar = alPlegar,
            contador = contador,
        )

        AnimatedVisibility(visible = desplegada) {
            Column {
                if (misiones.isEmpty()) {
                    Comentario("hoy no hay misiones")
                } else {
                    misiones.forEach {
                        FilaMision(
                            etiqueta = it.etiqueta,
                            completada = it.completada,
                            avance = it.avance,
                        )
                    }
                }
            }
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaListaMisiones() {
    SRankTheme {
        ListaMisiones(
            misiones = listOf(
                Mision("water", "Beber 2 litros de agua", true, null),
                Mision("train", "Entrenar", false, null),
                Mision("steps_8000", "8.000 pasos", false, "5.240 de 8.000"),
            ),
            contador = "1 de 3",
            desplegada = true,
            alPlegar = {},
        )
    }
}
