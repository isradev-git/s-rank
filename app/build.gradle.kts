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
            // ponytail: sin R8. El techo es el tamaño del APK, que en 1.1 no
            // importa porque no se publica. Si algún día esto va a Play
            // Store, se activa y se escriben las reglas de kotlinx.serialization
            // que R8 necesita para no romper la reflexión de los DTO.
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

    implementation(project(":core:ui"))
    implementation(project(":core:system"))
    implementation(project(":data:api"))
    implementation(project(":data:session"))
    implementation(project(":feature:auth"))

    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.lifecycle.runtime.compose)

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
