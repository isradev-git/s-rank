package es.israelzamora.srank.auth

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.CorreoPeticionDto
import es.israelzamora.srank.api.LoginPeticionDto
import es.israelzamora.srank.api.RegistroPeticionDto
import es.israelzamora.srank.api.ResetPeticionDto
import es.israelzamora.srank.api.pide
import es.israelzamora.srank.session.Sesion

/**
 * Todo lo que la app sabe hacer con una cuenta.
 *
 * Guardar la sesión es parte de entrar: si la pantalla tuviera que acordarse
 * de hacerlo, tarde o temprano una de las tres se olvidaría.
 */
class AuthRepositorio(
    private val api: ApiSrank,
    private val sesion: Sesion,
) {

    suspend fun entrar(correo: String, contrasena: String): Result<Unit> =
        pide { api.login(LoginPeticionDto(correo.trim(), contrasena)) }
            .map { sesion.guarda(it.accessToken, it.userName) }

    suspend fun registrar(nombre: String, correo: String, contrasena: String): Result<Unit> =
        pide { api.registro(RegistroPeticionDto(nombre.trim(), correo.trim(), contrasena)) }
            .map { sesion.guarda(it.accessToken, it.userName) }

    /**
     * Responde bien exista o no el correo. La pantalla **no puede** cambiar de
     * comportamiento según el resultado: eso reintroduciría desde el cliente la
     * fuga que el servidor evita respondiendo siempre lo mismo.
     */
    suspend fun pideCodigo(correo: String): Result<Unit> =
        pide { api.olvideContrasena(CorreoPeticionDto(correo.trim())) }.map { }

    suspend fun cambiaContrasena(
        correo: String,
        codigo: String,
        contrasena: String,
    ): Result<Unit> =
        pide { api.cambiaContrasena(ResetPeticionDto(correo.trim(), codigo.trim(), contrasena)) }
            .map { }

    /**
     * Limpia la sesión local pase lo que pase. Si el servidor no contesta, el
     * token se queda vivo allí, pero dejarlo también en el móvil sería peor:
     * el usuario pidió salir.
     */
    suspend fun salir() {
        pide { api.salir() }
        sesion.limpia()
    }
}
