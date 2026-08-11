# Fase 1.1 · Esqueleto Android — Plan de implementación

> **Para quien ejecute esto:** los pasos van con casilla (`- [ ]`) para poder marcarlos.
> Cada tarea acaba en algo que compila y se puede probar por separado.

**Objetivo:** que exista un proyecto Android que arranca, se ve como S-RANK, deja entrar a
un usuario y le enseña su nivel.

**Arquitectura:** seis módulos de Gradle. `core/ui` no depende de nadie y `core/system` no
declara `feature/*`, así que la dirección de dependencias la vigila el compilador. La app
pinta lo que el servidor decide: el XP no se calcula aquí nunca.

**Herramientas:** Kotlin · Jetpack Compose · AGP 9.3.1 con Kotlin integrado · Retrofit 3 ·
kotlinx.serialization · DataStore · JUnit 4.

---

## Restricciones globales

Valen para **todas** las tareas. Copiadas del spec, con los valores exactos.

- **Idioma:** todo en español —commits, comentarios, identificadores de dominio y cualquier
  texto que vea el usuario. Los identificadores en castellano van **sin tildes**.
- **La estética es decoración.** El `$`, los `//` y los `[✓]` van encima de listas y
  botones normales. Si una pantalla solo se entiende sabiendo lo que es una terminal, está
  mal diseñada.
- **Nunca se alinea con espacios.** Siempre `Row` + `Spacer(Modifier.weight(1f))`. La app
  respeta el tamaño de fuente del sistema y los anchos fijos en caracteres se rompen.
- **Todos los tamaños de texto en `sp`**, nunca en `dp`.
- **Objetivos táctiles: 48 dp de alto mínimo.**
- **`Accept: application/json` en toda petición.** Sin esa cabecera el servidor devuelve
  HTML en los errores.
- **Ningún código HTTP en pantalla.** «No hay conexión», no «Error 503».
- **El cian `#22d3ee` es exclusivo de las ventanas del Sistema.** Es la única regla de
  color sin excepciones. Rojo y morado, lo mismo: solo en el momento de recompensa.
- **`apagado` nunca lleva la única copia de un dato** (2,72:1, por debajo de 4,5:1).
- **Zona horaria fija `Europe/Madrid`**, no la del móvil.
- **`forgot-password` avanza al paso 2 siempre**, con el mismo texto exista o no la cuenta.
- **Nunca se commitea** `*.sqlite`, `*.sql` ni `.env*`.
- **`old/` y `backend/` no se tocan** en esta fase.
- **Un test que no falla sin el arreglo no vale.** Al implementar: escribe el test, míralo
  fallar, y solo entonces implementa.
- **`ponytail:`** marca una simplificación deliberada y **nombra su techo**.

---

## Entorno — ya está preparado

Instalado y comprobado el 2026-08-11. **No hay que instalar nada más ni usar `sudo`.**

| Qué | Dónde | Versión |
|---|---|---|
| JDK | `~/jdk` | Temurin 21.0.12 |
| Android SDK | `~/Android/Sdk` | platform-tools · android-36 · build-tools 36.1.0 |
| Gradle suelto | `~/gradle-dist/gradle-9.5.0` | solo para generar el wrapper una vez |

Toda orden de Gradle se lanza con estas dos variables:

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
```

**No se crea `local.properties`.** `ANDROID_HOME` ya se lo dice a Gradle, y ese fichero
apuntaría a un SDK distinto si algún día se abre el proyecto desde Windows.

### Lo que se midió, para no repetir el susto

Comprobado con un proyecto de prueba antes de escribir este plan, sobre `/mnt/c`:

- compilación en frío ≈ 2 min · incremental ≈ 15 s · tests ≈ 23 s. **No hace falta mover
  el `buildDir`** al disco de WSL.
- AGP 9.3.1 con Compose y con `kotlinx.serialization` **compila y pasa tests**. El Kotlin
  integrado (2.2.10) convive con los dos plugins.
- JetBrains Mono 2.304 **trae todos los glifos del diseño**: `▓ ░ ▒ █ ▸ ▾ ✓ ◆ ◇ ─ ▔`.
  Cae el riesgo del spec §5.4: **no hace falta el respaldo con `Canvas`**.

### Trampa de AGP 9

El DSL cambió. Esto **ya no compila**:

```kotlin
compileSdk = 36          // ❌ AGP 8
minSdk = 26              // ❌ AGP 8
```

Lo que hay que escribir:

```kotlin
compileSdk { version = release(36) }              // ✅ AGP 9
defaultConfig { minSdk { version = release(26) } } // ✅ AGP 9
```

Y **no se aplica el plugin `org.jetbrains.kotlin.android`**: AGP 9 trae Kotlin dentro y ese
plugin es incompatible con el DSL nuevo.

`minSdk 26` sale de `java.time`: a partir de ahí funciona sin *desugaring*.

### El `.gitignore` ya está

El documento de fase avisa de que hay que añadirle lo de Android. **Ya lo tiene**: `.gradle/`,
`build/`, `local.properties`, `captures/`, `*.iml`, `*.apk`, `*.aab`, `*.keystore`, `*.jks`,
y encima de las reglas que protegen `*.sqlite`, `*.sql` y `.env*`. No hay que tocarlo.

Lo único que sigue haciendo falta es **mirar el `git status` antes de cada commit**: la
regla de `local.properties` no está anclada a la raíz, así que cubre el fichero, pero un
volcado o un `.env` que aparezca con otro nombre no lo cubre nadie.

---

## Estructura de ficheros

Paquete raíz `es.israelzamora.srank`.

```
settings.gradle.kts · build.gradle.kts · gradle.properties · gradle/libs.versions.toml

app/                       es.israelzamora.srank
  MainActivity.kt          arranque
  Aplicacion.kt            construye el Grafo
  Grafo.kt                 quién depende de quién, a mano
  nav/NavRaiz.kt           rutas, pestañas y el 401 global
  hoy/PantallaHoy.kt       la pantalla de hoy
  hoy/HoyViewModel.kt
  hoy/PantallasVacias.kt   progreso y perfil

core/ui/                   es.israelzamora.srank.ui
  theme/Color.kt           los once tokens
  theme/Type.kt            la escala de cinco tamaños
  theme/Theme.kt           SRankTheme
  componentes/*.kt         los ocho componentes
  res/font/                JetBrains Mono

core/system/               es.israelzamora.srank.system
  Modelos.kt               Progreso, Mision, Rango
  SystemRepositorio.kt
  CabeceraProgreso.kt      nivel, rango, barra, racha
  ListaMisiones.kt

data/api/                  es.israelzamora.srank.api
  ApiSrank.kt              la interfaz de Retrofit
  Dtos.kt
  ErrorApi.kt              + el traductor
  Fechas.kt
  Red.kt                   OkHttp, Retrofit, interceptor

data/session/              es.israelzamora.srank.session
  Sesion.kt                interfaz + DataStore + en memoria

feature/auth/              es.israelzamora.srank.auth
  AuthRepositorio.kt
  login/ · registro/ · recuperar/
```

`feature/training`, `nutrition`, `progress`, `profile` y `data/draft` **no se crean**: los
levanta cada fase cuando le toque.

---

## Tarea 1 · Esqueleto de Gradle que compila y ejecuta un test

**Ficheros:**
- Crear: `settings.gradle.kts`, `build.gradle.kts`, `gradle.properties`,
  `gradle/libs.versions.toml`
- Crear: `app/build.gradle.kts`, `app/src/main/AndroidManifest.xml`
- Crear: `app/src/main/java/es/israelzamora/srank/MainActivity.kt`
- Test: `app/src/test/java/es/israelzamora/srank/ArranqueTest.kt`

**Interfaces:**
- Produce: el catálogo `libs.*` que usan todos los módulos siguientes, y los seis módulos
  declarados en `settings.gradle.kts`.

---

- [ ] **Paso 1: Generar el wrapper de Gradle**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/fit_app_android_beta
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
~/gradle-dist/gradle-9.5.0/bin/gradle wrapper --gradle-version 9.5.0
```

Comprueba que quedó bien:

```bash
grep distributionUrl gradle/wrapper/gradle-wrapper.properties
# distributionUrl=https\://services.gradle.org/distributions/gradle-9.5.0-bin.zip
```

- [ ] **Paso 2: Escribir `gradle/libs.versions.toml`**

```toml
[versions]
agp = "9.3.1"
kotlin = "2.2.10"
composeBom = "2026.06.01"
retrofit = "3.0.0"
okhttp = "5.4.0"
serialization = "1.11.0"
coroutines = "1.11.0"
datastore = "1.2.1"
navigation = "2.9.8"
lifecycle = "2.10.0"  # 2.11.0 exige compileSdk 37, igual que coreKtx 1.19.0
activity = "1.13.0"
coreKtx = "1.18.0"   # 1.19.0 exige compileSdk 37; ver la nota de abajo
junit = "4.13.2"

[libraries]
compose-bom = { group = "androidx.compose", name = "compose-bom", version.ref = "composeBom" }
compose-ui = { group = "androidx.compose.ui", name = "ui" }
compose-ui-tooling = { group = "androidx.compose.ui", name = "ui-tooling" }
compose-ui-tooling-preview = { group = "androidx.compose.ui", name = "ui-tooling-preview" }
compose-material3 = { group = "androidx.compose.material3", name = "material3" }
androidx-activity-compose = { group = "androidx.activity", name = "activity-compose", version.ref = "activity" }
androidx-core-ktx = { group = "androidx.core", name = "core-ktx", version.ref = "coreKtx" }
androidx-lifecycle-viewmodel-compose = { group = "androidx.lifecycle", name = "lifecycle-viewmodel-compose", version.ref = "lifecycle" }
androidx-lifecycle-runtime-compose = { group = "androidx.lifecycle", name = "lifecycle-runtime-compose", version.ref = "lifecycle" }
androidx-navigation-compose = { group = "androidx.navigation", name = "navigation-compose", version.ref = "navigation" }
androidx-datastore-preferences = { group = "androidx.datastore", name = "datastore-preferences", version.ref = "datastore" }
retrofit = { group = "com.squareup.retrofit2", name = "retrofit", version.ref = "retrofit" }
retrofit-serialization = { group = "com.squareup.retrofit2", name = "converter-kotlinx-serialization", version.ref = "retrofit" }
okhttp = { group = "com.squareup.okhttp3", name = "okhttp", version.ref = "okhttp" }
kotlinx-serialization-json = { group = "org.jetbrains.kotlinx", name = "kotlinx-serialization-json", version.ref = "serialization" }
kotlinx-coroutines-android = { group = "org.jetbrains.kotlinx", name = "kotlinx-coroutines-android", version.ref = "coroutines" }
kotlinx-coroutines-test = { group = "org.jetbrains.kotlinx", name = "kotlinx-coroutines-test", version.ref = "coroutines" }
junit = { group = "junit", name = "junit", version.ref = "junit" }

[plugins]
android-application = { id = "com.android.application", version.ref = "agp" }
android-library = { id = "com.android.library", version.ref = "agp" }
kotlin-compose = { id = "org.jetbrains.kotlin.plugin.compose", version.ref = "kotlin" }
kotlin-serialization = { id = "org.jetbrains.kotlin.plugin.serialization", version.ref = "kotlin" }
```

- [ ] **Paso 3: Escribir `settings.gradle.kts`**

```kotlin
pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositories {
        google()
        mavenCentral()
    }
}

rootProject.name = "srank"

include(":app")
include(":core:ui")
include(":core:system")
include(":data:api")
include(":data:session")
include(":feature:auth")
```

- [ ] **Paso 4: Escribir `build.gradle.kts` (raíz) y `gradle.properties`**

`build.gradle.kts`:

```kotlin
plugins {
    alias(libs.plugins.android.application) apply false
    alias(libs.plugins.android.library) apply false
    alias(libs.plugins.kotlin.compose) apply false
    alias(libs.plugins.kotlin.serialization) apply false
}
```

`gradle.properties`:

```properties
org.gradle.jvmargs=-Xmx4096m -Dfile.encoding=UTF-8
org.gradle.parallel=true
org.gradle.caching=true
android.useAndroidX=true
android.nonTransitiveRClass=true
```

- [ ] **Paso 5: Escribir `app/build.gradle.kts`**

Sin dependencias de otros módulos todavía; se van añadiendo en su tarea.

```kotlin
plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.compose)
}

android {
    namespace = "es.israelzamora.srank"
    compileSdk { version = release(36) }

    defaultConfig {
        applicationId = "es.israelzamora.srank"
        minSdk { version = release(26) }
        targetSdk { version = release(36) }
        versionCode = 1
        versionName = "1.1"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
        }
    }

    buildFeatures { compose = true }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    implementation(platform(libs.compose.bom))
    implementation(libs.compose.ui)
    implementation(libs.compose.material3)
    implementation(libs.compose.ui.tooling.preview)
    debugImplementation(libs.compose.ui.tooling)
    implementation(libs.androidx.activity.compose)
    implementation(libs.androidx.core.ktx)

    testImplementation(libs.junit)
}
```

> `isMinifyEnabled = false` en release: es una app personal que se instala a mano y R8 solo
> añadiría reglas que mantener. `ponytail: sin ofuscar. El techo es el tamaño del APK; si
> algún día va a Play Store, se activa R8 y se escriben las reglas de kotlinx.serialization.`

- [ ] **Paso 6: Escribir el manifiesto**

`app/src/main/AndroidManifest.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

    <uses-permission android:name="android.permission.INTERNET" />

    <application
        android:allowBackup="false"
        android:label="S-RANK"
        android:supportsRtl="false"
        android:theme="@android:style/Theme.Material.NoActionBar">

        <activity
            android:name=".MainActivity"
            android:exported="true"
            android:windowSoftInputMode="adjustResize">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
```

> `allowBackup="false"`: la copia automática de Android se llevaría el token de sesión a
> Google Drive. La sesión se recupera entrando otra vez, que cuesta menos que ese riesgo.

- [ ] **Paso 7: Escribir el test que exige que la app tenga nombre**

`app/src/test/java/es/israelzamora/srank/ArranqueTest.kt`:

```kotlin
package es.israelzamora.srank

import org.junit.Assert.assertEquals
import org.junit.Test

class ArranqueTest {
    @Test
    fun el_nombre_de_la_app_es_el_de_la_marca() {
        assertEquals("S-RANK", NOMBRE_APP)
    }
}
```

- [ ] **Paso 8: Ejecutar el test y verlo fallar**

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
./gradlew :app:testDebugUnitTest
```

Esperado: **FALLA** al compilar, con `Unresolved reference: NOMBRE_APP`.

- [ ] **Paso 9: Escribir `MainActivity.kt`**

`app/src/main/java/es/israelzamora/srank/MainActivity.kt`:

```kotlin
package es.israelzamora.srank

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable

const val NOMBRE_APP = "S-RANK"

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { Arranque() }
    }
}

@Composable
private fun Arranque() {
    Text(NOMBRE_APP)
}
```

- [ ] **Paso 10: Ejecutar el test y la compilación**

```bash
./gradlew :app:testDebugUnitTest
./gradlew assembleDebug
```

Esperado: los dos en **BUILD SUCCESSFUL**, y el APK en
`app/build/outputs/apk/debug/app-debug.apk`.

- [ ] **Paso 11: Commit**

```bash
git add settings.gradle.kts build.gradle.kts gradle.properties gradle/ gradlew gradlew.bat app/
git commit -m "$(cat <<'EOF'
feat(android): el esqueleto de Gradle, que compila y ejecuta un test

Seis módulos declarados y solo app implementado, para que la primera
compilación falle por lo suyo y no por seis sitios a la vez.

AGP 9.3.1 con el DSL nuevo: compileSdk y minSdk son bloques, no
asignaciones, y no se aplica el plugin de Kotlin porque AGP lo trae
dentro. Comprobado con un proyecto de prueba antes de escribirlo.

minSdk 26 para tener java.time sin desugaring.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

⚠️ **Comprueba antes de commitear que no se cuela `local.properties`:**

```bash
git status --short | grep -i "local.properties" && echo "NO LO COMMITEES" || echo "limpio"
```

---

## Tarea 2 · `core/ui` — los once tokens, la tipografía y el tema

**Ficheros:**
- Crear: `core/ui/build.gradle.kts`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/theme/Color.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/theme/Type.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/theme/Theme.kt`
- Crear: `core/ui/src/main/res/font/jetbrains_mono_regular.ttf` y `..._bold.ttf`
- Test: `core/ui/src/test/java/es/israelzamora/srank/ui/theme/ContrasteTest.kt`

**Interfaces:**
- Produce: `SRank.color` (`SRankColors`), `SRank.texto` (`SRankTypography`), `SRankTheme`,
  y `contraste(a: Int, b: Int): Double`. Todo lo demás los usa.

---

- [ ] **Paso 1: Crear el módulo y meter la fuente**

`core/ui/build.gradle.kts`:

```kotlin
plugins {
    alias(libs.plugins.android.library)
    alias(libs.plugins.kotlin.compose)
}

android {
    namespace = "es.israelzamora.srank.ui"
    compileSdk { version = release(36) }
    defaultConfig { minSdk { version = release(26) } }
    buildFeatures { compose = true }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    api(platform(libs.compose.bom))
    api(libs.compose.ui)
    api(libs.compose.material3)
    api(libs.compose.ui.tooling.preview)
    debugImplementation(libs.compose.ui.tooling)

    testImplementation(libs.junit)
}
```

> `api` y no `implementation`: quien dependa de `core/ui` va a escribir `Composable`, así
> que necesita Compose en su classpath. Repetirlo en cinco módulos sería peor.

La fuente ya está descargada en `/tmp/jbmono`. Cópiala con el nombre que exige Android
(minúsculas y guiones bajos):

```bash
mkdir -p core/ui/src/main/res/font core/ui/src/main/assets/font
cp /tmp/jbmono/fonts/ttf/JetBrainsMono-Regular.ttf core/ui/src/main/res/font/jetbrains_mono_regular.ttf
cp /tmp/jbmono/fonts/ttf/JetBrainsMono-Bold.ttf    core/ui/src/main/res/font/jetbrains_mono_bold.ttf
cp /tmp/jbmono/OFL.txt                             core/ui/src/main/assets/font/LICENCIA-JetBrainsMono.txt
ls -la core/ui/src/main/res/font/ core/ui/src/main/assets/font/
```

Si `/tmp/jbmono` ya no existe:

```bash
cd /tmp && curl -sL -o jbmono.zip \
  "https://github.com/JetBrains/JetBrainsMono/releases/download/v2.304/JetBrainsMono-2.304.zip" \
  && unzip -q -o jbmono.zip -d jbmono
```

> La licencia viaja empaquetada a propósito: JetBrains Mono es SIL OFL y la cláusula 2 pide
> que la licencia acompañe a la fuente redistribuida.
>
> Va en `assets/font/` y no en `res/font/`, aunque quede menos al lado del TTF: aapt2
> rechaza en `res/font/` cualquier fichero que no acabe en `.ttf`, `.ttc`, `.otf` o `.xml`,
> y el módulo no compila. `res/raw/` tampoco vale sin renombrar, porque exige minúsculas y
> guiones bajos. Los assets se empaquetan igual en el AAR y en el APK.

- [ ] **Paso 2: Escribir el test de contraste, que es el que protege los colores**

Este es el test que hace que cambiar un hexadecimal a ojo salte. Los valores esperados
están medidos, no copiados.

`core/ui/src/test/java/es/israelzamora/srank/ui/theme/ContrasteTest.kt`:

```kotlin
package es.israelzamora.srank.ui.theme

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * WCAG 2.1: 1.4.3 pide 4,5:1 para texto normal. El spec §7 lo hereda.
 *
 * Este test existe porque un color se cambia en un segundo y el daño no se ve
 * hasta que alguien con poca vista abre la app.
 */
