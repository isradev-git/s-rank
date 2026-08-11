package es.israelzamora.srank.hoy

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.system.CabeceraProgreso
import es.israelzamora.srank.system.ListaMisiones
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaHoy(vm: HoyViewModel, modifier: Modifier = Modifier) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    Column(
        modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
    ) {
        // El `$` es dibujo: no se lee y no hay que saber qué significa.
        Text("$ hoy", style = SRank.texto.titulo, color = SRank.color.ambar)
        Spacer(Modifier.height(8.dp))

        when {
            estado.hoy != null -> {
                val hoy = estado.hoy!!
                CabeceraProgreso(progreso = hoy.progreso, dia = hoy.dia)
                Spacer(Modifier.height(24.dp))
                ListaMisiones(
                    misiones = hoy.misiones,
                    contador = hoy.contadorMisiones,
                    desplegada = estado.misionesDesplegadas,
                    alPlegar = vm::plegaMisiones,
                )
            }

            estado.cargando -> CircularProgressIndicator(color = SRank.color.ambar)

            estado.error != null -> {
                Text(estado.error!!, style = SRank.texto.cuerpo, color = SRank.color.texto)
                Spacer(Modifier.height(16.dp))
                BotonSRank("reintentar", vm::carga)
            }
        }
    }
}
