package es.israelzamora.srank.api

import java.time.Instant
import java.time.LocalDate
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

/**
 * Madrid fijo, **no la zona del móvil**.
 *
 * El servidor decide a qué día pertenecen las misiones con
 * `config('srank.timezone')`, que es Europe/Madrid. Si la app usara la zona del
 * dispositivo, en un viaje diría «hoy» sobre un día distinto del que el
 * servidor está puntuando.
 */
val ZONA_SRANK: ZoneId = ZoneId.of("Europe/Madrid")

private val FORMATO_DIA_LARGO =
    DateTimeFormatter.ofPattern("EEEE, d 'de' MMMM", Locale.forLanguageTag("es-ES"))

/**
 * De un instante en UTC —como los serializa la API— al día que le toca en
 * Madrid.
 *
 * En 1.1 solo lo usa `date` de `/system/today`, que ya llega calculado. Existe
 * porque a partir de la fase 1.2 la app recibe instantes de verdad (entrenos,
 * comidas) y esta es la conversión que hay que hacer con todos.
 */
fun diaEnMadrid(instanteUtc: String): LocalDate =
    Instant.parse(instanteUtc).atZone(ZONA_SRANK).toLocalDate()

/** «martes, 11 de agosto». En minúscula, que es como va detrás del `//`. */
fun formateaDiaLargo(dia: LocalDate): String = FORMATO_DIA_LARGO.format(dia)
