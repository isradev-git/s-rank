package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsFocusedAsState
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.ButtonDefaults
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Botón con borde visible y 48 dp de alto mínimo.
 *
 * ponytail: el borde va en apagado (2,72:1) y no en lineas (1,28:1), que no se
 * percibe. Sigue por debajo de los 3:1 que pide WCAG 1.4.11 para el borde de un
 * control; el techo es ese. El botón se identifica por su texto en texto
 * (16,55:1), que siempre está visible. Si en el móvil cuesta verlo, el recambio
 * es un token nuevo #6b7280, que da 4,34:1.
 *
 * Los corchetes de `[ ENTRAR ]` son dibujo: sin el `clearAndSetSemantics` de
 * abajo, TalkBack leería «corchete, entrar, corchete». Va en el `Text` de
 * dentro y no en el `modifier` del botón: `OutlinedButton` delega en `Button`,
 * que hace `Surface(modifier = modifier.semantics { role = Role.Button }, ...)`
 * — el `modifier` que recibe esta función es exterior a ese rol y al
 * `clickable` interno de `Surface`, así que limpiar semántica ahí borraría
 * también el rol de botón y la acción de pulsar. Puesto en el `Text`, que es
 * descendiente, solo sustituye lo que el propio `Text` aporta.
 */
@Composable
fun BotonSRank(
    texto: String,
    alPulsar: () -> Unit,
    modifier: Modifier = Modifier,
    activo: Boolean = true,
    cargando: Boolean = false,
) {
    OutlinedButton(
        onClick = alPulsar,
        enabled = activo && !cargando,
        modifier = modifier.defaultMinSize(minHeight = 48.dp),
        border = BorderStroke(1.dp, if (activo) SRank.color.ambar else SRank.color.apagado),
        colors = ButtonDefaults.outlinedButtonColors(
            contentColor = SRank.color.ambar,
            disabledContentColor = SRank.color.apagado,
        ),
    ) {
        Text(
            text = if (cargando) "[ ... ]" else "[ ${texto.uppercase()} ]",
            style = SRank.texto.cuerpo,
            modifier = Modifier.clearAndSetSemantics {
                text = AnnotatedString(if (cargando) "cargando" else texto)
            },
        )
    }
}

/**
 * Campo de texto. El borde en reposo va en apagado y al enfocar en ámbar, que
 * es lo que dice dónde estás escribiendo. Mismo techo que el botón.
 *
 * El mensaje de error va en `supportingText`, no en un `Text` suelto debajo:
 * `isError = true` solo cambia el color, no crea ninguna asociación con el
 * campo, así que un `Text` aparte se lee «de verdad», pero solo si TalkBack
 * sigue bajando por la pantalla, y no al enfocar el campo que falló. El slot
 * de Material 3 sí lo asocia programáticamente al `OutlinedTextField`.
 */
@Composable
fun CampoSRank(
    valor: String,
    alCambiar: (String) -> Unit,
    etiqueta: String,
    modifier: Modifier = Modifier,
    error: String? = null,
    esContrasena: Boolean = false,
    tecladoCorreo: Boolean = false,
    tecladoNumerico: Boolean = false,
) {
    val interacciones = remember { MutableInteractionSource() }
    val enfocado by interacciones.collectIsFocusedAsState()

    val tipoTeclado = when {
        tecladoCorreo -> KeyboardType.Email
        tecladoNumerico -> KeyboardType.NumberPassword
        esContrasena -> KeyboardType.Password
        else -> KeyboardType.Text
    }

    OutlinedTextField(
        value = valor,
        onValueChange = alCambiar,
        label = { Text(etiqueta, style = SRank.texto.nota) },
        singleLine = true,
        isError = error != null,
        supportingText = error?.let { mensaje ->
            { Text(text = mensaje, style = SRank.texto.nota, color = SRank.color.rojo) }
        },
        interactionSource = interacciones,
        visualTransformation =
            if (esContrasena) PasswordVisualTransformation() else androidx.compose.ui.text.input.VisualTransformation.None,
        keyboardOptions = KeyboardOptions(keyboardType = tipoTeclado),
        textStyle = SRank.texto.cuerpo,
        modifier = modifier.fillMaxWidth().defaultMinSize(minHeight = 48.dp),
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = SRank.color.ambar,
            unfocusedBorderColor = SRank.color.apagado,
            errorBorderColor = SRank.color.rojo,
            focusedTextColor = SRank.color.texto,
            unfocusedTextColor = SRank.color.texto,
            focusedLabelColor = if (enfocado) SRank.color.ambar else SRank.color.apagado,
            unfocusedLabelColor = SRank.color.apagado,
            cursorColor = SRank.color.ambar,
            focusedContainerColor = SRank.color.superficie,
            unfocusedContainerColor = SRank.color.superficie,
        ),
    )
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaControles() {
    SRankTheme {
        Column(Modifier.padding(16.dp)) {
            CampoSRank("hola@ejemplo.es", {}, "correo", tecladoCorreo = true)
            CampoSRank("", {}, "contraseña", esContrasena = true, error = "Credenciales incorrectas.")
            BotonSRank("entrar", {})
            BotonSRank("entrar", {}, cargando = true)
        }
    }
}
