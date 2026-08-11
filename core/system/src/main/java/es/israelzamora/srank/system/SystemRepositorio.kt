package es.israelzamora.srank.system

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.pide

/**
 * `/api/system/today` genera las misiones del día si aún no existen, y es
 * idempotente. No hace falta pedir nada más al arrancar.
 */
class SystemRepositorio(private val api: ApiSrank) {
    suspend fun hoy(): Result<Hoy> = pide { api.hoy().aDominio() }
}
