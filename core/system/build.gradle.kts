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
    // api, no implementation: HoyDto/ProgresoDto/MisionDto salen como
    // receptor de las funciones públicas `aDominio()` de Modelos.kt, y
    // ApiSrank en la firma pública del constructor de SystemRepositorio.
    api(project(":data:api"))

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
