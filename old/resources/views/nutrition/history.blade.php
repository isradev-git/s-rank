@extends('layouts.app')

@section('title', 'Historial Nutricional')

@section('content')
<div class="page-container">

    {{-- ── CABECERA ────────────────────────────────────────────────── --}}
    <header class="page-header sticky-header">
        <div style="display:flex;align-items:center;gap:0.75rem;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <a href="/nutrition" class="btn btn-ghost btn-icon">
                    <i data-lucide="arrow-left"></i>
                </a>
                <h1 class="page-title">Historial nutricional</h1>
            </div>
            <a href="/informe-salud" class="btn btn-ghost btn-icon" aria-label="Informe para el médico" title="Informe para el médico">
                <i data-lucide="file-text"></i>
            </a>
        </div>
    </header>

    {{-- ── SELECTOR DE RANGO ──────────────────────────────────────── --}}
    <section style="padding:1rem 1rem 0;display:flex;flex-direction:column;gap:0.75rem;">
        <div class="tabs-container" id="rangeTabs">
            <button class="tab-btn active" data-days="7">7 días</button>
            <button class="tab-btn" data-days="14">14 días</button>
            <button class="tab-btn" data-days="30">30 días</button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:0.5rem;align-items:end;">
            <div>
                <label for="histFrom" style="display:block;font-size:0.66rem;font-weight:700;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Desde</label>
                <input type="date" id="histFrom" class="input" style="padding:0.45rem 0.6rem;">
            </div>
            <div>
                <label for="histTo" style="display:block;font-size:0.66rem;font-weight:700;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Hasta</label>
                <input type="date" id="histTo" class="input" style="padding:0.45rem 0.6rem;">
            </div>
            <button class="btn btn-outline btn-sm" onclick="applyCustomRange()" style="height:fit-content;">Aplicar</button>
        </div>

        <button class="btn btn-primary btn-sm btn-block" onclick="exportNutritionCsv()">
            <i data-lucide="download" style="width:0.875rem;height:0.875rem;"></i> Exportar CSV
        </button>
    </section>

    {{-- ── RESUMEN PROMEDIO ────────────────────────────────────────── --}}
    <section style="padding:1rem 1rem 0;">
        <div class="section-header"><h2 class="section-title">Promedio diario</h2></div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.75rem;" id="avgCards">
            <div class="stat-card card">
                <span class="stat-value" id="avgCalories">–</span>
                <span class="stat-label">kcal / día</span>
            </div>
            <div class="stat-card card">
                <span class="stat-value" id="avgProtein">–</span>
                <span class="stat-label">g proteína / día</span>
            </div>
            <div class="stat-card card">
                <span class="stat-value" id="avgCarbs">–</span>
                <span class="stat-label">g carbos / día</span>
            </div>
            <div class="stat-card card">
                <span class="stat-value" id="avgFat">–</span>
                <span class="stat-label">g grasa / día</span>
            </div>
            {{-- Micronutriente: fibra promedio --}}
            <div class="stat-card card" style="border:1px solid rgba(52,211,153,0.2);background:rgba(52,211,153,0.04);">
                <span class="stat-value" id="avgFiber" style="color:#34d399;">–</span>
                <span class="stat-label">g fibra / día</span>
            </div>
            {{-- Micronutriente: azúcar promedio --}}
            <div class="stat-card card" style="border:1px solid rgba(248,113,113,0.18);background:rgba(248,113,113,0.04);">
                <span class="stat-value" id="avgSugar" style="color:#f87171;">–</span>
                <span class="stat-label">g azúcar / día</span>
            </div>
        </div>
    </section>

    {{-- ── GRÁFICA DE CALORÍAS ─────────────────────────────────────── --}}
    <section style="padding:1rem 1rem 0;">
        <div class="section-header"><h2 class="section-title">Calorías por día</h2></div>
        <div class="card" style="padding:1rem;">
            <div id="chartSkeleton" class="skeleton" style="height:140px;border-radius:0.5rem;"></div>
            <canvas id="caloriesChart" style="display:none;max-height:160px;"></canvas>
        </div>
    </section>

    {{-- ── HISTORIAL POR DÍA ───────────────────────────────────────── --}}
    <section style="padding:1rem 1rem 6rem;">
        <div class="section-header"><h2 class="section-title">Detalle por día</h2></div>
        <div id="historyList">
            <div class="skeleton-card"></div>
            <div class="skeleton-card" style="margin-top:0.5rem;"></div>
            <div class="skeleton-card" style="margin-top:0.5rem;"></div>
        </div>
    </section>

