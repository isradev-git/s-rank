package es.israelzamora.srank.session

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.emptyPreferences
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.onEach

/**
 * Dónde vive la sesión.
 *
 * `tokenActual` es una copia en memoria del último valor. Existe porque el
 * interceptor de OkHttp no puede suspender para leer DataStore, y hacer
 * runBlocking en cada petición sería pagar disco en el hilo de red.
 *
 * Dos implementaciones de verdad: DataStore en la app y en memoria en los
 * tests. La interfaz no es ceremonia, es lo que permite probar el login sin
 * un Context de Android.
 *
 * ponytail: el token se guarda en claro en DataStore, sin cifrar. Hoy lo
 * protege el sandbox de la app (otro proceso no puede leer este fichero) y
 * `allowBackup="false"` (no viaja a una copia de seguridad de Google). No
 * protege nada si el móvil está rooteado o si alguien saca una copia manual
 * del directorio de datos de la app. El recambio, si algún día hace falta,
 * es `EncryptedFile` o el `MasterKey` de `androidx.security.crypto` sobre
 * este mismo DataStore.
 */
interface Sesion {
    val token: Flow<String?>
    val nombre: Flow<String?>

    /** Última copia conocida, para quien no puede suspender. */
    val tokenActual: String?

    suspend fun guarda(token: String, nombre: String)
    suspend fun limpia()
}

private val Context.almacen: DataStore<Preferences> by preferencesDataStore(name = "sesion")

private val CLAVE_TOKEN = stringPreferencesKey("token")
private val CLAVE_NOMBRE = stringPreferencesKey("nombre")

class SesionDataStore(private val context: Context) : Sesion {

    @Volatile
    override var tokenActual: String? = null
        private set

    // `.catch`: con el fichero de DataStore corrupto, `.data` lanza
    // IOException en vez de emitir. Sin esto, `token.first()` revienta
    // dentro del LaunchedEffect de arranque (`decideRutaInicial` en
    // `NavRaiz.kt`), `rutaInicial` no se resuelve nunca y la app se queda en
    // la pantalla de carga para siempre. Tratar el fichero corrupto como
    // vacío es lo mismo que hace DataStore internamente para un fichero que
    // no existe todavía.
    override val token: Flow<String?> =
        context.almacen.data
            .catch { emit(emptyPreferences()) }
            .map { it[CLAVE_TOKEN] }
            .onEach { tokenActual = it }

    override val nombre: Flow<String?> =
        context.almacen.data
            .catch { emit(emptyPreferences()) }
            .map { it[CLAVE_NOMBRE] }

    override suspend fun guarda(token: String, nombre: String) {
        context.almacen.edit {
            it[CLAVE_TOKEN] = token
            it[CLAVE_NOMBRE] = nombre
        }
        tokenActual = token
    }

    override suspend fun limpia() {
        context.almacen.edit { it.clear() }
        tokenActual = null
    }
}

/** La de los tests. Mismo contrato, sin disco. */
class SesionEnMemoria : Sesion {
    private val _token = MutableStateFlow<String?>(null)
    private val _nombre = MutableStateFlow<String?>(null)

    override val token: Flow<String?> = _token.asStateFlow()
    override val nombre: Flow<String?> = _nombre.asStateFlow()
    override val tokenActual: String? get() = _token.value

    override suspend fun guarda(token: String, nombre: String) {
        _token.value = token
        _nombre.value = nombre
    }

    override suspend fun limpia() {
        _token.value = null
        _nombre.value = null
    }
}