class ContrasteTest {

    private val negro = 0x000000

    @Test
    fun los_colores_que_llevan_informacion_pasan_de_45() {
        val exigidos = mapOf(
            "texto" to 0xe4e4e7,
            "ambar" to 0xf59e0b,
            "verde" to 0x4ade80,
            "azul" to 0x60a5fa,
            "rojo" to 0xf87171,
            "cian" to 0x22d3ee,
            "morado" to 0xa78bfa,
        )
        exigidos.forEach { (nombre, color) ->
            val ratio = contraste(color, negro)
            assertTrue(
                "$nombre da %.2f:1 sobre negro y hace falta 4,5:1".format(ratio),
                ratio >= 4.5,
            )
        }
    }

    @Test
    fun apagado_no_llega_a_45_y_por_eso_no_puede_llevar_datos() {
        // Si alguien "arregla" este gris subiéndolo, que se entere aquí y
        // relea §5.3 antes de repartir apagado por las pantallas.
        assertEquals(2.72, contraste(0x52525b, negro), 0.01)
    }

    @Test
    fun lineas_solo_sirve_de_separador_decorativo() {
        // 1,28:1. Un borde de control necesita 3:1 (WCAG 1.4.11), así que
        // los campos y botones usan apagado, no lineas. Ver §5.5.
        assertEquals(1.28, contraste(0x1f1f23, negro), 0.01)
    }

    @Test
    fun el_color_de_rescate_del_borde_si_pasaria_311() {
        // Si en el móvil no se ve dónde se escribe, este es el recambio.
        assertTrue(contraste(0x6b7280, negro) >= 3.0)
    }
}
```

- [ ] **Paso 3: Ejecutar el test y verlo fallar**

```bash
./gradlew :core:ui:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: contraste`.

- [ ] **Paso 4: Escribir `Color.kt`**

`core/ui/src/main/java/es/israelzamora/srank/ui/theme/Color.kt`:

```kotlin
package es.israelzamora.srank.ui.theme

import androidx.compose.runtime.Immutable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color
import kotlin.math.pow

/**
 * Los once colores del spec §7. Se llaman por su nombre en castellano porque
 * ColorScheme de Material 3 no tiene hueco para «apagado» ni para «el cian del
 * Sistema», y escribir `tertiary` queriendo decir «cian» es justo cómo el cian
 * acabaría escapándose a la interfaz normal sin que nadie lo note.
 */
@Immutable
data class SRankColors(
    val fondo: Color = Color(0xFF000000),
    val superficie: Color = Color(0xFF0D0D10),
    val lineas: Color = Color(0xFF1F1F23),
    val texto: Color = Color(0xFFE4E4E7),
    val apagado: Color = Color(0xFF52525B),
    val ambar: Color = Color(0xFFF59E0B),   // marca, acción, XP
    val verde: Color = Color(0xFF4ADE80),   // completado
    val azul: Color = Color(0xFF60A5FA),    // información y navegación
    // ponytail: los tres siguientes solo los toca el momento de recompensa.
    // Si aparecen en una pantalla normal, el premio deja de significar nada.
    // El techo es la disciplina: nadie lo comprueba salvo la revisión.
    val rojo: Color = Color(0xFFF87171),    // récord, alerta
    val cian: Color = Color(0xFF22D3EE),    // EXCLUSIVO de las ventanas del Sistema
    val morado: Color = Color(0xFFA78BFA),  // rareza épica
)

val LocalSRankColors = staticCompositionLocalOf { SRankColors() }

/**
 * Luminancia relativa según WCAG 2.1. Se usa desde el test: es la única forma
 * de que cambiar un hexadecimal a ojo salte antes de llegar al móvil.
 */
private fun luminancia(rgb: Int): Double {
    val canales = listOf(16, 8, 0).map { ((rgb shr it) and 0xFF) / 255.0 }
    val lineal = canales.map { c ->
        if (c <= 0.03928) c / 12.92 else ((c + 0.055) / 1.055).pow(2.4)
    }
    return 0.2126 * lineal[0] + 0.7152 * lineal[1] + 0.0722 * lineal[2]
}

/** Razón de contraste entre dos colores, de 1:1 a 21:1. */
fun contraste(a: Int, b: Int): Double {
    val (alta, baja) = listOf(luminancia(a), luminancia(b)).sorted().reversed()
    return (alta + 0.05) / (baja + 0.05)
}
```

- [ ] **Paso 5: Ejecutar el test y verlo pasar**

```bash
./gradlew :core:ui:testDebugUnitTest
```

Esperado: **PASA**, los cuatro.

- [ ] **Paso 6: Escribir `Type.kt`**

`core/ui/src/main/java/es/israelzamora/srank/ui/theme/Type.kt`:

```kotlin
package es.israelzamora.srank.ui.theme

import androidx.compose.runtime.Immutable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.Font
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp
import es.israelzamora.srank.ui.R

/**
 * JetBrains Mono va empaquetada. Nunca la monoespaciada del sistema: cada
 * fabricante trae una distinta y la estética se rompe fuera de tu móvil.
 */
val JetBrainsMono = FontFamily(
    Font(R.font.jetbrains_mono_regular, FontWeight.Normal),
    Font(R.font.jetbrains_mono_bold, FontWeight.Bold),
)

/**
 * Los cinco tamaños del spec §7. Todos en sp: la app respeta el tamaño de
 * fuente del sistema, que llega a 2x.
 */
@Immutable
data class SRankTypography(
    val titulo: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Bold, fontSize = 20.sp,
    ),
    val seccion: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Bold, fontSize = 16.sp,
    ),
    val cuerpo: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Normal, fontSize = 13.sp,
    ),
    val nota: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Normal, fontSize = 11.5.sp,
    ),
    // Versales fingidas con mayúsculas y letterSpacing: Compose no tiene
    // versales de verdad y empaquetar una segunda fuente para eso no compensa.
    val etiqueta: TextStyle = TextStyle(
        fontFamily = JetBrainsMono, fontWeight = FontWeight.Bold, fontSize = 10.5.sp,
        letterSpacing = 1.sp,
    ),
)

val LocalSRankTypography = staticCompositionLocalOf { SRankTypography() }
```

- [ ] **Paso 7: Escribir `Theme.kt`**

`core/ui/src/main/java/es/israelzamora/srank/ui/theme/Theme.kt`:

```kotlin
package es.israelzamora.srank.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider

/**
 * Material 3 aporta lo aburrido y difícil: campos de texto, foco, teclado,
 * selección, TabRow, Scaffold. Se usa para eso y solo para eso.
 *
 * Su ColorScheme se rellena con los mismos valores para que ningún componente
 * de M3 aporte color propio por debajo. El vocabulario de color de la app es
 * SRank.color, no MaterialTheme.colorScheme.
 */
@Composable
fun SRankTheme(content: @Composable () -> Unit) {
    val colores = SRankColors()
    val tipografia = SRankTypography()

    val esquemaM3 = darkColorScheme(
        primary = colores.ambar,
        onPrimary = colores.fondo,
        secondary = colores.azul,
        onSecondary = colores.fondo,
        background = colores.fondo,
        onBackground = colores.texto,
        surface = colores.superficie,
        onSurface = colores.texto,
        surfaceVariant = colores.superficie,
        onSurfaceVariant = colores.apagado,
        outline = colores.apagado,
        error = colores.rojo,
        onError = colores.fondo,
    )

    CompositionLocalProvider(
        LocalSRankColors provides colores,
        LocalSRankTypography provides tipografia,
    ) {
        MaterialTheme(colorScheme = esquemaM3, content = content)
    }
}

/** Punto de entrada corto: `SRank.color.ambar`, `SRank.texto.cuerpo`. */
object SRank {
    val color: SRankColors
        @Composable get() = LocalSRankColors.current
    val texto: SRankTypography
        @Composable get() = LocalSRankTypography.current
}
```

- [ ] **Paso 8: Compilar el módulo**

```bash
./gradlew :core:ui:assembleDebug :core:ui:testDebugUnitTest
```

Esperado: **BUILD SUCCESSFUL**.

- [ ] **Paso 9: Commit**

```bash
git add core/ui/
git commit -m "$(cat <<'EOF'
feat(ui): los once tokens, la tipografía y el tema

Tokens propios en castellano en vez del vocabulario de Material 3: su
ColorScheme no tiene hueco para «apagado» ni para «el cian del Sistema»,
y escribir tertiary queriendo decir cian es cómo el cian acabaría
escapándose a la interfaz normal sin que nadie lo notara.

El test de contraste es lo que protege esto de verdad. Los valores están
medidos, no copiados: apagado da 2,72:1 y lineas 1,28:1, y el test los
fija para que subirlos a ojo obligue a releer por qué están ahí.

JetBrains Mono empaquetada con su licencia OFL al lado.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 3 · `core/ui` — los componentes de texto

Los tres que llevan la regla de accesibilidad encima: si TalkBack lee la decoración, la
estética ha dejado de ser decoración.

**Ficheros:**
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/Comentario.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/FilaMision.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/CabeceraSeccion.kt`

**Interfaces:**
- Consume: `SRank.color`, `SRank.texto` de la tarea 2.
- Produce:
  - `Comentario(texto: String, dato: String? = null, modifier: Modifier = Modifier)`
  - `FilaMision(etiqueta: String, completada: Boolean, avance: String? = null, modifier: Modifier = Modifier)`
  - `CabeceraSeccion(titulo: String, desplegada: Boolean, alPulsar: () -> Unit, modifier: Modifier = Modifier, contador: String? = null, color: Color = SRank.color.ambar)`

---

- [ ] **Paso 1: Escribir `Comentario.kt`**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.layout.Row
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * El comentario `// …` del spec §5.3.
 *
 * Si lleva un dato, el color se parte: el marcador va en apagado (2,72:1, que
 * es decoración) y el dato en texto (16,55:1, que se lee). Sigue leyéndose como
 * comentario porque lo dicen el marcador y el tamaño, no el gris.
 *
 * Para TalkBack se anuncia solo el contenido: las dos barras son dibujo.
 */
