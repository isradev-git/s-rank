@extends('layouts.app')

@section('title', 'Logros')

@section('content')
<div class="pb-24">

    {{-- ── HEADER ─────────────────────────────────────────────────── --}}
    <header style="position:sticky; top:0; z-index:30;
                   background:rgba(10,10,11,0.95); backdrop-filter:blur(16px);
                   border-bottom:1px solid var(--border-light);">
        <div style="display:flex; align-items:center; gap:0.75rem; padding:1rem 1.25rem;">
            <a href="/profile" class="btn btn-ghost btn-icon" aria-label="Volver al perfil">
                <i data-lucide="arrow-left" style="width:1.25rem;height:1.25rem;"></i>
            </a>
            <div style="flex:1;">
                <h1 style="font-size:1.25rem; font-weight:800; margin:0;">Logros</h1>
            </div>
            <span id="achievements-counter"
                  style="font-size:0.8125rem; font-weight:700;
                         background:rgba(167,139,250,0.15);
                         color:#a78bfa; padding:0.25rem 0.75rem;
                         border-radius:9999px; white-space:nowrap;"></span>
        </div>

        {{-- Tabs filtro --}}
        <div class="flex gap-2 overflow-x-auto no-scrollbar" style="padding:0 1.25rem 0.75rem;">
            <button class="pill-tab active" data-filter="all"       onclick="setFilter('all')">Todos</button>
            <button class="pill-tab"        data-filter="unlocked"  onclick="setFilter('unlocked')">Desbloqueados</button>
            <button class="pill-tab"        data-filter="locked"    onclick="setFilter('locked')">Bloqueados</button>
        </div>
    </header>

    <div class="p-5 space-y-5">

        {{-- ── BARRA DE PROGRESO GLOBAL ─────────────────────────── --}}
        <div class="card" id="progress-card" style="padding:1.25rem; display:none;">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(167,139,250,0.12);">
                        <i data-lucide="award" style="width:0.875rem;height:0.875rem;color:#a78bfa;"></i>
                    </div>
                    <span style="font-size:0.9375rem; font-weight:700;">Tu progreso</span>
                </div>
                <span id="progress-text" style="font-size:0.8125rem;color:var(--text-muted);font-weight:600;"></span>
            </div>
            <div class="progress-container">
                <div class="progress-bar" id="progress-bar"
                     role="progressbar" aria-valuemin="0" aria-valuemax="100"
                     style="width:0%; background: linear-gradient(90deg,#a78bfa,#c084fc);"></div>
            </div>
            <p id="progress-message" style="font-size:0.75rem;color:var(--text-muted);margin:0.625rem 0 0;"></p>
        </div>

        {{-- ── SKELETON ─────────────────────────────────────────── --}}
        <div id="achievements-skeleton">
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;">
                @for($i=0;$i<6;$i++)
                <div class="skeleton" style="height:9rem;border-radius:1rem;"></div>
                @endfor
            </div>
        </div>

        {{-- ── GRID DE LOGROS ───────────────────────────────────── --}}
        <div id="achievements-grid"
             style="display:none;grid-template-columns:repeat(2,1fr);gap:0.75rem;"></div>

        {{-- ── EMPTY STATE ──────────────────────────────────────── --}}
        <div id="achievements-empty" style="display:none;">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="lock" style="color:#a78bfa;width:1.75rem;height:1.75rem;"></i>
                </div>
                <p class="empty-state-title">Sin logros aquí</p>
                <p class="empty-state-desc">¡Sigue entrenando para desbloquearlos!</p>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
