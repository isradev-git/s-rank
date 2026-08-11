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