@Composable
fun Comentario(
    texto: String,
    dato: String? = null,
    modifier: Modifier = Modifier,
) {
    val leido = if (dato == null) texto else "$texto $dato"

    Row(
        modifier.clearAndSetSemantics { text = AnnotatedString(leido) },
    ) {
        Text(
            text = "// ",
            style = SRank.texto.nota,
            color = SRank.color.apagado,
        )
        Text(
            text = texto,
            style = SRank.texto.nota,
            // Sin dato, el comentario entero es secundario y puede ir apagado.
            // Con dato, el texto de delante lo acompaña y también es secundario.
            color = SRank.color.apagado,
        )
        if (dato != null) {
            Text(
                text = " $dato",
                style = SRank.texto.nota,
                color = SRank.color.texto,
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaComentario() {
    SRankTheme {
        androidx.compose.foundation.layout.Column {
            Comentario("lunes, 11 de agosto")
            Comentario("de 8.000 pasos llevas", dato = "5.240")
        }
    }
}
```

- [ ] **Paso 2: Escribir `FilaMision.kt`**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Fila de misión: `[✓] Beber 2 litros de agua`, con el avance parcial debajo.
 *
 * `Text("[✓] Beber 2 litros")` lo lee TalkBack como «corchete, marca de
 * verificación, corchete». Por eso la fila entera va como un solo nodo: los
 * corchetes, la marca y el `//` son dibujo, no contenido. Un usuario ciego oye
 * «Beber 2 litros de agua, hecha».
 *
 * En 1.1 es de solo lectura, así que no es objetivo táctil y no necesita los
 * 48 dp. La fase 1.2 traerá las opcionales marcables a mano
 * (POST /api/system/quests/{key}/complete): entonces es añadir un onClick
 * nullable y el alto mínimo, no rediseñar.
 */
@Composable
fun FilaMision(
    etiqueta: String,
    completada: Boolean,
    avance: String? = null,
    modifier: Modifier = Modifier,
) {
    val estado = if (completada) "hecha" else "pendiente"

    Column(
        modifier
            .clearAndSetSemantics {
                text = AnnotatedString(if (avance == null) etiqueta else "$etiqueta, $avance")
                stateDescription = estado
            }
            .padding(vertical = 4.dp),
    ) {
        Row {
            Text(
                text = if (completada) "[✓] " else "[ ] ",
                style = SRank.texto.cuerpo,
                color = if (completada) SRank.color.verde else SRank.color.apagado,
            )
            Text(
                text = etiqueta,
                style = SRank.texto.cuerpo,
                // Dentro de una fila el color solo dice estado (spec §6).
                color = if (completada) SRank.color.verde else SRank.color.texto,
            )
        }
        if (avance != null) {
            Comentario(
                texto = "",
                dato = avance,
                modifier = Modifier.padding(PaddingValues(start = 32.dp)),
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaFilaMision() {
    SRankTheme {
        Column {
            FilaMision("Beber 2 litros de agua", completada = true)
            FilaMision("Entrenar", completada = false, avance = "1 de 3 esta semana")
            FilaMision("8.000 pasos", completada = false, avance = "5.240 de 8.000")
        }
    }
}
```

> ⚠️ `Comentario(texto = "", dato = avance)` deja el `//` seguido del dato. Es lo que pide
> §5.3: el dato en `texto`, el marcador en `apagado`.

- [ ] **Paso 3: Escribir `CabeceraSeccion.kt`**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.stateDescription
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * `▸ MISIONES DE HOY   [1 de 4] ▾`, plegable.
 *
 * El hueco del medio es `Spacer(weight)`, nunca espacios: con la fuente del
 * sistema al máximo, alinear con caracteres descuadra la línea.
 *
 * Los triángulos no se leen. TalkBack ya sabe anunciar plegado/desplegado, y
 * eso es lo que se le da en stateDescription.
 */
@Composable
fun CabeceraSeccion(
    titulo: String,
    desplegada: Boolean,
    alPulsar: () -> Unit,
    modifier: Modifier = Modifier,
    contador: String? = null,
    color: Color = SRank.color.ambar,
) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = modifier
            .clickable(onClick = alPulsar)
            .defaultMinSize(minHeight = 48.dp)
            .padding(vertical = 8.dp)
            .clearAndSetSemantics {
                text = AnnotatedString(
                    if (contador == null) titulo else "$titulo, $contador",
                )
                stateDescription = if (desplegada) "desplegado" else "plegado"
            },
    ) {
        Text(
            text = if (desplegada) "▾ " else "▸ ",
            style = SRank.texto.seccion,
            color = color,
        )
        Text(
            text = titulo.uppercase(),
            style = SRank.texto.seccion,
            color = color,
        )
        Spacer(Modifier.weight(1f))
        if (contador != null) {
            Text(
                text = "[$contador]",
                style = SRank.texto.nota,
                color = SRank.color.texto,
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaCabeceraSeccion() {
    SRankTheme {
        androidx.compose.foundation.layout.Column {
            CabeceraSeccion("misiones de hoy", desplegada = true, alPulsar = {}, contador = "1 de 4")
            CabeceraSeccion("nutrición", desplegada = false, alPulsar = {}, color = SRank.color.verde)
        }
    }
}
```

- [ ] **Paso 4: Compilar**

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
./gradlew :core:ui:assembleDebug
```

Esperado: **BUILD SUCCESSFUL**.

> No hay test unitario de estos tres a propósito. Un test de Robolectric comprobaría que
> escribí `stateDescription`, no que TalkBack lo lee bien, y eso último es lo único que
> importa. Se verifica en la tarea 13, con TalkBack encendido en un móvil de verdad.
> `ponytail: sin tests de interfaz. El techo es que una regresión de semántica no salta
> sola. Si algún día duele, Robolectric + compose-ui-test y se comprueba el árbol.`

- [ ] **Paso 5: Commit**

```bash
git add core/ui/
git commit -m "$(cat <<'EOF'
feat(ui): comentario, fila de misión y cabecera de sección

Los tres van con clearAndSetSemantics porque «[✓] Beber 2 litros» lo lee
TalkBack como «corchete, marca de verificación, corchete». La regla de
que la estética es puramente visual solo se cumple del todo si con un
lector de pantalla la decoración no existe.

El hueco de la cabecera es Spacer(weight), no espacios: con la fuente del
sistema al máximo, alinear con caracteres descuadra la línea.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 4 · `core/ui` — barra, botón, campo, insignia y ventana del Sistema

**Ficheros:**
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/BarraBloques.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/Controles.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/InsigniaRango.kt`
- Crear: `core/ui/src/main/java/es/israelzamora/srank/ui/componentes/VentanaSistema.kt`
- Test: `core/ui/src/test/java/es/israelzamora/srank/ui/componentes/BarraBloquesTest.kt`

**Interfaces:**
- Produce:
  - `bloquesEncendidos(progreso: Int, total: Int, bloques: Int = 10): Int`
  - `BarraBloques(progreso: Int, total: Int, color: Color = SRank.color.ambar, modifier: Modifier = Modifier)`
  - `BotonSRank(texto: String, alPulsar: () -> Unit, modifier: Modifier = Modifier, activo: Boolean = true, cargando: Boolean = false)`
  - `CampoSRank(valor: String, alCambiar: (String) -> Unit, etiqueta: String, modifier: Modifier = Modifier, error: String? = null, esContrasena: Boolean = false, tecladoCorreo: Boolean = false, tecladoNumerico: Boolean = false)`
  - `InsigniaRango(rango: String, modifier: Modifier = Modifier)`
  - `VentanaSistema(titulo: String, lineas: List<String>, modifier: Modifier = Modifier)`

> **Nota de alcance.** `CampoSRank` no está en la tabla de siete componentes del spec §5,
> pero §5.5 ya legisla el borde de «campos y botones» y los tres formularios de la fase lo
> necesitan. Se añade como octavo. No cambia ninguna decisión de color.

---

- [ ] **Paso 1: Escribir el test de la barra**

Es la única de estas piezas con una rama de verdad, así que es la única con test.

`core/ui/src/test/java/es/israelzamora/srank/ui/componentes/BarraBloquesTest.kt`:

```kotlin
package es.israelzamora.srank.ui.componentes

import org.junit.Assert.assertEquals
import org.junit.Test

class BarraBloquesTest {

    @Test
    fun vacia_cuando_no_hay_progreso() {
        assertEquals(0, bloquesEncendidos(progreso = 0, total = 400))
    }

    @Test
    fun avanza_a_saltos_del_diez_por_ciento() {
        assertEquals(6, bloquesEncendidos(progreso = 240, total = 400))
    }

    @Test
    fun no_se_llena_hasta_haber_llegado_de_verdad() {
        // 399 de 400 es 99,75%. Enseñar la barra llena sería mentir sobre
        // que falta XP para subir de nivel.
        assertEquals(9, bloquesEncendidos(progreso = 399, total = 400))
        assertEquals(10, bloquesEncendidos(progreso = 400, total = 400))
    }

    @Test
    fun nunca_se_pasa_ni_se_queda_corta() {
        assertEquals(10, bloquesEncendidos(progreso = 900, total = 400))
        assertEquals(0, bloquesEncendidos(progreso = -5, total = 400))
    }

    @Test
    fun un_total_de_cero_no_revienta() {
        // El servidor manda target 0 en alguna misión opcional. Dividir aquí
        // sería una excepción en mitad de la pantalla de hoy.
        assertEquals(0, bloquesEncendidos(progreso = 10, total = 0))
    }
}
```

- [ ] **Paso 2: Ejecutar el test y verlo fallar**

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
./gradlew :core:ui:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: bloquesEncendidos`.

- [ ] **Paso 3: Escribir `BarraBloques.kt`**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.tooling.preview.Preview
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Cuántos de los diez bloques están encendidos.
 *
 * Trunca en vez de redondear: 399 de 400 son nueve bloques, no diez. Enseñar
 * la barra llena cuando todavía falta XP sería mentir en la única pantalla
 * donde el número importa.
 */
fun bloquesEncendidos(progreso: Int, total: Int, bloques: Int = 10): Int {
    if (total <= 0 || progreso <= 0) return 0
    val encendidos = (progreso.toLong() * bloques / total).toInt()
    return encendidos.coerceIn(0, bloques)
}

/**
 * `[▓▓▓▓▓▓░░░░]` en línea propia, del spec §5.4.
 *
 * En línea propia y no junto a los números: juntos son unos 24 caracteres, que
 * con la fuente del sistema al máximo no caben en un móvil de 320 dp. La barra
 * sola ocupa unos 187 dp de los 288 disponibles.
 *
 * Los glifos U+2593 y U+2591 están comprobados en JetBrains Mono 2.304.
 *
 * Para TalkBack no se leen veinticuatro caracteres de dibujo: se anuncia el
 * porcentaje, que es lo que la barra significa.
 */
@Composable
fun BarraBloques(
    progreso: Int,
    total: Int,
    modifier: Modifier = Modifier,
    color: Color = SRank.color.ambar,
) {
    val bloques = 10
    val llenos = bloquesEncendidos(progreso, total, bloques)
    val porcentaje = if (total <= 0) 0 else (progreso.toLong() * 100 / total).toInt().coerceIn(0, 100)

    val pintada = buildAnnotatedString {
        withStyle(SpanStyle(color = SRank.color.apagado)) { append("[") }
        withStyle(SpanStyle(color = color)) { append("▓".repeat(llenos)) }
        withStyle(SpanStyle(color = SRank.color.lineas)) { append("░".repeat(bloques - llenos)) }
        withStyle(SpanStyle(color = SRank.color.apagado)) { append("]") }
    }

    Text(
        text = pintada,
        style = SRank.texto.cuerpo,
        modifier = modifier.clearAndSetSemantics {
            text = AnnotatedString("$porcentaje por ciento")
        },
    )
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaBarraBloques() {
    SRankTheme {
        androidx.compose.foundation.layout.Column {
            BarraBloques(progreso = 240, total = 400)
            BarraBloques(progreso = 1250, total = 2000, color = SRank.color.verde)
        }
    }
}
```

> Los bloques vacíos van en `lineas` (1,28:1) a propósito: son el hueco, no información.
> El dato lo llevan la parte llena y la línea de números de debajo, que va en `texto`.

- [ ] **Paso 4: Ejecutar el test y verlo pasar**

```bash
./gradlew :core:ui:testDebugUnitTest
```

Esperado: **PASA**, los cinco.

- [ ] **Paso 5: Escribir `Controles.kt` (botón y campo)**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsFocusedAsState
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.ButtonDefaults
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Botón con borde visible y 48 dp de alto mínimo.
 *
 * ponytail: el borde va en apagado (2,72:1) y no en lineas (1,28:1), que no se
 * percibe. Sigue por debajo de los 3:1 que pide WCAG 1.4.11 para el borde de un
 * control; el techo es ese. El botón se identifica por su texto en texto
 * (16,55:1), que siempre está visible. Si en el móvil cuesta verlo, el recambio
 * es un token nuevo #6b7280, que da 4,34:1.
 */
@Composable
fun BotonSRank(
    texto: String,
    alPulsar: () -> Unit,
    modifier: Modifier = Modifier,
    activo: Boolean = true,
    cargando: Boolean = false,
) {
    OutlinedButton(
        onClick = alPulsar,
        enabled = activo && !cargando,
        modifier = modifier.defaultMinSize(minHeight = 48.dp),
        border = BorderStroke(1.dp, if (activo) SRank.color.ambar else SRank.color.apagado),
        colors = ButtonDefaults.outlinedButtonColors(
            contentColor = SRank.color.ambar,
            disabledContentColor = SRank.color.apagado,
        ),
    ) {
        Text(
            text = if (cargando) "[ ... ]" else "[ ${texto.uppercase()} ]",
            style = SRank.texto.cuerpo,
        )
    }
}

/**
 * Campo de texto. El borde en reposo va en apagado y al enfocar en ámbar, que
 * es lo que dice dónde estás escribiendo. Mismo techo que el botón.
 *
 * El mensaje de error va debajo, en rojo, y lo lee TalkBack porque es texto de
 * verdad y no un color.
 */
@Composable
fun CampoSRank(
    valor: String,
    alCambiar: (String) -> Unit,
    etiqueta: String,
    modifier: Modifier = Modifier,
    error: String? = null,
    esContrasena: Boolean = false,
    tecladoCorreo: Boolean = false,
    tecladoNumerico: Boolean = false,
) {
    val interacciones = remember { MutableInteractionSource() }
    val enfocado by interacciones.collectIsFocusedAsState()

    val tipoTeclado = when {
        tecladoCorreo -> KeyboardType.Email
        tecladoNumerico -> KeyboardType.NumberPassword
        esContrasena -> KeyboardType.Password
        else -> KeyboardType.Text
    }

    Column(modifier) {
        OutlinedTextField(
            value = valor,
            onValueChange = alCambiar,
            label = { Text(etiqueta, style = SRank.texto.nota) },
            singleLine = true,
            isError = error != null,
            interactionSource = interacciones,
            visualTransformation =
                if (esContrasena) PasswordVisualTransformation() else androidx.compose.ui.text.input.VisualTransformation.None,
            keyboardOptions = KeyboardOptions(keyboardType = tipoTeclado),
            textStyle = SRank.texto.cuerpo,
            modifier = Modifier.fillMaxWidth().defaultMinSize(minHeight = 48.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = SRank.color.ambar,
                unfocusedBorderColor = SRank.color.apagado,
                errorBorderColor = SRank.color.rojo,
                focusedTextColor = SRank.color.texto,
                unfocusedTextColor = SRank.color.texto,
                focusedLabelColor = if (enfocado) SRank.color.ambar else SRank.color.apagado,
                unfocusedLabelColor = SRank.color.apagado,
                cursorColor = SRank.color.ambar,
                focusedContainerColor = SRank.color.superficie,
                unfocusedContainerColor = SRank.color.superficie,
            ),
        )
        if (error != null) {
            Text(
                text = error,
                style = SRank.texto.nota,
                color = SRank.color.rojo,
                modifier = Modifier.padding(start = 4.dp, top = 4.dp),
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaControles() {
    SRankTheme {
        Column(Modifier.padding(16.dp)) {
            CampoSRank("hola@ejemplo.es", {}, "correo", tecladoCorreo = true)
            CampoSRank("", {}, "contraseña", esContrasena = true, error = "Credenciales incorrectas.")
            BotonSRank("entrar", {})
            BotonSRank("entrar", {}, cargando = true)
        }
    }
}
```

- [ ] **Paso 6: Escribir `InsigniaRango.kt`**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.text
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * La letra del rango con su marco: E · D · C · B · A · S.
 *
 * El color sube con el rango, pero **sin tocar cian, rojo ni morado**: esos
 * tres son del momento de recompensa y gastarlos en una insignia permanente
 * desactivaría el premio. S se queda en ámbar, que ya es el color de marca.
 */
@Composable
fun InsigniaRango(rango: String, modifier: Modifier = Modifier) {
    val color: Color = when (rango.uppercase()) {
        "E" -> SRank.color.apagado
        "D" -> SRank.color.texto
        "C" -> SRank.color.azul
        "B" -> SRank.color.verde
        else -> SRank.color.ambar   // A y S
    }

    Row(
        modifier
            .border(1.dp, color)
            .padding(horizontal = 8.dp, vertical = 2.dp)
            .clearAndSetSemantics { text = AnnotatedString("rango ${rango.uppercase()}") },
    ) {
        Text(
            text = rango.uppercase(),
            style = SRank.texto.seccion,
            color = color,
        )
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaInsignia() {
    SRankTheme {
        Row {
            listOf("E", "D", "C", "B", "A", "S").forEach {
                InsigniaRango(it, Modifier.padding(4.dp))
            }
        }
    }
}
```

> El rango E va en `apagado`, que es el único sitio donde ese gris lleva un dato. Se salva
> porque la insignia nunca va sola: al lado está siempre «NIVEL n» en `texto`, y el rango
> se anuncia entero por TalkBack. Si en el móvil se lee mal, sube a `texto`.

- [ ] **Paso 7: Escribir `VentanaSistema.kt`**

```kotlin
package es.israelzamora.srank.ui.componentes

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * La ventana del Sistema: el momento de recompensa.
 *
 * **El cian de aquí no aparece en ninguna otra pantalla.** Es la única regla de
 * color sin excepciones: en cuanto el cian sale en la interfaz normal deja de
 * significar «premio» y esto se queda en un recuadro más.
 *
 * En la fase 1.1 nada la dispara todavía y solo se ve en este @Preview. Se
 * escribe ahora porque define la identidad de la app: verla antes de cerrar los
 * tokens evita descubrir en 1.2 que obliga a retocarlos.
 */
@Composable
fun VentanaSistema(
    titulo: String,
    lineas: List<String>,
    modifier: Modifier = Modifier,
) {
    Column(
        modifier
            .fillMaxWidth()
            .background(SRank.color.superficie)
            .border(1.dp, SRank.color.cian)
            .padding(16.dp),
    ) {
        Text(
            text = "◆ $titulo".uppercase(),
            style = SRank.texto.seccion,
            color = SRank.color.cian,
        )
        Text(
            text = "─".repeat(24),
            style = SRank.texto.nota,
            color = SRank.color.cian,
        )
        lineas.forEach {
            Text(
                text = it,
                style = SRank.texto.cuerpo,
                color = SRank.color.texto,
                modifier = Modifier.padding(top = 4.dp),
            )
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaVentanaSistema() {
    SRankTheme {
        Column(Modifier.padding(16.dp)) {
            VentanaSistema(
                titulo = "has subido de nivel",
                lineas = listOf("Nivel 4 → 5", "+22 XP", "Logro: Hidratado"),
            )
        }
    }
}
```

> ⚠️ `"─".repeat(24)` es la única alineación por caracteres de todo el proyecto, y está
> dentro de un recuadro que se desborda con la fuente al máximo.
> `ponytail: separador de ancho fijo. El techo es que con fuente grande se sale del marco.
> Si pasa, se cambia por un Divider de 1 dp, que es lo correcto y no hace falta hasta
> que la ventana se use de verdad en la fase 1.2.`

- [ ] **Paso 8: Compilar y pasar los tests**

```bash
./gradlew :core:ui:assembleDebug :core:ui:testDebugUnitTest
```

Esperado: **BUILD SUCCESSFUL** y los nueve tests de `core/ui` en verde.

- [ ] **Paso 9: Commit**

```bash
git add core/ui/
git commit -m "$(cat <<'EOF'
feat(ui): barra de bloques, botón, campo, insignia y ventana del Sistema

La barra trunca en vez de redondear: 399 de 400 son nueve bloques. Con
diez estaría diciendo que ya has subido de nivel cuando todavía falta XP,
y es la única pantalla donde ese número importa.

El borde de botón y campo va en apagado, no en lineas: 1,28:1 no se
percibe. Sigue por debajo de los 3:1 de WCAG 1.4.11 y queda anotado con
su recambio, que es un token nuevo #6b7280 con 4,34:1.

CampoSRank no estaba en la lista de siete del spec, pero §5.5 ya legisla
su borde y los tres formularios de la fase lo necesitan.

La ventana del Sistema se escribe aunque nada la dispare en 1.1: define
la identidad de la app y verla ahora evita retocar tokens ya cerrados.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 5 · `data/api` — modelos, errores y fechas

Nada de red todavía: solo las piezas puras, que son las que se pueden probar de verdad.

**Ficheros:**
- Crear: `data/api/build.gradle.kts`
- Crear: `data/api/src/main/java/es/israelzamora/srank/api/Dtos.kt`
- Crear: `data/api/src/main/java/es/israelzamora/srank/api/ErrorApi.kt`
- Crear: `data/api/src/main/java/es/israelzamora/srank/api/Fechas.kt`
- Test: `data/api/src/test/java/es/israelzamora/srank/api/TraductorErroresTest.kt`
- Test: `data/api/src/test/java/es/israelzamora/srank/api/FechasTest.kt`
- Test: `data/api/src/test/java/es/israelzamora/srank/api/DtosTest.kt`

**Interfaces:**
- Produce:
  - `ErrorApi` (sellada, hereda de `Exception`) con `SinRed`, `SesionCaducada`,
    `Validacion(porCampo, mensaje)`, `DemasiadosIntentos`, `Desconocido`
  - `traduceError(t: Throwable): ErrorApi`
  - `jsonSrank: Json`
  - `LoginRespuestaDto`, `MensajeDto`, `HoyDto`, `ProgresoDto`, `MisionDto`,
    `EstadisticasDto`, `UsuarioDto`, `SugerenciaDto`
  - `diaEnMadrid(instanteUtc: String): LocalDate`, `formateaDiaLargo(dia: LocalDate): String`

---

- [ ] **Paso 1: Crear el módulo**

`data/api/build.gradle.kts`:

```kotlin
plugins {
    alias(libs.plugins.android.library)
    alias(libs.plugins.kotlin.serialization)
}

android {
    namespace = "es.israelzamora.srank.api"
    compileSdk { version = release(36) }
    defaultConfig { minSdk { version = release(26) } }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    api(libs.kotlinx.serialization.json)
    api(libs.retrofit)
    implementation(libs.retrofit.serialization)
    implementation(libs.okhttp)
    implementation(libs.kotlinx.coroutines.android)

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
```

- [ ] **Paso 2: Escribir el test de los DTO**

El JSON de estos tests está copiado de las respuestas reales del servidor, no inventado.

`data/api/src/test/java/es/israelzamora/srank/api/DtosTest.kt`:

```kotlin
package es.israelzamora.srank.api

import org.junit.Assert.assertEquals
import org.junit.Test

class DtosTest {

    @Test
    fun deserializa_una_mision_tal_como_la_manda_el_servidor() {
        val crudo = """
            {"key":"water","label":"Beber 2 litros de agua","target":2000,
             "progress":1250,"xp_reward":10,"is_optional":false,"completed":false}
        """.trimIndent()

        val m = jsonSrank.decodeFromString<MisionDto>(crudo)

        assertEquals("water", m.key)
        assertEquals("Beber 2 litros de agua", m.label)
        assertEquals(2000, m.target)
        assertEquals(1250, m.progress)
        assertEquals(10, m.xpReward)
        assertEquals(false, m.isOptional)
        assertEquals(false, m.completed)
    }

    @Test
    fun un_campo_nuevo_del_servidor_no_tumba_la_app() {
        // El backend puede añadir claves sin publicar una versión nueva de la
        // app. Si eso reventase, cada cambio del servidor dejaría fuera a
        // quien no actualice.
        val conExtra = """
            {"key":"train","label":"Entrenar","target":1,"progress":0,
             "xp_reward":30,"is_optional":false,"completed":false,
             "icono_que_aun_no_existe":"pesa"}
        """.trimIndent()

        val m = jsonSrank.decodeFromString<MisionDto>(conExtra)

        assertEquals("train", m.key)
    }

    @Test
    fun deserializa_el_progreso_entero() {
        val crudo = """
            {"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
             "xp_for_next":400,"current_streak":12,"longest_streak":30,
             "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}}
        """.trimIndent()

        val p = jsonSrank.decodeFromString<ProgresoDto>(crudo)

        assertEquals(4, p.level)
        assertEquals("E", p.rank)
        assertEquals(240, p.xpIntoLevel)
        assertEquals(400, p.xpForNext)
        assertEquals(12, p.currentStreak)
        assertEquals(8, p.stats.consistency)
    }

    @Test
    fun deserializa_la_respuesta_de_login() {
        val crudo = """
            {"access_token":"42|abcdef","token_type":"Bearer",
             "user_name":"Israel","is_admin":false}
        """.trimIndent()

        val r = jsonSrank.decodeFromString<LoginRespuestaDto>(crudo)

        assertEquals("42|abcdef", r.accessToken)
        assertEquals("Israel", r.userName)
        assertEquals(false, r.isAdmin)
    }
}
```

- [ ] **Paso 3: Ejecutarlo y verlo fallar**

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
./gradlew :data:api:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: jsonSrank`.

- [ ] **Paso 4: Escribir `Dtos.kt`**

```kotlin
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
```

- [ ] **Paso 5: Ejecutar el test de DTO y verlo pasar**

```bash
./gradlew :data:api:testDebugUnitTest --tests "*DtosTest*"
```

Esperado: **PASA**, los cuatro.

- [ ] **Paso 6: Escribir el test del traductor de errores**

Una prueba por fila de la tabla del spec §8.1.

`data/api/src/test/java/es/israelzamora/srank/api/TraductorErroresTest.kt`:

```kotlin
package es.israelzamora.srank.api

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response
import java.io.IOException
import java.net.UnknownHostException

class TraductorErroresTest {

    private fun httpConCuerpo(codigo: Int, cuerpo: String): HttpException =
        HttpException(
            Response.error<Any>(
                codigo,
                cuerpo.toResponseBody("application/json".toMediaType()),
            ),
        )

    @Test
    fun sin_red_se_cuenta_en_castellano_y_sin_codigos() {
        val e = traduceError(UnknownHostException("s-rank.israelzamora.es"))

        assertEquals(ErrorApi.SinRed, e)
        assertEquals("No hay conexión. Comprueba el wifi o los datos.", e.mensaje)
    }

    @Test
    fun cualquier_fallo_de_entrada_salida_es_falta_de_red() {
        assertEquals(ErrorApi.SinRed, traduceError(IOException("socket cerrado")))
    }

    @Test
    fun el_401_es_sesion_caducada() {
        val e = traduceError(httpConCuerpo(401, """{"message":"Unauthenticated."}"""))

        assertEquals(ErrorApi.SesionCaducada, e)
    }

    @Test
    fun el_422_usa_el_mensaje_del_servidor_capitalizado() {
        // Los mensajes de lang/es empiezan en minúscula porque van debajo de
        // un campo. Al pintarlos sueltos hay que capitalizarlos.
        val cuerpo = """{"message":"El correo ya está en uso.",
            "errors":{"email":["el correo ya está en uso"]}}"""

        val e = traduceError(httpConCuerpo(422, cuerpo)) as ErrorApi.Validacion

        assertEquals("El correo ya está en uso", e.porCampo["email"])
        assertEquals("El correo ya está en uso", e.mensaje)
    }

    @Test
    fun el_422_del_login_llega_por_el_campo_email() {
        // Ojo: credenciales malas NO son un 401, son un 422 con este cuerpo.
        // Lo lanza ValidationException en AuthController::login.
        val cuerpo = """{"message":"Credenciales incorrectas.",
            "errors":{"email":["Credenciales incorrectas."]}}"""

        val e = traduceError(httpConCuerpo(422, cuerpo)) as ErrorApi.Validacion

        assertEquals("Credenciales incorrectas.", e.porCampo["email"])
    }

    @Test
    fun el_429_lo_traduce_la_app_porque_el_servidor_no_puede() {
        // El limitador de Laravel responde antes de entrar en la ruta, así que
        // no pasa por lang/es. Es el único texto que pone la app.
        val e = traduceError(httpConCuerpo(429, """{"message":"Too Many Attempts."}"""))

        assertEquals(ErrorApi.DemasiadosIntentos, e)
        assertEquals("Demasiados intentos. Espera un momento y vuelve a probar.", e.mensaje)
        assertTrue("no puede colarse el inglés del servidor", !e.mensaje.contains("Attempts"))
    }

    @Test
    fun un_500_no_ensena_el_numero() {
        val e = traduceError(httpConCuerpo(500, "<html>Server Error</html>"))

        assertEquals(ErrorApi.Desconocido, e)
        assertEquals("No hemos podido conectar. Inténtalo otra vez.", e.mensaje)
        assertTrue("ningún código HTTP en pantalla", !e.mensaje.contains("500"))
    }

    @Test
    fun un_422_con_el_cuerpo_roto_no_revienta() {
        // Si el servidor devolviera HTML por olvidar Accept: application/json,
        // la app tiene que decir algo en castellano, no propagar la excepción.
        val e = traduceError(httpConCuerpo(422, "<html>lo que sea</html>"))

        assertEquals(ErrorApi.Desconocido, e)
    }
}
```

- [ ] **Paso 7: Ejecutarlo y verlo fallar**

```bash
./gradlew :data:api:testDebugUnitTest --tests "*TraductorErroresTest*"
```

Esperado: **FALLA** con `Unresolved reference: traduceError`.

- [ ] **Paso 8: Escribir `ErrorApi.kt`**

```kotlin
package es.israelzamora.srank.api

import retrofit2.HttpException
import java.io.IOException

/**
 * Los errores tal como se cuentan en pantalla. Se traducen aquí, una sola vez,
 * y las pantallas enseñan el texto ya hecho.
 *
 * Ningún código HTTP sale de este fichero: «No hay conexión», no «Error 503».
 */
sealed class ErrorApi(val mensaje: String) : Exception(mensaje) {

    data object SinRed : ErrorApi("No hay conexión. Comprueba el wifi o los datos.")

    data object SesionCaducada : ErrorApi("Tu sesión ha caducado. Vuelve a entrar.")

    data object DemasiadosIntentos :
        ErrorApi("Demasiados intentos. Espera un momento y vuelve a probar.")

    data object Desconocido : ErrorApi("No hemos podido conectar. Inténtalo otra vez.")

    /** 422 de Laravel: el mensaje ya viene en español desde `lang/es`. */
    class Validacion(
        val porCampo: Map<String, String>,
        mensaje: String,
    ) : ErrorApi(mensaje)
}

/**
 * Traduce lo que suelte la red a algo que se pueda enseñar.
 *
 * El 429 es el único que pone su texto la app: lo emite el limitador de Laravel
 * antes de entrar en la ruta, así que no pasa por `lang/es` y llega en inglés
 * («Too Many Attempts.»).
 */
fun traduceError(t: Throwable): ErrorApi = when {
    t is ErrorApi -> t
    t is IOException -> ErrorApi.SinRed
    t is HttpException -> when (t.code()) {
        401 -> ErrorApi.SesionCaducada
        422 -> leeValidacion(t)
        429 -> ErrorApi.DemasiadosIntentos
        else -> ErrorApi.Desconocido
    }
    else -> ErrorApi.Desconocido
}

private fun leeValidacion(t: HttpException): ErrorApi {
    val cuerpo = t.response()?.errorBody()?.string().orEmpty()

    val dto = runCatching { jsonSrank.decodeFromString<ErrorValidacionDto>(cuerpo) }
        .getOrNull() ?: return ErrorApi.Desconocido

    val porCampo = dto.errors
        .mapNotNull { (campo, mensajes) ->
            mensajes.firstOrNull()?.let { campo to capitaliza(it) }
        }
        .toMap()

    val principal = porCampo.values.firstOrNull()
        ?: dto.message.takeIf { it.isNotBlank() }?.let(::capitaliza)
        ?: return ErrorApi.Desconocido

    return ErrorApi.Validacion(porCampo, principal)
}

/**
 * Los mensajes de `lang/es` empiezan en minúscula porque van debajo de un
 * campo. Al enseñarlos sueltos quedan mal, así que se capitalizan al pintar.
 */
private fun capitaliza(texto: String): String =
    texto.replaceFirstChar { it.uppercase() }.trimEnd('.')
```

> ⚠️ `errorBody()?.string()` **solo se puede leer una vez**. Por eso se lee dentro de
> `leeValidacion` y nunca fuera.

- [ ] **Paso 9: Ejecutarlo y verlo pasar**

```bash
./gradlew :data:api:testDebugUnitTest --tests "*TraductorErroresTest*"
```

Esperado: **PASA**, los ocho.

> Si falla el de `el_422_usa_el_mensaje_del_servidor_capitalizado` por el punto final:
> `capitaliza` quita el punto con `trimEnd('.')` a propósito, para que el mensaje no acabe
> con dos puntos al ir dentro de una frase. Los valores esperados del test ya lo reflejan.

- [ ] **Paso 10: Escribir el test de fechas**

`data/api/src/test/java/es/israelzamora/srank/api/FechasTest.kt`:

```kotlin
package es.israelzamora.srank.api

import org.junit.Assert.assertEquals
import org.junit.Test
import java.time.LocalDate

class FechasTest {

    @Test
    fun un_instante_de_las_2330_utc_en_madrid_ya_es_el_dia_siguiente() {
        // En verano Madrid va dos horas por delante de UTC. Sin convertir,
        // «hoy» cambiaría a medianoche menos dos horas y las misiones del día
        // aparecerían como las de ayer.
        assertEquals(
            LocalDate.of(2026, 8, 12),
            diaEnMadrid("2026-08-11T23:30:00.000000Z"),
        )
    }

    @Test
    fun antes_de_las_2200_utc_sigue_siendo_el_mismo_dia() {
        assertEquals(
            LocalDate.of(2026, 8, 11),
            diaEnMadrid("2026-08-11T21:59:00.000000Z"),
        )
    }

    @Test
    fun en_invierno_madrid_va_una_hora_por_delante() {
        // Enero: CET, +1. A las 23:30 UTC en Madrid es la 00:30 del día 12.
        assertEquals(
            LocalDate.of(2026, 1, 12),
            diaEnMadrid("2026-01-11T23:30:00.000000Z"),
        )
    }

    @Test
    fun el_dia_se_escribe_en_castellano() {
        assertEquals(
            "martes, 11 de agosto",
            formateaDiaLargo(LocalDate.of(2026, 8, 11)),
        )
    }
}
```

- [ ] **Paso 11: Ejecutarlo, verlo fallar, y escribir `Fechas.kt`**

```bash
./gradlew :data:api:testDebugUnitTest --tests "*FechasTest*"
```

Esperado: **FALLA** con `Unresolved reference: diaEnMadrid`.

`data/api/src/main/java/es/israelzamora/srank/api/Fechas.kt`:

```kotlin
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
```

- [ ] **Paso 12: Pasar la suite entera del módulo**

```bash
./gradlew :data:api:testDebugUnitTest
```

Esperado: **PASA**: 4 de DTO + 8 del traductor + 4 de fechas = 16.

- [ ] **Paso 13: Commit**

```bash
git add data/api/ settings.gradle.kts
git commit -m "$(cat <<'EOF'
feat(api): modelos, traducción de errores y fechas

Los errores se traducen en un solo sitio y las pantallas enseñan el texto
ya hecho. Ningún código HTTP sale de ErrorApi.kt.

El 429 es el único mensaje que pone la app: lo emite el limitador de
Laravel antes de entrar en la ruta, no pasa por lang/es y llega en
inglés.

Credenciales malas son un 422 con errors.email, no un 401. Está
comprobado contra AuthController y tiene su propio test, porque el
manejador global de 401 depende de que sea así.

ignoreUnknownKeys para que añadir una clave en el servidor no deje fuera
a quien no haya actualizado la app.

Madrid fijo y no la zona del móvil: el servidor asigna las misiones al
día con srank.timezone, y en un viaje «hoy» sería otro día distinto del
que se está puntuando.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 6 · `data/session` — el token, que sobrevive a cerrar la app

Va antes que Retrofit porque el interceptor necesita leer el token.

**Ficheros:**
- Crear: `data/session/build.gradle.kts`
- Crear: `data/session/src/main/java/es/israelzamora/srank/session/Sesion.kt`
- Test: `data/session/src/test/java/es/israelzamora/srank/session/SesionEnMemoriaTest.kt`

**Interfaces:**
- Produce:
  - `interface Sesion` con `val token: Flow<String?>`, `val tokenActual: String?`,
    `suspend fun guarda(token: String, nombre: String)`, `suspend fun limpia()`,
    `val nombre: Flow<String?>`
  - `SesionDataStore(context: Context)` — la de verdad
  - `SesionEnMemoria()` — la de los tests

---

- [ ] **Paso 1: Crear el módulo**

`data/session/build.gradle.kts`:

```kotlin
plugins {
    alias(libs.plugins.android.library)
}

android {
    namespace = "es.israelzamora.srank.session"
    compileSdk { version = release(36) }
    defaultConfig { minSdk { version = release(26) } }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    api(libs.androidx.datastore.preferences)
    api(libs.kotlinx.coroutines.android)

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
```

- [ ] **Paso 2: Escribir el test**

`data/session/src/test/java/es/israelzamora/srank/session/SesionEnMemoriaTest.kt`:

```kotlin
package es.israelzamora.srank.session

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class SesionEnMemoriaTest {

    @Test
    fun al_empezar_no_hay_sesion() = runTest {
        val sesion = SesionEnMemoria()

        assertNull(sesion.token.first())
        assertNull(sesion.tokenActual)
    }

    @Test
    fun guardar_deja_el_token_disponible_tambien_de_forma_sincrona() = runTest {
        // El interceptor de OkHttp no puede suspender, así que lee
        // tokenActual. Si esa copia no se actualizara al guardar, la primera
        // petición después de entrar iría sin Authorization.
        val sesion = SesionEnMemoria()

        sesion.guarda(token = "42|abcdef", nombre = "Israel")

        assertEquals("42|abcdef", sesion.token.first())
        assertEquals("42|abcdef", sesion.tokenActual)
        assertEquals("Israel", sesion.nombre.first())
    }

    @Test
    fun limpiar_borra_las_dos_copias() = runTest {
        // Si tokenActual sobreviviera al cierre de sesión, el interceptor
        // seguiría mandando el token de quien acaba de salir.
        val sesion = SesionEnMemoria()
        sesion.guarda(token = "42|abcdef", nombre = "Israel")

        sesion.limpia()

        assertNull(sesion.token.first())
        assertNull(sesion.tokenActual)
        assertNull(sesion.nombre.first())
    }
}
```

- [ ] **Paso 3: Ejecutarlo y verlo fallar**

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
./gradlew :data:session:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: SesionEnMemoria`.

- [ ] **Paso 4: Escribir `Sesion.kt`**

```kotlin
package es.israelzamora.srank.session

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
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

    override val token: Flow<String?> =
        context.almacen.data
            .map { it[CLAVE_TOKEN] }
            .onEach { tokenActual = it }

    override val nombre: Flow<String?> =
        context.almacen.data.map { it[CLAVE_NOMBRE] }

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
```

> ⚠️ `tokenActual` de `SesionDataStore` solo se rellena cuando alguien **colecta** el flujo
> `token`. La tarea 12 lanza esa colecta al arrancar la app y espera al primer valor antes
> de decidir la pantalla inicial, así que para cuando se pueda pedir algo ya está puesto.

- [ ] **Paso 5: Ejecutarlo y verlo pasar, y commit**

```bash
./gradlew :data:session:testDebugUnitTest
```

Esperado: **PASA**, los tres.

```bash
git add data/session/
git commit -m "$(cat <<'EOF'
feat(session): el token en DataStore, con copia en memoria para el interceptor

El interceptor de OkHttp no puede suspender para leer DataStore, y hacer
runBlocking en cada petición sería pagar disco en el hilo de red. La copia
en memoria resuelve eso, y el test exige que se actualice tanto al guardar
como al limpiar: si sobreviviera al cierre de sesión, seguiríamos mandando
el token de quien acaba de salir.

Dos implementaciones reales, DataStore y en memoria. La interfaz no es
ceremonia: es lo que permite probar el login sin un Context.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 7 · `data/api` — Retrofit, el interceptor y el aviso de 401

**Ficheros:**
- Crear: `data/api/src/main/java/es/israelzamora/srank/api/ApiSrank.kt`
- Crear: `data/api/src/main/java/es/israelzamora/srank/api/Red.kt`
- Modificar: `data/api/build.gradle.kts` (añadir `data/session`)

**Interfaces:**
- Consume: `Sesion` de la tarea 6, DTOs y `traduceError` de la tarea 5.
- Produce:
  - `interface ApiSrank` con los ocho endpoints
  - `object SesionExpirada { val avisos: SharedFlow<Unit> }`
  - `fun creaApi(sesion: Sesion, urlBase: String = URL_BASE): ApiSrank`
  - `suspend fun <T> pide(bloque: suspend () -> T): Result<T>`

---

- [ ] **Paso 1: Añadir la dependencia de sesión**

En `data/api/build.gradle.kts`, dentro de `dependencies`:

```kotlin
    implementation(project(":data:session"))
```

- [ ] **Paso 2: Escribir `ApiSrank.kt`**

Los ocho endpoints de la fase. Las rutas están comprobadas contra
`backend/routes/api.php`.

```kotlin
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
```

Añade el DTO que falta al final de `Dtos.kt`:

```kotlin
@Serializable
data class PerfilDto(
    val progress: ProgresoDto,
    val modules: Map<String, Boolean> = emptyMap(),
)
```

- [ ] **Paso 3: Escribir `Red.kt`**

```kotlin
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
```

> ⚠️ `CancellationException` se vuelve a lanzar. Si se tragara, cancelar una corrutina
> —salir de una pantalla mientras carga— se enseñaría como «No hemos podido conectar».

> **Sin registro de peticiones.** `logging-interceptor` imprimiría el token y las
> contraseñas en el logcat. `ponytail: sin logs de red. El techo es depurar a ciegas; si
> hace falta, se añade en debugImplementation con Level.BASIC, que no imprime cuerpos ni
> cabeceras.`

- [ ] **Paso 4: Compilar el módulo**

```bash
./gradlew :data:api:assembleDebug :data:api:testDebugUnitTest
```

Esperado: **BUILD SUCCESSFUL** y los 16 tests siguen en verde.

- [ ] **Paso 5: Comprobar contra el servidor de verdad**

Esto no es un test automático: es la comprobación de que la URL y las rutas existen.

```bash
curl -s -o /dev/null -w "sin token -> %{http_code}\n" \
  -H "Accept: application/json" https://s-rank.israelzamora.es/api/system/today
# esperado: 401

curl -s -w "\n-> %{http_code}\n" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"noexiste@ejemplo.es","password":"malacontrasena"}' \
  https://s-rank.israelzamora.es/api/auth/login
# esperado: 422 con {"message":"Credenciales incorrectas.", "errors":{"email":[...]}}
```

⚠️ El límite del login es **5 por minuto por IP**. Si sale un 429, espera un minuto; el
contador vive en la tabla `cache` de MySQL.

- [ ] **Paso 6: Commit**

```bash
git add data/api/
git commit -m "$(cat <<'EOF'
feat(api): Retrofit, interceptor y aviso global de sesión caducada

Accept: application/json va en toda petición porque sin esa cabecera el
servidor devuelve HTML en los errores y el traductor no puede leer el
cuerpo.

El 401 se emite a un SharedFlow que recoge la raíz de navegación: un solo
sitio y funciona desde cualquier pantalla. Como las credenciales malas son
un 422, un 401 solo puede significar que el token dejó de valer, así que
el manejador no necesita excepciones para las rutas de auth.

Sin logging-interceptor: imprimiría el token y las contraseñas en el
logcat.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 8 · `feature/auth` — el repositorio y la pantalla de login

**Ficheros:**
- Crear: `feature/auth/build.gradle.kts`
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/AuthRepositorio.kt`
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/login/LoginViewModel.kt`
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/login/PantallaLogin.kt`
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/Marco.kt`
- Test: `feature/auth/src/test/java/es/israelzamora/srank/auth/login/LoginViewModelTest.kt`
- Test: `feature/auth/src/test/java/es/israelzamora/srank/auth/ApiFalsa.kt`

**Interfaces:**
- Consume: `ApiSrank`, `pide`, `ErrorApi` (tarea 7); `Sesion` (tarea 6); `core/ui` (2-4).
- Produce:
  - `AuthRepositorio(api: ApiSrank, sesion: Sesion)` con `entrar`, `registrar`,
    `pideCodigo`, `cambiaContrasena`, `salir`
  - `LoginViewModel(auth: AuthRepositorio)` con `estado: StateFlow<EstadoLogin>`
  - `PantallaLogin(vm, alEntrar, alRegistrarse, alOlvidar)`
  - `MarcoAuth(titulo: String, modifier: Modifier = Modifier, content: @Composable ColumnScope.() -> Unit)`

---

- [ ] **Paso 1: Crear el módulo**

`feature/auth/build.gradle.kts`:

```kotlin
plugins {
    alias(libs.plugins.android.library)
    alias(libs.plugins.kotlin.compose)
}

android {
    namespace = "es.israelzamora.srank.auth"
    compileSdk { version = release(36) }
    defaultConfig { minSdk { version = release(26) } }
    buildFeatures { compose = true }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    implementation(project(":core:ui"))
    implementation(project(":data:api"))
    implementation(project(":data:session"))

    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.lifecycle.runtime.compose)

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
```

- [ ] **Paso 2: Escribir el repositorio**

`feature/auth/src/main/java/es/israelzamora/srank/auth/AuthRepositorio.kt`:

```kotlin
package es.israelzamora.srank.auth

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.CorreoPeticionDto
import es.israelzamora.srank.api.LoginPeticionDto
import es.israelzamora.srank.api.RegistroPeticionDto
import es.israelzamora.srank.api.ResetPeticionDto
import es.israelzamora.srank.api.pide
import es.israelzamora.srank.session.Sesion

/**
 * Todo lo que la app sabe hacer con una cuenta.
 *
 * Guardar la sesión es parte de entrar: si la pantalla tuviera que acordarse
 * de hacerlo, tarde o temprano una de las tres se olvidaría.
 */
class AuthRepositorio(
    private val api: ApiSrank,
    private val sesion: Sesion,
) {

    suspend fun entrar(correo: String, contrasena: String): Result<Unit> =
        pide { api.login(LoginPeticionDto(correo.trim(), contrasena)) }
            .map { sesion.guarda(it.accessToken, it.userName) }

    suspend fun registrar(nombre: String, correo: String, contrasena: String): Result<Unit> =
        pide { api.registro(RegistroPeticionDto(nombre.trim(), correo.trim(), contrasena)) }
            .map { sesion.guarda(it.accessToken, it.userName) }

    /**
     * Responde bien exista o no el correo. La pantalla **no puede** cambiar de
     * comportamiento según el resultado: eso reintroduciría desde el cliente la
     * fuga que el servidor evita respondiendo siempre lo mismo.
     */
    suspend fun pideCodigo(correo: String): Result<Unit> =
        pide { api.olvideContrasena(CorreoPeticionDto(correo.trim())) }.map { }

    suspend fun cambiaContrasena(
        correo: String,
        codigo: String,
        contrasena: String,
    ): Result<Unit> =
        pide { api.cambiaContrasena(ResetPeticionDto(correo.trim(), codigo.trim(), contrasena)) }
            .map { }

    /**
     * Limpia la sesión local pase lo que pase. Si el servidor no contesta, el
     * token se queda vivo allí, pero dejarlo también en el móvil sería peor:
     * el usuario pidió salir.
     */
    suspend fun salir() {
        pide { api.salir() }
        sesion.limpia()
    }
}
```

- [ ] **Paso 3: Escribir la API falsa que usan los tests**

Sin librería de dobles: `ApiSrank` ya es una interfaz porque Retrofit lo exige, así que
falsearla no cuesta ninguna dependencia nueva.

`feature/auth/src/test/java/es/israelzamora/srank/auth/ApiFalsa.kt`:

```kotlin
package es.israelzamora.srank.auth

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.CorreoPeticionDto
import es.israelzamora.srank.api.HoyDto
import es.israelzamora.srank.api.LoginPeticionDto
import es.israelzamora.srank.api.LoginRespuestaDto
import es.israelzamora.srank.api.MensajeDto
import es.israelzamora.srank.api.PerfilDto
import es.israelzamora.srank.api.RegistroPeticionDto
import es.israelzamora.srank.api.ResetPeticionDto
import es.israelzamora.srank.api.UsuarioDto

/**
 * Devuelve lo que se le diga, y apunta cuántas veces la han llamado.
 *
 * Cada campo es la respuesta de un endpoint: o un valor, o una excepción que
 * se lanza. Así un test escribe `api.respuestaLogin = { throw ... }` y ya.
 */
open class ApiFalsa : ApiSrank {

    var vecesLogin = 0
        private set
    var vecesOlvide = 0
        private set
    var ultimoCorreoOlvido: String? = null
        private set

    var respuestaLogin: () -> LoginRespuestaDto = {
        LoginRespuestaDto("42|abcdef", "Bearer", "Israel", false)
    }
    var respuestaRegistro: () -> LoginRespuestaDto = {
        LoginRespuestaDto("43|ghijkl", "Bearer", "Nuevo", false)
    }
    var respuestaOlvide: () -> MensajeDto = {
        MensajeDto("Si ese correo está registrado, te hemos enviado un código.")
    }
    var respuestaReset: () -> MensajeDto = {
        MensajeDto("Contraseña cambiada. Ya puedes entrar.")
    }
    var respuestaHoy: () -> HoyDto = { error("sin configurar") }

    override suspend fun login(peticion: LoginPeticionDto): LoginRespuestaDto {
        vecesLogin++
        return respuestaLogin()
    }

    override suspend fun registro(peticion: RegistroPeticionDto) = respuestaRegistro()

    override suspend fun olvideContrasena(peticion: CorreoPeticionDto): MensajeDto {
        vecesOlvide++
        ultimoCorreoOlvido = peticion.email
        return respuestaOlvide()
    }

    override suspend fun cambiaContrasena(peticion: ResetPeticionDto) = respuestaReset()

    override suspend fun salir() = MensajeDto("Sesión cerrada correctamente.")

    override suspend fun usuario() = UsuarioDto("1", "Israel", "hola@ejemplo.es")

    override suspend fun hoy() = respuestaHoy()

    override suspend fun perfil(): PerfilDto = error("sin configurar")
}
```

- [ ] **Paso 4: Escribir el test del ViewModel de login**

`feature/auth/src/test/java/es/israelzamora/srank/auth/login/LoginViewModelTest.kt`:

```kotlin
package es.israelzamora.srank.auth.login

import es.israelzamora.srank.auth.ApiFalsa
import es.israelzamora.srank.auth.AuthRepositorio
import es.israelzamora.srank.session.SesionEnMemoria
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response
import java.net.UnknownHostException

@OptIn(ExperimentalCoroutinesApi::class)
class LoginViewModelTest {

    private val dispatcher = StandardTestDispatcher()
    private lateinit var api: ApiFalsa
    private lateinit var sesion: SesionEnMemoria
    private lateinit var vm: LoginViewModel

    @Before
    fun preparar() {
        Dispatchers.setMain(dispatcher)
        api = ApiFalsa()
        sesion = SesionEnMemoria()
        vm = LoginViewModel(AuthRepositorio(api, sesion))
    }

    @After
    fun limpiar() {
        Dispatchers.resetMain()
    }

    private fun httpError(codigo: Int, cuerpo: String) = HttpException(
        Response.error<Any>(codigo, cuerpo.toResponseBody("application/json".toMediaType())),
    )

    @Test
    fun entrar_bien_guarda_la_sesion_y_avisa() = runTest {
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.entrar()
        advanceUntilIdle()

        assertTrue(vm.estado.value.entrado)
        assertNull(vm.estado.value.error)
        assertEquals("42|abcdef", sesion.token.first())
    }

    @Test
    fun credenciales_malas_llegan_como_422_y_se_ensenan_bajo_el_campo() = runTest {
        // Es un 422, no un 401: AuthController lanza ValidationException.
        api.respuestaLogin = {
            throw httpError(
                422,
                """{"message":"Credenciales incorrectas.",
                    "errors":{"email":["Credenciales incorrectas."]}}""",
            )
        }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("mala")

        vm.entrar()
        advanceUntilIdle()

        assertFalse(vm.estado.value.entrado)
        assertEquals("Credenciales incorrectas", vm.estado.value.error)
        assertNull("no se guarda sesión con credenciales malas", sesion.tokenActual)
    }

    @Test
    fun el_429_se_cuenta_en_castellano() = runTest {
        // El límite es 5 por minuto y llega en inglés desde el limitador.
        api.respuestaLogin = { throw httpError(429, """{"message":"Too Many Attempts."}""") }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.entrar()
        advanceUntilIdle()

        assertEquals(
            "Demasiados intentos. Espera un momento y vuelve a probar.",
            vm.estado.value.error,
        )
    }

    @Test
    fun sin_red_lo_dice_y_deja_reintentar() = runTest {
        api.respuestaLogin = { throw UnknownHostException("s-rank.israelzamora.es") }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.entrar()
        advanceUntilIdle()

        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)
        assertFalse(vm.estado.value.cargando)
    }

    @Test
    fun no_llama_al_servidor_con_los_campos_vacios() = runTest {
        // Gastar uno de los cinco intentos por minuto en algo que se sabe
        // que va a fallar es regalar el límite.
        vm.entrar()
        advanceUntilIdle()

        assertEquals(0, api.vecesLogin)
        assertEquals("Escribe tu correo y tu contraseña.", vm.estado.value.error)
    }

    @Test
    fun al_escribir_otra_vez_desaparece_el_error_anterior() = runTest {
        api.respuestaLogin = { throw httpError(429, """{"message":"Too Many Attempts."}""") }
        vm.escribeCorreo("hola@ejemplo.es")
        vm.escribeContrasena("micontrasena")
        vm.entrar()
        advanceUntilIdle()

        vm.escribeContrasena("otra")

        assertNull(vm.estado.value.error)
    }
}
```

- [ ] **Paso 5: Ejecutarlo y verlo fallar**

```bash
./gradlew :feature:auth:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: LoginViewModel`.

- [ ] **Paso 6: Escribir `LoginViewModel.kt`**

```kotlin
package es.israelzamora.srank.auth.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import es.israelzamora.srank.api.ErrorApi
import es.israelzamora.srank.auth.AuthRepositorio
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class EstadoLogin(
    val correo: String = "",
    val contrasena: String = "",
    val cargando: Boolean = false,
    val error: String? = null,
    val entrado: Boolean = false,
)

class LoginViewModel(private val auth: AuthRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoLogin())
    val estado: StateFlow<EstadoLogin> = _estado.asStateFlow()

    fun escribeCorreo(valor: String) = _estado.update { it.copy(correo = valor, error = null) }

    fun escribeContrasena(valor: String) =
        _estado.update { it.copy(contrasena = valor, error = null) }

    fun entrar() {
        val actual = _estado.value
        if (actual.cargando) return

        // Comprobar aquí no es cortesía: el login son 5 intentos por minuto y
        // por IP, y gastar uno en algo que se sabe que va a fallar deja al
        // usuario fuera antes de haber escrito bien.
        if (actual.correo.isBlank() || actual.contrasena.isBlank()) {
            _estado.update { it.copy(error = "Escribe tu correo y tu contraseña.") }
            return
        }

        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            auth.entrar(actual.correo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, entrado = true) } }
                .onFailure { fallo ->
                    _estado.update {
                        it.copy(
                            cargando = false,
                            error = (fallo as? ErrorApi)?.mensaje
                                ?: ErrorApi.Desconocido.mensaje,
                        )
                    }
                }
        }
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { LoginViewModel(auth) }
        }
    }
}
```

> `ponytail: sin Hilt. Las dependencias se pasan a mano con viewModelFactory, que para seis
> pantallas cabe en una línea por pantalla. El techo es el número de pantallas: cuando el
> cableado de app/ empiece a doler —digamos a partir de quince—, Hilt.`

- [ ] **Paso 7: Ejecutarlo y verlo pasar**

```bash
./gradlew :feature:auth:testDebugUnitTest
```

Esperado: **PASA**, los seis.

- [ ] **Paso 8: Escribir el marco común de las tres pantallas de auth**

`feature/auth/src/main/java/es/israelzamora/srank/auth/Marco.kt`:

```kotlin
package es.israelzamora.srank.auth

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.theme.SRank

/**
 * El marco de las tres pantallas de cuenta: la línea de prompt y el hueco.
 *
 * `verticalScroll` no es un adorno: con el tamaño de fuente del sistema al
 * máximo, un formulario de tres campos no cabe en un móvil pequeño, y sin
 * scroll el botón queda debajo del borde y la pantalla deja de funcionar.
 */
@Composable
fun MarcoAuth(
    titulo: String,
    modifier: Modifier = Modifier,
    content: @Composable ColumnScope.() -> Unit,
) {
    Column(
        modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
    ) {
        // El `$` es dibujo. No se lee y no hace falta saber qué significa.
        Text(
            text = "$ $titulo",
            style = SRank.texto.titulo,
            color = SRank.color.ambar,
            modifier = Modifier.clearAndSetSemantics { },
        )
        content()
    }
}
```

- [ ] **Paso 9: Escribir `PantallaLogin.kt`**

```kotlin
package es.israelzamora.srank.auth.login

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.defaultMinSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.auth.MarcoAuth
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.componentes.CampoSRank
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaLogin(
    vm: LoginViewModel,
    alEntrar: () -> Unit,
    alRegistrarse: () -> Unit,
    alOlvidar: () -> Unit,
) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    LaunchedEffect(estado.entrado) {
        if (estado.entrado) alEntrar()
    }

    MarcoAuth("entrar") {
        Comentario("escribe tus datos para continuar")
        Spacer(Modifier.height(24.dp))

        CampoSRank(
            valor = estado.correo,
            alCambiar = vm::escribeCorreo,
            etiqueta = "correo",
            tecladoCorreo = true,
        )
        Spacer(Modifier.height(12.dp))

        CampoSRank(
            valor = estado.contrasena,
            alCambiar = vm::escribeContrasena,
            etiqueta = "contraseña",
            esContrasena = true,
            error = estado.error,
        )
        Spacer(Modifier.height(24.dp))

        BotonSRank(
            texto = "entrar",
            alPulsar = vm::entrar,
            cargando = estado.cargando,
            modifier = Modifier.fillMaxWidth(),
        )
        Spacer(Modifier.height(24.dp))

        Enlace("no recuerdo mi contraseña", alOlvidar)
        Enlace("crear una cuenta", alRegistrarse)
    }
}

/**
 * Un enlace es un botón de texto con 48 dp de alto: aunque parezca texto,
 * hay que poder acertarle con el dedo.
 */
@Composable
internal fun Enlace(texto: String, alPulsar: () -> Unit) {
    Text(
        text = texto,
        style = SRank.texto.cuerpo,
        color = SRank.color.azul,
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = alPulsar)
            .defaultMinSize(minHeight = 48.dp)
            .padding(vertical = 12.dp),
    )
}
```

- [ ] **Paso 10: Compilar y commitear**

```bash
./gradlew :feature:auth:assembleDebug :feature:auth:testDebugUnitTest
```

Esperado: **BUILD SUCCESSFUL**.

```bash
git add feature/auth/
git commit -m "$(cat <<'EOF'
feat(auth): repositorio de cuenta y pantalla de login

El ViewModel no llama al servidor con los campos vacíos. No es cortesía:
el login son cinco intentos por minuto y por IP, y gastar uno en algo que
se sabe que va a fallar deja al usuario fuera antes de escribir bien.

Guardar la sesión es parte de entrar, no de la pantalla: si cada pantalla
tuviera que acordarse, una de las tres se olvidaría.

Los tests usan una ApiSrank falsa escrita a mano. La interfaz ya existe
porque Retrofit la exige, así que falsearla no cuesta una dependencia
nueva de dobles.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 9 · `feature/auth` — registro

**Ficheros:**
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/registro/RegistroViewModel.kt`
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/registro/PantallaRegistro.kt`
- Test: `feature/auth/src/test/java/es/israelzamora/srank/auth/registro/RegistroViewModelTest.kt`

**Interfaces:**
- Consume: `AuthRepositorio` (tarea 8), `ApiFalsa` (tarea 8), `core/ui`.
- Produce: `RegistroViewModel(auth)` con `estado: StateFlow<EstadoRegistro>`, y
  `PantallaRegistro(vm, alRegistrarse, alVolver)`.

---

- [ ] **Paso 1: Escribir el test**

```kotlin
package es.israelzamora.srank.auth.registro

import es.israelzamora.srank.auth.ApiFalsa
import es.israelzamora.srank.auth.AuthRepositorio
import es.israelzamora.srank.session.SesionEnMemoria
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class RegistroViewModelTest {

    private val dispatcher = StandardTestDispatcher()
    private lateinit var api: ApiFalsa
    private lateinit var sesion: SesionEnMemoria
    private lateinit var vm: RegistroViewModel

    @Before fun preparar() {
        Dispatchers.setMain(dispatcher)
        api = ApiFalsa()
        sesion = SesionEnMemoria()
        vm = RegistroViewModel(AuthRepositorio(api, sesion))
    }

    @After fun limpiar() = Dispatchers.resetMain()

    private fun rellenaBien() {
        vm.escribeNombre("Israel")
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("micontrasena")
    }

    @Test
    fun registrarse_bien_deja_la_sesion_puesta() = runTest {
        rellenaBien()

        vm.registrar()
        advanceUntilIdle()

        assertTrue(vm.estado.value.registrado)
        assertEquals("43|ghijkl", sesion.tokenActual)
    }

    @Test
    fun una_contrasena_corta_se_avisa_antes_de_llamar() = runTest {
        // El registro son 3 por hora. Gastar uno en algo que el servidor va a
        // rechazar seguro deja al usuario una hora fuera.
        vm.escribeNombre("Israel")
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("corta")

        vm.registrar()
        advanceUntilIdle()

        assertEquals("La contraseña necesita 8 caracteres como mínimo.",
            vm.estado.value.errorContrasena)
        assertNull(sesion.tokenActual)
    }

    @Test
    fun ocho_caracteres_justos_valen() = runTest {
        vm.escribeNombre("Israel")
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("12345678")

        vm.registrar()
        advanceUntilIdle()

        assertTrue(vm.estado.value.registrado)
    }

    @Test
    fun el_correo_repetido_se_ensena_bajo_su_campo() = runTest {
        api.respuestaRegistro = {
            throw HttpException(
                Response.error<Any>(
                    422,
                    """{"message":"El correo ya está en uso.",
                        "errors":{"email":["el correo ya está en uso"]}}"""
                        .toResponseBody("application/json".toMediaType()),
                ),
            )
        }
        rellenaBien()

        vm.registrar()
        advanceUntilIdle()

        assertEquals("El correo ya está en uso", vm.estado.value.errorCorreo)
        assertNull(vm.estado.value.errorGeneral)
    }

    @Test
    fun sin_nombre_no_se_llama_al_servidor() = runTest {
        vm.escribeCorreo("nuevo@ejemplo.es")
        vm.escribeContrasena("micontrasena")

        vm.registrar()
        advanceUntilIdle()

        assertEquals("Escribe tu nombre.", vm.estado.value.errorNombre)
    }
}
```

- [ ] **Paso 2: Ejecutarlo, verlo fallar, escribir el ViewModel**

```bash
./gradlew :feature:auth:testDebugUnitTest --tests "*RegistroViewModelTest*"
```

Esperado: **FALLA** con `Unresolved reference: RegistroViewModel`.

`feature/auth/src/main/java/es/israelzamora/srank/auth/registro/RegistroViewModel.kt`:

```kotlin
package es.israelzamora.srank.auth.registro

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import es.israelzamora.srank.api.ErrorApi
import es.israelzamora.srank.auth.AuthRepositorio
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class EstadoRegistro(
    val nombre: String = "",
    val correo: String = "",
    val contrasena: String = "",
    val cargando: Boolean = false,
    val errorNombre: String? = null,
    val errorCorreo: String? = null,
    val errorContrasena: String? = null,
    val errorGeneral: String? = null,
    val registrado: Boolean = false,
)

/** El mínimo que exige el servidor (`min:8` en AuthController::register). */
private const val MINIMO_CONTRASENA = 8

class RegistroViewModel(private val auth: AuthRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoRegistro())
    val estado: StateFlow<EstadoRegistro> = _estado.asStateFlow()

    fun escribeNombre(v: String) = _estado.update { it.copy(nombre = v, errorNombre = null) }
    fun escribeCorreo(v: String) = _estado.update { it.copy(correo = v, errorCorreo = null) }
    fun escribeContrasena(v: String) =
        _estado.update { it.copy(contrasena = v, errorContrasena = null) }

    fun registrar() {
        val actual = _estado.value
        if (actual.cargando) return

        // El registro son 3 por hora y por IP. Gastar uno en algo que el
        // servidor va a rechazar seguro deja al usuario una hora fuera.
        val fallos = EstadoRegistro(
            errorNombre = "Escribe tu nombre.".takeIf { actual.nombre.isBlank() },
            errorCorreo = "Escribe tu correo.".takeIf { actual.correo.isBlank() },
            errorContrasena = "La contraseña necesita $MINIMO_CONTRASENA caracteres como mínimo."
                .takeIf { actual.contrasena.length < MINIMO_CONTRASENA },
        )
        if (fallos.errorNombre != null || fallos.errorCorreo != null ||
            fallos.errorContrasena != null
        ) {
            _estado.update {
                it.copy(
                    errorNombre = fallos.errorNombre,
                    errorCorreo = fallos.errorCorreo,
                    errorContrasena = fallos.errorContrasena,
                )
            }
            return
        }

        _estado.update { it.copy(cargando = true, errorGeneral = null) }

        viewModelScope.launch {
            auth.registrar(actual.nombre, actual.correo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, registrado = true) } }
                .onFailure { fallo -> _estado.update { it.conFallo(fallo) } }
        }
    }

    /**
     * Un 422 trae el campo que falla, así que el mensaje va debajo de ese campo
     * y no en un aviso suelto que obliga a adivinar cuál era.
     */
    private fun EstadoRegistro.conFallo(fallo: Throwable): EstadoRegistro {
        val error = fallo as? ErrorApi ?: ErrorApi.Desconocido
        if (error !is ErrorApi.Validacion) {
            return copy(cargando = false, errorGeneral = error.mensaje)
        }
        return copy(
            cargando = false,
            errorNombre = error.porCampo["name"],
            errorCorreo = error.porCampo["email"],
            errorContrasena = error.porCampo["password"],
            errorGeneral = if (error.porCampo.isEmpty()) error.mensaje else null,
        )
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { RegistroViewModel(auth) }
        }
    }
}
```

- [ ] **Paso 3: Verlo pasar y escribir la pantalla**

```bash
./gradlew :feature:auth:testDebugUnitTest --tests "*RegistroViewModelTest*"
```

Esperado: **PASA**, los cinco.

`feature/auth/src/main/java/es/israelzamora/srank/auth/registro/PantallaRegistro.kt`:

```kotlin
package es.israelzamora.srank.auth.registro

import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.auth.MarcoAuth
import es.israelzamora.srank.auth.login.Enlace
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.componentes.CampoSRank
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaRegistro(
    vm: RegistroViewModel,
    alRegistrarse: () -> Unit,
    alVolver: () -> Unit,
) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    LaunchedEffect(estado.registrado) {
        if (estado.registrado) alRegistrarse()
    }

    MarcoAuth("crear cuenta") {
        Comentario("la contraseña necesita 8 caracteres como mínimo")
        Spacer(Modifier.height(24.dp))

        CampoSRank(estado.nombre, vm::escribeNombre, "nombre", error = estado.errorNombre)
        Spacer(Modifier.height(12.dp))

        CampoSRank(
            estado.correo, vm::escribeCorreo, "correo",
            error = estado.errorCorreo, tecladoCorreo = true,
        )
        Spacer(Modifier.height(12.dp))

        CampoSRank(
            estado.contrasena, vm::escribeContrasena, "contraseña",
            error = estado.errorContrasena, esContrasena = true,
        )
        Spacer(Modifier.height(24.dp))

        BotonSRank(
            "crear cuenta", vm::registrar,
            cargando = estado.cargando, modifier = Modifier.fillMaxWidth(),
        )

        if (estado.errorGeneral != null) {
            Spacer(Modifier.height(12.dp))
            Text(estado.errorGeneral!!, style = SRank.texto.cuerpo, color = SRank.color.rojo)
        }

        Spacer(Modifier.height(24.dp))
        Enlace("ya tengo cuenta", alVolver)
    }
}
```

- [ ] **Paso 4: Compilar y commitear**

```bash
./gradlew :feature:auth:assembleDebug :feature:auth:testDebugUnitTest
git add feature/auth/
git commit -m "$(cat <<'EOF'
feat(auth): pantalla de registro

Los ocho caracteres se comprueban en el cliente antes de llamar. El
registro son tres por hora y por IP: gastar uno en algo que el servidor
va a rechazar seguro deja al usuario una hora fuera.

El 422 trae el campo que falla, así que el mensaje va debajo de ese campo
y no en un aviso suelto que obligue a adivinar cuál era.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 10 · `feature/auth` — recuperar contraseña

⚠️ **Esta es la pantalla con una regla de seguridad de verdad.** El servidor responde 200
exista o no el correo, a propósito. Si la pantalla solo avanzara cuando la cuenta existe,
reintroduciría desde el cliente la fuga que el servidor evita.

**Ficheros:**
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/recuperar/RecuperarViewModel.kt`
- Crear: `feature/auth/src/main/java/es/israelzamora/srank/auth/recuperar/PantallaRecuperar.kt`
- Test: `feature/auth/src/test/java/es/israelzamora/srank/auth/recuperar/RecuperarViewModelTest.kt`

**Interfaces:**
- Produce: `RecuperarViewModel(auth)` con `estado: StateFlow<EstadoRecuperar>` y
  `PantallaRecuperar(vm, alTerminar, alVolver)`.

---

- [ ] **Paso 1: Escribir el test, que es el que protege la regla**

```kotlin
package es.israelzamora.srank.auth.recuperar

import es.israelzamora.srank.auth.ApiFalsa
import es.israelzamora.srank.auth.AuthRepositorio
import es.israelzamora.srank.session.SesionEnMemoria
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import retrofit2.HttpException
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class RecuperarViewModelTest {

    private val dispatcher = StandardTestDispatcher()
    private lateinit var api: ApiFalsa
    private lateinit var vm: RecuperarViewModel

    @Before fun preparar() {
        Dispatchers.setMain(dispatcher)
        api = ApiFalsa()
        vm = RecuperarViewModel(AuthRepositorio(api, SesionEnMemoria()))
    }

    @After fun limpiar() = Dispatchers.resetMain()

    @Test
    fun con_un_correo_registrado_avanza_al_paso_dos() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")

        vm.pideCodigo()
        advanceUntilIdle()

        assertEquals(PasoRecuperar.CODIGO, vm.estado.value.paso)
    }

    @Test
    fun con_un_correo_que_no_existe_avanza_exactamente_igual() = runTest {
        // ESTA ES LA REGLA. El servidor responde 200 exista o no la cuenta.
        // Si la pantalla se comportara distinto, este endpoint volvería a ser
        // una lista de qué correos están registrados.
        vm.escribeCorreo("noexiste@ejemplo.es")

        vm.pideCodigo()
        advanceUntilIdle()

        assertEquals(PasoRecuperar.CODIGO, vm.estado.value.paso)
        assertNull(vm.estado.value.error)
    }

    @Test
    fun el_texto_es_el_mismo_en_los_dos_casos() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        val conCuenta = vm.estado.value.aviso

        val otro = RecuperarViewModel(AuthRepositorio(ApiFalsa(), SesionEnMemoria()))
        otro.escribeCorreo("noexiste@ejemplo.es")
        otro.pideCodigo()
        advanceUntilIdle()

        assertEquals(conCuenta, otro.estado.value.aviso)
        assertTrue(conCuenta!!.startsWith("Si ese correo está registrado"))
    }

    @Test
    fun un_fallo_de_red_si_se_cuenta_y_no_avanza() = runTest {
        // Quedarse callado ante un fallo real dejaría al usuario esperando un
        // correo que nunca se pidió. Esto no delata nada: pasa igual exista o
        // no la cuenta.
        api.respuestaOlvide = { throw java.net.UnknownHostException("s-rank") }
        vm.escribeCorreo("existe@ejemplo.es")

        vm.pideCodigo()
        advanceUntilIdle()

        assertEquals(PasoRecuperar.CORREO, vm.estado.value.paso)
        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)
    }

    @Test
    fun un_codigo_mal_lo_dice_y_deja_reintentar() = runTest {
        api.respuestaReset = {
            throw HttpException(
                Response.error<Any>(
                    422,
                    """{"message":"El código no es válido o ha caducado.",
                        "errors":{"code":["El código no es válido o ha caducado."]}}"""
                        .toResponseBody("application/json".toMediaType()),
                ),
            )
        }
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("000000")
        vm.escribeContrasena("micontrasenanueva")

        vm.cambiaContrasena()
        advanceUntilIdle()

        assertEquals("El código no es válido o ha caducado", vm.estado.value.errorCodigo)
        assertEquals(PasoRecuperar.CODIGO, vm.estado.value.paso)
    }

    @Test
    fun el_codigo_tiene_seis_cifras_y_se_comprueba_antes_de_llamar() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("123")
        vm.escribeContrasena("micontrasenanueva")

        vm.cambiaContrasena()
        advanceUntilIdle()

        assertEquals("El código son 6 cifras.", vm.estado.value.errorCodigo)
    }

    @Test
    fun cambiar_bien_termina() = runTest {
        vm.escribeCorreo("existe@ejemplo.es")
        vm.pideCodigo()
        advanceUntilIdle()
        vm.escribeCodigo("123456")
        vm.escribeContrasena("micontrasenanueva")

        vm.cambiaContrasena()
        advanceUntilIdle()

        assertTrue(vm.estado.value.cambiada)
    }
}
```

- [ ] **Paso 2: Ejecutarlo y verlo fallar**

```bash
./gradlew :feature:auth:testDebugUnitTest --tests "*RecuperarViewModelTest*"
```

Esperado: **FALLA** con `Unresolved reference: RecuperarViewModel`.

- [ ] **Paso 3: Escribir el ViewModel**

```kotlin
package es.israelzamora.srank.auth.recuperar

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import es.israelzamora.srank.api.ErrorApi
import es.israelzamora.srank.auth.AuthRepositorio
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

enum class PasoRecuperar { CORREO, CODIGO }

data class EstadoRecuperar(
    val paso: PasoRecuperar = PasoRecuperar.CORREO,
    val correo: String = "",
    val codigo: String = "",
    val contrasena: String = "",
    val cargando: Boolean = false,
    val aviso: String? = null,
    val error: String? = null,
    val errorCodigo: String? = null,
    val errorContrasena: String? = null,
    val cambiada: Boolean = false,
)

/**
 * El texto que se enseña **siempre**, exista o no la cuenta. No hay ninguna
 * otra rama: si la hubiera, el endpoint volvería a ser una lista de qué
 * correos están registrados.
 */
private const val AVISO_UNICO =
    "Si ese correo está registrado, te hemos enviado un código de 6 cifras. " +
        "Caduca en 30 minutos."

private const val MINIMO_CONTRASENA = 8

class RecuperarViewModel(private val auth: AuthRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoRecuperar())
    val estado: StateFlow<EstadoRecuperar> = _estado.asStateFlow()

    fun escribeCorreo(v: String) = _estado.update { it.copy(correo = v, error = null) }

    fun escribeCodigo(v: String) =
        _estado.update { it.copy(codigo = v.filter(Char::isDigit).take(6), errorCodigo = null) }

    fun escribeContrasena(v: String) =
        _estado.update { it.copy(contrasena = v, errorContrasena = null) }

    /**
     * Avanza al paso 2 **siempre que el servidor conteste**, tenga cuenta ese
     * correo o no. Solo un fallo de red o un 429 dejan el paso donde estaba, y
     * eso pasa igual en los dos casos, así que no dice nada de nadie.
     */
    fun pideCodigo() {
        val actual = _estado.value
        if (actual.cargando) return

        if (actual.correo.isBlank()) {
            _estado.update { it.copy(error = "Escribe tu correo.") }
            return
        }

        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            auth.pideCodigo(actual.correo)
                .onSuccess {
                    _estado.update {
                        it.copy(
                            cargando = false,
                            paso = PasoRecuperar.CODIGO,
                            aviso = AVISO_UNICO,
                        )
                    }
                }
                .onFailure { fallo ->
                    _estado.update {
                        it.copy(
                            cargando = false,
                            error = (fallo as? ErrorApi)?.mensaje ?: ErrorApi.Desconocido.mensaje,
                        )
                    }
                }
        }
    }

    fun cambiaContrasena() {
        val actual = _estado.value
        if (actual.cargando) return

        val errorCodigo = "El código son 6 cifras.".takeIf { actual.codigo.length != 6 }
        val errorContrasena =
            "La contraseña necesita $MINIMO_CONTRASENA caracteres como mínimo."
                .takeIf { actual.contrasena.length < MINIMO_CONTRASENA }

        if (errorCodigo != null || errorContrasena != null) {
            _estado.update {
                it.copy(errorCodigo = errorCodigo, errorContrasena = errorContrasena)
            }
            return
        }

        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            auth.cambiaContrasena(actual.correo, actual.codigo, actual.contrasena)
                .onSuccess { _estado.update { e -> e.copy(cargando = false, cambiada = true) } }
                .onFailure { fallo ->
                    val error = fallo as? ErrorApi ?: ErrorApi.Desconocido
                    _estado.update {
                        if (error is ErrorApi.Validacion) {
                            it.copy(
                                cargando = false,
                                errorCodigo = error.porCampo["code"],
                                errorContrasena = error.porCampo["password"],
                                error = if (error.porCampo.isEmpty()) error.mensaje else null,
                            )
                        } else {
                            it.copy(cargando = false, error = error.mensaje)
                        }
                    }
                }
        }
    }

    companion object {
        fun factoria(auth: AuthRepositorio) = viewModelFactory {
            initializer { RecuperarViewModel(auth) }
        }
    }
}
```

- [ ] **Paso 4: Verlo pasar**

```bash
./gradlew :feature:auth:testDebugUnitTest --tests "*RecuperarViewModelTest*"
```

Esperado: **PASA**, los siete.

- [ ] **Paso 5: Comprobar que el test vale de verdad**

Regla del proyecto: un test que no falla sin el arreglo no vale.

1. En `pideCodigo()`, cambia el `onSuccess` para que solo avance si el correo contiene
   `"existe"` (simulando la fuga).
2. Ejecuta: `con_un_correo_que_no_existe_avanza_exactamente_igual` **debe fallar**.
3. Deshaz el cambio y comprueba que vuelve a pasar.

Si no falla en el paso 2, el test no está protegiendo nada y hay que arreglarlo antes de
seguir.

- [ ] **Paso 6: Escribir la pantalla**

```kotlin
package es.israelzamora.srank.auth.recuperar

import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.auth.MarcoAuth
import es.israelzamora.srank.auth.login.Enlace
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.componentes.CampoSRank
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaRecuperar(
    vm: RecuperarViewModel,
    alTerminar: () -> Unit,
    alVolver: () -> Unit,
) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    LaunchedEffect(estado.cambiada) {
        if (estado.cambiada) alTerminar()
    }

    MarcoAuth("recuperar contraseña") {
        when (estado.paso) {
            PasoRecuperar.CORREO -> {
                Comentario("te enviaremos un código por correo")
                Spacer(Modifier.height(24.dp))

                CampoSRank(
                    estado.correo, vm::escribeCorreo, "correo",
                    error = estado.error, tecladoCorreo = true,
                )
                Spacer(Modifier.height(24.dp))

                BotonSRank(
                    "enviar código", vm::pideCodigo,
                    cargando = estado.cargando, modifier = Modifier.fillMaxWidth(),
                )
            }

            PasoRecuperar.CODIGO -> {
                // El aviso es el mismo exista o no la cuenta. Ver el ViewModel.
                Text(
                    text = estado.aviso.orEmpty(),
                    style = SRank.texto.cuerpo,
                    color = SRank.color.texto,
                )
                Spacer(Modifier.height(24.dp))

                CampoSRank(
                    estado.codigo, vm::escribeCodigo, "código de 6 cifras",
                    error = estado.errorCodigo, tecladoNumerico = true,
                )
                Spacer(Modifier.height(12.dp))

                CampoSRank(
                    estado.contrasena, vm::escribeContrasena, "contraseña nueva",
                    error = estado.errorContrasena, esContrasena = true,
                )
                Spacer(Modifier.height(24.dp))

                BotonSRank(
                    "cambiar contraseña", vm::cambiaContrasena,
                    cargando = estado.cargando, modifier = Modifier.fillMaxWidth(),
                )

                if (estado.error != null) {
                    Spacer(Modifier.height(12.dp))
                    Text(estado.error!!, style = SRank.texto.cuerpo, color = SRank.color.rojo)
                }
            }
        }

        Spacer(Modifier.height(24.dp))
        Enlace("volver a entrar", alVolver)
    }
}
```

- [ ] **Paso 7: Compilar y commitear**

```bash
./gradlew :feature:auth:assembleDebug :feature:auth:testDebugUnitTest
git add feature/auth/
git commit -m "$(cat <<'EOF'
feat(auth): recuperar contraseña en dos pasos

La pantalla avanza al paso 2 siempre que el servidor conteste, exista o no
la cuenta, y con el mismo texto. Es la mitad que le toca al cliente: el
servidor responde 200 en los dos casos a propósito, y si la pantalla se
comportara distinto volvería a convertir el endpoint en una lista de qué
correos están registrados.

El test de esa regla se comprobó rompiéndolo a propósito: con la fuga
metida a mano, falla.

Un fallo de red sí se cuenta y no avanza. Eso no delata nada porque pasa
igual en los dos casos, y callarse dejaría al usuario esperando un correo
que nunca se pidió.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 11 · `core/system` — progreso, misiones y su cabecera

**Ficheros:**
- Crear: `core/system/build.gradle.kts`
- Crear: `core/system/src/main/java/es/israelzamora/srank/system/Modelos.kt`
- Crear: `core/system/src/main/java/es/israelzamora/srank/system/SystemRepositorio.kt`
- Crear: `core/system/src/main/java/es/israelzamora/srank/system/CabeceraProgreso.kt`
- Crear: `core/system/src/main/java/es/israelzamora/srank/system/ListaMisiones.kt`
- Test: `core/system/src/test/java/es/israelzamora/srank/system/ModelosTest.kt`

**Interfaces:**
- Consume: `ApiSrank`, `HoyDto`, `pide`, `formateaDiaLargo` (tareas 5 y 7); `core/ui`.
- Produce:
  - `Progreso`, `Mision`, `Hoy` (modelos de dominio)
  - `HoyDto.aDominio(): Hoy`
  - `SystemRepositorio(api)` con `suspend fun hoy(): Result<Hoy>`
  - `CabeceraProgreso(progreso, dia, modifier)`
  - `ListaMisiones(misiones: List<Mision>, contador: String, desplegada: Boolean, alPlegar: () -> Unit, modifier: Modifier = Modifier)`

> **`core/system` no declara `feature/*`.** Es la mitad de la regla rectora que en Android
> sale gratis: la vigila el compilador y no la disciplina. No añadas esa dependencia ni
> «solo para esto».

---

- [ ] **Paso 1: Crear el módulo**

`core/system/build.gradle.kts`:

```kotlin
plugins {
    alias(libs.plugins.android.library)
    alias(libs.plugins.kotlin.compose)
}

android {
    namespace = "es.israelzamora.srank.system"
    compileSdk { version = release(36) }
    defaultConfig { minSdk { version = release(26) } }
    buildFeatures { compose = true }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    implementation(project(":core:ui"))
    implementation(project(":data:api"))

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
```

- [ ] **Paso 2: Escribir el test de los modelos**

```kotlin
package es.israelzamora.srank.system

import es.israelzamora.srank.api.jsonSrank
import es.israelzamora.srank.api.HoyDto
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ModelosTest {

    private val respuestaReal = """
        {"date":"2026-08-11",
         "progress":{"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
                     "xp_for_next":400,"current_streak":12,"longest_streak":30,
                     "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}},
         "quests":[
           {"key":"water","label":"Beber 2 litros de agua","target":2000,"progress":2000,
            "xp_reward":10,"is_optional":false,"completed":true},
           {"key":"steps_8000","label":"8.000 pasos","target":8000,"progress":5240,
            "xp_reward":15,"is_optional":true,"completed":false}],
         "suggested_workout":{"reason":"Te faltan 2 entrenos para tu meta de esta semana.",
                              "weekly_done":1,"weekly_goal":3,"template":null}}
    """.trimIndent()

    @Test
    fun traduce_la_respuesta_de_hoy_a_dominio() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()

        assertEquals(4, hoy.progreso.nivel)
        assertEquals("E", hoy.progreso.rango)
        assertEquals(240, hoy.progreso.xpEnNivel)
        assertEquals(400, hoy.progreso.xpParaSiguiente)
        assertEquals(12, hoy.progreso.racha)
        assertEquals(2, hoy.misiones.size)
    }

    @Test
    fun el_dia_se_escribe_en_castellano() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()

        assertEquals("martes, 11 de agosto", hoy.dia)
    }

    @Test
    fun una_mision_terminada_no_ensena_avance_parcial() {
        // «2.000 de 2.000» debajo de una misión ya marcada es ruido.
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()
        val agua = hoy.misiones.first { it.clave == "water" }

        assertTrue(agua.completada)
        assertEquals(null, agua.avance)
    }

    @Test
    fun una_mision_a_medias_ensena_cuanto_lleva_con_separador_de_miles() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()
        val pasos = hoy.misiones.first { it.clave == "steps_8000" }

        assertFalse(pasos.completada)
        assertEquals("5.240 de 8.000", pasos.avance)
    }

    @Test
    fun una_mision_de_objetivo_uno_no_ensena_avance() {
        // «0 de 1» debajo de «Entrenar» no dice nada que no diga la casilla.
        val crudo = """
            {"date":"2026-08-11",
             "progress":{"level":1,"rank":"E","xp_total":0,"xp_into_level":0,
                         "xp_for_next":100,"current_streak":0,"longest_streak":0,
                         "stats":{"strength":0,"endurance":0,"consistency":0,"vitality":0}},
             "quests":[{"key":"train","label":"Entrenar","target":1,"progress":0,
                        "xp_reward":30,"is_optional":false,"completed":false}]}
        """.trimIndent()

        val hoy = jsonSrank.decodeFromString<HoyDto>(crudo).aDominio()

        assertEquals(null, hoy.misiones.first().avance)
    }

    @Test
    fun el_contador_de_la_seccion_cuenta_las_hechas() {
        val hoy = jsonSrank.decodeFromString<HoyDto>(respuestaReal).aDominio()

        assertEquals("1 de 2", hoy.contadorMisiones)
    }
}
```

- [ ] **Paso 3: Ejecutarlo, verlo fallar, escribir `Modelos.kt`**

```bash
export JAVA_HOME=~/jdk ANDROID_HOME=~/Android/Sdk
./gradlew :core:system:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: aDominio`.

`core/system/src/main/java/es/israelzamora/srank/system/Modelos.kt`:

```kotlin
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

private val NUMEROS: NumberFormat = NumberFormat.getIntegerInstance(Locale.forLanguageTag("es-ES"))

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
    else -> "${NUMEROS.format(progress)} de ${NUMEROS.format(target)}"
}
```

- [ ] **Paso 4: Verlo pasar**

```bash
./gradlew :core:system:testDebugUnitTest
```

Esperado: **PASA**, los seis.

- [ ] **Paso 5: Escribir el repositorio**

```kotlin
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
```

- [ ] **Paso 6: Escribir `CabeceraProgreso.kt`**

```kotlin
package es.israelzamora.srank.system

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.componentes.BarraBloques
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.componentes.InsigniaRango
import es.israelzamora.srank.ui.theme.SRank
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Nivel, rango, barra de XP y racha.
 *
 * El hueco entre el nivel y la insignia es `Spacer(weight)`, no espacios: con
 * la fuente del sistema al máximo se descuadraría.
 */
@Composable
fun CabeceraProgreso(
    progreso: Progreso,
    dia: String,
    modifier: Modifier = Modifier,
) {
    Column(modifier) {
        Comentario(dia)
        Spacer(Modifier.height(16.dp))

        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(
                text = "NIVEL ${progreso.nivel}",
                style = SRank.texto.titulo,
                color = SRank.color.texto,
            )
            Spacer(Modifier.weight(1f))
            InsigniaRango(progreso.rango)
        }

        Spacer(Modifier.height(8.dp))
        BarraBloques(progreso = progreso.xpEnNivel, total = progreso.xpParaSiguiente)

        Text(
            text = "${progreso.xpEnNivel} / ${progreso.xpParaSiguiente} XP",
            style = SRank.texto.nota,
            color = SRank.color.texto,
        )

        Spacer(Modifier.height(8.dp))
        Comentario("racha de", dato = "${progreso.racha} días")
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaCabecera() {
    SRankTheme {
        CabeceraProgreso(
            progreso = Progreso(4, "E", 240, 400, 12, 30),
            dia = "martes, 11 de agosto",
            modifier = Modifier.padding(16.dp),
        )
    }
}
```

- [ ] **Paso 7: Escribir `ListaMisiones.kt`**

```kotlin
package es.israelzamora.srank.system

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.layout.Column
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.tooling.preview.Preview
import es.israelzamora.srank.ui.componentes.CabeceraSeccion
import es.israelzamora.srank.ui.componentes.Comentario
import es.israelzamora.srank.ui.componentes.FilaMision
import es.israelzamora.srank.ui.theme.SRankTheme

/**
 * Las misiones del día, de solo lectura en 1.1.
 *
 * La cabecera va en ámbar porque es la sección de misiones (spec §6). Dentro
 * de cada fila el color solo dice estado.
 */
@Composable
fun ListaMisiones(
    misiones: List<Mision>,
    contador: String,
    desplegada: Boolean,
    alPlegar: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Column(modifier) {
        CabeceraSeccion(
            titulo = "misiones de hoy",
            desplegada = desplegada,
            alPulsar = alPlegar,
            contador = contador,
        )

        AnimatedVisibility(visible = desplegada) {
            Column {
                if (misiones.isEmpty()) {
                    Comentario("hoy no hay misiones")
                } else {
                    misiones.forEach {
                        FilaMision(
                            etiqueta = it.etiqueta,
                            completada = it.completada,
                            avance = it.avance,
                        )
                    }
                }
            }
        }
    }
}

@Preview(showBackground = true, backgroundColor = 0xFF000000)
@Composable
private fun VistaListaMisiones() {
    SRankTheme {
        ListaMisiones(
            misiones = listOf(
                Mision("water", "Beber 2 litros de agua", true, null),
                Mision("train", "Entrenar", false, null),
                Mision("steps_8000", "8.000 pasos", false, "5.240 de 8.000"),
            ),
            contador = "1 de 3",
            desplegada = true,
            alPlegar = {},
        )
    }
}
```

- [ ] **Paso 8: Compilar y commitear**

```bash
./gradlew :core:system:assembleDebug :core:system:testDebugUnitTest
git add core/system/
git commit -m "$(cat <<'EOF'
feat(system): progreso, misiones y su cabecera

Ningún número se calcula aquí: el XP lo decide siempre el servidor y la
app solo lo pinta.

El avance parcial se esconde cuando no dice nada. «2.000 de 2.000» debajo
de una casilla ya marcada es ruido, y «0 de 1» debajo de «Entrenar» no
añade nada a la propia casilla.

core/system no declara feature/*, que es la mitad de la regla rectora que
en Android sale gratis y la vigila el compilador.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 12 · `app` — cableado, pestañas, «hoy» y el 401 global

**Ficheros:**
- Modificar: `app/build.gradle.kts` (añadir los cinco módulos)
- Modificar: `app/src/main/AndroidManifest.xml` (declarar `Aplicacion`)
- Crear: `app/src/main/java/es/israelzamora/srank/Grafo.kt`, `Aplicacion.kt`
- Modificar: `app/src/main/java/es/israelzamora/srank/MainActivity.kt`
- Crear: `app/src/main/java/es/israelzamora/srank/nav/NavRaiz.kt`
- Crear: `app/src/main/java/es/israelzamora/srank/hoy/HoyViewModel.kt`, `PantallaHoy.kt`,
  `PantallasVacias.kt`
- Test: `app/src/test/java/es/israelzamora/srank/hoy/HoyViewModelTest.kt`

**Interfaces:**
- Consume: todo lo anterior.
- Produce: la app entera.

---

- [ ] **Paso 1: Añadir las dependencias**

En `app/build.gradle.kts`, dentro de `dependencies`:

```kotlin
    implementation(project(":core:ui"))
    implementation(project(":core:system"))
    implementation(project(":data:api"))
    implementation(project(":data:session"))
    implementation(project(":feature:auth"))

    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.lifecycle.runtime.compose)

    testImplementation(libs.kotlinx.coroutines.test)
```

- [ ] **Paso 2: Escribir el test de «hoy»**

`app/src/test/java/es/israelzamora/srank/hoy/HoyViewModelTest.kt`:

```kotlin
package es.israelzamora.srank.hoy

import es.israelzamora.srank.api.ApiSrank
import es.israelzamora.srank.api.CorreoPeticionDto
import es.israelzamora.srank.api.HoyDto
import es.israelzamora.srank.api.LoginPeticionDto
import es.israelzamora.srank.api.LoginRespuestaDto
import es.israelzamora.srank.api.MensajeDto
import es.israelzamora.srank.api.PerfilDto
import es.israelzamora.srank.api.RegistroPeticionDto
import es.israelzamora.srank.api.ResetPeticionDto
import es.israelzamora.srank.api.UsuarioDto
import es.israelzamora.srank.api.jsonSrank
import es.israelzamora.srank.system.SystemRepositorio
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import java.net.UnknownHostException

private const val RESPUESTA_HOY = """
    {"date":"2026-08-11",
     "progress":{"level":4,"rank":"E","xp_total":1240,"xp_into_level":240,
                 "xp_for_next":400,"current_streak":12,"longest_streak":30,
                 "stats":{"strength":3,"endurance":5,"consistency":8,"vitality":2}},
     "quests":[{"key":"water","label":"Beber 2 litros de agua","target":2000,
                "progress":2000,"xp_reward":10,"is_optional":false,"completed":true}]}
"""

private class ApiHoyFalsa(var respuesta: () -> HoyDto) : ApiSrank {
    var veces = 0
        private set

    override suspend fun hoy(): HoyDto {
        veces++
        return respuesta()
    }

    override suspend fun login(peticion: LoginPeticionDto): LoginRespuestaDto = error("no")
    override suspend fun registro(peticion: RegistroPeticionDto): LoginRespuestaDto = error("no")
    override suspend fun olvideContrasena(peticion: CorreoPeticionDto): MensajeDto = error("no")
    override suspend fun cambiaContrasena(peticion: ResetPeticionDto): MensajeDto = error("no")
    override suspend fun salir(): MensajeDto = error("no")
    override suspend fun usuario(): UsuarioDto = error("no")
    override suspend fun perfil(): PerfilDto = error("no")
}

@OptIn(ExperimentalCoroutinesApi::class)
class HoyViewModelTest {

    private val dispatcher = StandardTestDispatcher()

    @Before fun preparar() = Dispatchers.setMain(dispatcher)

    @After fun limpiar() = Dispatchers.resetMain()

    private fun vmCon(api: ApiHoyFalsa) = HoyViewModel(SystemRepositorio(api))

    @Test
    fun carga_el_progreso_y_las_misiones() = runTest {
        val api = ApiHoyFalsa { jsonSrank.decodeFromString(RESPUESTA_HOY) }
        val vm = vmCon(api)

        vm.carga()
        advanceUntilIdle()

        val estado = vm.estado.value
        assertEquals(4, estado.hoy?.progreso?.nivel)
        assertEquals("martes, 11 de agosto", estado.hoy?.dia)
        assertEquals(1, estado.hoy?.misiones?.size)
        assertNull(estado.error)
        assertEquals(false, estado.cargando)
    }

    @Test
    fun sin_red_lo_dice_en_castellano() = runTest {
        val api = ApiHoyFalsa { throw UnknownHostException("s-rank") }
        val vm = vmCon(api)

        vm.carga()
        advanceUntilIdle()

        assertEquals("No hay conexión. Comprueba el wifi o los datos.", vm.estado.value.error)
        assertNull(vm.estado.value.hoy)
    }

    @Test
    fun reintentar_vuelve_a_pedir() = runTest {
        var fallar = true
        val api = ApiHoyFalsa {
            if (fallar) throw UnknownHostException("s-rank")
            else jsonSrank.decodeFromString(RESPUESTA_HOY)
        }
        val vm = vmCon(api)
        vm.carga()
        advanceUntilIdle()

        fallar = false
        vm.carga()
        advanceUntilIdle()

        assertEquals(2, api.veces)
        assertNull(vm.estado.value.error)
        assertEquals(4, vm.estado.value.hoy?.progreso?.nivel)
    }

    @Test
    fun plegar_la_seccion_no_borra_lo_cargado() = runTest {
        val api = ApiHoyFalsa { jsonSrank.decodeFromString(RESPUESTA_HOY) }
        val vm = vmCon(api)
        vm.carga()
        advanceUntilIdle()

        vm.plegaMisiones()

        assertTrue(!vm.estado.value.misionesDesplegadas)
        assertEquals(4, vm.estado.value.hoy?.progreso?.nivel)
    }
}
```

- [ ] **Paso 3: Ejecutarlo, verlo fallar, escribir `HoyViewModel.kt`**

```bash
./gradlew :app:testDebugUnitTest
```

Esperado: **FALLA** con `Unresolved reference: HoyViewModel`.

```kotlin
package es.israelzamora.srank.hoy

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import es.israelzamora.srank.api.ErrorApi
import es.israelzamora.srank.system.Hoy
import es.israelzamora.srank.system.SystemRepositorio
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class EstadoHoy(
    val cargando: Boolean = false,
    val hoy: Hoy? = null,
    val error: String? = null,
    val misionesDesplegadas: Boolean = true,
)

class HoyViewModel(private val sistema: SystemRepositorio) : ViewModel() {

    private val _estado = MutableStateFlow(EstadoHoy())
    val estado: StateFlow<EstadoHoy> = _estado.asStateFlow()

    init {
        carga()
    }

    fun carga() {
        if (_estado.value.cargando) return
        _estado.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            sistema.hoy()
                .onSuccess { datos -> _estado.update { it.copy(cargando = false, hoy = datos) } }
                .onFailure { fallo ->
                    _estado.update {
                        it.copy(
                            cargando = false,
                            error = (fallo as? ErrorApi)?.mensaje ?: ErrorApi.Desconocido.mensaje,
                        )
                    }
                }
        }
    }

    fun plegaMisiones() =
        _estado.update { it.copy(misionesDesplegadas = !it.misionesDesplegadas) }

    companion object {
        fun factoria(sistema: SystemRepositorio) = viewModelFactory {
            initializer { HoyViewModel(sistema) }
        }
    }
}
```

> ⚠️ `init { carga() }` hace que el test llame una vez de más si además invoca `carga()`.
> El test `reintentar_vuelve_a_pedir` cuenta 2 porque el `init` ya gastó la primera y la
> llamada explícita del test es la segunda. Si al ejecutarlo sale 3, es que `carga()` no
> está respetando el `if (cargando) return`.

- [ ] **Paso 4: Verlo pasar**

```bash
./gradlew :app:testDebugUnitTest
```

Esperado: **PASA**, los cuatro.

- [ ] **Paso 5: Escribir el grafo de dependencias**

`app/src/main/java/es/israelzamora/srank/Grafo.kt`:

```kotlin
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
```

`app/src/main/java/es/israelzamora/srank/Aplicacion.kt`:

```kotlin
package es.israelzamora.srank

