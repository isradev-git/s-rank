package es.israelzamora.srank.nav

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import es.israelzamora.srank.Grafo
import es.israelzamora.srank.api.SesionExpirada
import es.israelzamora.srank.auth.login.LoginViewModel
import es.israelzamora.srank.auth.login.PantallaLogin
import es.israelzamora.srank.auth.recuperar.PantallaRecuperar
import es.israelzamora.srank.auth.recuperar.RecuperarViewModel
import es.israelzamora.srank.auth.registro.PantallaRegistro
import es.israelzamora.srank.auth.registro.RegistroViewModel
import es.israelzamora.srank.hoy.HoyViewModel
import es.israelzamora.srank.hoy.PantallaHoy
import es.israelzamora.srank.hoy.PantallaPerfil
import es.israelzamora.srank.hoy.PantallaProgreso
import es.israelzamora.srank.session.Sesion
import es.israelzamora.srank.ui.theme.SRank
import kotlinx.coroutines.flow.first

private object Rutas {
    const val CARGANDO = "cargando"
    const val LOGIN = "login"
    const val REGISTRO = "registro"
    const val RECUPERAR = "recuperar"
    const val HOY = "hoy"
    const val PROGRESO = "progreso"
    const val PERFIL = "perfil"
}

private val PESTANAS = listOf(Rutas.HOY, Rutas.PROGRESO, Rutas.PERFIL)

/**
 * Contrato 1 de la tarea 12: `SesionDataStore.tokenActual` solo se rellena
 * cuando algo colecta `token` (ver `Sesion.kt`). Si decidiéramos la pantalla
 * con `sesion.tokenActual` directamente, en un arranque en frío valdría
 * `null` aunque hubiera sesión guardada, y se mandaría al usuario a login.
 *
 * `token.first()` espera esa primera emisión de verdad, y de paso dispara el
 * `onEach` que rellena `tokenActual`: cuando esta función devuelve, ya está
 * listo para que el interceptor lo lea en la primera petición autenticada.
 * Se extrae como función `internal` (en vez de vivir dentro del
 * `LaunchedEffect`) para poder fijar ese orden con un test de verdad — ver
 * `NavRaizTest.kt`.
 */
internal suspend fun decideRutaInicial(sesion: Sesion): String =
    if (sesion.token.first() != null) Rutas.HOY else Rutas.LOGIN

