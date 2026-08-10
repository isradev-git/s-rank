@extends('layouts.app')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('content')
<div class="pb-24">

    {{-- Header + Search --}}
    <div style="position:sticky; top:0; z-index:30; background:rgba(10,10,11,0.95);
                backdrop-filter:blur(16px); border-bottom:1px solid var(--border-light); padding:1rem 1.25rem 0;">

        <div class="flex items-center gap-3 mb-3">
            <a href="/" class="btn btn-ghost btn-icon" aria-label="Volver al inicio" style="color:var(--text-muted);">
                <i data-lucide="arrow-left" style="width:1.25rem;height:1.25rem;"></i>
            </a>
            <h1 style="font-size:1.25rem; font-weight:800; margin:0;">Explorador</h1>
        </div>

        {{-- Search --}}
        <div style="position:relative; margin-bottom:0.75rem;">
            <i data-lucide="search" style="position:absolute; left:0.875rem; top:50%; transform:translateY(-50%);
               width:1rem; height:1rem; color:var(--text-muted);"></i>
            <input type="text" id="search-input" placeholder="Buscar ejercicio..."
                   class="input" style="padding-left:2.5rem;"
                   oninput="renderExercises()">
        </div>

        {{-- Filter Pills --}}
        <div class="flex gap-2 overflow-x-auto no-scrollbar pb-3">
            <button onclick="setFilter('all')" class="pill-tab active" data-filter="all">Todas</button>
            <button onclick="setFilter('Pierna')"    class="pill-tab" data-filter="Pierna">🦵 Pierna</button>
            <button onclick="setFilter('Empuje')"    class="pill-tab" data-filter="Empuje">💪 Empuje</button>
            <button onclick="setFilter('Tracción')"  class="pill-tab" data-filter="Tracción">🧗 Tracción</button>
            <button onclick="setFilter('Core')"      class="pill-tab" data-filter="Core">🔥 Core</button>
            <button onclick="setFilter('Cardio')"    class="pill-tab" data-filter="Cardio">🏃 Cardio</button>
            <button onclick="setFilter('Natación')"  class="pill-tab" data-filter="Natación">🏊 Natación</button>
        </div>
    </div>

    <div class="p-5 space-y-3">
        <div id="exercise-grid">
            @for($i = 0; $i < 5; $i++)
            <div class="card skeleton-card" style="height:4.5rem; margin-bottom:0.75rem;"></div>
            @endfor
        </div>
    </div>
</div>