import android.app.Application

class Aplicacion : Application() {
    lateinit var grafo: Grafo
        private set

    override fun onCreate() {
        super.onCreate()
        grafo = Grafo(this)
    }
}
```

En el manifiesto, añade a `<application>`:

```xml
        android:name=".Aplicacion"
```

- [ ] **Paso 6: Escribir las pantallas vacías**

`app/src/main/java/es/israelzamora/srank/hoy/PantallasVacias.kt`:

```kotlin
package es.israelzamora.srank.hoy

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import es.israelzamora.srank.ui.componentes.Comentario

@Composable
fun PantallaProgreso(modifier: Modifier = Modifier) {
    Column(modifier.fillMaxSize().padding(24.dp)) {
        Comentario("el historial, el calendario y las gráficas llegan en la fase 1.4")
    }
}

@Composable
fun PantallaPerfil(modifier: Modifier = Modifier) {
    Column(modifier.fillMaxSize().padding(24.dp)) {
        Comentario("los logros y los ajustes llegan en la fase 1.5")
    }
}
```

- [ ] **Paso 7: Escribir `PantallaHoy.kt`**

```kotlin
package es.israelzamora.srank.hoy

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import es.israelzamora.srank.system.CabeceraProgreso
import es.israelzamora.srank.system.ListaMisiones
import es.israelzamora.srank.ui.componentes.BotonSRank
import es.israelzamora.srank.ui.theme.SRank