</div>
@endsection

@push('styles')
<style>
.history-day-card   { padding:0.9rem 1rem; display:flex; justify-content:space-between; align-items:center; }
.history-day-card + .history-day-card { border-top:1px solid rgba(255,255,255,.06); }
.history-date       { font-size:0.85rem; font-weight:600; color:var(--color-foreground); }
.history-macros     { font-size:0.72rem; color:var(--color-muted); margin-top:0.2rem; }
.history-kcal       { font-size:1rem; font-weight:700; color:var(--color-primary); }
.goal-indicator     { display:inline-block; width:6px; height:6px; border-radius:50%; margin-right:0.3rem; }
</style>
@endpush

@push('scripts')
{{-- Chart.js para la gráfica --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
let currentDays   = 7;
let caloriesChart = null;
let goalCalories  = 2000;
let customRange   = null;   // {from, to} cuando se usa rango de fechas libre
let lastHistory   = [];     // último historial cargado (para exportar CSV)

document.addEventListener('DOMContentLoaded', async () => {
    // Cargamos el objetivo para poder comparar
    try {
        const data = await apiCall('/nutrition/goal');
        goalCalories = data.goal.daily_calories ?? 2000;
    } catch(e) {}

    // Tabs de rango de días (limpian el rango de fechas libre)
    document.querySelectorAll('#rangeTabs .tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#rangeTabs .tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentDays = parseInt(btn.dataset.days);
            customRange = null;
            document.getElementById('histFrom').value = '';
            document.getElementById('histTo').value = '';
            loadHistory();
        });
    });

    loadHistory();
    lucide.createIcons();
});

// ── Aplica un rango de fechas libre ───────────────────────────────────────
function applyCustomRange() {
    const from = document.getElementById('histFrom').value;
    const to   = document.getElementById('histTo').value;
    if (!from && !to) { showToast('Elige al menos una fecha', 'error'); return; }
    customRange = { from, to };
    document.querySelectorAll('#rangeTabs .tab-btn').forEach(b => b.classList.remove('active'));
    loadHistory();
}

// ── Exporta el historial mostrado a CSV ───────────────────────────────────
function exportNutritionCsv() {
    if (!lastHistory.length) { showToast('No hay datos para exportar', 'error'); return; }
    const head = ['Fecha', 'Calorias', 'Proteina_g', 'Carbohidratos_g', 'Grasas_g', 'Fibra_g', 'Azucar_g'];
    const rows = lastHistory.map(d => [
        d.date, Math.round(d.calories), Math.round(d.protein), Math.round(d.carbs),
        Math.round(d.fat), Math.round(d.fiber ?? 0), Math.round(d.sugar ?? 0),
    ]);
    const csv = [head, ...rows]
        .map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(','))
        .join('\n');
    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'nutricion-fitloop.csv';
    a.click();
    URL.revokeObjectURL(url);
    showToast('CSV generado');
}

// ── Carga el historial de calorías ────────────────────────────────────────
async function loadHistory() {
    // Mostramos skeletons mientras carga
    document.getElementById('historyList').innerHTML = [1,2,3].map(() =>
        `<div class="skeleton-card" style="margin-bottom:0.5rem;"></div>`
    ).join('');
    document.getElementById('avgCalories').textContent = '–';
    document.getElementById('avgProtein').textContent  = '–';
    document.getElementById('avgCarbs').textContent    = '–';
    document.getElementById('avgFat').textContent      = '–';
    document.getElementById('avgFiber').textContent    = '–';
    document.getElementById('avgSugar').textContent    = '–';

    const query = customRange
        ? `date_from=${customRange.from}&date_to=${customRange.to}`
        : `days=${currentDays}`;

    try {
        const data = await apiCall(`/meals/history?${query}`);
        lastHistory = data.history || [];
        renderHistory(data.history);
        renderChart(data.history);
        renderAverages(data.history);
    } catch(e) {
        document.getElementById('historyList').innerHTML =
            '<p style="color:var(--color-muted);text-align:center;padding:2rem;">Error al cargar el historial.</p>';
    }
}