{{-- Exercise Modal --}}
<div id="ex-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title"
     style="display:none; position:fixed; inset:0; z-index:9000;
     background:rgba(0,0,0,0.85); backdrop-filter:blur(8px);
     align-items:flex-end; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:var(--radius-2xl) var(--radius-2xl) 0 0;
                border-top:1px solid var(--border-medium); width:100%; max-width:48rem;
                padding-bottom:env(safe-area-inset-bottom); max-height:85dvh; overflow-y:auto;">

        {{-- Modal Handle --}}
        <div style="width:2.5rem; height:3px; background:var(--bg-muted); border-radius:9999px; margin:0.875rem auto 0;"></div>

        <div class="p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div id="modal-icon" style="width:2.5rem; height:2.5rem; background:var(--bg-muted);
                         border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; font-size:1.25rem;">🏋️</div>
                    <div>
                        <h3 id="modal-title" style="font-size:1rem; font-weight:700; margin:0;"></h3>
                        <p id="modal-muscle" style="font-size:0.75rem; color:var(--text-muted); margin:0;"></p>
                    </div>
                </div>
                <button onclick="closeModal()" class="btn btn-ghost btn-icon" aria-label="Cerrar" style="color:var(--text-muted);">
                    <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
                </button>
            </div>

            <div style="background:var(--bg-surface); border-radius:var(--radius-lg); padding:1rem; margin-bottom:1rem;">
                <p style="font-size:0.6875rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;
                          letter-spacing:0.06em; margin-bottom:0.5rem;">Tu Máximo Reciente</p>
                <div id="history-data" style="font-size:1.25rem; font-weight:800; color:var(--text-primary);">—</div>
            </div>

            {{-- FEAT-5: Gráfica de progreso --}}
            <div id="progress-section" style="margin-bottom:1rem;">
                <p style="font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;
                          letter-spacing:0.06em;margin-bottom:0.5rem;">Progreso de peso</p>
                <div id="progress-loading" style="font-size:0.8125rem;color:var(--text-muted);">Cargando...</div>
                <canvas id="progress-chart" style="display:none; max-height:160px;"></canvas>
                <p id="progress-empty" style="display:none;font-size:0.8125rem;color:var(--text-muted);">
                    Sin registros previos para este ejercicio.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    auth.check();

    let allExercises = [];
    let filteredExercises = [];
    let currentFilter = 'all';

    const EMOJI_MAP = {
        'Pierna':'🦵', 'Empuje':'💪', 'Tracción':'🧗',
        'Core':'🔥', 'Cardio':'🏃', 'Natación':'🏊',
    };

    async function loadExercises() {
        try {
            allExercises = await apiCall('/exercises');
            renderExercises();
        } catch(e) {
            document.getElementById('exercise-grid').innerHTML =
                '<div class="empty-state"><p class="empty-state-title">Error al cargar</p></div>';
        }
    }

    window.renderExercises = function() {
        const grid   = document.getElementById('exercise-grid');
        const search = document.getElementById('search-input').value.toLowerCase();

        const filtered = allExercises.filter(ex => {
            const matchSearch = ex.name.toLowerCase().includes(search) || (ex.muscle_group || '').toLowerCase().includes(search);
            const matchCat    = currentFilter === 'all' || ex.category === currentFilter;
            return matchSearch && matchCat;
        });

        filteredExercises = filtered;

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="search-x" style="width:1.5rem;height:1.5rem;"></i></div>
                    <p class="empty-state-title">Sin resultados</p>
                    <p class="empty-state-desc">Prueba con otro término o categoría.</p>
                </div>`;
            lucide.createIcons();
            return;
        }

        grid.innerHTML = filtered.map((ex, idx) => {
            const emoji = EMOJI_MAP[ex.category] || '🏋️';
            return `
                <div class="card card-interactive flex items-center gap-3 animate-fade-in"
                     style="padding:0.875rem; margin-bottom:0.625rem;"
                     onclick="openModal(${idx})">
                    <div style="width:2.5rem; height:2.5rem; background:var(--bg-muted); border-radius:var(--radius-md);
                                display:flex; align-items:center; justify-content:center; font-size:1.125rem; flex-shrink:0;">
                        ${emoji}
                    </div>
                    <div class="flex-1" style="min-width:0;">
                        <p style="font-size:0.9375rem; font-weight:600; color:var(--text-primary); margin:0;" class="truncate">${ex.name.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>
                        <p style="font-size:0.75rem; color:var(--text-muted); margin:0;">${(ex.muscle_group || '').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>
                    </div>
                    <i data-lucide="chevron-right" style="width:1rem;height:1rem;color:var(--text-muted);flex-shrink:0;"></i>
                </div>`;
        }).join('');

        lucide.createIcons();
    };

    window.setFilter = function(cat) {
        currentFilter = cat;
        document.querySelectorAll('.pill-tab[data-filter]').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === cat);
        });
        renderExercises();
    };

    window.openModal = async function(idx) {
        const ex = filteredExercises[idx];
        if (!ex) return;
        const modal = document.getElementById('ex-modal');
        document.getElementById('modal-title').textContent  = ex.name;
        document.getElementById('modal-muscle').textContent = ex.muscle_group || '';
        document.getElementById('modal-icon').textContent   = EMOJI_MAP[ex.category] || '🏋️';
        document.getElementById('history-data').textContent = '⏳ Cargando...';
        document.getElementById('progress-loading').style.display = 'block';
        document.getElementById('progress-chart').style.display   = 'none';
        document.getElementById('progress-empty').style.display   = 'none';
        modal.style.display = 'flex';
        lucide.createIcons();

        // Cargar último máximo
        try {
            const h = await apiCall(`/exercises/history?name=${encodeURIComponent(ex.name)}`);
            document.getElementById('history-data').innerHTML = h
                ? `<span style="color:var(--color-primary)">${h.weight_kg} kg</span> <span style="font-size:0.875rem;color:var(--text-secondary);">× ${h.reps} reps</span>`
                : '<span style="font-size:0.875rem;color:var(--text-muted);">Sin registros previos</span>';
        } catch(e) {
            document.getElementById('history-data').textContent = '—';
        }

        // Cargar gráfica de progreso (FEAT-5)
        try {
            const progress = await apiCall(`/exercises/progress?name=${encodeURIComponent(ex.name)}`);
            document.getElementById('progress-loading').style.display = 'none';
            if (!progress || progress.length === 0) {
                document.getElementById('progress-empty').style.display = 'block';
            } else {
                const canvas = document.getElementById('progress-chart');
                canvas.style.display = 'block';
                if (window._progressChart) window._progressChart.destroy();
                window._progressChart = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: progress.map(p => new Date(p.date).toLocaleDateString('es-ES', { day:'numeric', month:'short' })),
                        datasets: [{
                            label: 'Peso (kg)',
                            data: progress.map(p => p.weight_kg),
                            borderColor: 'var(--color-primary)',
                            backgroundColor: 'rgba(245,158,11,0.12)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointBackgroundColor: 'var(--color-primary)',
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { ticks: { color: '#71717a', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                            y: { ticks: { color: '#71717a', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } }
                        }
                    }
                });
            }
        } catch(e) {
            document.getElementById('progress-loading').style.display = 'none';
        }
    };

    window.closeModal = function() {
        document.getElementById('ex-modal').style.display = 'none';
    };

    document.getElementById('ex-modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    loadExercises();
</script>
@endpush