@Composable
fun PantallaHoy(vm: HoyViewModel, modifier: Modifier = Modifier) {
    val estado by vm.estado.collectAsStateWithLifecycle()

    Column(
        modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(24.dp),
    ) {
        // El `$` es dibujo: no se lee y no hay que saber qué significa.
        Text("$ hoy", style = SRank.texto.titulo, color = SRank.color.ambar)
        Spacer(Modifier.height(8.dp))

        when {
            estado.hoy != null -> {
                val hoy = estado.hoy!!
                CabeceraProgreso(progreso = hoy.progreso, dia = hoy.dia)
                Spacer(Modifier.height(24.dp))
                ListaMisiones(
                    misiones = hoy.misiones,
                    contador = hoy.contadorMisiones,
                    desplegada = estado.misionesDesplegadas,
                    alPlegar = vm::plegaMisiones,
                )
            }

            estado.cargando -> CircularProgressIndicator(color = SRank.color.ambar)

            estado.error != null -> {
                Text(estado.error!!, style = SRank.texto.cuerpo, color = SRank.color.texto)
                Spacer(Modifier.height(16.dp))
                BotonSRank("reintentar", vm::carga)
            }
        }
    }
}
```

> El orden del `when` importa: si ya hay datos se enseñan aunque una recarga falle. Perder
> la pantalla entera por un fallo de red al refrescar sería peor que no refrescar.

- [ ] **Paso 8: Escribir `NavRaiz.kt`, con las pestañas y el 401**

```kotlin
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
import androidx.compose.runtime.collectAsState
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
import es.israelzamora.srank.ui.theme.SRank
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

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