// ── Parsea 'YYYY-MM-DD' sin problemas de timezone ─────────────────────────
function parseLocalDate(dateStr) {
    const [y, m, d] = String(dateStr).split('T')[0].split('-').map(Number);
    return new Date(y, m - 1, d);
}

// ── Renderiza el listado de días ──────────────────────────────────────────
function renderHistory(history) {
    const list = document.getElementById('historyList');
    if (!history.length) {
        list.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i data-lucide="calendar-x"></i></div>
                <p class="empty-state-title">Sin datos</p>
                <p class="empty-state-desc">No hay registros de comidas en este período.</p>
            </div>`;
        lucide.createIcons();
        return;
    }

    // Mostramos los días en orden inverso (más reciente primero)
    const sorted = [...history].reverse();
    list.innerHTML = `<div class="card">` + sorted.map(day => {
        const date      = parseLocalDate(day.date);
        const dateLabel = date.toLocaleDateString('es-ES', { weekday:'short', day:'numeric', month:'short' });
        const pct       = Math.round((day.calories / goalCalories) * 100);
        // Dot verde si está dentro del objetivo (80-110%), rojo si está muy lejos
        const dotColor  = pct >= 80 && pct <= 110 ? '#4ade80' : pct > 110 ? '#f87171' : '#fbbf24';

        return `
            <div class="history-day-card">
                <div>
                    <div class="history-date">
                        <span class="goal-indicator" style="background:${dotColor};"></span>
                        ${capitalizeFirst(dateLabel)}
                    </div>
                    <div class="history-macros">
                        ${Math.round(day.protein)}g P · ${Math.round(day.carbs)}g C · ${Math.round(day.fat)}g G${day.fiber > 0 ? ' · <span style="color:#34d399;">' + Math.round(day.fiber) + 'g fibra</span>' : ''}${day.sugar > 0 ? ' · <span style="color:#f87171;">' + Math.round(day.sugar) + 'g azúcar</span>' : ''}
                    </div>
                </div>
                <span class="history-kcal">${Math.round(day.calories)} kcal</span>
            </div>
        `;
    }).join('') + `</div>`;
}

// ── Renderiza la gráfica con Chart.js ────────────────────────────────────
function renderChart(history) {
    document.getElementById('chartSkeleton').style.display = 'none';
    const canvas = document.getElementById('caloriesChart');
    canvas.style.display = 'block';

    // Si ya hay una gráfica, la destruimos para redibujar
    if (caloriesChart) { caloriesChart.destroy(); }

    if (!history.length) return;

    const labels   = history.map(d => {
        const date = parseLocalDate(d.date);
        return date.toLocaleDateString('es-ES', { day:'numeric', month:'short' });
    });
    const calories = history.map(d => Math.round(d.calories));

    caloriesChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Calorías',
                data: calories,
                backgroundColor: calories.map(c => c > goalCalories ? 'rgba(248,113,113,0.7)' : 'rgba(245,158,11,0.7)'),
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.raw} kcal`,
                    },
                },
            },
            scales: {
                x: { grid: { color:'rgba(255,255,255,.05)' }, ticks: { color:'#9ca3af', font:{size:10} } },
                y: {
                    grid: { color:'rgba(255,255,255,.05)' },
                    ticks: { color:'#9ca3af', font:{size:10} },
                    // Línea del objetivo
                },
            },
        },
    });
}

// ── Calcula y muestra promedios ───────────────────────────────────────────
function renderAverages(history) {
    if (!history.length) return;
    const avg = (key) => Math.round(history.reduce((sum, d) => sum + d[key], 0) / history.length);
    document.getElementById('avgCalories').textContent = avg('calories').toLocaleString('es');
    document.getElementById('avgProtein').textContent  = avg('protein') + 'g';
    document.getElementById('avgCarbs').textContent    = avg('carbs') + 'g';
    document.getElementById('avgFat').textContent      = avg('fat') + 'g';
    document.getElementById('avgFiber').textContent    = avg('fiber') + 'g';
    document.getElementById('avgSugar').textContent    = avg('sugar') + 'g';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
@endpush
