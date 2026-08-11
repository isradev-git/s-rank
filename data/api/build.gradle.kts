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
    // api, no implementation: `Sesion` sale en la firma pública de `creaApi`.
    api(project(":data:session"))

    api(libs.kotlinx.serialization.json)
    api(libs.retrofit)
    implementation(libs.retrofit.serialization)
    implementation(libs.okhttp)
    implementation(libs.kotlinx.coroutines.android)

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
