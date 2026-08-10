@extends('layouts.app')

@section('content')
<div class="pb-24">

    {{-- ═══════════════════════════════════════════════
         HERO BANNER
    ═══════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden" style="background: linear-gradient(135deg, #1a1400 0%, #0a0a0b 60%); border-bottom: 1px solid var(--border-light);">

        {{-- Decoración fondo --}}
        <div class="absolute inset-0 pointer-events-none">
            <div style="position:absolute; top:-40px; right:-40px; width:220px; height:220px;
                        background: radial-gradient(circle, rgba(245,158,11,0.15) 0%, transparent 70%); border-radius:50%;"></div>
            <div style="position:absolute; bottom:-20px; left:-20px; width:140px; height:140px;
                        background: radial-gradient(circle, rgba(245,158,11,0.07) 0%, transparent 70%); border-radius:50%;"></div>
        </div>

        <div class="absolute -bottom-4 -right-4 opacity-5 pointer-events-none" style="transform: rotate(15deg);">
            <i data-lucide="dumbbell" style="width:14rem; height:14rem; color:#f59e0b;"></i>
        </div>

        <div class="relative z-10 p-6 pb-5">
            {{-- Logo --}}
            <div class="flex items-center gap-2 mb-3">
                <div style="background: var(--color-primary); width:6px; height:28px; border-radius:3px;"></div>
                <h1 style="font-size:1.75rem; font-weight:900; font-style:italic; letter-spacing:-0.04em; color:var(--color-primary); margin:0; text-transform:uppercase;">FitLoop</h1>
            </div>

            {{-- Greeting --}}
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size:0.6875rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em;">Bienvenido de nuevo</p>
                    <h2 style="font-size:1.375rem; font-weight:700; color:var(--text-primary); margin:0; margin-top:0.1rem;" id="user-name">...</h2>
                </div>
                <div id="user-initial"
                     style="width:2.75rem; height:2.75rem; background:var(--bg-muted); border:1.5px solid rgba(245,158,11,0.3);
                            border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center;
                            font-weight:700; font-size:1.125rem; color:var(--color-primary);">-</div>
            </div>

            {{-- Quote --}}
            <p id="daily-quote" style="font-size:0.75rem; color:var(--text-muted); font-style:italic; margin-top:0.75rem; margin-bottom:0; line-height:1.5;"></p>
        </div>
    </div>

    <div class="p-5 space-y-6">

        {{-- ═══════════════════════════════════════════
             STATS ROW
        ═══════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 gap-3">

            {{-- Weekly Goal --}}
            <div class="stat-card" id="stat-goal-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="icon-wrap icon-wrap-md" style="background:var(--color-primary-subtle);">
                        <i data-lucide="target" style="width:1.1rem; height:1.1rem; color:var(--color-primary);"></i>
                    </div>
                    <span id="progress-percent" style="font-size:0.75rem; font-weight:700; color:var(--color-primary);">0%</span>
                </div>
                <div class="stat-value" id="stat-streak">0</div>
                <div class="stat-label">/ <span id="stat-goal">3</span> días</div>
                <div class="progress-container mt-3">
                    <div class="progress-bar" id="goal-progress"
                         role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                         aria-label="Progreso semanal"
                         style="width:0%;"></div>
                </div>
            </div>

            {{-- Monthly Activity --}}
            <div class="stat-card" id="stat-activity-card">
                <div class="flex items-center justify-between mb-3">
                    <div class="icon-wrap icon-wrap-md" style="background:var(--color-blue-subtle);">
                        <i data-lucide="activity" style="width:1.1rem; height:1.1rem; color:var(--color-blue);"></i>
                    </div>
                </div>
                <div class="stat-value" id="stat-minutes">0</div>
                <div class="stat-label">min este mes</div>
                <div style="margin-top:0.75rem; display:flex; align-items:center; gap:0.4rem;">
                    <i data-lucide="zap" style="width:0.875rem; height:0.875rem; color:var(--text-muted);"></i>
                    <span style="font-size:0.75rem; color:var(--text-muted);"><span id="stat-total" style="font-weight:700; color:var(--text-secondary);">0</span> entrenamientos</span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
             CTA – REGISTRAR RUTINA
        ═══════════════════════════════════════════ --}}
        <a href="/training" class="card card-interactive flex items-center gap-4"
           style="background:linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dim) 100%);
                  border:none; box-shadow: var(--shadow-primary-lg); padding:1.25rem 1.5rem;">
            <div style="width:3rem; height:3rem; background:rgba(0,0,0,0.15); border-radius:var(--radius-md);
                        display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="plus" style="width:1.5rem; height:1.5rem; color:#000;"></i>
            </div>
            <div>
                <p style="font-size:1rem; font-weight:800; color:#000; margin:0; letter-spacing:-0.01em;">Registrar Rutina</p>
                <p style="font-size:0.8125rem; color:rgba(0,0,0,0.6); margin:0; margin-top:0.1rem;">Empieza tu entrenamiento de hoy</p>
            </div>
            <div style="margin-left:auto;">
                <i data-lucide="arrow-right" style="width:1.25rem; height:1.25rem; color:rgba(0,0,0,0.5);"></i>
            </div>
        </a>

        {{-- ═══════════════════════════════════════════
             HERRAMIENTAS
        ═══════════════════════════════════════════ --}}
        <div>
            <div class="section-header">
                <span class="section-title">Herramientas</span>
                <a href="/tools/explore" class="section-link">Ver todo</a>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <a href="/tools/1rm" class="card card-interactive p-4 text-center space-y-2" style="padding:1rem 0.75rem;">
                    <div class="icon-wrap icon-wrap-md mx-auto" style="background:var(--color-blue-subtle);">
                        <i data-lucide="calculator" style="width:1rem; height:1rem; color:var(--color-blue);"></i>
                    </div>
                    <div>
                        <p style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); margin:0;">1RM</p>
                        <p style="font-size:0.6875rem; color:var(--text-muted); margin:0; margin-top:0.1rem;">Fuerza máx</p>
                    </div>
                </a>

                <a href="/history" class="card card-interactive p-4 text-center space-y-2" style="padding:1rem 0.75rem;">
                    <div class="icon-wrap icon-wrap-md mx-auto" style="background:var(--color-purple-subtle);">
                        <i data-lucide="history" style="width:1rem; height:1rem; color:var(--color-purple);"></i>
                    </div>
                    <div>
                        <p style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); margin:0;">Historial</p>
                        <p style="font-size:0.6875rem; color:var(--text-muted); margin:0; margin-top:0.1rem;">Registro</p>
                    </div>
                </a>

                <a href="/tools/timer" class="card card-interactive p-4 text-center space-y-2" style="padding:1rem 0.75rem;">
                    <div class="icon-wrap icon-wrap-md mx-auto" style="background:var(--color-teal-subtle);">
                        <i data-lucide="timer" style="width:1rem; height:1rem; color:var(--color-teal);"></i>
                    </div>
                    <div>
                        <p style="font-size:0.8125rem; font-weight:700; color:var(--text-primary); margin:0;">Descanso</p>
                        <p style="font-size:0.6875rem; color:var(--text-muted); margin:0; margin-top:0.1rem;">Temporizador</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
             ENTRENAMIENTOS
        ═══════════════════════════════════════════ --}}
        <div id="workouts-section">
            <div class="section-header">
                <span class="section-title">Entrenamientos</span>
            </div>

            {{-- Pill Tabs --}}
            <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar mb-4">
                <button onclick="filterWorkouts('gym', this)" class="pill-tab active" data-cat="gym">
                    <i data-lucide="dumbbell" style="width:0.75rem; height:0.75rem; display:inline; vertical-align:-1px; margin-right:0.25rem;"></i>Gimnasio
                </button>
                <button onclick="filterWorkouts('home', this)" class="pill-tab" data-cat="home">
                    <i data-lucide="home" style="width:0.75rem; height:0.75rem; display:inline; vertical-align:-1px; margin-right:0.25rem;"></i>Casa
                </button>
                <button onclick="filterWorkouts('calisthenics', this)" class="pill-tab" data-cat="calisthenics">
                    <i data-lucide="user" style="width:0.75rem; height:0.75rem; display:inline; vertical-align:-1px; margin-right:0.25rem;"></i>Calistenia
                </button>
                <button onclick="filterWorkouts('swimming', this)" class="pill-tab" data-cat="swimming">
                    <i data-lucide="waves" style="width:0.75rem; height:0.75rem; display:inline; vertical-align:-1px; margin-right:0.25rem;"></i>Natación
                </button>
            </div>

            {{-- Sub-filter (nivel) --}}
            <div class="flex gap-2 overflow-x-auto no-scrollbar mb-4" id="level-filters" style="display:none;">
            </div>

            {{-- Cards --}}
            <div id="workouts-list" class="space-y-3">
                {{-- Skeleton --}}
                @for($i = 0; $i < 3; $i++)
                <div class="card" style="padding:1rem;">
                    <div class="flex gap-3 items-center">
                        <div class="skeleton" style="width:4rem; height:4rem; border-radius:var(--radius-lg); flex-shrink:0;"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton skeleton-title" style="width:60%;"></div>
                            <div class="skeleton skeleton-text" style="width:85%;"></div>
                            <div class="skeleton skeleton-text" style="width:40%;"></div>
                        </div>
                    </div>
                </div>
                @endfor
            </div>
        </div>

    </div>{{-- /p-5 --}}
