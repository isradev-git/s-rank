package es.israelzamora.srank.auth.recuperar

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
fun PantallaRecuperar(
    vm: RecuperarViewModel,
    alTerminar: () -> Unit,
    alVolver: () -> Unit,
) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    LaunchedEffect(estado.cambiada) {
        if (estado.cambiada) alTerminar()
    }

    MarcoAuth("recuperar contraseña") {
        when (estado.paso) {
            PasoRecuperar.CORREO -> {
                Comentario("te enviaremos un código por correo")
                Spacer(Modifier.height(24.dp))

                CampoSRank(
                    estado.correo, vm::escribeCorreo, "correo",
                    error = estado.error, tecladoCorreo = true,
                )
                Spacer(Modifier.height(24.dp))

                BotonSRank(
                    "enviar código", vm::pideCodigo,
                    cargando = estado.cargando, modifier = Modifier.fillMaxWidth(),
                )
            }

            PasoRecuperar.CODIGO -> {
                // El aviso es el mismo exista o no la cuenta. Ver el ViewModel.
                Text(
                    text = estado.aviso.orEmpty(),
                    style = SRank.texto.cuerpo,
                    color = SRank.color.texto,
                )
                Spacer(Modifier.height(24.dp))

                CampoSRank(
                    estado.codigo, vm::escribeCodigo, "código de 6 cifras",
                    error = estado.errorCodigo, tecladoNumerico = true,
                )
                Spacer(Modifier.height(12.dp))

                CampoSRank(
                    estado.contrasena, vm::escribeContrasena, "contraseña nueva",
                    error = estado.errorContrasena, esContrasena = true,
                )
                Spacer(Modifier.height(24.dp))

                BotonSRank(
                    "cambiar contraseña", vm::cambiaContrasena,
                    cargando = estado.cargando, modifier = Modifier.fillMaxWidth(),
                )

                estado.error?.let { mensaje ->
                    Spacer(Modifier.height(12.dp))
                    Text(mensaje, style = SRank.texto.cuerpo, color = SRank.color.rojo)
                }
            }
        }

        Spacer(Modifier.height(24.dp))
        Enlace("volver a entrar", alVolver)
    }
}
