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