</div>

<style>
    .workout-card-img {
        width: 4rem;
        height: 4rem;
        border-radius: var(--radius-lg);
        background-color: var(--bg-muted);
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }
    .workout-card-img::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, transparent 100%);
    }

    /* Mode icon fallback */
    .workout-card-img-fallback {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
</style>
@endsection

@push('scripts')
<script>
    auth.check();

    const QUOTES = [
        { text: '"No pain, no gain."', author: 'Arnold Schwarzenegger' },
        { text: '"Light weight, baby!"', author: 'Ronnie Coleman' },
        { text: '"The Iron never lies to you."', author: 'Henry Rollins' },
        { text: '"Everybody wants to be a bodybuilder but nobody wants to lift no heavy ass weight."', author: 'Ronnie Coleman' },
        { text: '"Pain is temporary, glory is forever."', author: 'Unknown' },
        { text: '"Your body can stand almost anything. It\'s your mind you have to convince."', author: 'Unknown' },
        { text: '"Sé el héroe de tu propia historia."', author: 'FitLoop' },
    ];

    // Init user info
    const user = auth.getUser();
    if (user) {
        document.getElementById('user-name').textContent = user.name.split(' ')[0];
        document.getElementById('user-initial').textContent = user.name.charAt(0).toUpperCase();
    }

    // Random daily quote
    const q = QUOTES[new Date().getDay() % QUOTES.length];
    document.getElementById('daily-quote').textContent = `${q.text} — ${q.author}`;

    // ─── Stats ─────────────────────────────────────
    async function loadStats() {
        try {
            const stats = await apiCall('/stats');
            if (!stats) return;

            document.getElementById('stat-total').textContent   = stats.total_workouts;
            document.getElementById('stat-minutes').textContent = stats.total_minutes;
            document.getElementById('stat-streak').textContent  = stats.weekly_streak;
            document.getElementById('stat-goal').textContent    = stats.weekly_goal;

            const pct = stats.weekly_goal > 0
                ? Math.min(100, Math.round((stats.weekly_streak / stats.weekly_goal) * 100))
                : 0;
            const progressEl = document.getElementById('goal-progress');
            progressEl.style.width = `${pct}%`;
            progressEl.setAttribute('aria-valuenow', pct);
            document.getElementById('progress-percent').textContent = `${pct}%`;
        } catch(e) {
            console.error(e);
        }
    }

    // ─── Templates ─────────────────────────────────
    let allTemplates = [];

    async function loadWorkouts() {
        try {
            const res = await apiCall('/templates?t=' + Date.now());
            allTemplates = Array.isArray(res) ? res : (res.data || []);
            filterWorkouts('gym');
        } catch(e) {
            console.error(e);
            document.getElementById('workouts-list').innerHTML =
                '<p class="text-center text-danger text-sm py-6">Error al cargar entrenamientos.</p>';
        }
    }

    const LEVEL_MAP = {
        'Básico':     { badge: 'badge-success', label: 'Básico' },
        'Intermedio': { badge: 'badge-warning',  label: 'Intermedio' },
        'Avanzado':   { badge: 'badge-danger',   label: 'Avanzado' },
    };

    const MODE_ICONS = {
        gym:          'dumbbell',
        home:         'home',
        calisthenics: 'user',
        swimming:     'waves',
    };

    const MODE_COLORS = {
        gym:          'var(--color-primary)',
        home:         'var(--color-teal)',
        calisthenics: 'var(--color-blue)',
        swimming:     'var(--color-purple)',
    };

    window.filterWorkouts = function(category, btnEl) {
        // Tab UI
        if (!btnEl) {
            btnEl = document.querySelector(`.pill-tab[data-cat="${category}"]`);
        }
        if (btnEl) {
            document.querySelectorAll('.pill-tab').forEach(b => b.classList.remove('active'));
            btnEl.classList.add('active');
        }

        const filtered = allTemplates.filter(t => (t.mode || '').trim() === category);
        const container = document.getElementById('workouts-list');
        container.innerHTML = '';

        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="${MODE_ICONS[category] || 'dumbbell'}" style="width:1.5rem;height:1.5rem;"></i>
                    </div>
                    <p class="empty-state-title">Sin entrenamientos</p>
                    <p class="empty-state-desc">No hay rutinas disponibles para esta categoría aún.</p>
                </div>`;
            lucide.createIcons();
            return;
        }

        filtered.forEach(t => {
            const lv   = LEVEL_MAP[t.level] || { badge: 'badge-blue', label: t.level || 'N/A' };
            const icon = MODE_ICONS[t.mode] || 'dumbbell';
            const col  = MODE_COLORS[t.mode] || 'var(--color-primary)';
            const exCount = t.exercises ? (Array.isArray(t.exercises) ? t.exercises.length : 0) : 0;

            const imgStyle = t.image_url
                ? `background-image:url('${t.image_url}');`
                : '';
            const fallback = t.image_url ? '' : `
                <div class="workout-card-img-fallback">
                    <i data-lucide="${icon}" style="width:1.5rem;height:1.5rem;color:${col};opacity:0.7;"></i>
                </div>`;

            const card = document.createElement('div');
            card.className = 'card card-interactive flex gap-3 items-center animate-fade-in';
            card.style.padding = '0.875rem 1rem';
            card.onclick = () => { window.location.href = `/training?template_id=${t.id}`; };

            card.innerHTML = `
                <div class="workout-card-img" style="${imgStyle}">${fallback}</div>
                <div class="flex-1" style="min-width:0;">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h4 style="font-size:0.9375rem; font-weight:700; color:var(--text-primary); margin:0; line-height:1.3;"
                            class="truncate">${t.name}</h4>
                        <span class="badge ${lv.badge}" style="flex-shrink:0;">${lv.label}</span>
                    </div>
                    <p style="font-size:0.75rem; color:var(--text-muted); margin:0; margin-bottom:0.5rem; line-height:1.4;" class="line-clamp-2">${t.description || ''}</p>
                    <div class="flex items-center gap-3" style="font-size:0.6875rem; color:var(--text-muted);">
                        <span style="display:flex; align-items:center; gap:0.25rem;">
                            <i data-lucide="clock" style="width:0.75rem;height:0.75rem;"></i>
                            ${t.duration_minutes} min
                        </span>
                        <span style="display:flex; align-items:center; gap:0.25rem;">
                            <i data-lucide="layers" style="width:0.75rem;height:0.75rem;"></i>
                            ${exCount} ejercicios
                        </span>
                    </div>
                </div>
                <div style="color:var(--text-muted); flex-shrink:0;">
                    <i data-lucide="chevron-right" style="width:1rem;height:1rem;"></i>
                </div>`;

            container.appendChild(card);
        });

        lucide.createIcons();
    };

    loadStats();
    loadWorkouts();
</script>
@endpush
