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
