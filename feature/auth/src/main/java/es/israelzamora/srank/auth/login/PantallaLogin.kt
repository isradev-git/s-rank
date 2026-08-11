package es.israelzamora.srank.auth.login

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.auth.MarcoAuth
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.componentes.CampoSRank
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaLogin(
    vm: LoginViewModel,
    alEntrar: () -> Unit,
    alRegistrarse: () -> Unit,
    alOlvidar: () -> Unit,
) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    LaunchedEffect(estado.entrado) {
        if (estado.entrado) alEntrar()
    }

    MarcoAuth("entrar") {
        Comentario("escribe tus datos para continuar")
        Spacer(Modifier.height(24.dp))

        CampoSRank(
            valor = estado.correo,
            alCambiar = vm::escribeCorreo,
            etiqueta = "correo",
            tecladoCorreo = true,
        )
        Spacer(Modifier.height(12.dp))

        CampoSRank(
            valor = estado.contrasena,
            alCambiar = vm::escribeContrasena,
            etiqueta = "contraseña",
            esContrasena = true,
            error = estado.error,
        )
        Spacer(Modifier.height(24.dp))

        BotonSRank(
            texto = "entrar",
            alPulsar = vm::entrar,
            cargando = estado.cargando,
            modifier = Modifier.fillMaxWidth(),
        )
        Spacer(Modifier.height(24.dp))

        Enlace("no recuerdo mi contraseña", alOlvidar)
        Enlace("crear una cuenta", alRegistrarse)
    }
}

/**
 * Un enlace es un botón de texto con 48 dp de alto: aunque parezca texto,
 * hay que poder acertarle con el dedo.
 */
@Composable
internal fun Enlace(texto: String, alPulsar: () -> Unit) {
    Text(
        text = texto,
        style = SRank.texto.cuerpo,
        color = SRank.color.azul,
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = alPulsar)
            .defaultMinSize(minHeight = 48.dp)
            .padding(vertical = 12.dp),
    )
}
