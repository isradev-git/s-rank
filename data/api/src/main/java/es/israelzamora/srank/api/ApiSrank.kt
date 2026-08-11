package es.israelzamora.srank.api

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

/**
 * Los ocho endpoints de la fase 1.1. Las rutas están copiadas de
 * backend/routes/api.php, no recordadas.
 *
 * Ojo con `auth/login`: credenciales malas devuelven **422**, no 401. El 401
 * solo puede venir de las rutas con token, y de eso depende que el manejador
 * global no necesite excepciones para auth.
 */
interface ApiSrank {

    @POST("api/auth/login")
    suspend fun login(@Body peticion: LoginPeticionDto): LoginRespuestaDto

    @POST("api/auth/register")
    suspend fun registro(@Body peticion: RegistroPeticionDto): LoginRespuestaDto

    /** Responde 200 exista o no el correo. A propósito. */
    @POST("api/auth/forgot-password")
    suspend fun olvideContrasena(@Body peticion: CorreoPeticionDto): MensajeDto

    @POST("api/auth/reset-password")
    suspend fun cambiaContrasena(@Body peticion: ResetPeticionDto): MensajeDto

    @POST("api/auth/logout")
    suspend fun salir(): MensajeDto

    @GET("api/user")
    suspend fun usuario(): UsuarioDto

    @GET("api/system/today")
    suspend fun hoy(): HoyDto

    @GET("api/system/profile")
    suspend fun perfil(): PerfilDto
}
