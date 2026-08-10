@extends('layouts.app')

@push('styles')
<style>
/* ═══ TRAINING MODE STYLES ═══════════════════════════════════════════════ */
.training-phase { display:none; }
.training-phase.active { display:block; }

/* ── Selector de templates ────────────────────────────────────────────── */
.mode-tabs {
    display:flex; gap:0.5rem; overflow-x:auto; padding:0.75rem 1.25rem;
    border-bottom:1px solid var(--border-light);
}
.mode-tabs::-webkit-scrollbar { display:none; }

.training-selector-body {
    padding:1rem 1.25rem 1.5rem;
    display:flex;
    flex-direction:column;
    gap:0.875rem;
}

.quick-actions-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:0.625rem;
}

.quick-action-btn {
    border-radius:0.9rem;
    border:1px solid var(--border-light);
    background:linear-gradient(135deg, rgba(245,158,11,0.12), rgba(245,158,11,0.03));
    color:var(--text-primary);
    padding:0.75rem;
    display:flex;
    align-items:center;
    gap:0.625rem;
    cursor:pointer;
    transition:var(--transition-fast);
    min-width:0;
}

.quick-action-btn:hover {
    border-color:rgba(245,158,11,0.45);
    transform:translateY(-1px);
}

.quick-action-btn:active { transform:scale(0.98); }

.quick-action-title {
    font-size:0.8125rem;
    font-weight:800;
    margin:0;
    line-height:1.2;
}

.quick-action-desc {
    margin:0.125rem 0 0;
    font-size:0.6875rem;
    color:var(--text-muted);
}

.template-toolbar {
    border:1px solid var(--border-light);
    border-radius:1rem;
    background:var(--bg-card);
    padding:0.875rem;
    display:flex;
    flex-direction:column;
    gap:0.75rem;
}

.template-search-wrap {
    display:flex;
    align-items:center;
    gap:0.5rem;
    border:1px solid var(--border-light);
    border-radius:0.75rem;
    background:var(--bg-muted);
    padding:0 0.625rem;
}

.template-search {
    width:100%;
    border:none;
    background:transparent;
    color:var(--text-primary);
    font-size:0.8125rem;
    padding:0.625rem 0;
    outline:none;
}

.template-search::placeholder { color:var(--text-muted); }

.level-filter-row {
    display:flex;
    gap:0.5rem;
    overflow-x:auto;
    padding-bottom:0.125rem;
}

.level-filter-row::-webkit-scrollbar { display:none; }

.level-chip {
    border:1px solid var(--border-light);
    background:var(--bg-muted);
    color:var(--text-muted);
    border-radius:999px;
    padding:0.42rem 0.72rem;
    font-size:0.75rem;
    font-weight:700;
    white-space:nowrap;
    cursor:pointer;
    transition:var(--transition-fast);
}

.level-chip.active {
    background:rgba(245,158,11,0.18);
    border-color:rgba(245,158,11,0.5);
    color:var(--color-primary);
}

.template-metrics {
    display:grid;
    grid-template-columns:repeat(3, minmax(0,1fr));
    gap:0.5rem;
}

.template-metric {
    background:var(--bg-muted);
    border:1px solid var(--border-light);
    border-radius:0.65rem;
    padding:0.5rem;
    text-align:center;
}

.template-metric-value {
    margin:0;
    font-size:0.95rem;
    font-weight:800;
    color:var(--text-primary);
}

.template-metric-label {
    margin:0.1rem 0 0;
    font-size:0.625rem;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:0.05em;
}

.template-card {
    background:var(--bg-card); border:1.5px solid var(--border-light);
    border-radius:1rem; padding:1rem 1.25rem; cursor:pointer;
    transition:var(--transition-fast); display:flex; align-items:center; gap:1rem;
    width:100%;
    overflow:hidden;
}
.template-card:hover { border-color:rgba(245,158,11,0.4); background:rgba(245,158,11,0.04); }
.template-card:active { transform:scale(0.98); }

.template-card-main {
    flex:1; display:flex; align-items:center; gap:1rem;
    border:none; background:transparent; padding:0; margin:0;
    color:inherit; text-align:left; cursor:pointer;
    min-width:0;
}

.template-actions {
    display:flex; align-items:center; gap:0.5rem; margin-left:auto;
    flex-shrink:0;
}

.template-edit-btn {
    width:2.1rem; height:2.1rem; border-radius:0.7rem;
    border:1px solid var(--border-light); background:var(--bg-muted);
    color:var(--text-muted); display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:var(--transition-fast); flex-shrink:0;
}

.template-edit-btn:hover {
    border-color:rgba(245,158,11,0.5);
    color:var(--color-primary);
    background:rgba(245,158,11,0.12);
}

.template-card-v2 {
    flex-direction:column;
    align-items:stretch;
    gap:0.75rem;
    padding:0.95rem;
}

.template-card-head {
    display:flex;
    gap:0.7rem;
    align-items:flex-start;
    min-width:0;
}

.template-card-copy {
    flex:1;
    min-width:0;
    overflow:hidden;
}

.template-card-title-row {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:0.5rem;
}

.template-card-title {
    margin:0;
    font-size:0.95rem;
    font-weight:800;
    line-height:1.25;
    color:var(--text-primary);
}

.template-card-desc {
    margin:0.3rem 0 0;
    font-size:0.75rem;
    color:var(--text-muted);
    line-height:1.4;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

.template-card-meta {
    display:flex;
    gap:0.45rem;
    flex-wrap:wrap;
    margin-top:0.5rem;
}

.template-pill {
    font-size:0.6875rem;
    font-weight:700;
    padding:0.22rem 0.55rem;
    border-radius:999px;
    background:var(--bg-muted);
    color:var(--text-muted);
    border:1px solid var(--border-light);
}

.template-card-preview {
    margin:0;
    font-size:0.6875rem;
    color:var(--text-muted);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.template-card-foot {
    display:flex;
    align-items:center;
    gap:0.5rem;
    min-width:0;
}

.template-card-foot .btn {
    height:2.15rem;
    font-size:0.75rem;
}

.template-card-foot .btn.btn-outline {
    flex:1;
}

.template-card-foot .btn.btn-primary {
    flex:1.2;
}

.template-card-foot .template-edit-btn {
    width:2.15rem;
    height:2.15rem;
}

@media (max-width:380px) {
    .quick-actions-grid { grid-template-columns:1fr; }
    .template-metrics { grid-template-columns:1fr 1fr; }
}

/* ── Pantalla de entrenamiento activo ────────────────────────────────── */
#training-screen {
    display:flex; flex-direction:column; height:100dvh;
    height:calc(100dvh - env(safe-area-inset-bottom));
    overflow:hidden; background:var(--bg-background);
}

.training-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:1rem 1.25rem 0.75rem;
    background:rgba(10,10,11,0.95); backdrop-filter:blur(12px);
    border-bottom:1px solid var(--border-light); flex-shrink:0;
    position:relative;
}

.training-elapsed {
    font-size:1.5rem; font-weight:900; font-variant-numeric:tabular-nums;
    color:var(--color-primary); letter-spacing:-0.02em;
}

/* Barra de progreso de ejercicios */
.exercise-progress-bar {
    height:3px; background:var(--bg-muted); flex-shrink:0;
    position:relative;
}
.exercise-progress-bar .fill {
    height:100%; background:var(--color-primary);
    transition:width 0.35s ease; border-radius:9999px;
}

.exercise-dot {
    transition:all 0.25s;
}

.exercise-dot-active {
    box-shadow:0 0 0.5rem rgba(245,158,11,0.4);
}

.exercise-dot-pulse {
    animation:exerciseDotPulse 0.42s ease;
}

@keyframes exerciseDotPulse {
    0% { transform:scale(1); }
    50% { transform:scale(1.18); }
    100% { transform:scale(1); }
}

/* Scroll area del ejercicio activo */
.training-body {
    flex:1; overflow-y:auto; padding:1.25rem;
    display:flex; flex-direction:column; gap:1rem;
    padding-bottom:11.5rem;
}

.exercise-rail {
    display:flex;
    gap:0.5rem;
    overflow-x:auto;
    padding:0.5rem 1.25rem 0.7rem;
    background:var(--bg-background);
    border-bottom:1px solid var(--border-light);
}

.exercise-rail::-webkit-scrollbar { display:none; }

.exercise-rail-card {
    min-width:8.8rem;
    max-width:11rem;
    border:1px solid var(--border-light);
    background:var(--bg-card);
    border-radius:0.8rem;
    padding:0.45rem 0.6rem;
    cursor:pointer;
    transition:var(--transition-fast);
    text-align:left;
}

.exercise-rail-card.active {
    border-color:rgba(245,158,11,0.5);
    background:rgba(245,158,11,0.12);
}

.exercise-rail-card.done {
    border-color:rgba(34,197,94,0.4);
}

.exercise-rail-card:active { transform:scale(0.98); }

.exercise-rail-label {
    margin:0;
    font-size:0.625rem;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:0.05em;
}

.exercise-rail-name {
    margin:0.15rem 0 0;
    font-size:0.75rem;
    font-weight:700;
    color:var(--text-primary);
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.exercise-rail-meta {
    margin:0.15rem 0 0;
    font-size:0.6875rem;
    color:var(--text-muted);
}

.exercise-context-card {
    border:1px solid var(--border-light);
    background:linear-gradient(180deg, rgba(245,158,11,0.08), rgba(24,24,27,0.7));
    border-radius:1rem;
    padding:0.9rem;
}

.exercise-goals-row {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:0.5rem;
    margin-top:0.7rem;
}

.exercise-goal-chip {
    border:1px solid var(--border-light);
    background:rgba(24,24,27,0.78);
    border-radius:0.65rem;
    padding:0.45rem 0.5rem;
    text-align:center;
    min-width:0;
}

.exercise-goal-label {
    margin:0;
    font-size:0.625rem;
    color:var(--text-muted);
    text-transform:uppercase;
    letter-spacing:0.05em;
}

.exercise-goal-value {
    margin:0.12rem 0 0;
    font-size:0.8rem;
    color:var(--text-primary);
    font-weight:800;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.exercise-name-header {
    font-size:1.5rem; font-weight:900; color:var(--text-primary);
    line-height:1.2; margin:0;
}

.set-row {
    display:grid; grid-template-columns:1.5rem minmax(0, 1fr) auto;
    align-items:center; gap:0.75rem;
    padding:0.75rem 0;
    border-bottom:1px solid var(--border-light);
    animation:fadeIn 0.2s ease;
    cursor:pointer;
}
@keyframes fadeIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:none} }

.set-row-saved {
    animation:setSavedFlash 0.9s ease;
}

@keyframes setSavedFlash {
    0% { background:transparent; }
    20% { background:rgba(245,158,11,0.18); }
    100% { background:transparent; }
}

.sets-card-feedback {
    animation:setsCardPulse 0.28s ease;
}

@keyframes setsCardPulse {
    0% { box-shadow:0 0 0 rgba(245,158,11,0); border-color:var(--border-light); }
    50% { box-shadow:0 0 0.75rem rgba(245,158,11,0.25); border-color:rgba(245,158,11,0.5); }
    100% { box-shadow:0 0 0 rgba(245,158,11,0); border-color:var(--border-light); }
}

.counter-bump {
    animation:counterBump 0.28s ease;
}

@keyframes counterBump {
    0% { transform:scale(1); color:var(--text-muted); }
    45% { transform:scale(1.12); color:var(--color-primary); }
    100% { transform:scale(1); color:var(--text-muted); }
}

