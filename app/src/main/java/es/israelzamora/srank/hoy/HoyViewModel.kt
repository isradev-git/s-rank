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