@Composable
fun NavRaiz(grafo: Grafo) {
    val nav = rememberNavController()

    // Espera al primer valor de DataStore antes de decidir la pantalla. De
    // paso, eso deja lleno el `tokenActual` que lee el interceptor, así que la
    // primera petición ya sale con Authorization.
    LaunchedEffect(Unit) {
        val hay = grafo.sesion.token.first() != null
        nav.navigate(if (hay) Rutas.HOY else Rutas.LOGIN) {
            popUpTo(Rutas.CARGANDO) { inclusive = true }
        }
    }

    // El 401 desde cualquier pantalla: un solo sitio.
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
                    alEntrar = { nav.navigate(Rutas.HOY) { popUpTo(0) } },
                    alRegistrarse = { nav.navigate(Rutas.REGISTRO) },
                    alOlvidar = { nav.navigate(Rutas.RECUPERAR) },
                )
            }

            composable(Rutas.REGISTRO) {
                PantallaRegistro(
                    vm = viewModel(factory = RegistroViewModel.factoria(grafo.auth)),
                    alRegistrarse = { nav.navigate(Rutas.HOY) { popUpTo(0) } },
                    alVolver = { nav.popBackStack() },
                )
            }

            composable(Rutas.RECUPERAR) {
                PantallaRecuperar(
                    vm = viewModel(factory = RecuperarViewModel.factoria(grafo.auth)),
                    alTerminar = { nav.navigate(Rutas.LOGIN) { popUpTo(0) } },
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
```

- [ ] **Paso 9: Rehacer `MainActivity.kt`**

```kotlin
package es.israelzamora.srank

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import es.israelzamora.srank.nav.NavRaiz
import es.israelzamora.srank.ui.theme.SRankTheme

const val NOMBRE_APP = "S-RANK"

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val grafo = (application as Aplicacion).grafo

        setContent {
            SRankTheme { NavRaiz(grafo) }
        }
    }
}
```

- [ ] **Paso 10: Compilar la app entera y pasar toda la suite**

```bash
./gradlew assembleDebug test
```

Esperado: **BUILD SUCCESSFUL** y **56 tests** en verde:

| Módulo | Tests |
|---|---|
| `core:ui` | 9 · contraste 4 + barra 5 |
| `data:api` | 16 · DTO 4 + errores 8 + fechas 4 |
| `data:session` | 3 |
| `feature:auth` | 18 · login 6 + registro 5 + recuperar 7 |
| `core:system` | 6 |
| `app` | 4 |
| **total** | **56** |

> Si el total no cuadra, cuéntalos por módulo antes de seguir. Un módulo cuyo
> `testDebugUnitTest` no se ejecuta pasa desapercibido en el total.

- [ ] **Paso 11: Commit**

```bash
git add app/
git commit -m "$(cat <<'EOF'
feat(app): navegación, pestañas, pantalla de hoy y 401 global

