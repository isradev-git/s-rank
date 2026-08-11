package es.israelzamora.srank.hoy

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.componentes.Comentario

@Composable
fun PantallaProgreso(modifier: Modifier = Modifier) {
    Column(modifier.fillMaxSize().padding(24.dp)) {
        Comentario("el historial, el calendario y las gráficas llegan en la fase 1.4")
    }
}

@Composable
fun PantallaPerfil(modifier: Modifier = Modifier) {
    Column(modifier.fillMaxSize().padding(24.dp)) {
        Comentario("los logros y los ajustes llegan en la fase 1.5")
    }
}
