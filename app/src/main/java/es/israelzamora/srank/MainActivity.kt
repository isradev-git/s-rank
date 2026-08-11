package es.israelzamora.srank

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import es.israelzamora.srank.nav.NavRaiz
import es.israelzamora.srank.ui.theme.SRankTheme

const val NOMBRE_APP = "S-RANK"

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val grafo = (application as Aplicacion).grafo

        setContent {
            SRankTheme { NavRaiz(grafo) }
        }
    }
}
