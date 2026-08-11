package es.israelzamora.srank.nav

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.WindowInsetsSides
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.only
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawing
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.material3.PrimaryTabRow
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Tab
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
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
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first

private object Rutas {
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
 *
 * Esto por sí solo no basta: ver el comentario de `rutaInicial` en `NavRaiz`
 * sobre por qué además hay que evitar que se componga ninguna pantalla antes
 * de que esta función devuelva.
 */
internal suspend fun decideRutaInicial(sesion: Sesion): String =
    if (sesion.token.first() != null) Rutas.HOY else Rutas.LOGIN

/**
 * Contrato 2: quien colecta `avisos` es quien limpia la sesión y decide a
 * dónde ir, nunca el interceptor (que no puede suspender). Se extrae del
 * `LaunchedEffect` de `NavRaiz` por la misma razón que `decideRutaInicial`:
 * es lógica de corrutina pura —nada de `NavController` ni de Compose—, así
 * que se puede fijar con un test normal sin más infraestructura. El
 * `Flow<Unit>` se recibe por parámetro (no se lee `SesionExpirada.avisos`
 * directamente aquí) porque `SesionExpirada.avisa()` es `internal` de
 * `data/api`: un test de `app` no tiene forma de emitir sobre el objeto real,
 * así que hace falta poder pasarle uno de mentira.
 */
internal suspend fun escuchaExpiracion(avisos: Flow<Unit>, sesion: Sesion, alLogin: () -> Unit) {
    avisos.collect {
        sesion.limpia()
        alLogin()
    }
}

@Composable
fun NavRaiz(grafo: Grafo) {
    val nav = rememberNavController()

    // Contrato 1, la vuelta que dejó abierta la primera versión: esperar el
    // primer valor del token y luego *navegar* no basta. Si el proceso había
    // muerto con «hoy» en el back stack, NavHost restaura esa pantalla
    // durante la composición, antes de que corra ningún LaunchedEffect;
    // HoyViewModel se crea, su `init` dispara la petición, y sale sin
    // Authorization porque tokenActual todavía no se ha rellenado. El 401
    // que contesta el servidor hace que el colector de más abajo borre un
    // token válido: el fallo que este contrato existe para impedir, entrando
    // por la puerta de al lado.
    //
    // La forma de cerrarla es no componer ninguna pantalla —y por tanto
    // ningún ViewModel— hasta tener la ruta resuelta, en vez de navegar
    // hacia ella después. `NavHost` solo se compone cuando `rutaInicial` deja
    // de ser null.
    var rutaInicial by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(Unit) {
        rutaInicial = decideRutaInicial(grafo.sesion)
    }

    val inicio = rutaInicial
    if (inicio == null) {
        // Ni Scaffold ni NavHost: nada que pudiera componer una pantalla
        // todavía. Solo el fondo, para no dejar un parpadeo del color por
        // defecto de la ventana mientras se resuelve la ruta.
        Box(Modifier.fillMaxSize().background(SRank.color.fondo))
        return
    }

    // El 401 desde cualquier pantalla: un solo sitio. Tiene que ir *después*
    // del gate de arriba: `NavHost`, más abajo, todavía no ha llamado a
    // `setGraph()` mientras `rutaInicial` es null, y `nav.navigate()` antes
    // de eso revienta con «You must call setGraph() before calling
    // getGraph()». En arranque en frío ese hueco no se nota porque
    // `decideRutaInicial` resuelve antes de que pueda llegar ningún 401. En
    // una recreación de Activity sí: el `ViewModelStore` del back stack
    // sobrevive a la rotación, así que una petición de `HoyViewModel` puede
    // seguir en vuelo mientras la composición nueva espera a `rutaInicial`,
    // y si esa petición vuelve con 401 antes de llegar aquí, el aviso se
    // pierde (el `SharedFlow` de `SesionExpirada` tiene `replay = 0`) en vez
    // de hacer caer la app.
    //
    // ponytail: esa ventana sin colector en cada rotación es el techo de
    // este arreglo — perder un aviso puntual es preferible a un crash, pero
    // sigue siendo perder un aviso. No se cierra subiendo `replay` a 1:
    // reentregaría un aviso viejo a cada composición nueva y echaría al
    // usuario a login en cada rotación, un fallo peor que el que se evita. El
    // arreglo de verdad, que es de la fase 1.2 y no de esta: un colector en
    // `Aplicacion`, con scope de proceso (no de Activity), que solo llame a
    // `sesion.limpia()`; y que la navegación a login se conduzca observando
    // `sesion.token` en vez de reaccionar a un aviso puntual que puede pasar
    // sin nadie escuchando.
    LaunchedEffect(Unit) {
        escuchaExpiracion(SesionExpirada.avisos, grafo.sesion) {
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
            startDestination = inicio,
            modifier = Modifier.fillMaxSize().padding(relleno),
        ) {
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
 *
 * `windowInsetsPadding(WindowInsets.safeDrawing.only(Top + Horizontal))`: con
 * `enableEdgeToEdge()` en `MainActivity`, `Scaffold` coloca su `topBar` en
 * (0,0) sin aplicarle ningún inset —lo espera del propio contenido, como
 * hacen los `TopAppBar` de Material 3—, así que sin esto la pestaña queda
 * debajo de la barra de estado del sistema, que además se come el toque.
 * `safeDrawing` y no solo `statusBars`: en horizontal con muesca lateral
 * (notch), las pestañas de los extremos quedan bajo el recorte si solo se
 * evita la barra de estado. Solo arriba y a los lados: abajo no hace falta
 * evitar nada porque `topBar` no llega ahí.
 *
 * El color de la pestaña inactiva es `texto`, no `apagado`: el nombre de la
 * pestaña es la única información de que hay una sección «progreso» o
 * «perfil» —no hay icono—, y `apagado` da 2,72:1 sobre negro (ver
 * `ContrasteTest.apagado_no_llega_a_45_y_por_eso_no_puede_llevar_datos`),
 * muy por debajo del 4,5:1 que pide WCAG 1.4.3 para texto que dice algo. La
 * pestaña activa se distingue por el color y por el subrayado; las inactivas
 * solo tienen el color, así que no pueden ser el gris decorativo.
 */
@Composable
private fun Pestanas(rutaActual: String?, nav: NavHostController) {
    val indice = PESTANAS.indexOf(rutaActual).coerceAtLeast(0)

    PrimaryTabRow(
        selectedTabIndex = indice,
        modifier = Modifier.windowInsetsPadding(
            WindowInsets.safeDrawing.only(WindowInsetsSides.Top + WindowInsetsSides.Horizontal),
        ),
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
                        color = if (ruta == rutaActual) SRank.color.ambar else SRank.color.texto,
                    )
                },
            )
        }
    }
}