.set-number { font-size:0.75rem; font-weight:700; color:var(--text-muted); text-align:center; }
.set-main { min-width:0; }
.set-primary  { font-size:0.9375rem; font-weight:700; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.set-meta    { font-size:0.75rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.set-delete  {
    width:1.5rem; height:1.5rem; border:none; background:none;
    color:var(--text-muted); cursor:pointer; display:flex;
    align-items:center; justify-content:center; border-radius:50%;
    transition:var(--transition-fast);
}
.set-delete:hover { color:var(--color-danger); background:rgba(239,68,68,0.1); }

#sets-card { position:relative; min-height:8.8rem; }

.sets-empty-state {
    display:flex;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    gap:0.4rem;
    text-align:center;
    padding:1rem 0.25rem;
}

.sets-empty-state-title {
    margin:0;
    font-size:0.875rem;
    font-weight:700;
    color:var(--text-primary);
}

.sets-empty-state-desc {
    margin:0;
    font-size:0.75rem;
    color:var(--text-muted);
}

.set-inline-feedback {
    position:absolute; top:0.55rem; right:0.65rem;
    font-size:0.6875rem; font-weight:700; letter-spacing:0.01em;
    padding:0.25rem 0.5rem; border-radius:9999px;
    border:1px solid transparent;
    background:rgba(245,158,11,0.14); color:var(--color-primary);
    opacity:0; transform:translateY(-4px) scale(0.98);
    pointer-events:none;
}

.set-inline-feedback.show {
    animation:setInlinePop 0.9s ease;
}

.exercise-inline-feedback {
    display:inline-flex; align-items:center;
    margin-top:0.45rem;
    font-size:0.6875rem; font-weight:700;
    padding:0.2rem 0.5rem; border-radius:9999px;
    border:1px solid rgba(59,130,246,0.25);
    background:rgba(59,130,246,0.12);
    color:var(--color-blue);
    opacity:0; transform:translateY(-3px) scale(0.98);
    pointer-events:none;
}

.exercise-inline-feedback.show {
    animation:exerciseInlinePop 0.85s ease;
}

.exercise-inline-feedback.add {
    background:rgba(34,197,94,0.12);
    color:var(--color-success);
    border-color:rgba(34,197,94,0.22);
}

@keyframes exerciseInlinePop {
    0% { opacity:0; transform:translateY(-5px) scale(0.96); }
    20% { opacity:1; transform:translateY(0) scale(1); }
    75% { opacity:1; transform:translateY(0) scale(1); }
    100% { opacity:0; transform:translateY(-2px) scale(0.98); }
}

.set-inline-feedback.update {
    background:rgba(59,130,246,0.14);
    color:var(--color-blue);
    border-color:rgba(59,130,246,0.25);
}

.set-inline-feedback.delete {
    background:rgba(239,68,68,0.12);
    color:#f87171;
    border-color:rgba(239,68,68,0.25);
}

@keyframes setInlinePop {
    0% { opacity:0; transform:translateY(-6px) scale(0.96); }
    18% { opacity:1; transform:translateY(0) scale(1); }
    75% { opacity:1; transform:translateY(0) scale(1); }
    100% { opacity:0; transform:translateY(-3px) scale(0.98); }
}

/* Botón añadir serie + (grande, prominent) */
.btn-add-set {
    background:rgba(245,158,11,0.1); border:1.5px dashed rgba(245,158,11,0.4);
    border-radius:1rem; padding:1rem; width:100%;
    color:var(--color-primary); font-size:0.9375rem; font-weight:700;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    gap:0.5rem; transition:var(--transition-fast);
}
.btn-add-set:hover { background:rgba(245,158,11,0.18); border-color:rgba(245,158,11,0.6); }
.btn-add-set:active { transform:scale(0.98); }

.add-set-fixed {
    position:fixed;
    left:50%;
    transform:translateX(-50%);
    bottom:4.9rem;
    width:100%;
    max-width:48rem;
    z-index:240;
    padding:0 1.25rem;
    pointer-events:none;
}

.add-set-fixed .btn-add-set {
    pointer-events:auto;
    box-shadow:0 0.45rem 1rem rgba(0,0,0,0.35);
}

/* Navegación entre ejercicios */
.exercise-nav {
    position:fixed; bottom:0; left:50%; transform:translateX(-50%);
    width:100%; max-width:48rem; z-index:200;
    padding:0.75rem 1.25rem; padding-bottom:calc(0.75rem + env(safe-area-inset-bottom));
    background:rgba(10,10,11,0.97); backdrop-filter:blur(16px);
    border-top:1px solid var(--border-light);
    display:flex; gap:0.75rem; align-items:center;
}

/* Sheet de añadir serie */
.add-set-sheet {
    display:none; position:fixed; inset:0; z-index:9100;
    background:rgba(0,0,0,0.8); backdrop-filter:blur(8px);
    align-items:flex-end; justify-content:center;
}
.add-set-sheet.open { display:flex; }
.add-set-content {
    background:var(--bg-card); border-radius:1.5rem 1.5rem 0 0;
    border-top:1px solid var(--border-medium); width:100%; max-width:48rem;
    padding:1.25rem 1.5rem; padding-bottom:calc(1.5rem + env(safe-area-inset-bottom));
}

.template-preview-sheet,
.workout-summary-sheet {
    display:none; position:fixed; inset:0; z-index:9150;
    background:rgba(0,0,0,0.82); backdrop-filter:blur(8px);
    align-items:flex-end; justify-content:center;
}
.template-preview-sheet.open,
.workout-summary-sheet.open { display:flex; }

.sheet-content-lg {
    background:var(--bg-card); border-radius:1.5rem 1.5rem 0 0;
    border-top:1px solid var(--border-medium); width:100%; max-width:48rem;
    max-height:84dvh; overflow-y:auto;
    padding:1.25rem 1.5rem; padding-bottom:calc(1.5rem + env(safe-area-inset-bottom));
}

.inline-suggestions {
    margin-top:0.5rem; display:none; flex-direction:column; gap:0.25rem;
    max-height:11rem; overflow-y:auto;
}

.inline-suggestions.open { display:flex; }

.inline-suggestion {
    border:1px solid var(--border-light); background:var(--bg-muted);
    border-radius:0.65rem; padding:0.55rem 0.7rem;
    color:var(--text-primary); font-size:0.8125rem; text-align:left;
    cursor:pointer; transition:var(--transition-fast);
}

.inline-suggestion:hover {
    border-color:rgba(245,158,11,0.4);
    background:rgba(245,158,11,0.12);
}

/* Timer de descanso en training */
.rest-overlay {
    display:none; position:fixed; inset:0; z-index:9200;
    background:rgba(0,0,0,0.92); backdrop-filter:blur(16px);
    align-items:center; justify-content:center; flex-direction:column; gap:1rem;
    text-align:center;
}
.rest-overlay.open { display:flex; }
.rest-countdown {
    font-size:5rem; font-weight:900; font-variant-numeric:tabular-nums;
    color:var(--color-primary); line-height:1;
    transition:color 0.3s;
}
.rest-countdown.ending { color:#f87171; }

/* ── Tabla por ejercicio (rediseño) ──────────────────────────────────── */
.training-subprogress { display:flex; justify-content:space-between; padding:0.5rem 1.25rem; font-size:0.6875rem; color:var(--text-muted); background:var(--bg-background); }
.exercise-table-card { background:var(--bg-card); border:1px solid var(--border-light); border-radius:1rem; padding:0.85rem 0.9rem; margin-bottom:0.85rem; }
.exercise-table-card.active { border-color:rgba(245,158,11,0.4); box-shadow:0 0 0 1px rgba(245,158,11,0.15); }
.etc-head { display:flex; align-items:center; gap:0.5rem; }
.etc-name { font-size:0.95rem; font-weight:800; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.etc-name-edit { display:inline-flex; align-items:center; gap:0.4rem; background:none; border:none; color:inherit; text-align:left; cursor:pointer; padding:0; font:inherit; }
.etc-name-edit:active { opacity:0.7; }
.etc-pr { background:rgba(245,158,11,0.12); color:var(--color-primary); font-size:0.625rem; font-weight:800; padding:0.15rem 0.4rem; border-radius:0.35rem; white-space:nowrap; }
.etc-kebab { color:var(--text-muted); background:none; border:none; font-size:1rem; cursor:pointer; padding:0 0.25rem; line-height:1; }
.etc-sub { font-size:0.6875rem; color:var(--text-muted); margin:0.15rem 0 0.5rem; }
.exercise-collapsed .cnt { font-size:0.75rem; color:var(--text-muted); font-weight:700; }
.set-table { width:100%; border-collapse:collapse; }
.set-table th { font-size:0.5625rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em; padding-bottom:0.3rem; text-align:center; }
.set-table th.l { text-align:left; }
.set-table td { font-size:0.8125rem; text-align:center; padding:0.35rem 0.15rem; border-top:1px solid var(--border-light); font-variant-numeric:tabular-nums; }
.set-table td.idx { color:var(--text-muted); font-weight:700; }
.set-table tr.done td { color:var(--color-success); background:rgba(34,197,94,0.05); }
.set-table td.prev { color:var(--text-muted); font-size:0.6875rem; }
.set-cell-input { width:3rem; max-width:100%; text-align:center; background:var(--bg-muted); border:1px solid var(--border-light); border-radius:0.4rem; padding:0.25rem; font-size:0.8125rem; font-weight:700; color:var(--text-primary); }
.set-cell-input.rpe { width:2.4rem; }
.set-cell-input:focus { border-color:var(--color-primary); outline:none; }
.set-check { width:1.4rem; height:1.4rem; border-radius:0.4rem; border:1.5px solid var(--border-medium); background:none; color:#000; font-weight:900; cursor:pointer; }
.set-check.on { background:var(--color-success); border-color:var(--color-success); }
.etc-addset { color:var(--color-primary); font-size:0.75rem; font-weight:700; text-align:center; padding:0.5rem 0 0.1rem; cursor:pointer; }
.addex-btn { display:block; width:100%; margin:0.25rem 0 1rem; padding:0.7rem; border:1.5px dashed var(--border-medium); border-radius:0.9rem; background:none; color:var(--text-secondary); font-weight:700; font-size:0.8125rem; cursor:pointer; }
.rest-bar { position:fixed; left:50%; transform:translateX(-50%); bottom:0; width:100%; max-width:48rem; z-index:250; display:flex; align-items:center; gap:0.6rem; padding:0.7rem 1.25rem; padding-bottom:calc(0.7rem + env(safe-area-inset-bottom)); background:#1a1206; border-top:1px solid rgba(245,158,11,0.35); }
.rest-bar-label { font-size:0.75rem; font-weight:800; color:var(--color-primary); flex:1; }
.rest-bar-time { font-size:1rem; font-weight:900; color:var(--color-primary); font-variant-numeric:tabular-nums; }
.rest-bar-btn { font-size:0.6875rem; color:var(--text-secondary); border:1px solid var(--border-medium); border-radius:0.4rem; padding:0.25rem 0.5rem; background:none; cursor:pointer; }
.rest-bar-btn.primary { color:#000; background:var(--color-primary); border-color:var(--color-primary); font-weight:800; }
.resume-banner { display:flex; align-items:center; gap:0.6rem; background:#1a1206; border:1px solid rgba(245,158,11,0.35); border-radius:0.9rem; padding:0.7rem 0.85rem; margin-bottom:0.85rem; cursor:pointer; }
.resume-banner .rb-tx { flex:1; min-width:0; }
.resume-banner .rb-tx b { font-size:0.8125rem; display:block; }
.resume-banner .rb-tx span { font-size:0.6875rem; color:var(--text-muted); }
.resume-banner .rb-go { background:var(--color-primary); color:#000; font-size:0.6875rem; font-weight:800; padding:0.3rem 0.6rem; border-radius:0.45rem; flex-shrink:0; }
</style>
@endpush

@section('content')

{{-- ═══ FASE 1: SELECCIÓN DE PLANTILLA ════════════════════════════════════ --}}
<div id="phase-select" class="training-phase active pb-24">

    {{-- Header --}}
    <div style="position:sticky;top:0;z-index:30;background:rgba(10,10,11,0.95);
                backdrop-filter:blur(16px);border-bottom:1px solid var(--border-light);">
        <div style="display:flex;align-items:center;gap:0.75rem;padding:1rem 1.25rem 0.875rem;">
            <a href="/" class="btn btn-ghost btn-icon" aria-label="Volver">
                <i data-lucide="arrow-left" style="width:1.25rem;height:1.25rem;"></i>
            </a>
            <div>
                <h1 style="font-size:1.25rem;font-weight:800;margin:0;">Entrenar</h1>
                <p style="font-size:0.6875rem;color:var(--text-muted);margin:0;">Elige una rutina o empieza libre</p>
            </div>
        </div>

        {{-- Tabs de modo --}}
        <div class="mode-tabs no-scrollbar">
            <button class="pill-tab active" data-mode="gym" onclick="filterMode('gym')">🏋️ Gym</button>
            <button class="pill-tab" data-mode="calisthenics" onclick="filterMode('calisthenics')">🤸 Calistenia</button>
            <button class="pill-tab" data-mode="home" onclick="filterMode('home')">🏠 Casa</button>
            <button class="pill-tab" data-mode="swimming" onclick="filterMode('swimming')">🏊 Natación</button>
            <button class="pill-tab" data-mode="pasos" onclick="filterMode('pasos')">👟 Pasos</button>
        </div>
    </div>

    <div class="training-selector-body">
        <div id="resume-banner" class="resume-banner" style="display:none;" onclick="resumeDraft()">
            <span class="rb-ic"><i data-lucide="pause" style="width:1.05rem;height:1.05rem;color:var(--color-primary);"></i></span>
            <div class="rb-tx"><b>Entreno sin terminar</b><span id="resume-meta"></span></div>
            <span class="rb-go">Retomar</span>
        </div>
        <div class="quick-actions-grid">
            <button onclick="repeatLastWorkout()" class="quick-action-btn" aria-label="Repetir último entreno">
                <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.16);flex-shrink:0;">
                    <i data-lucide="rotate-cw" style="width:0.95rem;height:0.95rem;color:var(--color-primary);"></i>
                </div>
                <div style="min-width:0;">
                    <p class="quick-action-title">Repetir último</p>
                    <p class="quick-action-desc">Tu último de este modo</p>
                </div>
            </button>

            <button onclick="startFreeWorkout()" class="quick-action-btn" aria-label="Iniciar entrenamiento libre">
                <div class="icon-wrap icon-wrap-sm" style="background:rgba(59,130,246,0.16);flex-shrink:0;">
                    <i data-lucide="zap" style="width:0.95rem;height:0.95rem;color:var(--color-blue);"></i>
                </div>
                <div style="min-width:0;">
                    <p class="quick-action-title">Entreno libre</p>
                    <p class="quick-action-desc">En blanco, 1 toque</p>
                </div>
            </button>
        </div>

        <div class="template-toolbar">
            <div class="template-search-wrap">
                <i data-lucide="search" style="width:0.9rem;height:0.9rem;color:var(--text-muted);"></i>
                <input id="template-search-input"
                       class="template-search"
                       placeholder="Buscar por rutina o ejercicio"
                       autocomplete="off"
                       oninput="handleTemplateSearch(this.value)">
                <button onclick="clearTemplateFilters()" class="btn btn-ghost btn-sm" style="padding:0.2rem 0.45rem;height:auto;font-size:0.6875rem;">Limpiar</button>
            </div>

            <div class="level-filter-row" id="level-filter-row">
                <button class="level-chip active" data-level="all" onclick="setLevelFilter('all', this)">Todos</button>
                <button class="level-chip" data-level="Básico" onclick="setLevelFilter('Básico', this)">Básico</button>
                <button class="level-chip" data-level="Intermedio" onclick="setLevelFilter('Intermedio', this)">Intermedio</button>
                <button class="level-chip" data-level="Avanzado" onclick="setLevelFilter('Avanzado', this)">Avanzado</button>
            </div>

            <div class="template-metrics">
                <div class="template-metric">
                    <p class="template-metric-value" id="metric-visible-templates">0</p>
                    <p class="template-metric-label">Mostradas</p>
                </div>
                <div class="template-metric">
                    <p class="template-metric-value" id="metric-mode-templates">0</p>
                    <p class="template-metric-label">En este modo</p>
                </div>
                <div class="template-metric">
                    <p class="template-metric-value" id="metric-custom-templates">0</p>
                    <p class="template-metric-label">Personalizadas</p>
                </div>
            </div>
        </div>

        {{-- Skeleton --}}
        <div id="templates-skeleton">
            @for($i=0;$i<4;$i++)
            <div class="skeleton" style="height:7.2rem;border-radius:1rem;"></div>
            @endfor
        </div>

        {{-- Lista de plantillas --}}
        <div id="templates-list" style="display:none;display:flex;flex-direction:column;gap:0.75rem;"></div>
    </div>
</div>

{{-- ═══ FASE 2: MODO ACTIVO ═════════════════════════════════════════════════ --}}
<div id="training-screen" style="display:none;">

    {{-- Cabecera con timer --}}
    <div class="training-header">
        <button onclick="confirmAbort()" class="btn btn-ghost btn-sm"
                style="color:var(--color-danger);font-size:0.8125rem;font-weight:700;">
            Cancelar
        </button>
        <div style="text-align:center;">
            <div class="training-elapsed" id="elapsed-timer">0:00</div>
            <div id="training-name"
                 style="font-size:0.6875rem;color:var(--text-muted);margin-top:0.125rem;max-width:160px;
                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:center;"></div>
        </div>
        <button onclick="finishWorkout()"
                class="btn btn-primary btn-sm" id="btn-finish"
                style="font-size:0.8125rem;font-weight:700;">
            Terminar
        </button>
    </div>

    {{-- Barra de progreso de ejercicios --}}
    <div class="exercise-progress-bar">
        <div class="fill" id="exercise-progress-fill" style="width:0%;"></div>
    </div>
    <div class="training-subprogress">
        <span id="sets-progress">0 de 0 series</span>
        <span id="volume-progress">0 kg</span>
    </div>

    {{-- Tabla por ejercicio (scroll) --}}
    <div class="training-body" id="exercises-container"></div>

    {{-- Barra de descanso fina --}}
    <div id="rest-bar" class="rest-bar" style="display:none;">
        <span class="rest-bar-label">Descanso</span>
        <span class="rest-bar-time" id="rest-bar-time">0:00</span>
        <button class="rest-bar-btn" onclick="adjustRestBar(-15)">−15</button>
        <button class="rest-bar-btn" onclick="adjustRestBar(15)">+15</button>
        <button class="rest-bar-btn primary" onclick="skipRestBar()">Saltar ▸</button>
    </div>
</div>

{{-- ═══ SHEET: AÑADIR SERIE ════════════════════════════════════════════════ --}}
<div id="add-set-sheet" class="add-set-sheet" role="dialog" aria-modal="true" aria-label="Añadir serie">
    <div class="add-set-content">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h3 style="font-size:1rem;font-weight:800;margin:0;" id="add-set-title">Serie</h3>
            <button onclick="closeAddSet()" class="btn btn-ghost btn-icon" aria-label="Cerrar">
                <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
            </button>
        </div>

        {{-- Indicadores de última serie --}}
        <div id="last-set-hint"
             style="display:none;background:var(--bg-muted);border-radius:0.75rem;
                    padding:0.625rem 0.875rem;margin-bottom:1rem;font-size:0.75rem;color:var(--text-muted);">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.875rem;margin-bottom:1.25rem;">
            {{-- Peso --}}
            <div class="form-group" style="margin:0;">
                <label class="input-label">Peso (kg)</label>
                <input type="number" id="set-weight" class="input" min="0" max="1000"
                       step="0.5" placeholder="0" inputmode="decimal" style="font-size:1.25rem;font-weight:700;text-align:center;">
            </div>
            {{-- Reps --}}
            <div class="form-group" style="margin:0;">
                <label class="input-label">Repeticiones</label>
                <input type="number" id="set-reps" class="input" min="0" max="999"
                       step="1" placeholder="0" inputmode="numeric" style="font-size:1.25rem;font-weight:700;text-align:center;">
            </div>
        </div>

        {{-- RPE --}}
        <div style="margin-bottom:1rem;">
            <label class="input-label" style="display:block;margin-bottom:0.5rem;">
                Esfuerzo percibido (RPE) — <span id="rpe-label" style="color:var(--color-primary);">Sin definir</span>
            </label>
            <div style="display:flex;gap:0.375rem;">
                @for($r=1;$r<=10;$r++)
                <button class="rpe-btn" data-rpe="{{ $r }}" onclick="setRpe({{ $r }})"
                        style="flex:1;padding:0.5rem 0;border-radius:0.5rem;font-size:0.75rem;font-weight:700;
                               border:1.5px solid var(--border-light);background:var(--bg-muted);
                               color:var(--text-muted);cursor:pointer;transition:var(--transition-fast);">
                    {{ $r }}
                </button>
                @endfor
            </div>
        </div>

        <div style="margin-bottom:1.25rem;">
            <label class="input-label" style="display:block;margin-bottom:0.5rem;">
                Descanso — <span id="rest-label" style="color:var(--color-primary);">60s</span>
            </label>
            <div style="display:flex;gap:0.375rem;margin-bottom:0.55rem;">
                <button class="rest-btn" data-seconds="30" onclick="setRestSeconds(30)" style="flex:1;padding:0.45rem 0;border-radius:0.5rem;font-size:0.75rem;font-weight:700;border:1.5px solid var(--border-light);background:var(--bg-muted);color:var(--text-muted);cursor:pointer;transition:var(--transition-fast);">30s</button>
                <button class="rest-btn" data-seconds="60" onclick="setRestSeconds(60)" style="flex:1;padding:0.45rem 0;border-radius:0.5rem;font-size:0.75rem;font-weight:700;border:1.5px solid var(--border-light);background:var(--bg-muted);color:var(--text-muted);cursor:pointer;transition:var(--transition-fast);">60s</button>
                <button class="rest-btn" data-seconds="90" onclick="setRestSeconds(90)" style="flex:1;padding:0.45rem 0;border-radius:0.5rem;font-size:0.75rem;font-weight:700;border:1.5px solid var(--border-light);background:var(--bg-muted);color:var(--text-muted);cursor:pointer;transition:var(--transition-fast);">90s</button>
                <button class="rest-btn" data-seconds="120" onclick="setRestSeconds(120)" style="flex:1;padding:0.45rem 0;border-radius:0.5rem;font-size:0.75rem;font-weight:700;border:1.5px solid var(--border-light);background:var(--bg-muted);color:var(--text-muted);cursor:pointer;transition:var(--transition-fast);">2m</button>
                <button class="rest-btn" data-seconds="180" onclick="setRestSeconds(180)" style="flex:1;padding:0.45rem 0;border-radius:0.5rem;font-size:0.75rem;font-weight:700;border:1.5px solid var(--border-light);background:var(--bg-muted);color:var(--text-muted);cursor:pointer;transition:var(--transition-fast);">3m</button>
            </div>
            <input type="number" id="set-rest-seconds" class="input" min="5" max="900" step="5" placeholder="Descanso en segundos" inputmode="numeric">
        </div>

        <button id="add-set-confirm-btn" onclick="confirmAddSet()" class="btn btn-primary btn-block" style="height:3rem;font-size:1rem;font-weight:700;">
            <i data-lucide="check" style="width:1.125rem;height:1.125rem;"></i>
            Guardar serie
        </button>
    </div>
</div>

{{-- ═══ SHEET: PREVIEW DE PLANTILLA ══════════════════════════════════════ --}}
<div id="template-preview-sheet" class="template-preview-sheet" role="dialog" aria-modal="true" aria-label="Vista previa de plantilla">
    <div class="sheet-content-lg">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;">
            <div>
                <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-muted);margin:0 0 0.375rem;">Vista previa</p>
                <h3 id="preview-title" style="font-size:1.125rem;font-weight:800;margin:0;"></h3>
            </div>
            <button onclick="closeTemplatePreview()" class="btn btn-ghost btn-icon" aria-label="Cerrar">
                <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
            </button>
        </div>

        <p id="preview-description" style="font-size:0.8125rem;color:var(--text-muted);line-height:1.5;margin:0 0 1rem;"></p>

        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">
            <span id="preview-mode" class="badge badge-blue"></span>
            <span id="preview-level" class="badge badge-primary"></span>
            <span id="preview-count" class="badge badge-teal"></span>
        </div>

        <div id="preview-exercise-list" class="card" style="padding:0.875rem 1rem;display:flex;flex-direction:column;gap:0.625rem;"></div>

        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button onclick="closeTemplatePreview()" class="btn btn-outline btn-block">Cancelar</button>
            <button id="preview-edit-btn" onclick="openTemplateEditorFromPreview()" class="btn btn-ghost btn-block" style="display:none;">Editar</button>
            <button onclick="confirmStartTemplate()" class="btn btn-primary btn-block">Iniciar entrenamiento</button>
        </div>
    </div>
</div>

{{-- ═══ SHEET: EDITAR PLANTILLA ══════════════════════════════════════════ --}}
<div id="template-editor-sheet" class="template-preview-sheet" role="dialog" aria-modal="true" aria-label="Editar plantilla">
    <div class="sheet-content-lg">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;">
            <div>
                <p style="font-size:0.6875rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-muted);margin:0 0 0.375rem;">Plantilla personalizada</p>
                <h3 style="font-size:1.125rem;font-weight:800;margin:0;">Editar rutina</h3>
            </div>
            <button onclick="closeTemplateEditor()" class="btn btn-ghost btn-icon" aria-label="Cerrar">
                <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
            </button>
        </div>

        <div class="form-group" style="margin-bottom:0.75rem;">
            <label class="input-label">Nombre</label>
            <input id="template-edit-name" class="input" maxlength="100" placeholder="Nombre de la plantilla">
        </div>

        <div class="form-group" style="margin-bottom:0.75rem;">
            <label class="input-label">Descripción</label>
            <textarea id="template-edit-description" class="input" rows="2" maxlength="500" placeholder="Opcional"></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
            <div class="form-group" style="margin:0;">
                <label class="input-label">Nivel</label>
                <select id="template-edit-level" class="input">
                    <option>Básico</option>
                    <option>Intermedio</option>
                    <option>Avanzado</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="input-label">Modo</label>
                <select id="template-edit-mode" class="input">
                    <option value="gym">Gym</option>
                    <option value="calisthenics">Calistenia</option>
                    <option value="home">Casa</option>
                    <option value="swimming">Natación</option>
                    <option value="pasos">Pasos / Actividad</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:0.75rem;">
            <label class="input-label">Duración estimada (min)</label>
            <input id="template-edit-duration" class="input" type="number" min="1" max="600" step="1" placeholder="60">
        </div>

        <div class="card" style="padding:0.875rem 1rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin-bottom:0.75rem;">
                <p style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin:0;">Ejercicios</p>
                <button type="button" onclick="addTemplateExerciseRow()" class="btn btn-ghost btn-sm">
                    <i data-lucide="plus" style="width:1rem;height:1rem;"></i> Añadir
                </button>
            </div>
            <div id="template-edit-exercises" style="display:flex;flex-direction:column;gap:0.625rem;"></div>
        </div>

        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button onclick="closeTemplateEditor()" class="btn btn-outline btn-block">Cancelar</button>
            <button id="template-edit-save-btn" onclick="saveTemplateEdit()" class="btn btn-primary btn-block">Guardar cambios</button>
        </div>
    </div>
</div>

{{-- ═══ SHEET: REGISTRO DE PASOS ══════════════════════════════════════════ --}}
<div id="pasos-sheet" class="add-set-sheet" role="dialog" aria-modal="true" aria-label="Registrar pasos">
    <div class="add-set-content">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h3 style="font-size:1rem;font-weight:800;margin:0;">👟 Registrar pasos del día</h3>
        </div>
        <div class="space-y-3">
            <div class="form-group" style="margin-bottom:0;">
                <label class="input-label">Pasos totales</label>
                <input type="number" id="pasos-steps" class="input" placeholder="8000" min="0" max="100000">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="input-label">Calorías quemadas (según el reloj)</label>
                <input type="number" id="pasos-calories" class="input" placeholder="350" min="0" max="5000">
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0.375rem 0 0;">
                    Introduce el valor que muestra tu reloj o pulsera. Esto se usará en el cálculo nutricional del día.
                </p>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="input-label">Minutos activo <span style="color:var(--text-muted);font-weight:400;">(opcional)</span></label>
                <input type="number" id="pasos-duration" class="input" placeholder="60" min="0" max="1440">
            </div>
        </div>
        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button onclick="closePasosSheet()" class="btn btn-outline btn-block">Cancelar</button>
            <button onclick="savePasosWorkout()" class="btn btn-primary btn-block">Guardar</button>
        </div>
    </div>
</div>

{{-- ═══ SHEET: NUEVO EJERCICIO ═══════════════════════════════════════════ --}}
<div id="exercise-name-sheet" class="add-set-sheet" role="dialog" aria-modal="true" aria-label="Nuevo ejercicio">
    <div class="add-set-content">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;">
            <h3 style="font-size:1rem;font-weight:800;margin:0;">Añadir ejercicio</h3>
            <button onclick="closeExerciseNameSheet()" class="btn btn-ghost btn-icon" aria-label="Cerrar">
                <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
            </button>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label class="input-label">Nombre del ejercicio</label>
            <input id="new-exercise-name-training" class="input" maxlength="100" placeholder="Ej. Press banca">
            <div id="training-exercise-suggestions" class="inline-suggestions"></div>
        </div>

        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button onclick="closeExerciseNameSheet()" class="btn btn-outline btn-block">Cancelar</button>
            <button onclick="confirmNewExerciseName()" class="btn btn-primary btn-block">Añadir</button>
        </div>
    </div>
</div>

{{-- ═══ OVERLAY: DESCANSO ══════════════════════════════════════════════════ --}}
<div id="rest-overlay" class="rest-overlay" role="dialog" aria-modal="true" aria-label="Descanso">
    <div style="color:var(--text-muted);font-size:0.8125rem;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;">
        DESCANSO
    </div>
    <div class="rest-countdown" id="rest-countdown">60</div>
    <div style="color:var(--text-muted);font-size:0.8125rem;">segundos</div>
    <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
        <button onclick="adjustRest(-15)" class="btn btn-outline" style="padding:0.625rem 1.25rem;">
            <i data-lucide="minus" style="width:1rem;height:1rem;"></i> 15s
        </button>
        <button onclick="skipRest()" class="btn btn-primary" style="padding:0.625rem 1.75rem;font-weight:700;">
            Saltar →
        </button>
        <button onclick="adjustRest(+15)" class="btn btn-outline" style="padding:0.625rem 1.25rem;">
            +15s <i data-lucide="plus" style="width:1rem;height:1rem;"></i>
        </button>
    </div>
    <div style="margin-top:1rem;">
        <div style="width:200px;height:3px;background:rgba(255,255,255,0.1);border-radius:9999px;overflow:hidden;">
            <div id="rest-progress" style="height:100%;background:var(--color-primary);width:100%;transition:width linear;border-radius:9999px;"></div>
        </div>
    </div>
</div>

{{-- ═══ MODAL: CONFIRMAR CANCELAR ══════════════════════════════════════════ --}}
<div id="abort-modal" role="dialog" aria-modal="true"
     style="display:none;position:fixed;inset:0;z-index:9300;
            background:rgba(0,0,0,0.85);backdrop-filter:blur(8px);
            align-items:flex-end;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:1.5rem 1.5rem 0 0;
                border-top:1px solid var(--border-medium);width:100%;max-width:48rem;
                padding:1.5rem 1.5rem calc(1.5rem + env(safe-area-inset-bottom));">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <h3 style="font-size:1.125rem;font-weight:800;margin:0 0 0.5rem;text-align:center;">¿Cancelar entrenamiento?</h3>
        <p style="font-size:0.875rem;color:var(--text-muted);text-align:center;margin:0 0 1.5rem;line-height:1.5;">
            Todo el progreso se perderá si cancelas ahora.
        </p>
        <div style="display:flex;gap:0.75rem;">
            <button onclick="closeAbortModal()" class="btn btn-outline btn-block">Continuar</button>
            <button onclick="abortWorkout()" class="btn btn-danger btn-block">Cancelar</button>
        </div>
    </div>
</div>

{{-- ═══ SHEET: RESUMEN DE ENTRENAMIENTO ══════════════════════════════════ --}}
<div id="workout-summary-sheet" class="workout-summary-sheet" role="dialog" aria-modal="true" aria-label="Resumen de entrenamiento">
    <div class="sheet-content-lg">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0 auto 1.25rem;"></div>
        <div style="text-align:center;margin-bottom:1.25rem;">
            <p style="font-size:0.6875rem;font-weight:800;letter-spacing:0.1em;text-transform:uppercase;color:var(--color-primary);margin:0 0 0.375rem;">Entrenamiento completado</p>
            <h3 style="font-size:1.25rem;font-weight:900;margin:0;">Buen trabajo</h3>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
            <div class="stat-card" style="padding:0.875rem;">
                <p class="stat-value" id="summary-duration" style="font-size:1.2rem;">0:00</p>
                <p class="stat-label">Tiempo</p>
            </div>
            <div class="stat-card" style="padding:0.875rem;">
                <p class="stat-value" id="summary-volume" style="font-size:1.2rem;">0 kg</p>
                <p class="stat-label">Volumen</p>
            </div>
            <div class="stat-card" style="padding:0.875rem;">
                <p class="stat-value" id="summary-exercises" style="font-size:1.2rem;">0</p>
                <p class="stat-label">Ejercicios</p>
            </div>
            <div class="stat-card" style="padding:0.875rem;">
                <p class="stat-value" id="summary-sets" style="font-size:1.2rem;">0</p>
                <p class="stat-label">Series</p>
            </div>
        </div>

        <div class="card" style="padding:0.875rem 1rem;">
            <p style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin:0 0 0.625rem;">Nuevos PR</p>
            <div id="summary-pr-list" style="display:flex;flex-direction:column;gap:0.5rem;"></div>
        </div>

        <div class="card" style="padding:0.875rem 1rem;margin-top:0.75rem;">
            <p style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin:0 0 0.5rem;">Resumen por ejercicio</p>
            <div id="summary-recap" style="display:flex;flex-direction:column;gap:0.1rem;"></div>
        </div>

        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button onclick="goToHistoryFromSummary()" class="btn btn-outline btn-block">Ver en historial</button>
            <button onclick="closeWorkoutSummary()" class="btn btn-primary btn-block">Cerrar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
auth.check();

// ── Estado global ─────────────────────────────────────────────────────────
let allTemplates     = [];
let currentMode      = 'gym';
let activeWorkout    = null;  // { name, mode, exercises:[{name,sets:[]}], currentIdx, startTime }
let currentRpe       = null;
let restTimerHandle  = null;
let restTotal        = 60;
let restRemaining    = 60;
let elapsedHandle    = null;
let pendingTemplate  = null;
let editingSetIndex  = null;
let editingTemplateId = null;
let exerciseSuggestionTimer = null;
const REST_DEFAULT_KEY = 'fitloop.default_rest_seconds';
let currentRestSeconds = getSavedDefaultRestSeconds();
let pendingSetHighlightExerciseIdx = null;
let pendingSetHighlightSetIdx = null;
let pendingSetHighlightHandle = null;
let setInlineFeedbackHandle = null;
let exerciseInlineFeedbackHandle = null;
let lastRenderedExerciseIdx = null;
let currentLevelFilter = 'all';
let templateSearchQuery = '';
const templateIdParam = new URLSearchParams(window.location.search).get('template_id');

// ── Modelo por-serie + persistencia ───────────────────────────────────────
const DRAFT_KEY = 'fitloop.active_workout';
const MODE_KEY  = 'fitloop.training_mode';
let draftSaveTimer = null;
let restHandle = null;
let renamingExerciseIdx = null;   // null = añadir; nº = renombrar ese ejercicio
let presetExercisesCache = null;

function saveDraft() {
    if (!activeWorkout) return;
    clearTimeout(draftSaveTimer);
    draftSaveTimer = setTimeout(() => {
        try { localStorage.setItem(DRAFT_KEY, JSON.stringify(activeWorkout)); } catch (e) {}
    }, 400);
}
function clearDraft() {
    clearTimeout(draftSaveTimer);
    try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
}
function loadDraft() {
    try { const raw = localStorage.getItem(DRAFT_KEY); return raw ? JSON.parse(raw) : null; } catch (e) { return null; }
}

// Crea un ejercicio con la forma nueva (una serie semilla con el descanso objetivo).
function makeExercise(name, target = null) {
    const rest = target?.rest ?? getSavedDefaultRestSeconds();
    return {
        name,
        target: target ? { sets: target.sets ?? null, reps: target.reps ?? null, rest } : null,
        pr: null,
        previous: null,
        sets: [{ weight_kg: null, reps: null, rpe: null, rest_seconds: rest, done: false }],
    };
}

// Columnas de la tabla segun modo. Fuerza: Kg/Reps/RPE. Natacion: Distancia/Tiempo.
function columnsForMode(mode) {
    if (mode === 'swimming') {
        return { kind: 'swim', headers: ['Serie', 'Distancia (m)', 'Tiempo (s)', '✓'] };
    }
    const weightLabel = mode === 'calisthenics' ? 'Lastre' : 'Kg';
    return { kind: 'strength', headers: ['Serie', 'Anterior', weightLabel, 'Reps', 'RPE', '✓'] };
}

// Payload por-serie para POST /workouts (descarta series sin datos).
function buildWorkoutPayload() {
    const exercises = (activeWorkout.exercises || []).map(ex => ({
        name: ex.name,
        sets: (ex.sets || [])
            .filter(s => s.weight_kg != null || s.reps != null || s.time_seconds != null || s.distance_m != null)
            .map(s => ({
                weight_kg: s.weight_kg ?? null,
                reps: s.reps ?? null,
                rpe: s.rpe ?? null,
                rest_seconds: s.rest_seconds ?? null,
                time_seconds: s.time_seconds ?? null,
                distance_m: s.distance_m ?? null,
                style: s.style ?? null,
            })),
    })).filter(ex => ex.sets.length > 0);

    return {
        mode: activeWorkout.mode,
        date: new Date(activeWorkout.startTime).toISOString().slice(0, 10),
        duration_minutes: Math.max(1, Math.round((Date.now() - activeWorkout.startTime) / 60000)),
        notes: activeWorkout.notes || null,
        exercises,
    };
}

// ── Cargar plantillas ─────────────────────────────────────────────────────
async function loadTemplates() {
    try {
        const res = await apiCall('/templates');
        allTemplates = Array.isArray(res) ? res : (res.templates || []);
        document.getElementById('templates-skeleton').style.display = 'none';
        renderTemplates();

        if (templateIdParam) {
            const found = allTemplates.find(t => String(t.id) === String(templateIdParam));
            if (found) {
                currentMode = found.mode || currentMode;
                document.querySelectorAll('.mode-tabs .pill-tab').forEach(b => {
                    b.classList.toggle('active', b.dataset.mode === currentMode);
                });
                renderTemplates();
                startTemplate(found.id);
            }
        }
    } catch(e) {
        document.getElementById('templates-skeleton').style.display = 'none';
        console.error(e);
    }
}

function filterMode(mode) {
    currentMode = mode;
    try { localStorage.setItem(MODE_KEY, mode); } catch (e) {}
    document.querySelectorAll('.mode-tabs .pill-tab').forEach(b => {
        b.classList.toggle('active', b.dataset.mode === mode);
    });
    renderTemplates();
}

function setLevelFilter(level, btnEl) {
    currentLevelFilter = level;
    document.querySelectorAll('#level-filter-row .level-chip').forEach(chip => {
        chip.classList.toggle('active', chip === btnEl);
    });
    renderTemplates();
}

function handleTemplateSearch(value) {
    templateSearchQuery = (value || '').trim().toLowerCase();
    renderTemplates();
}

function clearTemplateFilters() {
    templateSearchQuery = '';
    currentLevelFilter = 'all';
    const input = document.getElementById('template-search-input');
    if (input) input.value = '';
    document.querySelectorAll('#level-filter-row .level-chip').forEach(chip => {
        chip.classList.toggle('active', chip.dataset.level === 'all');
    });
    renderTemplates();
}

function getTemplatesByCurrentMode() {
    return allTemplates.filter(t => t.mode === currentMode);
}

function getFilteredTemplates() {
    const modeTemplates = getTemplatesByCurrentMode();
    return modeTemplates.filter(t => {
        if (currentLevelFilter !== 'all' && (t.level || '') !== currentLevelFilter) return false;
        if (!templateSearchQuery) return true;

        const haystack = [
            t.name || '',
            t.description || '',
            ...(Array.isArray(t.exercises) ? t.exercises.map(ex => ex.name || '') : []),
        ].join(' ').toLowerCase();

        return haystack.includes(templateSearchQuery);
    });
}

function refreshTemplateMetrics(visibleTemplates, modeTemplates) {
    const visible = visibleTemplates.length;
    const modeCount = modeTemplates.length;
    const customCount = visibleTemplates.filter(t => !!t.user_id).length;

    document.getElementById('metric-visible-templates').textContent = visible;
    document.getElementById('metric-mode-templates').textContent = modeCount;
    document.getElementById('metric-custom-templates').textContent = customCount;
}

function renderTemplates() {
    const list = document.getElementById('templates-list');
    const modeTemplates = getTemplatesByCurrentMode();
    const filtered = getFilteredTemplates();
    list.style.display = 'flex';
    list.style.flexDirection = 'column';
    list.style.gap = '0.75rem';
    refreshTemplateMetrics(filtered, modeTemplates);

    if (filtered.length === 0) {
        list.innerHTML = `<div class="empty-state" style="padding:2rem 0;">
            <div class="empty-state-icon"><i data-lucide="dumbbell"></i></div>
            <p class="empty-state-title">No hay coincidencias</p>
            <p class="empty-state-desc">Cambia filtros o busca otro ejercicio para encontrar una rutina.</p>
            <button onclick="clearTemplateFilters()" class="btn btn-outline btn-sm" style="margin-top:0.75rem;">Limpiar filtros</button>
        </div>`;
        lucide.createIcons();
        return;
    }

    const levelColor = { 'Básico':'#34d399', 'Intermedio':'#60a5fa', 'Avanzado':'#f87171' };
    const modeIcon   = { gym:'dumbbell', calisthenics:'user', home:'home', swimming:'waves', pasos:'footprints' };

    list.innerHTML = filtered.map(t => {
        const exCount = t.exercises ? t.exercises.length : 0;
        const previewNames = (t.exercises || []).slice(0, 3).map(ex => escHtml(ex.name)).join(' · ');
        const color   = levelColor[t.level] || 'var(--color-primary)';
        const canEdit = !!t.user_id;
        const duration = Number.parseInt(t.duration_minutes, 10);
        return `<article class="template-card template-card-v2 card-interactive" onclick="startTemplate('${t.id}')">
            <div class="template-card-head">
                <div class="icon-wrap icon-wrap-md" style="background:${color}1a;flex-shrink:0;">
                    <i data-lucide="${modeIcon[t.mode] || 'dumbbell'}" style="width:1.1rem;height:1.1rem;color:${color};"></i>
                </div>
                <div class="template-card-copy">
                    <div class="template-card-title-row">
                        <p class="template-card-title">${escHtml(t.name)}</p>
                        <span style="font-size:0.6875rem;font-weight:700;padding:0.22rem 0.55rem;border-radius:9999px;background:${color}1a;color:${color};white-space:nowrap;">${escHtml(t.level || '')}</span>
                    </div>
                    ${t.description ? `<p class="template-card-desc">${escHtml(t.description)}</p>` : ''}
                    <div class="template-card-meta">
                        <span class="template-pill">${exCount} ejercicios</span>
                        ${Number.isFinite(duration) && duration > 0 ? `<span class="template-pill">${duration} min</span>` : ''}
                        ${canEdit ? '<span class="template-pill" style="color:var(--color-blue);border-color:rgba(59,130,246,0.25);background:rgba(59,130,246,0.12);">Tuya</span>' : ''}
                    </div>
                </div>
            </div>
            ${previewNames ? `<p class="template-card-preview">${previewNames}</p>` : ''}
            <div class="template-card-foot">
                <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); startTemplate('${t.id}')">
                    Ver detalle
                </button>
                <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); quickStartTemplate('${t.id}')">
                    Iniciar
                </button>
                ${canEdit ? `<button class="template-edit-btn" onclick="event.stopPropagation(); openTemplateEditor('${t.id}')" aria-label="Editar plantilla ${escHtml(t.name)}">
                    <i data-lucide="pencil" style="width:0.95rem;height:0.95rem;"></i>
                </button>` : ''}
            </div>
        </article>`;
    }).join('');
    lucide.createIcons();
}

function mapTemplateExercises(template) {
    return (template.exercises || []).map(ex => makeExercise(ex.name, {
        sets: ex.sets || 3,
        reps: ex.reps || null,
        rest: ex.rest_seconds || getSavedDefaultRestSeconds(),
    }));
}

function quickStartTemplate(templateId) {
    const t = allTemplates.find(x => String(x.id) === String(templateId));
    if (!t) return;

    pendingTemplate = t;
    beginWorkout(t.name, t.mode, mapTemplateExercises(t));
}

function startRecommendedTemplate() {
    const filtered = getFilteredTemplates();
    if (filtered.length === 0) {
        window.showToast('No hay plantillas para iniciar en este filtro', 'error');
        return;
    }
    quickStartTemplate(filtered[0].id);
}

// ── Vista previa / inicio con plantilla ────────────────────────────────────
function startTemplate(templateId) {
    const t = allTemplates.find(x => x.id === templateId);
    if (!t) return;

    pendingTemplate = t;
    openTemplatePreview(t);
}

function openTemplatePreview(template) {
    const modeLabels = { gym:'Gym', calisthenics:'Calistenia', home:'Casa', swimming:'Natación', pasos:'Pasos / Actividad' };
    const previewList = document.getElementById('preview-exercise-list');

    document.getElementById('preview-title').textContent = template.name || 'Plantilla';
    document.getElementById('preview-description').textContent = template.description || 'Sin descripción';
    document.getElementById('preview-mode').textContent = modeLabels[template.mode] || (template.mode || 'Modo');
    document.getElementById('preview-level').textContent = template.level || 'Nivel';
    document.getElementById('preview-count').textContent = `${(template.exercises || []).length} ejercicios`;
    document.getElementById('preview-edit-btn').style.display = template.user_id ? 'inline-flex' : 'none';

    previewList.innerHTML = (template.exercises || []).map((ex, idx) => {
        const restText = ex.rest_seconds ? ` · ${ex.rest_seconds}s descanso` : '';
        return `<div style="display:flex;gap:0.625rem;align-items:flex-start;border-bottom:1px solid var(--border-light);padding-bottom:0.5rem;">
            <span style="font-size:0.75rem;font-weight:800;color:var(--text-muted);min-width:1.25rem;">${idx + 1}</span>
            <div style="flex:1;">
                <p style="font-size:0.875rem;font-weight:700;margin:0 0 0.1875rem;">${escHtml(ex.name)}</p>
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0;">${ex.sets || 3} × ${ex.reps || 10}${restText}</p>
            </div>
        </div>`;
    }).join('') || '<p style="font-size:0.8125rem;color:var(--text-muted);margin:0;">Esta plantilla no tiene ejercicios definidos.</p>';

    document.getElementById('template-preview-sheet').classList.add('open');
    lucide.createIcons();
}

function closeTemplatePreview() {
    document.getElementById('template-preview-sheet').classList.remove('open');
}

function openTemplateEditorFromPreview() {
    if (!pendingTemplate?.id) return;
    closeTemplatePreview();
    openTemplateEditor(pendingTemplate.id);
}

function confirmStartTemplate() {
    if (!pendingTemplate) return;

    const t = pendingTemplate;
    const exercises = mapTemplateExercises(t);

    closeTemplatePreview();
    beginWorkout(t.name, t.mode, exercises);
}

function startFreeWorkout() {
    const modeLabels = { gym:'Gym', calisthenics:'Calistenia', home:'Casa', swimming:'Natación', pasos:'Pasos / Actividad' };
    beginWorkout(`Entrenamiento libre (${modeLabels[currentMode]})`, currentMode, [
        makeExercise('Ejercicio 1', { sets: 3, reps: null, rest: getSavedDefaultRestSeconds() })
    ]);
}

// ── Iniciar el entrenamiento activo ──────────────────────────────────────
function beginWorkout(name, mode, exercises) {
    activeWorkout = { name, mode, templateId: pendingTemplate?.id ?? null, exercises, currentIdx: 0, startTime: Date.now() };
    showTrainingScreen(name);
    renderExercises();
    updateProgress();
    startElapsedTimer();
    loadPreviousFor(0);
    saveDraft();
}

function showTrainingScreen(name) {
    document.getElementById('phase-select').classList.remove('active');
    document.getElementById('training-screen').style.display = 'flex';
    document.querySelector('nav.navbar')?.style.setProperty('display','none');
    document.getElementById('training-name').textContent = name || (activeWorkout && activeWorkout.name) || '';
    lucide.createIcons();
}

// ── Render de la tabla por ejercicio ──────────────────────────────────────
function renderExercises() {
    if (!activeWorkout) return;
    const c = document.getElementById('exercises-container');
    c.innerHTML = activeWorkout.exercises.map((ex, i) => renderExerciseCard(ex, i)).join('')
        + `<button class="addex-btn" onclick="addNewExercise()"><i data-lucide="plus" style="width:1rem;height:1rem;"></i> Añadir ejercicio</button>`;
    lucide.createIcons();
}

function renderExerciseCard(ex, idx) {
    const cols = columnsForMode(activeWorkout.mode);
    const active = idx === activeWorkout.currentIdx;
    const doneCount = ex.sets.filter(s => s.done).length;
    const total = ex.sets.length || (ex.target && ex.target.sets) || 0;

    if (!active) {
        return `<div class="exercise-table-card" onclick="expandExercise(${idx})">
            <div class="etc-head exercise-collapsed">
                <span class="etc-name">${escHtml(ex.name)}</span>
                <span class="cnt">${doneCount}/${total}</span>
                <span class="etc-kebab">⌄</span>
            </div></div>`;
    }

    const prBadge = ex.pr ? `<span class="etc-pr">🏆 PR ${Math.round(ex.pr)}</span>` : '';
    const sub = ex.target ? `<p class="etc-sub">Objetivo ${ex.target.sets ?? '-'}×${ex.target.reps ?? '-'}${ex.target.rest ? ' · descanso ' + ex.target.rest + 's' : ''}</p>` : '';
    const head = `<tr>${cols.headers.map((h, i) => `<th class="${i === 0 ? 'l' : ''}">${h}</th>`).join('')}</tr>`;
    const rows = ex.sets.map((s, si) => renderSetRow(ex, idx, s, si, cols)).join('');

    return `<div class="exercise-table-card active">
        <div class="etc-head">
            <button class="etc-name etc-name-edit" onclick="renameExercise(${idx})" aria-label="Cambiar ejercicio">${escHtml(ex.name)} <i data-lucide="pencil" style="width:.85rem;height:.85rem;opacity:.6;"></i></button>${prBadge}
            <button class="etc-kebab" onclick="removeExercise(${idx})" aria-label="Quitar ejercicio">✕</button>
        </div>${sub}
        <table class="set-table">${head}${rows}</table>
        <div class="etc-addset" onclick="addSetRow(${idx})">+ Añadir serie</div>
    </div>`;
}

function renderSetRow(ex, exIdx, s, si, cols) {
    const num = `<td class="idx">${si + 1}</td>`;
    const check = `<td><button class="set-check ${s.done ? 'on' : ''}" onclick="toggleSetDone(${exIdx},${si})">${s.done ? '✓' : ''}</button></td>`;

    if (cols.kind === 'swim') {
        return `<tr class="${s.done ? 'done' : ''}">${num}
            <td><input class="set-cell-input" inputmode="numeric" value="${s.distance_m ?? ''}" onchange="editSet(${exIdx},${si},'distance_m',this.value)"></td>
            <td><input class="set-cell-input" inputmode="numeric" value="${s.time_seconds ?? ''}" onchange="editSet(${exIdx},${si},'time_seconds',this.value)"></td>
            ${check}</tr>`;
    }

    const prev = ex.previous && ex.previous[si]
        ? `${ex.previous[si].weight_kg ?? '–'}×${ex.previous[si].reps ?? '–'}`
        : '–';
    return `<tr class="${s.done ? 'done' : ''}">${num}
        <td class="prev">${prev}</td>
        <td><input class="set-cell-input" inputmode="decimal" value="${s.weight_kg ?? ''}" onchange="editSet(${exIdx},${si},'weight_kg',this.value)"></td>
        <td><input class="set-cell-input" inputmode="numeric" value="${s.reps ?? ''}" onchange="editSet(${exIdx},${si},'reps',this.value)"></td>
        <td><input class="set-cell-input rpe" inputmode="numeric" value="${s.rpe ?? ''}" onchange="editSet(${exIdx},${si},'rpe',this.value)"></td>
        ${check}</tr>`;
}

function editSet(exIdx, si, field, value) {
    const v = value === '' ? null : Number(value);
    activeWorkout.exercises[exIdx].sets[si][field] = (v != null && !isNaN(v)) ? v : null;
    saveDraft();
    updateProgress();
}

function toggleSetDone(exIdx, si) {
    const set = activeWorkout.exercises[exIdx].sets[si];
    set.done = !set.done;
    saveDraft();
    renderExercises();
    updateProgress();
    if (set.done) {
        const t = activeWorkout.exercises[exIdx].target;
        const rest = set.rest_seconds ?? (t && t.rest) ?? getSavedDefaultRestSeconds();
        startRestBar(rest);
        if (navigator.vibrate) navigator.vibrate(12);
    }
}

function addSetRow(exIdx) {
    const sets = activeWorkout.exercises[exIdx].sets;
    const last = sets[sets.length - 1];
    const t = activeWorkout.exercises[exIdx].target;
    sets.push({ weight_kg: last ? last.weight_kg : null, reps: last ? last.reps : null, rpe: null, rest_seconds: (t && t.rest) ?? getSavedDefaultRestSeconds(), done: false });
    saveDraft();
    renderExercises();
}

function removeSet(exIdx, si, event) {
    if (event) event.stopPropagation();
    const sets = activeWorkout.exercises[exIdx].sets;
    if (sets.length <= 1) {
        sets[0] = { weight_kg: null, reps: null, rpe: null, rest_seconds: (sets[0] && sets[0].rest_seconds) ?? getSavedDefaultRestSeconds(), done: false };
    } else {
        sets.splice(si, 1);
    }
    saveDraft();
    renderExercises();
    updateProgress();
}

function removeExercise(idx) {
    if (activeWorkout.exercises.length <= 1) { window.showToast('Debe quedar al menos un ejercicio', 'error'); return; }
    activeWorkout.exercises.splice(idx, 1);
    if (activeWorkout.currentIdx >= activeWorkout.exercises.length) activeWorkout.currentIdx = activeWorkout.exercises.length - 1;
    saveDraft();
    renderExercises();
    updateProgress();
}

function expandExercise(idx) {
    activeWorkout.currentIdx = idx;
    loadPreviousFor(idx);
    renderExercises();
    saveDraft();
}

function updateProgress() {
    if (!activeWorkout) return;
    const allSets = activeWorkout.exercises.flatMap(e => e.sets);
    const done = allSets.filter(s => s.done).length;
    const vol = allSets.reduce((a, s) => a + (Number(s.weight_kg) || 0) * (Number(s.reps) || 0), 0);
    document.getElementById('sets-progress').textContent = `${done} de ${allSets.length} series`;
    document.getElementById('volume-progress').textContent = `${Math.round(vol).toLocaleString('es')} kg`;
    document.getElementById('exercise-progress-fill').style.width = allSets.length ? `${(done / allSets.length) * 100}%` : '0%';
}

async function loadPreviousFor(idx) {
    const ex = activeWorkout && activeWorkout.exercises[idx];
    if (!ex || (ex.previous !== null && ex.pr !== null)) return;
    try {
        const prev = await apiCall(`/exercises/last-session?name=${encodeURIComponent(ex.name)}`);
        ex.previous = Array.isArray(prev) ? prev : [];
    } catch (e) { ex.previous = []; }
    if (ex.pr === null) {
        try {
            const records = await apiCall('/exercises/records');
            const rec = (records || []).find(r => r.name === ex.name);
            ex.pr = rec ? Number(rec.max_weight) : 0;
        } catch (e) { ex.pr = 0; }
    }
    renderExercises();
}

// ── Barra de descanso fina (sustituye el overlay) ─────────────────────────
function startRestBar(seconds) {
    if (!seconds || seconds < 1) return;
    restRemaining = seconds;
    document.getElementById('rest-bar').style.display = 'flex';
    updateRestBar();
    clearInterval(restHandle);
    restHandle = setInterval(() => {
        restRemaining--;
        if (restRemaining <= 0) { skipRestBar(); return; }
        updateRestBar();
    }, 1000);
}
function updateRestBar() {
    const m = Math.floor(restRemaining / 60), s = restRemaining % 60;
    document.getElementById('rest-bar-time').textContent = `${m}:${String(s).padStart(2, '0')}`;
}
function adjustRestBar(delta) { restRemaining = Math.max(1, restRemaining + delta); updateRestBar(); }
function skipRestBar() { clearInterval(restHandle); const el = document.getElementById('rest-bar'); if (el) el.style.display = 'none'; }

// ── Retomar borrador / repetir último ─────────────────────────────────────
function checkResumeBanner() {
    const banner = document.getElementById('resume-banner');
    if (!banner) return;
    const d = loadDraft();
    if (!d || !d.exercises) { banner.style.display = 'none'; return; }
    const mins = Math.max(0, Math.round((Date.now() - d.startTime) / 60000));
    const sets = d.exercises.flatMap(e => e.sets || []).filter(s => s.done).length;
    document.getElementById('resume-meta').textContent = `${d.name || 'Entreno'} · ${sets} series · hace ${mins} min`;
    banner.style.display = 'flex';
}

function resumeDraft() {
    const d = loadDraft();
    if (!d) return;
    activeWorkout = d;
    showTrainingScreen(d.name);
    renderExercises();
    updateProgress();
    startElapsedTimer();
    loadPreviousFor(activeWorkout.currentIdx || 0);
}

function groupSetsByName(sets) {
    const order = [], map = new Map();
    (sets || []).forEach(s => { if (!map.has(s.name)) { map.set(s.name, []); order.push(s.name); } map.get(s.name).push(s); });
    return order.map(name => ({ name, sets: map.get(name) }));
}

async function repeatLastWorkout() {
    try {
        const res = await apiCall(`/workouts?mode=${currentMode}&per_page=1&sort=desc`);
        const last = (res.data || res || [])[0];
        if (!last || !last.sets || !last.sets.length) { window.showToast('No hay un entreno previo de este modo', 'error'); return; }
        const groups = groupSetsByName(last.sets);
        activeWorkout = {
            mode: last.mode, name: 'Repetir entreno', templateId: null, startTime: Date.now(), currentIdx: 0,
            exercises: groups.map(g => ({
                name: g.name, target: null, pr: null, previous: null,
                sets: g.sets.map(s => ({ weight_kg: s.weight_kg ?? null, reps: s.reps ?? null, rpe: null, rest_seconds: s.rest_seconds ?? null, done: false })),
            })),
        };
        showTrainingScreen('Repetir entreno');
        renderExercises();
        updateProgress();
        startElapsedTimer();
        loadPreviousFor(0);
        saveDraft();
    } catch (e) { window.showToast('No se pudo cargar el último entreno', 'error'); }
}

// ── Timer de tiempo transcurrido ─────────────────────────────────────────
function startElapsedTimer() {
    elapsedHandle = setInterval(() => {
        const secs = Math.floor((Date.now() - activeWorkout.startTime) / 1000);
        const m = Math.floor(secs / 60);
        const s = String(secs % 60).padStart(2, '0');
        document.getElementById('elapsed-timer').textContent = `${m}:${s}`;
    }, 1000);
}

// ── Renderizar ejercicio actual ───────────────────────────────────────────
function renderExercise() {
    const { exercises, currentIdx } = activeWorkout;
    const ex = exercises[currentIdx];
    const shouldPulseDot = lastRenderedExerciseIdx !== null && lastRenderedExerciseIdx !== currentIdx;

    // Nombre y hint
    document.getElementById('current-exercise-name').textContent = ex.name;
    let hint = '';
    if (ex.targetSets) hint += `Objetivo: ${ex.targetSets} series`;
    if (ex.targetReps) hint += ` × ${ex.targetReps} reps`;
    if (ex.defaultRestSeconds) hint += `${hint ? ' · ' : ''}Descanso ${ex.defaultRestSeconds}s`;
    document.getElementById('exercise-hint').textContent = hint;

    // Contadores
    document.getElementById('exercise-counter').textContent = `Ejercicio ${currentIdx + 1} de ${exercises.length}`;
    document.getElementById('sets-counter').textContent     = `${ex.sets.length} serie${ex.sets.length !== 1 ? 's' : ''}`;
    const targetSets = ex.targetSets || ex.sets.length || 0;
    document.getElementById('exercise-goal-sets').textContent = `${ex.sets.length}/${targetSets || 0}`;
    document.getElementById('exercise-goal-reps').textContent = ex.targetReps ? `${ex.targetReps}` : 'Libre';
    document.getElementById('exercise-goal-rest').textContent = `${ex.defaultRestSeconds || ex.targetRest || getSavedDefaultRestSeconds()}s`;

    // Barra de progreso
    const pct = exercises.length > 1 ? (currentIdx / (exercises.length - 1)) * 100 : 100;
    document.getElementById('exercise-progress-fill').style.width = pct + '%';

    // Dots de navegación
    const dots = document.getElementById('exercise-dots');
    dots.innerHTML = exercises.map((e, i) => {
        const hasSets = e.sets.length > 0;
        const dotClass = `exercise-dot${i === currentIdx ? ' exercise-dot-active' : ''}${i === currentIdx && shouldPulseDot ? ' exercise-dot-pulse' : ''}`;
        return `<div class="${dotClass}" style="width:${i === currentIdx ? '1.25rem' : '0.5rem'};height:0.5rem;border-radius:9999px;
                    background:${i === currentIdx ? 'var(--color-primary)' : (hasSets ? 'rgba(245,158,11,0.4)' : 'var(--bg-muted)')};
                    transition:all 0.25s;" title="${escHtml(e.name)}"></div>`;
    }).join('');

    const rail = document.getElementById('exercise-rail');
    rail.innerHTML = exercises.map((item, idx) => {
        const isActive = idx === currentIdx;
        const hasSets = item.sets.length > 0;
        return `<button class="exercise-rail-card${isActive ? ' active' : ''}${hasSets ? ' done' : ''}" onclick="jumpToExercise(${idx})" aria-label="Ir a ejercicio ${idx + 1}">
            <p class="exercise-rail-label">Ejercicio ${idx + 1}</p>
            <p class="exercise-rail-name">${escHtml(item.name || `Ejercicio ${idx + 1}`)}</p>
            <p class="exercise-rail-meta">${item.sets.length} serie${item.sets.length !== 1 ? 's' : ''}</p>
        </button>`;
    }).join('');

    // Navegación
    document.getElementById('btn-prev-ex').disabled = currentIdx === 0;
    document.getElementById('btn-next-ex').disabled = currentIdx === exercises.length - 1;

    // Añadir ejercicio en nav si es último
    const nextBtn = document.getElementById('btn-next-ex');
    if (currentIdx === exercises.length - 1) {
        nextBtn.innerHTML = '<i data-lucide="plus" style="width:1.25rem;height:1.25rem;"></i>';
        nextBtn.disabled = false;
        nextBtn.onclick = addNewExercise;
    } else {
        nextBtn.innerHTML = '<i data-lucide="chevron-right" style="width:1.25rem;height:1.25rem;"></i>';
        nextBtn.onclick = nextExercise;
    }

    renderSets();
    lastRenderedExerciseIdx = currentIdx;
    lucide.createIcons();
}

function jumpToExercise(targetIdx) {
    if (!activeWorkout) return;
    if (targetIdx < 0 || targetIdx >= activeWorkout.exercises.length) return;
    activeWorkout.currentIdx = targetIdx;
    renderExercise();
    showExerciseInlineFeedback(`Ejercicio ${targetIdx + 1}/${activeWorkout.exercises.length}`);
    document.getElementById('training-body').scrollTop = 0;
}

function renderSets() {
    const ex   = activeWorkout.exercises[activeWorkout.currentIdx];
    const list = document.getElementById('sets-list');
    const body = document.getElementById('training-body');

    if (ex.sets.length === 0) {
        body.classList.add('training-body-empty');
        list.innerHTML = `<div class="sets-empty-state">
            <i data-lucide="circle-play" style="width:1.25rem;height:1.25rem;color:var(--text-muted);"></i>
            <p class="sets-empty-state-title">Aún no has registrado series</p>
            <p class="sets-empty-state-desc">Empieza con una serie para desbloquear tu progreso del ejercicio.</p>
        </div>`;
        lucide.createIcons();
        return;
    }

    body.classList.remove('training-body-empty');

    list.innerHTML = ex.sets.map((s, i) => {
        const weightStr = s.weight_kg > 0 ? `${s.weight_kg} kg` : 'Peso corp.';
        const repsStr   = s.reps > 0 ? `${s.reps} reps` : '–';
        const rpeStr    = s.rpe ? `RPE ${s.rpe}` : null;
        const restStr   = s.rest_seconds ? `Desc ${s.rest_seconds}s` : null;
        const metaParts = [rpeStr, restStr].filter(Boolean);
        const isHighlighted = activeWorkout.currentIdx === pendingSetHighlightExerciseIdx && i === pendingSetHighlightSetIdx;
        return `<div class="set-row${isHighlighted ? ' set-row-saved' : ''}" data-set-index="${i}" onclick="openAddSet(${i})" title="Editar serie ${i + 1}">
            <span class="set-number">${i + 1}</span>
            <div class="set-main">
                <div class="set-primary">${escHtml(weightStr)} · ${escHtml(repsStr)}</div>
                <div class="set-meta">${escHtml(metaParts.join(' · ') || 'Sin RPE ni descanso definido')}</div>
            </div>
            <button class="set-delete" onclick="deleteSet(${i}, event)" aria-label="Eliminar serie ${i+1}">
                <i data-lucide="trash-2" style="width:0.875rem;height:0.875rem;"></i>
            </button>
        </div>`;
    }).join('');
    lucide.createIcons();
}

function deleteSet(idx, event) {
    event?.stopPropagation();
    const ex = activeWorkout.exercises[activeWorkout.currentIdx];
    ex.sets.splice(idx, 1);
    renderSets();
    document.getElementById('sets-counter').textContent = `${ex.sets.length} serie${ex.sets.length !== 1 ? 's' : ''}`;
    bumpSetsCounter();
    showSetInlineFeedback('Serie eliminada', 'delete');
}

// ── Navegación entre ejercicios ───────────────────────────────────────────
function prevExercise() {
    if (activeWorkout.currentIdx > 0) {
        activeWorkout.currentIdx--;
        renderExercise();
        showExerciseInlineFeedback(`Ejercicio ${activeWorkout.currentIdx + 1}/${activeWorkout.exercises.length}`);
        document.getElementById('training-body').scrollTop = 0;
    }
}

function nextExercise() {
    if (activeWorkout.currentIdx < activeWorkout.exercises.length - 1) {
        activeWorkout.currentIdx++;
        renderExercise();
        showExerciseInlineFeedback(`Ejercicio ${activeWorkout.currentIdx + 1}/${activeWorkout.exercises.length}`);
        document.getElementById('training-body').scrollTop = 0;
    }
}

function addNewExercise() {
    renamingExerciseIdx = null;
    openExerciseSheet('');
}

function renameExercise(idx) {
    renamingExerciseIdx = idx;
    openExerciseSheet(activeWorkout.exercises[idx].name || '');
}

function openExerciseSheet(prefill) {
    const input = document.getElementById('new-exercise-name-training');
    input.value = prefill;
    document.querySelector('#exercise-name-sheet h3').textContent =
        renamingExerciseIdx != null ? 'Cambiar ejercicio' : 'Añadir ejercicio';
    showPresetChips();   // ejercicios preestablecidos seleccionables (sin escribir)
    document.getElementById('exercise-name-sheet').classList.add('open');
    setTimeout(() => { input.focus(); input.select(); }, 120);
}

function closeExerciseNameSheet() {
    document.getElementById('exercise-name-sheet').classList.remove('open');
    hideExerciseSuggestions();
}

function confirmNewExerciseName() {
    const input = document.getElementById('new-exercise-name-training');
    const name = (input.value || '').trim();

    if (renamingExerciseIdx != null) {
        if (!name) { window.showToast('Pon un nombre al ejercicio', 'error'); return; }
        const idx = renamingExerciseIdx;
        const ex = activeWorkout.exercises[idx];
        ex.name = name;
        ex.previous = null; ex.pr = null;   // recargar histórico/PR del nuevo nombre
        renamingExerciseIdx = null;
        closeExerciseNameSheet();
        loadPreviousFor(idx);
        renderExercises();
        saveDraft();
        return;
    }

    const finalName = name || `Ejercicio ${activeWorkout.exercises.length + 1}`;
    activeWorkout.exercises.push(makeExercise(finalName));
    activeWorkout.currentIdx = activeWorkout.exercises.length - 1;
    closeExerciseNameSheet();
    loadPreviousFor(activeWorkout.currentIdx);
    renderExercises();
    updateProgress();
    saveDraft();
}

async function loadPresetExercises() {
    if (presetExercisesCache) return presetExercisesCache;
    try { presetExercisesCache = await apiCall('/exercises') || []; }
    catch (e) { presetExercisesCache = []; }
    return presetExercisesCache;
}

async function showPresetChips() {
    const wrap = document.getElementById('training-exercise-suggestions');
    const list = await loadPresetExercises();
    if (!Array.isArray(list) || !list.length) { hideExerciseSuggestions(); return; }
    // No pisar si el usuario ya está escribiendo (sugerencias de historial)
    const input = document.getElementById('new-exercise-name-training');
    if ((input.value || '').trim().length >= 2) return;
    wrap.innerHTML = list.map(e =>
        `<button type="button" class="inline-suggestion" onclick="selectExerciseSuggestion('${escHtml(e.name).replace(/'/g, '&#39;')}')">${escHtml(e.name)}</button>`
    ).join('');
    wrap.classList.add('open');
}

function selectExerciseSuggestion(name) {
    document.getElementById('new-exercise-name-training').value = name;
    hideExerciseSuggestions();
}

function renderExerciseSuggestions(items) {
    const wrap = document.getElementById('training-exercise-suggestions');
    if (!Array.isArray(items) || items.length === 0) {
        hideExerciseSuggestions();
        return;
    }

    wrap.innerHTML = items.map(item => {
        return `<button type="button" class="inline-suggestion" onclick="selectExerciseSuggestion('${escHtml(item.name).replace(/'/g, '&#39;')}')">
            ${escHtml(item.name)}
        </button>`;
    }).join('');
    wrap.classList.add('open');
}

function hideExerciseSuggestions() {
    const wrap = document.getElementById('training-exercise-suggestions');
    wrap.classList.remove('open');
    wrap.innerHTML = '';
}

async function fetchExerciseSuggestions(query) {
    const q = (query || '').trim();
    if (q.length < 2) {
        hideExerciseSuggestions();
        return;
    }

    try {
        const data = await apiCall(`/exercises/suggestions?q=${encodeURIComponent(q)}&limit=6`);
        const items = Array.isArray(data?.suggestions) ? data.suggestions : (Array.isArray(data) ? data : []);
        renderExerciseSuggestions(items);
    } catch (error) {
        hideExerciseSuggestions();
    }
}

function addTemplateExerciseRow(exercise = null) {
    const wrap = document.getElementById('template-edit-exercises');
    const row = document.createElement('div');
    row.className = 'template-edit-row';
    row.style.display = 'grid';
    row.style.gridTemplateColumns = '1.6fr 0.7fr 0.7fr auto';
    row.style.gap = '0.5rem';
    row.innerHTML = `
        <input class="input template-edit-ex-name" maxlength="100" placeholder="Ejercicio" value="${escHtml(exercise?.name || '')}">
        <input class="input template-edit-ex-sets" type="number" min="1" max="100" step="1" placeholder="Series" value="${exercise?.sets ?? 3}">
        <input class="input template-edit-ex-reps" type="number" min="0" max="1000" step="1" placeholder="Reps" value="${exercise?.reps ?? 10}">
        <button type="button" class="btn btn-ghost btn-icon" onclick="removeTemplateExerciseRow(this)" aria-label="Eliminar ejercicio">
            <i data-lucide="trash-2" style="width:0.95rem;height:0.95rem;"></i>
        </button>
    `;
    wrap.appendChild(row);
    lucide.createIcons();
}

function removeTemplateExerciseRow(btn) {
    const row = btn.closest('.template-edit-row');
    row?.remove();
}

function openTemplateEditor(templateId) {
    const template = allTemplates.find(t => String(t.id) === String(templateId));
    if (!template || !template.user_id) {
        window.showToast('Solo puedes editar tus plantillas personalizadas', 'error');
        return;
    }

    editingTemplateId = template.id;
    document.getElementById('template-edit-name').value = template.name || '';
    document.getElementById('template-edit-description').value = template.description || '';
    document.getElementById('template-edit-level').value = template.level || 'Intermedio';
    document.getElementById('template-edit-mode').value = template.mode || 'gym';
    document.getElementById('template-edit-duration').value = template.duration_minutes || 60;

    const wrap = document.getElementById('template-edit-exercises');
    wrap.innerHTML = '';
    (template.exercises || []).forEach(ex => addTemplateExerciseRow(ex));
    if (!template.exercises || template.exercises.length === 0) {
        addTemplateExerciseRow();
    }

    document.getElementById('template-editor-sheet').classList.add('open');
    lucide.createIcons();
}

function closeTemplateEditor() {
    editingTemplateId = null;
    document.getElementById('template-editor-sheet').classList.remove('open');
}

async function saveTemplateEdit() {
    if (!editingTemplateId) return;

    const saveBtn = document.getElementById('template-edit-save-btn');
    const rows = Array.from(document.querySelectorAll('#template-edit-exercises .template-edit-row'));
    const exercises = rows.map(row => ({
        name: (row.querySelector('.template-edit-ex-name')?.value || '').trim(),
        sets: Number.parseInt(row.querySelector('.template-edit-ex-sets')?.value || '0', 10) || 0,
        reps: Number.parseInt(row.querySelector('.template-edit-ex-reps')?.value || '0', 10) || 0,
    })).filter(ex => ex.name.length > 0);

    if (exercises.length === 0) {
        window.showToast('Añade al menos un ejercicio válido', 'error');
        return;
    }

    const payload = {
        name: (document.getElementById('template-edit-name').value || '').trim(),
        description: (document.getElementById('template-edit-description').value || '').trim(),
        level: document.getElementById('template-edit-level').value,
        mode: document.getElementById('template-edit-mode').value,
        duration_minutes: Number.parseInt(document.getElementById('template-edit-duration').value || '60', 10),
        exercises,
    };

    if (!payload.name) {
        window.showToast('El nombre es obligatorio', 'error');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = 'Guardando...';

    try {
        const updated = await apiCall(`/templates/${editingTemplateId}`, 'PUT', payload);
        allTemplates = allTemplates.map(t => String(t.id) === String(updated.id) ? updated : t);
        if (pendingTemplate && String(pendingTemplate.id) === String(updated.id)) {
            pendingTemplate = updated;
        }
        closeTemplateEditor();
        renderTemplates();
        window.showToast('Plantilla actualizada', 'success');
    } catch (error) {
        window.showToast(error?.message || 'No se pudo guardar la plantilla', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Guardar cambios';
    }
}

// ── Añadir serie ──────────────────────────────────────────────────────────
function openAddSet(editIndex = null) {
    const ex = activeWorkout.exercises[activeWorkout.currentIdx];
    editingSetIndex = Number.isInteger(editIndex) ? editIndex : null;

    const confirmBtn = document.getElementById('add-set-confirm-btn');
    const confirmText = confirmBtn.childNodes[2];

    if (editingSetIndex !== null) {
        document.getElementById('add-set-title').textContent = `Editar serie ${editingSetIndex + 1} — ${ex.name}`;
        if (confirmText) confirmText.textContent = ' Actualizar serie';
    } else {
        document.getElementById('add-set-title').textContent = `Serie ${ex.sets.length + 1} — ${ex.name}`;
        if (confirmText) confirmText.textContent = ' Guardar serie';
    }

    const setToUse = editingSetIndex !== null
        ? ex.sets[editingSetIndex]
        : (ex.sets[ex.sets.length - 1] || null);
    const preferredRest = setToUse?.rest_seconds ?? ex.defaultRestSeconds ?? ex.targetRest ?? getSavedDefaultRestSeconds();

    if (setToUse) {
        document.getElementById('set-weight').value = setToUse.weight_kg || '';
        document.getElementById('set-reps').value   = setToUse.reps || '';
        document.getElementById('last-set-hint').style.display = 'block';
        document.getElementById('last-set-hint').textContent = editingSetIndex !== null
            ? `Editando: ${setToUse.weight_kg || 0} kg × ${setToUse.reps || 0} reps · descanso ${preferredRest}s`
            : `Última serie: ${setToUse.weight_kg || 0} kg × ${setToUse.reps || 0} reps · descanso ${preferredRest}s`;
        setRpe(setToUse.rpe || null);
    } else {
        document.getElementById('set-weight').value = '';
        document.getElementById('set-reps').value   = ex.targetReps || '';
        document.getElementById('last-set-hint').style.display = 'none';
        setRpe(null);
    }

    setRestSeconds(preferredRest);
    currentRpe = setToUse?.rpe || null;
    document.getElementById('add-set-sheet').classList.add('open');
    setTimeout(() => document.getElementById('set-reps').focus(), 150);
    lucide.createIcons();
}

function closeAddSet() {
    editingSetIndex = null;
    document.getElementById('add-set-sheet').classList.remove('open');
}

function setRpe(val) {
    currentRpe = val;
    const labels = {null:'Sin definir',1:'Muy fácil',2:'Fácil',3:'Moderado',4:'',5:'Medio',6:'',7:'Duro',8:'Muy duro',9:'Casi máximo',10:'Máximo'};
    document.getElementById('rpe-label').textContent = val ? `${val} — ${labels[val] || ''}` : 'Sin definir';
    document.querySelectorAll('.rpe-btn').forEach(b => {
        const r = parseInt(b.dataset.rpe);
        const selected = r === val;
        b.style.background = selected ? 'var(--color-primary)' : 'var(--bg-muted)';
        b.style.color      = selected ? '#000' : 'var(--text-muted)';
        b.style.borderColor = selected ? 'var(--color-primary)' : 'var(--border-light)';
    });
}

function normalizeRestSeconds(value) {
    const parsed = Number.parseInt(value, 10);
    if (!Number.isFinite(parsed)) return 60;
    return Math.min(900, Math.max(5, parsed));
}

function getSavedDefaultRestSeconds() {
    try {
        return normalizeRestSeconds(localStorage.getItem(REST_DEFAULT_KEY));
    } catch (error) {
        return 60;
    }
}

function saveDefaultRestSeconds(value) {
    try {
        localStorage.setItem(REST_DEFAULT_KEY, String(normalizeRestSeconds(value)));
    } catch (error) {
        // noop: si localStorage no está disponible, se mantiene el comportamiento en memoria
    }
}

function setRestSeconds(seconds) {
    currentRestSeconds = normalizeRestSeconds(seconds);
    document.getElementById('set-rest-seconds').value = currentRestSeconds;
    document.getElementById('rest-label').textContent = `${currentRestSeconds}s`;

    document.querySelectorAll('.rest-btn').forEach(b => {
        const val = Number.parseInt(b.dataset.seconds, 10);
        const selected = val === currentRestSeconds;
        b.style.background = selected ? 'var(--color-primary)' : 'var(--bg-muted)';
        b.style.color = selected ? '#000' : 'var(--text-muted)';
        b.style.borderColor = selected ? 'var(--color-primary)' : 'var(--border-light)';
    });
}

function triggerSetFeedback(setIndex, mode) {
    pendingSetHighlightExerciseIdx = activeWorkout.currentIdx;
    pendingSetHighlightSetIdx = setIndex;

    clearTimeout(pendingSetHighlightHandle);

    const card = document.getElementById('sets-card');
    card.classList.remove('sets-card-feedback');
    requestAnimationFrame(() => card.classList.add('sets-card-feedback'));
    setTimeout(() => card.classList.remove('sets-card-feedback'), 320);

    if (navigator.vibrate) {
        navigator.vibrate(mode === 'update' ? [18] : [12, 28, 18]);
    }

    renderSets();
    bumpSetsCounter();
    pendingSetHighlightHandle = setTimeout(() => {
        pendingSetHighlightExerciseIdx = null;
        pendingSetHighlightSetIdx = null;
        renderSets();
    }, 900);
}

function bumpSetsCounter() {
    const el = document.getElementById('sets-counter');
    if (!el) return;
    el.classList.remove('counter-bump');
    requestAnimationFrame(() => el.classList.add('counter-bump'));
    setTimeout(() => el.classList.remove('counter-bump'), 320);
}

function showSetInlineFeedback(message, type = 'save') {
    const el = document.getElementById('set-inline-feedback');
    if (!el) return;

    clearTimeout(setInlineFeedbackHandle);
    el.textContent = message;
    el.classList.remove('show', 'update', 'delete');
    if (type === 'update') el.classList.add('update');
    if (type === 'delete') el.classList.add('delete');
    requestAnimationFrame(() => el.classList.add('show'));

    setInlineFeedbackHandle = setTimeout(() => {
        el.classList.remove('show', 'update', 'delete');
        el.textContent = '';
    }, 920);
}

function showExerciseInlineFeedback(message, type = 'move') {
    const el = document.getElementById('exercise-inline-feedback');
    if (!el) return;

    clearTimeout(exerciseInlineFeedbackHandle);
    el.textContent = message;
    el.classList.remove('show', 'add');
    if (type === 'add') el.classList.add('add');
    requestAnimationFrame(() => el.classList.add('show'));

    exerciseInlineFeedbackHandle = setTimeout(() => {
        el.classList.remove('show', 'add');
        el.textContent = '';
    }, 880);
}

function confirmAddSet() {
    const weight = parseFloat(document.getElementById('set-weight').value) || 0;
    const reps   = parseInt(document.getElementById('set-reps').value) || 0;
    const restSeconds = normalizeRestSeconds(document.getElementById('set-rest-seconds').value || currentRestSeconds);

    const ex = activeWorkout.exercises[activeWorkout.currentIdx];
    const setPayload = { weight_kg: weight, reps, rpe: currentRpe, rest_seconds: restSeconds };
    const wasEditing = editingSetIndex !== null;
    const affectedSetIndex = editingSetIndex !== null ? editingSetIndex : ex.sets.length;

    if (editingSetIndex !== null && ex.sets[editingSetIndex]) {
        ex.sets[editingSetIndex] = setPayload;
    } else {
        ex.sets.push(setPayload);
    }

    ex.defaultRestSeconds = restSeconds;
    saveDefaultRestSeconds(restSeconds);

    closeAddSet();
    renderSets();
    document.getElementById('sets-counter').textContent = `${ex.sets.length} serie${ex.sets.length !== 1 ? 's' : ''}`;

    renderExercise();
    triggerSetFeedback(affectedSetIndex, wasEditing ? 'update' : 'create');
    showSetInlineFeedback(wasEditing ? 'Serie actualizada' : 'Serie guardada', wasEditing ? 'update' : 'save');

    if (!wasEditing) {
        startRestTimer(restSeconds);
    }
}

// ── Timer de descanso ──────────────────────────────────────────────────────
function startRestTimer(seconds) {
    restTotal     = seconds;
    restRemaining = seconds;
    updateRestUI();
    document.getElementById('rest-overlay').classList.add('open');

    // Animación barra de progreso
    const prog = document.getElementById('rest-progress');
    prog.style.transition = 'none';
    prog.style.width = '100%';
    requestAnimationFrame(() => {
        prog.style.transition = `width ${seconds}s linear`;
        prog.style.width = '0%';
    });

    restTimerHandle = setInterval(() => {
        restRemaining--;
        updateRestUI();
        if (restRemaining <= 0) skipRest();
    }, 1000);
}

function updateRestUI() {
    const cd = document.getElementById('rest-countdown');
    cd.textContent = restRemaining;
    cd.className   = 'rest-countdown' + (restRemaining <= 10 ? ' ending' : '');
}

function skipRest() {
    clearInterval(restTimerHandle);
    document.getElementById('rest-overlay').classList.remove('open');
    document.getElementById('rest-progress').style.transition = 'none';
    document.getElementById('rest-progress').style.width = '0%';
}

function adjustRest(delta) {
    restRemaining = Math.max(5, restRemaining + delta);
    restTotal     = Math.max(5, restTotal + delta);
    updateRestUI();
}

// ── Terminar entrenamiento ────────────────────────────────────────────────
async function finishWorkout() {
    clearInterval(elapsedHandle);
    skipRestBar();

    // Modo pasos: sheet especial en lugar del flujo normal
    if (activeWorkout?.mode === 'pasos') {
        const durationSecs = Math.floor((Date.now() - activeWorkout.startTime) / 1000);
        document.getElementById('pasos-steps').value    = '';
        document.getElementById('pasos-calories').value = '';
        document.getElementById('pasos-duration').value = Math.max(0, Math.round(durationSecs / 60)) || '';
        document.getElementById('pasos-sheet').classList.add('open');
        return;
    }

    const payload = buildWorkoutPayload();
    if (payload.exercises.length === 0) {
        window.showToast('Marca o rellena al menos una serie antes de terminar', 'error');
        startElapsedTimer();
        return;
    }

    const btn = document.getElementById('btn-finish');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        const res = await apiCall('/workouts', 'POST', payload);

        const durationSecs = Math.floor((Date.now() - activeWorkout.startTime) / 1000);
        const totalSets = payload.exercises.reduce((acc, ex) => acc + ex.sets.length, 0);
        const totalVolume = payload.exercises.reduce((acc, ex) =>
            acc + ex.sets.reduce((sa, s) => sa + ((s.weight_kg || 0) * (s.reps || 0)), 0), 0);

        clearDraft();
        openWorkoutSummary({
            durationSecs,
            exercisesCount: payload.exercises.length,
            totalSets,
            totalVolume,
            newRecords: res.new_records || [],
        });

        window.showToast('Entrenamiento guardado correctamente', 'success');
    } catch(e) {
        window.showToast('Error al guardar. Inténtalo de nuevo.', 'error');
        btn.disabled = false;
        btn.textContent = 'Terminar';
        startElapsedTimer();
    }
}

function openWorkoutSummary(summary) {
    const minutes = Math.floor(summary.durationSecs / 60);
    const seconds = String(summary.durationSecs % 60).padStart(2, '0');
    const prList = document.getElementById('summary-pr-list');

    document.getElementById('summary-duration').textContent = `${minutes}:${seconds}`;
    document.getElementById('summary-volume').textContent = `${Math.round(summary.totalVolume)} kg`;
    document.getElementById('summary-exercises').textContent = summary.exercisesCount;
    document.getElementById('summary-sets').textContent = summary.totalSets;

    if (summary.newRecords.length === 0) {
        prList.innerHTML = '<p style="font-size:0.8125rem;color:var(--text-muted);margin:0;">Sin nuevos récords hoy, pero sumaste otra sesión.</p>';
    } else {
        prList.innerHTML = summary.newRecords.map((record, idx) => {
            const value = record.weight_kg || record.max_weight || null;
            const valueLabel = value ? `${value} kg` : 'Nuevo récord';
            return `<div class="badge badge-success" style="justify-content:space-between;display:flex;animation:fadeIn 0.3s ease ${idx * 0.08}s both;">
                <span>🏆 ${escHtml(record.name || 'Ejercicio')}</span>
                <strong>${escHtml(valueLabel)}</strong>
            </div>`;
        }).join('');
    }

    // Recap por ejercicio (lee activeWorkout, aún disponible)
    const recapEl = document.getElementById('summary-recap');
    if (recapEl && activeWorkout) {
        recapEl.innerHTML = activeWorkout.exercises.map(ex => {
            const withW = ex.sets.filter(s => s.weight_kg != null);
            const count = ex.sets.filter(s => s.weight_kg != null || s.reps != null || s.time_seconds != null || s.distance_m != null).length || ex.sets.length;
            const range = withW.length
                ? `${Math.min(...withW.map(s => s.weight_kg))}–${Math.max(...withW.map(s => s.weight_kg))} kg`
                : `${count} series`;
            return `<div style="display:flex;justify-content:space-between;font-size:0.8125rem;padding:0.3rem 0;border-top:1px solid var(--border-light);">
                <span>${escHtml(ex.name)}</span><span style="color:var(--text-muted);">${count} series · ${range}</span></div>`;
        }).join('');
    }

    document.getElementById('workout-summary-sheet').classList.add('open');
}

function closeWorkoutSummary() {
    window.location.href = '/';
}

// ── Sheet de pasos diarios ────────────────────────────────────────────────
function closePasosSheet() {
    document.getElementById('pasos-sheet').classList.remove('open');
    // Reanudamos el timer por si el usuario decide no guardar
    startElapsedTimer();
}

async function savePasosWorkout() {
    const steps    = parseInt(document.getElementById('pasos-steps').value, 10) || 0;
    const calories = parseInt(document.getElementById('pasos-calories').value, 10) || null;
    const duration = parseInt(document.getElementById('pasos-duration').value, 10) || 0;

    const btn = document.querySelector('#pasos-sheet .btn-primary');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        await apiCall('/workouts', 'POST', {
            mode:             'pasos',
            date:             new Date().toISOString().slice(0, 10),
            duration_minutes: duration,
            calories_burned:  calories,
            exercises: steps > 0 ? [{ name: 'Pasos del día', sets: [{ reps: steps }] }] : [],
        });

        document.getElementById('pasos-sheet').classList.remove('open');
        window.showToast('Actividad guardada correctamente', 'success');

        // Mostrar resumen simplificado
        openWorkoutSummary({
            durationSecs:   duration * 60,
            exercisesCount: 1,
            totalSets:      1,
            totalVolume:    0,
            newRecords:     [],
        });
    } catch (e) {
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
}

function goToHistoryFromSummary() {
    window.location.href = '/history';
}

// ── Cancelar ──────────────────────────────────────────────────────────────
function confirmAbort() {
    document.getElementById('abort-modal').style.display = 'flex';
}

function closeAbortModal() {
    document.getElementById('abort-modal').style.display = 'none';
}

function abortWorkout() {
    clearInterval(elapsedHandle);
    skipRestBar();
    clearDraft();
    activeWorkout = null;
    document.getElementById('training-screen').style.display = 'none';
    document.getElementById('phase-select').classList.add('active');
    document.querySelector('nav.navbar')?.style.removeProperty('display');
    closeAbortModal();
    checkResumeBanner();
    lucide.createIcons();
}

// ── Utilidades ────────────────────────────────────────────────────────────
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Cerrar sheet al tocar fuera
document.getElementById('pasos-sheet').addEventListener('click', function(e) {
    if (e.target === this) closePasosSheet();
});
document.getElementById('template-preview-sheet').addEventListener('click', function(e) {
    if (e.target === this) closeTemplatePreview();
});
document.getElementById('workout-summary-sheet').addEventListener('click', function(e) {
    if (e.target === this) closeWorkoutSummary();
});
document.getElementById('template-editor-sheet').addEventListener('click', function(e) {
    if (e.target === this) closeTemplateEditor();
});
document.getElementById('exercise-name-sheet').addEventListener('click', function(e) {
    if (e.target === this) closeExerciseNameSheet();
});

document.getElementById('new-exercise-name-training').addEventListener('input', function(e) {
    clearTimeout(exerciseSuggestionTimer);
    const query = e.target.value;
    if ((query || '').trim().length < 2) { showPresetChips(); return; }
    exerciseSuggestionTimer = setTimeout(() => fetchExerciseSuggestions(query), 180);
});

document.getElementById('new-exercise-name-training').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        confirmNewExerciseName();
    }
});

document.getElementById('abort-modal').addEventListener('click', function(e) {
    if (e.target === this) closeAbortModal();
});

// Tecla Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (document.getElementById('pasos-sheet').classList.contains('open')) closePasosSheet();
        else if (document.getElementById('template-preview-sheet').classList.contains('open')) closeTemplatePreview();
        else if (document.getElementById('template-editor-sheet').classList.contains('open')) closeTemplateEditor();
        else if (document.getElementById('exercise-name-sheet').classList.contains('open')) closeExerciseNameSheet();
        else if (document.getElementById('workout-summary-sheet').classList.contains('open')) closeWorkoutSummary();
    }
});

try { currentMode = localStorage.getItem(MODE_KEY) || 'gym'; } catch (e) {}
document.querySelectorAll('.mode-tabs .pill-tab').forEach(b => b.classList.toggle('active', b.dataset.mode === currentMode));
loadTemplates();
checkResumeBanner();
</script>
@endpush
