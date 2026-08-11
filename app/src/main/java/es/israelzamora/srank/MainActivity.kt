package es.israelzamora.srank

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable

const val NOMBRE_APP = "S-RANK"

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { Arranque() }
    }
}

@Composable
private fun Arranque() {
    Text(NOMBRE_APP)
}
