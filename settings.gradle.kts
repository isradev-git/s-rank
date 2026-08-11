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
