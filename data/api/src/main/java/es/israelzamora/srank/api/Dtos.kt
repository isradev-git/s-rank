package es.israelzamora.srank.api

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json

/**
 * `ignoreUnknownKeys`: el backend puede añadir claves sin publicar una versión
 * nueva de la app. Si eso reventase, cada cambio del servidor dejaría fuera a
 * quien no hubiera actualizado.
 */
val jsonSrank = Json {
    ignoreUnknownKeys = true
    coerceInputValues = true
}

// ── auth ──────────────────────────────────────────────────────────────────

@Serializable
data class LoginPeticionDto(val email: String, val password: String)

@Serializable
data class RegistroPeticionDto(val name: String, val email: String, val password: String)

@Serializable
data class CorreoPeticionDto(val email: String)

@Serializable
data class ResetPeticionDto(val email: String, val code: String, val password: String)

@Serializable
data class LoginRespuestaDto(
    @SerialName("access_token") val accessToken: String,
    @SerialName("token_type") val tokenType: String,
    @SerialName("user_name") val userName: String,
    @SerialName("is_admin") val isAdmin: Boolean = false,
)

@Serializable
data class MensajeDto(val message: String)

@Serializable
data class UsuarioDto(val id: String, val name: String, val email: String)

// ── el Sistema ────────────────────────────────────────────────────────────

@Serializable
data class EstadisticasDto(
    val strength: Int = 0,
    val endurance: Int = 0,
    val consistency: Int = 0,
    val vitality: Int = 0,
)

@Serializable
data class ProgresoDto(
    val level: Int,
    val rank: String,
    @SerialName("xp_total") val xpTotal: Int,
    @SerialName("xp_into_level") val xpIntoLevel: Int,
    @SerialName("xp_for_next") val xpForNext: Int,
    @SerialName("current_streak") val currentStreak: Int,
    @SerialName("longest_streak") val longestStreak: Int,
    val stats: EstadisticasDto = EstadisticasDto(),
)

@Serializable
data class MisionDto(
    val key: String,
    val label: String,
    val target: Int,
    val progress: Int,
    @SerialName("xp_reward") val xpReward: Int,
    @SerialName("is_optional") val isOptional: Boolean,
    val completed: Boolean,
)

@Serializable
data class SugerenciaDto(
    val reason: String,
    @SerialName("weekly_done") val weeklyDone: Int,
    @SerialName("weekly_goal") val weeklyGoal: Int,
)

/**
 * `date` llega ya como día suelto («2026-08-11») calculado por el servidor con
 * su propia zona horaria, así que no hay que convertir nada aquí.
 *
 * `suggested_workout.template` se ignora en 1.1: lo usa la fase 1.2.
 */
@Serializable
data class HoyDto(
    val date: String,
    val progress: ProgresoDto,
    val quests: List<MisionDto>,
    @SerialName("suggested_workout") val suggestedWorkout: SugerenciaDto? = null,
)

// ── errores de Laravel ────────────────────────────────────────────────────

@Serializable
data class ErrorValidacionDto(
    val message: String = "",
    val errors: Map<String, List<String>> = emptyMap(),
)
