package es.israelzamora.srank.auth

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.CorreoPeticionDto
import es.israelzamora.srank.api.HoyDto
import es.israelzamora.srank.api.LoginPeticionDto
import es.israelzamora.srank.api.LoginRespuestaDto
import es.israelzamora.srank.api.MensajeDto
import es.israelzamora.srank.api.PerfilDto
import es.israelzamora.srank.api.RegistroPeticionDto
import es.israelzamora.srank.api.ResetPeticionDto
import es.israelzamora.srank.api.UsuarioDto

/**
 * Devuelve lo que se le diga, y apunta cuántas veces la han llamado.
 *
 * Cada campo es la respuesta de un endpoint: o un valor, o una excepción que
 * se lanza. Así un test escribe `api.respuestaLogin = { throw ... }` y ya.
 */
open class ApiFalsa : ApiSrank {

    var vecesLogin = 0
        private set
    var vecesRegistro = 0
        private set
    var vecesOlvide = 0
        private set
    var ultimoCorreoOlvido: String? = null
        private set

    var respuestaLogin: () -> LoginRespuestaDto = {
        LoginRespuestaDto("42|abcdef", "Bearer", "Israel", false)
    }
    var respuestaRegistro: () -> LoginRespuestaDto = {
        LoginRespuestaDto("43|ghijkl", "Bearer", "Nuevo", false)
    }
    var respuestaOlvide: () -> MensajeDto = {
        MensajeDto("Si ese correo está registrado, te hemos enviado un código.")
    }
    var respuestaReset: () -> MensajeDto = {
        MensajeDto("Contraseña cambiada. Ya puedes entrar.")
    }
    var respuestaHoy: () -> HoyDto = { error("sin configurar") }

    override suspend fun login(peticion: LoginPeticionDto): LoginRespuestaDto {
        vecesLogin++
        return respuestaLogin()
    }

    override suspend fun registro(peticion: RegistroPeticionDto): LoginRespuestaDto {
        vecesRegistro++
        return respuestaRegistro()
    }

    override suspend fun olvideContrasena(peticion: CorreoPeticionDto): MensajeDto {
        vecesOlvide++
        ultimoCorreoOlvido = peticion.email
        return respuestaOlvide()
    }

    override suspend fun cambiaContrasena(peticion: ResetPeticionDto) = respuestaReset()

    override suspend fun salir() = MensajeDto("Sesión cerrada correctamente.")

    override suspend fun usuario() = UsuarioDto("1", "Israel", "hola@ejemplo.es")

    override suspend fun hoy() = respuestaHoy()

    override suspend fun perfil(): PerfilDto = error("sin configurar")
}