El 401 se recoge en un solo sitio, la raíz de navegación, así que limpia
la sesión y lleva al login desde cualquier pantalla sin que cada una
tenga que acordarse.

Al arrancar se espera al primer valor de DataStore antes de decidir la
pantalla. De paso eso deja lleno el tokenActual que lee el interceptor,
así que la primera petición ya sale con Authorization y no hay carrera.

Si ya hay datos en pantalla, un fallo al refrescar no los borra: perder
la pantalla entera por un corte de red sería peor que no refrescar.

Las dependencias se cablean a mano en Grafo. Son cinco objetos, y Hilt
costaría un plugin y anotaciones para ahorrar doce líneas.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Tarea 13 · La comprobación que ningún test puede hacer

La fase 1.0 dejó cuatro fallos que pasaron 254 tests y solo aparecieron al tocar el
servidor real. Todo lo de esta tarea es de la misma familia: **no hay test que lo cubra**.

**Ficheros:** ninguno, salvo lo que haya que arreglar.

---

- [ ] **Paso 1: Instalar la app en un móvil de verdad**

Con depuración inalámbrica (Android 11+): en el móvil, *Opciones de desarrollador →
Depuración inalámbrica → Vincular dispositivo con código*.

```bash
export ANDROID_HOME=~/Android/Sdk
$ANDROID_HOME/platform-tools/adb pair IP:PUERTO_DE_VINCULACION
$ANDROID_HOME/platform-tools/adb connect IP:PUERTO
$ANDROID_HOME/platform-tools/adb devices
```

