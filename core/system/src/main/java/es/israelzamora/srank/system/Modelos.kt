package es.israelzamora.srank.system

import es.israelzamora.srank.api.HoyDto
import es.israelzamora.srank.api.MisionDto
import es.israelzamora.srank.api.ProgresoDto
import es.israelzamora.srank.api.formateaDiaLargo
import java.time.LocalDate
import java.text.NumberFormat
import java.util.Locale

/**
 * El progreso tal como se pinta. **Ningún número se calcula aquí**: el XP lo
 * decide siempre el servidor y la app solo lo enseña.
 */
data class Progreso(
    val nivel: Int,
    val rango: String,
    val xpEnNivel: Int,
    val xpParaSiguiente: Int,
    val racha: Int,
    val rachaMasLarga: Int,
)

data class Mision(
    val clave: String,
    val etiqueta: String,
    val completada: Boolean,
    /** «5.240 de 8.000», o null si no aporta nada. */
    val avance: String?,
)

data class Hoy(
    val dia: String,
    val progreso: Progreso,
    val misiones: List<Mision>,
) {
    /** «1 de 4», para la cabecera de la sección. */
    val contadorMisiones: String
        get() = "${misiones.count { it.completada }} de ${misiones.size}"
}

fun HoyDto.aDominio(): Hoy = Hoy(
    dia = formateaDiaLargo(LocalDate.parse(date)),
    progreso = progress.aDominio(),
    misiones = quests.map { it.aDominio() },
)

fun ProgresoDto.aDominio(): Progreso = Progreso(
    nivel = level,
    rango = rank,
    xpEnNivel = xpIntoLevel,
    xpParaSiguiente = xpForNext,
    racha = currentStreak,
    rachaMasLarga = longestStreak,
)

fun MisionDto.aDominio(): Mision = Mision(
    clave = key,
    etiqueta = label,
    completada = completed,
    avance = avanceLegible(),
)

/**
 * El avance parcial solo cuando dice algo.
 *
 * Ni en las hechas —«2.000 de 2.000» debajo de una casilla marcada es ruido—
 * ni en las de objetivo 1, donde «0 de 1» no añade nada a la propia casilla.
 */
private fun MisionDto.avanceLegible(): String? = when {
    completed -> null
    target <= 1 -> null
    else -> {
        // Instanciado aquí y no a nivel de fichero: NumberFormat/DecimalFormat
        // no son thread-safe, y en 1.2 esto lo llamará más de una pantalla.
        val numeros = NumberFormat.getIntegerInstance(Locale.forLanguageTag("es-ES"))
        "${numeros.format(progress)} de ${numeros.format(target)}"
    }
}