const $ = id => document.getElementById(id);
let allAchievements = [];
let currentFilter = 'all';

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function loadAchievements() {
    try {
        const res = await apiCall('/achievements');

        allAchievements = res.achievements;
        const unlocked = res.unlocked_count;
        const total    = res.total_count;

        // Counter en header
        $('achievements-counter').textContent = `${unlocked} / ${total}`;

        // Barra de progreso
        const pct = total > 0 ? Math.round((unlocked / total) * 100) : 0;
        $('progress-bar').style.width = pct + '%';
        $('progress-bar').setAttribute('aria-valuenow', pct);
        $('progress-text').textContent = `${unlocked} de ${total}`;

        let msg = '';
        if (pct === 100)      msg = '¡Increíble! Has desbloqueado todos los logros. 🏆';
        else if (pct >= 75)   msg = '¡Casi los tienes todos! Sigue así.';
        else if (pct >= 50)   msg = '¡Más de la mitad conseguidos! Buen trabajo.';
        else if (pct >= 25)   msg = 'Buen comienzo, continúa progresando.';
        else if (unlocked > 0) msg = '¡Ya empezaste! Cada logro cuenta.';
        else                   msg = 'Completa tu primer entrenamiento para empezar.';
        $('progress-message').textContent = msg;
        $('progress-card').style.display = 'block';

        $('achievements-skeleton').style.display = 'none';
        renderAchievements();
        lucide.createIcons();
    } catch(e) {
        $('achievements-skeleton').style.display = 'none';
        console.error(e);
    }
}

function setFilter(f) {
    currentFilter = f;
    document.querySelectorAll('.pill-tab').forEach(b => {
        b.classList.toggle('active', b.dataset.filter === f);
    });
    renderAchievements();
}

function renderAchievements() {
    const filtered = allAchievements.filter(a => {
        if (currentFilter === 'unlocked') return a.unlocked;
        if (currentFilter === 'locked')   return !a.unlocked;
        return true;
    });

    const grid  = $('achievements-grid');
    const empty = $('achievements-empty');

    if (filtered.length === 0) {
        grid.style.display  = 'none';
        empty.style.display = 'block';
        lucide.createIcons();
        return;
    }

    empty.style.display = 'none';
    grid.style.display  = 'grid';

    grid.innerHTML = filtered.map(a => {
        const locked = !a.unlocked;
        const dateStr = a.unlocked_at
            ? new Date(a.unlocked_at).toLocaleDateString('es-ES', {day:'numeric',month:'short',year:'numeric'})
            : null;

        return `<div style="
            border-radius:1rem;
            border:1px solid ${locked ? 'var(--border-light)' : `${a.color}40`};
            background:${locked ? 'var(--bg-card)' : `${a.color}0d`};
            padding:1.25rem 1rem;
            text-align:center;
            opacity:${locked ? '0.5' : '1'};
            transition:var(--transition-base);
            position:relative;
            overflow:hidden;
        ">
            ${!locked ? `<div style="
                position:absolute;top:0;left:0;right:0;height:2px;
                background:linear-gradient(90deg,transparent,${a.color},transparent);
            "></div>` : ''}
            {{-- Icono --}}
            <div style="
                width:3rem;height:3rem;border-radius:50%;
                background:${locked ? 'var(--bg-muted)' : `${a.color}22`};
                border:1.5px solid ${locked ? 'var(--border-light)' : `${a.color}44`};
                margin:0 auto 0.75rem;
                display:flex;align-items:center;justify-content:center;
            ">
                <i data-lucide="${escapeHtml(a.icon)}" style="width:1.375rem;height:1.375rem;color:${locked ? 'var(--text-muted)' : a.color};"></i>
            </div>
            {{-- Nombre --}}
            <p style="font-size:0.8125rem;font-weight:700;
                      color:${locked ? 'var(--text-muted)' : 'var(--text-primary)'};
                      margin:0 0 0.375rem;line-height:1.3;">${escapeHtml(a.name)}</p>
            {{-- Descripción --}}
            <p style="font-size:0.6875rem;color:var(--text-muted);margin:0;line-height:1.4;">${escapeHtml(a.description)}</p>
            {{-- Fecha de desbloqueo --}}
            ${dateStr ? `<p style="font-size:0.625rem;font-weight:600;
                color:${a.color};margin:0.5rem 0 0;opacity:0.9;">
                ✓ ${escapeHtml(dateStr)}</p>` : ''}
            ${locked ? `<div style="position:absolute;bottom:0.5rem;right:0.625rem;">
                <i data-lucide="lock" style="width:0.75rem;height:0.75rem;color:var(--text-muted);opacity:0.5;"></i>
            </div>` : ''}
        </div>`;
    }).join('');

    lucide.createIcons();
}

loadAchievements();
</script>
@endpush
@endsection