Y se instala:

```bash
export JAVA_HOME=~/jdk
./gradlew installDebug
```

Si `adb` no llega al móvil desde WSL, el plan B es copiar el APK
(`app/build/outputs/apk/debug/app-debug.apk`) al móvil y abrirlo.

- [ ] **Paso 2: Crear una cuenta de cero y comprobar que la sesión aguanta**

1. Crear cuenta con un correo nuevo.
2. Ver que entra directo a «hoy» con el nivel real.
3. **Cerrar la app del todo** (sacarla de recientes) y volver a abrirla → tiene que
   entrar sin pedir nada.

⚠️ El registro son **3 por hora y por IP**. Si sale «Demasiados intentos», el contador vive
en la tabla `cache` de MySQL: `DELETE FROM cache;` por phpMyAdmin lo reinicia sin tocar
ningún dato.

- [ ] **Paso 3: Recuperar la contraseña con el código de verdad**

1. «No recuerdo mi contraseña» → correo registrado → **tiene que avanzar al paso 2**.
2. Mirar el correo, meter el código de 6 cifras y una contraseña nueva.
3. Entrar con la nueva.
4. **Repetir con un correo que no exista**: tiene que avanzar al paso 2 **igual**, con el
   mismo texto. Si se comporta distinto, la fuga está de vuelta y hay que arreglarlo antes
   de cerrar la fase.

- [ ] **Paso 4: El 401 desde cualquier pantalla**

Invalida el token a mano y comprueba que la app se entera:

```sql
-- phpMyAdmin, en la base de datos de producción
DELETE FROM personal_access_tokens WHERE tokenable_id = 'EL-UUID-DEL-USUARIO';
```

Con la app abierta en «hoy», tira de refrescar. Tiene que **limpiar la sesión y llevar al
login**, no quedarse en blanco ni enseñar un error suelto.

- [ ] **Paso 5: El tamaño de fuente del sistema al máximo**

*Ajustes → Pantalla → Tamaño de fuente*, al máximo (2,0×). Y revisa **una por una**:

- [ ] Login, registro y recuperar: se llega al botón haciendo scroll, y ningún campo se
      sale.
- [ ] «Hoy»: la barra `[▓▓▓▓▓▓░░░░]` **cabe en su línea y no se parte**.
- [ ] Las filas de misión no cortan la etiqueta a media palabra.
- [ ] La cabecera `▸ MISIONES DE HOY   [1 de 4] ▾` no se solapa en el medio.
- [ ] Las tres pestañas siguen legibles.

Si la barra se parte, el arreglo es bajarla a 8 bloques, no quitar el `sp`.

- [ ] **Paso 6: Los bloques se ven como bloques**

Mira la barra de XP de cerca: `▓` y `░` tienen que salir como bloques sólidos, no como
rectángulos vacíos ni como cuadros de «carácter que falta».

Está comprobado que JetBrains Mono 2.304 trae los dos glifos, así que si se ven mal es que
**la fuente no se está aplicando** y Android ha caído a la del sistema. Revisa que
`core/ui/src/main/res/font/` tenga los `.ttf` y que el estilo salga de `SRank.texto`.

- [ ] **Paso 7: TalkBack**

*Ajustes → Accesibilidad → TalkBack*. Es lo único que comprueba de verdad la regla rectora.

- [ ] Una misión hecha se oye **«Beber 2 litros de agua, hecha»**, y **no** «corchete,
      marca de verificación, corchete».
- [ ] La cabecera de sección se oye con su título y «desplegado» o «plegado», sin
      triángulos.
- [ ] La barra de XP se oye como un porcentaje, no como veinticuatro caracteres.
- [ ] El `$` del título **no se lee**.
- [ ] Los enlaces de login se pueden enfocar y activar.

Si algo lee decoración, está mal y se arregla aquí: es la mitad de la regla rectora que no
tiene otro sitio donde comprobarse.

- [ ] **Paso 8: Sin conexión**

Modo avión, y abre la app:

- [ ] «Hoy» dice «No hay conexión. Comprueba el wifi o los datos.» y ofrece **reintentar**.
- [ ] El login dice lo mismo y deja volver a probar.
- [ ] **En ninguna pantalla aparece un número de error.**

- [ ] **Paso 9: Anotar lo que se rompió**

Todo lo que haya salido mal en esta tarea va al final de este plan, en «Lo que solo
apareció en el móvil», con qué era y cómo se arregló. Es lo que la fase 1.2 va a leer para
no repetirlo.

- [ ] **Paso 10: Commit final de la fase**

```bash
git add -A
git commit -m "$(cat <<'EOF'
fix(fase-1.1): lo que solo apareció en un móvil de verdad

[Describe aquí lo que se arregló. Si no se rompió nada, di eso mismo y
cuenta qué se comprobó: sesión que sobrevive al cierre, 401 desde hoy,
fuente al máximo, TalkBack y modo avión.]

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Cuando la fase está terminada

La lista es la del documento de fase, palabra por palabra:

- [ ] `./gradlew assembleDebug` compila y la app arranca en un móvil real.
- [ ] Se puede crear una cuenta desde cero, salir y volver a entrar.
- [ ] Se puede recuperar la contraseña con el código que llega por correo.
- [ ] La sesión sobrevive a cerrar y abrir la app.
- [ ] Un 401 lleva al login limpiando la sesión, desde cualquier pantalla.
- [ ] `hoy` enseña el nivel, el rango, la barra de XP y la racha reales.
- [ ] Las misiones del día se ven, con su texto en castellano.
- [ ] Sin conexión, cada pantalla dice qué pasa en español y ofrece reintentar.
- [ ] Los ViewModel tienen tests.
- [ ] Se ve bien con el tamaño de fuente del sistema al máximo.

---

## Lo que esta fase deja fuera a propósito

**Dos componentes del spec §5** que no tienen quién los dispare en 1.1: la barra continua
(fase 1.3/1.4, cuando haya estadísticas y macros) y el rombo de rareza `◆`/`◇` (fase 1.5,
con la pantalla de logros). No se escriben ahora porque no hay datos con los que juzgar si
están bien.

**Marcar misiones opcionales a mano.** `POST /api/system/quests/{key}/complete` está
esperando; `FilaMision` solo necesita un `onClick` nullable y el alto de 48 dp.

**Las cuatro estadísticas** (fuerza, resistencia, constancia, vitalidad). Llegan en el
`system` block y se ignoran: la pantalla que las enseña es de la fase 1.4.

**`suggested_workout`.** Se deserializa y no se pinta. Es de la fase 1.2.

**Sin borrador sin conexión.** Todo requiere red. Room y `data/draft` son de la fase 1.2.

**Sin tema claro** ni animaciones, más allá de plegar la sección de misiones.

**Sin R8 ni ofuscación**, y **sin registro de peticiones de red**.

---

## Lo que solo apareció en el móvil

<!-- La tarea 13 escribe aquí. Si está vacío al cerrar la fase, es que no se hizo. -->

_(pendiente de la tarea 13)_
