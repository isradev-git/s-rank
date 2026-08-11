package es.israelzamora.srank.auth.registro

import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.auth.MarcoAuth
import es.israelzamora.srank.auth.login.Enlace
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.componentes.CampoSRank
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaRegistro(
    vm: RegistroViewModel,
    alRegistrarse: () -> Unit,
    alVolver: () -> Unit,
) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    LaunchedEffect(estado.registrado) {
        if (estado.registrado) alRegistrarse()
    }

    MarcoAuth("crear cuenta") {
        Comentario("la contraseña necesita 8 caracteres como mínimo")
        Spacer(Modifier.height(24.dp))

        CampoSRank(estado.nombre, vm::escribeNombre, "nombre", error = estado.errorNombre)
        Spacer(Modifier.height(12.dp))

        CampoSRank(
            estado.correo, vm::escribeCorreo, "correo",
            error = estado.errorCorreo, tecladoCorreo = true,
        )
        Spacer(Modifier.height(12.dp))

        CampoSRank(
            estado.contrasena, vm::escribeContrasena, "contraseña",
            error = estado.errorContrasena, esContrasena = true,
        )
        Spacer(Modifier.height(24.dp))

        BotonSRank(
            "crear cuenta", vm::registrar,
            cargando = estado.cargando, modifier = Modifier.fillMaxWidth(),
        )

        estado.errorGeneral?.let { mensaje ->
            Spacer(Modifier.height(12.dp))
            Text(mensaje, style = SRank.texto.cuerpo, color = SRank.color.rojo)
        }

        Spacer(Modifier.height(24.dp))
        Enlace("ya tengo cuenta", alVolver)
    }
}
