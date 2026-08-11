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
import androidx.lifecycle.compose.LifecycleResumeEffect
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.system.CabeceraProgreso
import es.israelzamora.srank.system.ListaMisiones
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.componentes.TituloPantalla
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaHoy(vm: HoyViewModel, modifier: Modifier = Modifier) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    // «hoy» es la pantalla principal de una app de hábitos: si el móvil pasa
    // la noche en segundo plano, sin esto enseñaría la fecha y las misiones
    // de ayer indefinidamente, y el botón «reintentar» de más abajo sería la
    // única forma de refrescar (inalcanzable con datos ya cargados). `carga`
    // ya se protege sola contra la llamada duplicada del arranque en frío:
    // el `init` del ViewModel deja `cargando = true` antes de que este efecto
    // llegue a correr.
    LifecycleResumeEffect(Unit) {
        vm.carga()
        onPauseOrDispose { }
    }

    Column(
        modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
    ) {
        TituloPantalla("hoy")
        Spacer(Modifier.height(8.dp))

        when {
            estado.hoy != null -> {
                val hoy = estado.hoy!!

                // Una recarga (al volver a primer plano, o con «reintentar»)
                // puede fallar sin borrar lo que ya había en pantalla: `carga`
                // deja `hoy` intacto y solo rellena `error`. Sin este aviso
                // el fallo se tragaba en silencio y el usuario seguía viendo
                // datos que podían estar desactualizados sin saber por qué.
                estado.error?.let { mensaje ->
                    Text(mensaje, style = SRank.texto.nota, color = SRank.color.rojo)
                    Spacer(Modifier.height(8.dp))
                    BotonSRank("reintentar", vm::carga)
                    Spacer(Modifier.height(16.dp))
                }

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
