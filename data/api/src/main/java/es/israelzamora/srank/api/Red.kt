package es.israelzamora.srank.api

import es.israelzamora.srank.session.Sesion
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.serialization.json.Json
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Response
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory
import java.util.concurrent.TimeUnit

const val URL_BASE = "https://s-rank.israelzamora.es/"

/**
 * El aviso de que el token ya no vale.
 *
 * Lo emite el interceptor y lo recoge la raíz de navegación: un solo sitio, y
 * funciona desde cualquier pantalla sin que cada una tenga que acordarse.
 *
 * `extraBufferCapacity = 1` porque el interceptor corre en un hilo de OkHttp y
 * no puede suspender para emitir.
 */
object SesionExpirada {
    private val _avisos = MutableSharedFlow<Unit>(extraBufferCapacity = 1)
    val avisos: SharedFlow<Unit> = _avisos.asSharedFlow()

    internal fun avisa() {
        _avisos.tryEmit(Unit)
    }
}

/**
 * Mete las dos cabeceras y vigila el 401.
 *
 * `Accept: application/json` **no es opcional**: sin ella el servidor devuelve
 * HTML en los errores y el traductor no puede leer el cuerpo.
 */
private class InterceptorSrank(private val sesion: Sesion) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()

        val conCabeceras = original.newBuilder()
            .header("Accept", "application/json")
            .apply {
                sesion.tokenActual?.let { header("Authorization", "Bearer $it") }
            }
            .build()

        val respuesta = chain.proceed(conCabeceras)

        // Credenciales malas son 422, así que un 401 solo puede significar que
        // el token dejó de valer. No hace falta excluir las rutas de auth.
        if (respuesta.code == 401) SesionExpirada.avisa()

        return respuesta
    }
}

fun creaApi(sesion: Sesion, urlBase: String = URL_BASE): ApiSrank {
    val cliente = OkHttpClient.Builder()
        .addInterceptor(InterceptorSrank(sesion))
        .connectTimeout(15, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()

    return Retrofit.Builder()
        .baseUrl(urlBase)
        .client(cliente)
        .addConverterFactory(
            (jsonSrank as Json).asConverterFactory("application/json".toMediaType()),
        )
        .build()
        .create(ApiSrank::class.java)
}

/**
 * Envuelve una llamada para que los fallos salgan ya traducidos.
 *
 * Las pantallas hacen `pide { api.hoy() }.onFailure { ... }` y enseñan
 * `(it as ErrorApi).mensaje`, que ya está en castellano.
 */
suspend fun <T> pide(bloque: suspend () -> T): Result<T> =
    try {
        Result.success(bloque())
    } catch (t: Throwable) {
        if (t is kotlinx.coroutines.CancellationException) throw t
        Result.failure(traduceError(t))
    }
