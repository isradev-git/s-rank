package es.israelzamora.srank

import android.content.Context
import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.creaApi
import es.israelzamora.srank.auth.AuthRepositorio
import es.israelzamora.srank.session.Sesion
import es.israelzamora.srank.session.SesionDataStore
import es.israelzamora.srank.system.SystemRepositorio

/**
 * Quién depende de quién, a mano.
 *
 * ponytail: sin Hilt. Son cinco objetos y seis pantallas; un grafo de
 * inyección costaría un plugin, KSP y anotaciones para ahorrar estas doce
 * líneas. El techo es el número de pantallas: cuando cablearlas a mano
 * empiece a doler —digamos a partir de quince—, Hilt.
 */
class Grafo(contexto: Context) {
    val sesion: Sesion = SesionDataStore(contexto.applicationContext)
    val api: ApiSrank = creaApi(sesion)
    val auth = AuthRepositorio(api, sesion)
    val sistema = SystemRepositorio(api)
}