@Composable
fun NavRaiz(grafo: Grafo) {
    val nav = rememberNavController()

    // Espera al primer valor de DataStore antes de decidir la pantalla. De
    // paso, eso deja lleno el `tokenActual` que lee el interceptor, así que la
    // primera petición ya sale con Authorization.
    LaunchedEffect(Unit) {
        val ruta = decideRutaInicial(grafo.sesion)
        nav.navigate(ruta) {
            popUpTo(Rutas.CARGANDO) { inclusive = true }
        }
    }

    // El 401 desde cualquier pantalla: un solo sitio. `NavRaiz` es la raíz de
    // navegación, por encima del NavHost y de cualquier pantalla, así que
    // este scope vive tanto como la composición de la app entera y no se
    // cancela al cambiar de pestaña o de ruta.
    LaunchedEffect(Unit) {
        SesionExpirada.avisos.collect {
            grafo.sesion.limpia()
            nav.navigate(Rutas.LOGIN) { popUpTo(0) }
        }
    }

    val entrada by nav.currentBackStackEntryAsState()
    val rutaActual = entrada?.destination?.route
    val enPestanas = rutaActual in PESTANAS

    Scaffold(
        containerColor = SRank.color.fondo,
        topBar = { if (enPestanas) Pestanas(rutaActual, nav) },
    ) { relleno ->
        NavHost(
            navController = nav,
            startDestination = Rutas.CARGANDO,
            modifier = Modifier.fillMaxSize().padding(relleno),
        ) {
            composable(Rutas.CARGANDO) { Column {} }

            composable(Rutas.LOGIN) {
                PantallaLogin(
                    vm = viewModel(factory = LoginViewModel.factoria(grafo.auth)),
                    // Contrato 3: `LaunchedEffect(estado.entrado)` en
                    // `PantallaLogin` se dispara en cada composición nueva,
                    // no solo en el cambio false→true. Si la entrada de
                    // login sigue viva y se recompone con `entrado` ya en
                    // true, `alEntrar` se llama otra vez. Comprobar la ruta
                    // actual del propio `NavHostController` (no un `State`
                    // observado, sino el valor síncrono real) hace la
                    // llamada repetida inofensiva: si ya estamos en «hoy»,
                    // no hay nada que navegar.
                    alEntrar = {
                        if (nav.currentDestination?.route != Rutas.HOY) {
                            nav.navigate(Rutas.HOY) { popUpTo(0) }
                        }
                    },
                    alRegistrarse = { nav.navigate(Rutas.REGISTRO) },
                    alOlvidar = { nav.navigate(Rutas.RECUPERAR) },
                )
            }

            composable(Rutas.REGISTRO) {
                PantallaRegistro(
                    vm = viewModel(factory = RegistroViewModel.factoria(grafo.auth)),
                    // Mismo blindaje que en login: `PantallaRegistro` dispara
                    // `LaunchedEffect(estado.registrado)` en cada
                    // composición nueva.
                    alRegistrarse = {
                        if (nav.currentDestination?.route != Rutas.HOY) {
                            nav.navigate(Rutas.HOY) { popUpTo(0) }
                        }
                    },
                    alVolver = { nav.popBackStack() },
                )
            }

            composable(Rutas.RECUPERAR) {
                // Contrato 4: `RecuperarViewModel` no tiene `reinicia()`. No
                // hace falta: `alVolver` usa `popBackStack()` sin
                // `saveState`, así que la entrada de «recuperar» y su
                // `ViewModelStore` se destruyen al volver. La próxima vez
                // que se navega aquí (`alOlvidar` en login) es una entrada
                // nueva del back stack con un `RecuperarViewModel` recién
                // creado, en el paso 1, sin el código del intento anterior.
                // Esto solo se rompería si algún día esta ruta empezara a
                // usar `saveState`/`restoreState` como hacen las pestañas.
                PantallaRecuperar(
                    vm = viewModel(factory = RecuperarViewModel.factoria(grafo.auth)),
                    alTerminar = {
                        if (nav.currentDestination?.route != Rutas.LOGIN) {
                            nav.navigate(Rutas.LOGIN) { popUpTo(0) }
                        }
                    },
                    alVolver = { nav.popBackStack() },
                )
            }

            composable(Rutas.HOY) {
                PantallaHoy(vm = viewModel(factory = HoyViewModel.factoria(grafo.sistema)))
            }
            composable(Rutas.PROGRESO) { PantallaProgreso() }
            composable(Rutas.PERFIL) { PantallaPerfil() }
        }
    }
}

/**
 * Pestañas arriba y fijas, con subrayado en la activa.
 *
 * Fijas y no ocultables: perderlas en un scroll largo es peor que gastar los
 * 48 dp que ocupan.
 */
@Composable
private fun Pestanas(rutaActual: String?, nav: NavHostController) {
    val indice = PESTANAS.indexOf(rutaActual).coerceAtLeast(0)

    TabRow(
        selectedTabIndex = indice,
        containerColor = SRank.color.fondo,
        contentColor = SRank.color.ambar,
    ) {
        PESTANAS.forEach { ruta ->
            Tab(
                selected = ruta == rutaActual,
                onClick = {
                    if (ruta != rutaActual) {
                        nav.navigate(ruta) {
                            popUpTo(Rutas.HOY) { saveState = true }
                            launchSingleTop = true
                            restoreState = true
                        }
                    }
                },
                text = {
                    Text(
                        text = ruta,
                        style = SRank.texto.cuerpo,
                        color = if (ruta == rutaActual) SRank.color.ambar else SRank.color.apagado,
                    )
                },
            )
        }
    }
}
