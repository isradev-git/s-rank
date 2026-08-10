@extends('layouts.app')

@section('content')
<div class="pb-24">

    <div style="position:sticky; top:0; z-index:30; background:rgba(10,10,11,0.92); backdrop-filter:blur(16px);
                border-bottom:1px solid var(--border-light); padding:1rem 1.25rem;">
        <div class="flex items-center gap-3">
            <a href="/" class="btn btn-ghost btn-icon" aria-label="Volver al inicio" style="color:var(--text-muted);">
                <i data-lucide="arrow-left" style="width:1.25rem;height:1.25rem;"></i>
            </a>
            <div>
                <h1 style="font-size:1.25rem; font-weight:800; margin:0; color:var(--text-primary);">Historial</h1>
                <p style="font-size:0.6875rem; color:var(--text-muted); margin:0;" id="history-count">Cargando...</p>
            </div>
        </div>
    </div>

    <div style="padding:0.75rem 1.25rem 1rem; border-bottom:1px solid var(--border-light); display:flex; flex-direction:column; gap:0.75rem;">
        <div style="position:relative;">
            <i data-lucide="search" style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);
               width:1rem;height:1rem;color:var(--text-muted);pointer-events:none;"></i>
            <input type="text" id="history-search" placeholder="Buscar por ejercicio, nota o modo..."
                   class="input" style="padding-left:2.5rem;"
                   oninput="onFilterChange()">
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.625rem;">
            <div>
                <label for="history-date-from" style="display:block;font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Desde</label>
                <input type="date" id="history-date-from" class="input" onchange="onDateFilterChange()">
            </div>
            <div>
                <label for="history-date-to" style="display:block;font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Hasta</label>
                <input type="date" id="history-date-to" class="input" onchange="onDateFilterChange()">
            </div>
        </div>

        <div style="display:flex;gap:0.5rem;overflow-x:auto;" class="no-scrollbar">
            <button class="pill-tab active" data-mode="" onclick="onModeFilter(this)">Todos</button>
            <button class="pill-tab" data-mode="gym" onclick="onModeFilter(this)">Gym</button>
            <button class="pill-tab" data-mode="home" onclick="onModeFilter(this)">Casa</button>
            <button class="pill-tab" data-mode="calisthenics" onclick="onModeFilter(this)">Calistenia</button>
            <button class="pill-tab" data-mode="swimming" onclick="onModeFilter(this)">Natación</button>
        </div>

        <div style="display:flex;gap:0.5rem;">
            <button class="btn btn-outline btn-sm" style="flex:1;" onclick="clearHistoryFilters()">
                <i data-lucide="rotate-ccw" style="width:0.875rem;height:0.875rem;"></i>
                Limpiar filtros
            </button>
            <button class="btn btn-primary btn-sm" style="flex:1;" onclick="exportHistoryCsv()">
                <i data-lucide="download" style="width:0.875rem;height:0.875rem;"></i>
                Exportar CSV
            </button>
        </div>
    </div>

    <div class="p-5" style="display:flex;flex-direction:column;gap:1rem;">
        <div style="display:flex;gap:0.5rem;overflow-x:auto;" class="no-scrollbar">
            <button class="pill-tab active" data-history-view="list" onclick="switchHistoryView(this)">Lista</button>
            <button class="pill-tab" data-history-view="calendar" onclick="switchHistoryView(this)">Calendario</button>
            <button class="pill-tab" data-history-view="reports" onclick="switchHistoryView(this)">Informes</button>
        </div>

        <section id="history-view-list" style="display:flex;flex-direction:column;gap:1rem;">
            <div class="card" style="padding:1.25rem;">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="icon-wrap icon-wrap-sm" style="background:rgba(59,130,246,0.1);">
                            <i data-lucide="line-chart" style="width:0.875rem;height:0.875rem;color:var(--color-blue);"></i>
                        </div>
                        <span style="font-size:0.9375rem;font-weight:700;">Progreso por ejercicio</span>
                    </div>
                </div>

                <div style="position:relative;margin-bottom:0.625rem;">
                    <i data-lucide="search" style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);
                       width:1rem;height:1rem;color:var(--text-muted);pointer-events:none;"></i>
                    <input type="text" id="exercise-progress-search" class="input" placeholder="Ej: Press banca"
                           style="padding-left:2.5rem;">
                    <div id="exercise-progress-suggestions" style="display:none;position:absolute;left:0;right:0;top:100%;margin-top:0.25rem;z-index:40;
                        background:var(--bg-card);border:1px solid var(--border-medium);border-radius:0.75rem;max-height:10rem;overflow-y:auto;"></div>
                </div>

                <div style="display:flex;gap:0.5rem;overflow-x:auto;padding-bottom:0.5rem;" class="no-scrollbar" id="progress-metric-tabs">
                    <button class="pill-tab active" data-metric="weight_kg" onclick="setProgressMetric(this)">Peso max</button>
                    <button class="pill-tab" data-metric="volume" onclick="setProgressMetric(this)">Volumen</button>
                    <button class="pill-tab" data-metric="reps" onclick="setProgressMetric(this)">Repeticiones</button>
                </div>

                <div id="progress-chart-empty" style="display:flex;align-items:center;justify-content:center;height:10rem;color:var(--text-muted);font-size:0.8125rem;">
                    Busca un ejercicio para ver su evolución.
                </div>
                <canvas id="exercise-progress-chart" style="display:none;max-height:12rem;"></canvas>

                <div id="progress-stats" style="display:none;margin-top:0.875rem;grid-template-columns:repeat(3,1fr);gap:0.625rem;">
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="progress-stat-pr" style="font-size:0.95rem;">-</span>
                        <span class="stat-label">PR actual</span>
                    </div>
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="progress-stat-best-volume" style="font-size:0.95rem;">-</span>
                        <span class="stat-label">Mejor volumen</span>
                    </div>
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="progress-stat-sessions" style="font-size:0.95rem;">-</span>
                        <span class="stat-label">Sesiones</span>
                    </div>
                </div>
            </div>

            <div id="history-filter-summary" class="card" style="padding:1rem 1.25rem;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.75rem;">
                <div>
                    <p style="margin:0 0 0.25rem;font-size:0.6875rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;">Entrenos</p>
                    <p id="history-summary-workouts" style="margin:0;font-size:1.1rem;font-weight:800;">0</p>
                </div>
                <div>
                    <p style="margin:0 0 0.25rem;font-size:0.6875rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;">Minutos</p>
                    <p id="history-summary-minutes" style="margin:0;font-size:1.1rem;font-weight:800;">0</p>
                </div>
                <div>
                    <p style="margin:0 0 0.25rem;font-size:0.6875rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;">Días activos</p>
                    <p id="history-summary-days" style="margin:0;font-size:1.1rem;font-weight:800;">0</p>
                </div>
            </div>

            <div id="history-list" class="space-y-3">
                @for($i = 0; $i < 4; $i++)
                <div class="card" style="padding:1rem;">
                    <div class="flex justify-between items-start mb-3">
                        <div class="space-y-2" style="flex:1;">
                            <div class="skeleton skeleton-title" style="width:50%;"></div>
                            <div class="skeleton skeleton-text" style="width:35%;"></div>
                        </div>
                        <div class="skeleton" style="width:2rem;height:2rem;border-radius:var(--radius-md);"></div>
                    </div>
                    <div class="skeleton skeleton-text" style="width:75%;"></div>
                </div>
                @endfor
            </div>
        </section>

        <section id="history-view-calendar" style="display:none;flex-direction:column;gap:1rem;">
            <div class="card" style="padding:1.25rem;">
                <div class="flex items-center justify-between" style="gap:0.75rem;flex-wrap:wrap;">
                    <div class="flex items-center gap-2">
                        <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.12);">
                            <i data-lucide="calendar-range" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
                        </div>
                        <div>
                            <p style="margin:0;font-size:0.9375rem;font-weight:700;">Actividad</p>
                            <p id="calendar-label" style="margin:0;font-size:0.75rem;color:var(--text-muted);">Cargando...</p>
                        </div>
                    </div>
                    <div style="display:flex;gap:0.5rem;overflow-x:auto;" class="no-scrollbar">
                        <button class="pill-tab active" data-calendar-period="week" onclick="setCalendarPeriod(this)">Semanal</button>
                        <button class="pill-tab" data-calendar-period="month" onclick="setCalendarPeriod(this)">Mensual</button>
                        <button class="pill-tab" data-calendar-period="year" onclick="setCalendarPeriod(this)">Anual</button>
                    </div>
                </div>

                <div class="flex items-center justify-between" style="gap:0.75rem;margin-top:1rem;">
                    <button class="btn btn-ghost btn-icon" onclick="shiftCalendar(-1)" aria-label="Periodo anterior">
                        <i data-lucide="chevron-left" style="width:1rem;height:1rem;"></i>
                    </button>
                    <strong id="calendar-period-title" style="font-size:0.875rem;"></strong>
                    <button class="btn btn-ghost btn-icon" onclick="shiftCalendar(1)" aria-label="Periodo siguiente">
                        <i data-lucide="chevron-right" style="width:1rem;height:1rem;"></i>
                    </button>
                </div>

                <div id="calendar-summary" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0.625rem;margin-top:1rem;">
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="calendar-total-workouts" style="font-size:0.95rem;">0</span>
                        <span class="stat-label">Entrenos</span>
                    </div>
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="calendar-total-minutes" style="font-size:0.95rem;">0</span>
                        <span class="stat-label">Minutos</span>
                    </div>
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="calendar-active-days" style="font-size:0.95rem;">0</span>
                        <span class="stat-label">Días activos</span>
                    </div>
                    <div class="stat-card" style="padding:0.75rem;">
                        <span class="stat-value" id="calendar-average-duration" style="font-size:0.95rem;">0</span>
                        <span class="stat-label">Media min</span>
                    </div>
                </div>

                <div id="calendar-skeleton" class="skeleton" style="height:18rem;border-radius:0.75rem;margin-top:1rem;"></div>

                <div id="calendar-grid-wrapper" style="display:none;margin-top:1rem;">
                    <div id="calendar-weekday-head" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:0.375rem;margin-bottom:0.375rem;"></div>
                    <div id="calendar-grid" style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:0.375rem;"></div>
                </div>

                <div id="annual-heatmap-wrapper" style="display:none;margin-top:1rem;">
                    <div id="heatmap-summary" style="display:none;margin-bottom:0.875rem;">
                        <p id="heatmap-summary-text" style="font-size:0.75rem;color:var(--text-muted);margin:0;"></p>
                    </div>
                    <div id="heatmap-container" style="display:none;">
                        <div style="display:flex;gap:3px;">
                            <div style="display:flex;flex-direction:column;gap:3px;margin-right:2px;flex-shrink:0;">
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;"></div>
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;">L</div>
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;"></div>
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;">X</div>
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;"></div>
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;">V</div>
                                <div style="height:10px;font-size:0.55rem;color:var(--text-muted);line-height:10px;"></div>
                            </div>
                            <div style="overflow-x:auto;flex:1;" class="no-scrollbar">
                                <div id="heatmap-months" style="display:flex;margin-bottom:0.25rem;"></div>
                                <div id="heatmap-grid" style="display:flex;gap:3px;"></div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;margin-top:0.625rem;justify-content:flex-end;">
                            <span style="font-size:0.6rem;color:var(--text-muted);">Menos</span>
                            <div style="width:10px;height:10px;border-radius:2px;background:var(--bg-muted);"></div>
                            <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,0.25);"></div>
                            <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,0.55);"></div>
                            <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,0.8);"></div>
                            <div style="width:10px;height:10px;border-radius:2px;background:#f59e0b;"></div>
                            <span style="font-size:0.6rem;color:var(--text-muted);">Más</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="calendar-day-panel" class="card" style="padding:1.25rem;">
                <div class="flex items-center justify-between mb-3" style="gap:0.75rem;flex-wrap:wrap;">
                    <div>
                        <p style="margin:0;font-size:0.9375rem;font-weight:700;">Detalle del día</p>
                        <p id="calendar-day-title" style="margin:0;font-size:0.75rem;color:var(--text-muted);">Toca un día con actividad.</p>
                    </div>
                    <button class="btn btn-outline btn-sm" onclick="clearCalendarDaySelection()">Limpiar</button>
                </div>
                <div id="calendar-day-list">
                    <div class="empty-state" style="padding:1.5rem 0;">
                        <div class="empty-state-icon"><i data-lucide="calendar-search" style="width:1.25rem;height:1.25rem;"></i></div>
                        <p class="empty-state-title" style="font-size:0.875rem;">Sin día seleccionado</p>
                        <p class="empty-state-desc" style="font-size:0.75rem;">Selecciona un día para ver entrenamientos concretos.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="history-view-reports" style="display:none;flex-direction:column;gap:1rem;">
            <div class="card" style="padding:1.25rem;">
                <div class="flex items-center justify-between" style="gap:0.75rem;flex-wrap:wrap;">
                    <div class="flex items-center gap-2">
                        <div class="icon-wrap icon-wrap-sm" style="background:rgba(16,185,129,0.12);">
                            <i data-lucide="file-bar-chart-2" style="width:0.875rem;height:0.875rem;color:var(--color-teal);"></i>
                        </div>
                        <div>
                            <p style="margin:0;font-size:0.9375rem;font-weight:700;">Informes</p>
                            <p id="report-range-label" style="margin:0;font-size:0.75rem;color:var(--text-muted);">Cargando...</p>
                        </div>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button class="btn btn-outline btn-sm" onclick="exportReportSummaryCsv()">
                            <i data-lucide="sheet" style="width:0.875rem;height:0.875rem;"></i>
                            Resumen CSV
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="exportReportDetailCsv()">
                            <i data-lucide="download" style="width:0.875rem;height:0.875rem;"></i>
                            Detalle CSV
                        </button>
                    </div>
                </div>

                <div style="display:flex;gap:0.5rem;overflow-x:auto;margin-top:1rem;" class="no-scrollbar" id="report-range-tabs">
                    <button class="pill-tab" data-report-range="7d" onclick="setReportRange(this)">7 días</button>
                    <button class="pill-tab active" data-report-range="30d" onclick="setReportRange(this)">30 días</button>
                    <button class="pill-tab" data-report-range="90d" onclick="setReportRange(this)">90 días</button>
                    <button class="pill-tab" data-report-range="365d" onclick="setReportRange(this)">12 meses</button>
                    <button class="pill-tab" data-report-range="all" onclick="setReportRange(this)">Todo</button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.625rem;margin-top:0.875rem;">
                    <div>
                        <label for="report-date-from" style="display:block;font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Desde</label>
                        <input type="date" id="report-date-from" class="input">
                    </div>
                    <div>
                        <label for="report-date-to" style="display:block;font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Hasta</label>
                        <input type="date" id="report-date-to" class="input">
                    </div>
                </div>

                <div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
                    <button class="btn btn-outline btn-sm" style="flex:1;" onclick="resetReportDates()">Usar rango rápido</button>
                    <button class="btn btn-primary btn-sm" style="flex:1;" onclick="applyReportCustomRange()">Aplicar fechas</button>
                </div>
            </div>

            <div id="report-summary-grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.75rem;">
                <div class="stat-card"><div class="stat-value" id="report-total-workouts">0</div><div class="stat-label">Entrenos</div></div>
                <div class="stat-card"><div class="stat-value" id="report-total-minutes">0</div><div class="stat-label">Minutos</div></div>
                <div class="stat-card"><div class="stat-value" id="report-active-days">0</div><div class="stat-label">Días activos</div></div>
                <div class="stat-card"><div class="stat-value" id="report-average-duration">0</div><div class="stat-label">Media min</div></div>
                <div class="stat-card"><div class="stat-value" id="report-total-sets">0</div><div class="stat-label">Series</div></div>
                <div class="stat-card"><div class="stat-value" id="report-total-volume">0</div><div class="stat-label">Volumen</div></div>
            </div>

            <div class="card" style="padding:1.25rem;">
                <div class="section-header" style="margin-bottom:0.75rem;">
                    <h3 class="section-title" style="margin:0;">Nutrición del periodo</h3>
                </div>
                <div id="report-nutrition-grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.625rem;margin-bottom:0.875rem;">
                    <div class="stat-card" style="padding:0.75rem;"><div class="stat-value" id="report-nutrition-calories" style="font-size:0.95rem;">0</div><div class="stat-label">Kcal totales</div></div>
                    <div class="stat-card" style="padding:0.75rem;"><div class="stat-value" id="report-nutrition-protein" style="font-size:0.95rem;">0</div><div class="stat-label">Proteína (g)</div></div>
                    <div class="stat-card" style="padding:0.75rem;"><div class="stat-value" id="report-nutrition-water" style="font-size:0.95rem;">0</div><div class="stat-label">Agua (ml)</div></div>
                    <div class="stat-card" style="padding:0.75rem;"><div class="stat-value" id="report-nutrition-avg-calories" style="font-size:0.95rem;">0</div><div class="stat-label">Media kcal/día</div></div>
                    <div class="stat-card" style="padding:0.75rem;"><div class="stat-value" id="report-nutrition-goal" style="font-size:0.95rem;">-</div><div class="stat-label">Objetivo kcal</div></div>
                    <div class="stat-card" style="padding:0.75rem;"><div class="stat-value" id="report-nutrition-supplements" style="font-size:0.95rem;">0%</div><div class="stat-label">Suplementos</div></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.75rem;">
                    <div>
                        <p style="margin:0 0 0.375rem;font-size:0.75rem;color:var(--text-muted);">Calorías por tipo de comida</p>
                        <div id="report-mealtype-list"></div>
                    </div>
                    <div>
                        <p style="margin:0 0 0.375rem;font-size:0.75rem;color:var(--text-muted);">Tendencia nutricional</p>
                        <canvas id="report-nutrition-chart" style="max-height:11rem;"></canvas>
                    </div>
                </div>
            </div>

            <div class="card" style="padding:1.25rem;">
                <div class="section-header" style="margin-bottom:0.75rem;">
                    <h3 class="section-title" style="margin:0;">Comparativa</h3>
                </div>
                <p id="report-comparison-label" style="margin:0 0 0.75rem;font-size:0.75rem;color:var(--text-muted);">Sin comparación disponible.</p>
                <div id="report-comparison-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.625rem;"></div>
            </div>

            <div class="card" style="padding:1.25rem;">
                <div class="section-header" style="margin-bottom:0.75rem;">
                    <h3 class="section-title" style="margin:0;">Distribución por modo</h3>
                </div>
                <canvas id="report-mode-chart" style="max-height:14rem;"></canvas>
                <div id="report-mode-list" style="display:flex;flex-direction:column;gap:0.5rem;margin-top:1rem;"></div>
            </div>

            <div class="card" style="padding:1.25rem;">
                <div class="section-header" style="margin-bottom:0.75rem;">
                    <h3 class="section-title" style="margin:0;">Tendencia temporal</h3>
                </div>
                <canvas id="report-trend-chart" style="max-height:14rem;"></canvas>
            </div>

            <div class="card" style="padding:1.25rem;">
                <div class="section-header" style="margin-bottom:0.75rem;">
                    <h3 class="section-title" style="margin:0;">Ejercicios más repetidos</h3>
                </div>
                <div id="report-top-exercises"></div>
            </div>

            <div class="card" style="padding:1.25rem;">
                <div class="section-header" style="margin-bottom:0.75rem;">
                    <h3 class="section-title" style="margin:0;">Últimos entrenos del rango</h3>
                </div>
                <div id="report-recent-workouts"></div>
            </div>
        </section>
    </div>
</div>

<div id="heatmap-tooltip"
     style="display:none;position:fixed;z-index:9999;
            background:var(--bg-card);border:1px solid var(--border-medium);
            border-radius:0.5rem;padding:0.375rem 0.75rem;
            font-size:0.6875rem;color:var(--text-primary);
            pointer-events:none;box-shadow:var(--shadow-lg);
            white-space:nowrap;"></div>

<div id="detail-modal" role="dialog" aria-modal="true" aria-labelledby="detail-modal-title"
     style="display:none; position:fixed; inset:0; z-index:9000;
     background:rgba(0,0,0,0.85); backdrop-filter:blur(8px);
     align-items:flex-end; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:var(--radius-2xl) var(--radius-2xl) 0 0;
                border-top:1px solid var(--border-medium); width:100%; max-width:48rem;
                padding-bottom:env(safe-area-inset-bottom); max-height:90dvh; overflow-y:auto;">
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0.875rem auto 0;"></div>
        <div id="detail-content" class="p-5">
            <div class="empty-state" style="padding:2rem 0;">
                <i data-lucide="loader-2" style="width:1.5rem;height:1.5rem;" class="animate-spin"></i>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    auth.check();

    const MODE_CONFIG = {
        gym:          { label: 'Gimnasio',   icon: 'dumbbell', color: '#f59e0b', badge: 'badge-primary' },
        home:         { label: 'Casa',       icon: 'home',     color: '#14b8a6', badge: 'badge-teal' },
        calisthenics: { label: 'Calistenia', icon: 'user',     color: '#3b82f6', badge: 'badge-blue' },
        swimming:     { label: 'Natación',   icon: 'waves',    color: '#a855f7', badge: 'badge-purple' },
    };

    const WEEKDAY_SHORT = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    const MONTH_NAMES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    let currentView = 'list';
    let currentPage = 1;
    let lastPage = 1;
    let totalCount = 0;
    let progressChart = null;
    let reportModeChart = null;
    let reportTrendChart = null;
    let reportNutritionChart = null;
    let progressMetric = 'weight_kg';
    let progressDebounce = null;
    let progressRows = [];
    let currentReportData = null;

    const currentFilters = { mode: '', search: '', dateFrom: '', dateTo: '' };
    const calendarState = { period: 'week', anchorDate: todayString(), selectedDate: '' };
    const reportState = { range: '30d', dateFrom: '', dateTo: '' };

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function todayString() {
        return formatDateKey(new Date());
    }

    function formatDateKey(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function parseLocalDate(dateStr) {
        const [year, month, day] = String(dateStr).split('T')[0].split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function formatHumanDate(dateStr, options = { weekday: 'short', day: 'numeric', month: 'short' }) {
        return parseLocalDate(dateStr).toLocaleDateString('es-ES', options);
    }

    function toNumber(value) {
        return Number(value || 0);
    }

    function formatMode(mode) {
        return (MODE_CONFIG[mode] || { label: mode }).label;
    }

    function formatMealType(type) {
        const labels = {
            breakfast: 'Desayuno',
            lunch: 'Almuerzo',
            dinner: 'Cena',
            snack: 'Snack',
        };
        return labels[type] || type || 'Sin tipo';
    }

    // Agrupa filas de series por nombre de ejercicio (modelo por-serie).
    // Devuelve [{ name, setCount, sets:[fila,...] }] en orden de aparición.
    function groupSetsByExercise(sets) {
        const order = [];
        const map = new Map();
        (sets || []).forEach(s => {
            if (!map.has(s.name)) { map.set(s.name, []); order.push(s.name); }
            map.get(s.name).push(s);
        });
        return order.map(name => ({ name, setCount: map.get(name).length, sets: map.get(name) }));
    }

    function buildWorkoutQuery(extra = {}) {
        const params = new URLSearchParams();
        const payload = {
            page: extra.page,
            per_page: extra.perPage,
            all: extra.all ? '1' : undefined,
            mode: currentFilters.mode || undefined,
            search: currentFilters.search || undefined,
            date_from: currentFilters.dateFrom || undefined,
            date_to: currentFilters.dateTo || undefined,
            sort: 'desc',
        };

        Object.entries({ ...payload, ...extra }).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                params.set(key, value);
            }
        });

        return `/workouts?${params.toString()}`;
    }

    function switchHistoryView(button) {
        const nextView = button.dataset.historyView;
        if (!nextView || nextView === currentView) return;

        document.querySelectorAll('[data-history-view]').forEach(tab => tab.classList.remove('active'));
        button.classList.add('active');
        currentView = nextView;

        document.getElementById('history-view-list').style.display = nextView === 'list' ? 'flex' : 'none';
        document.getElementById('history-view-calendar').style.display = nextView === 'calendar' ? 'flex' : 'none';
        document.getElementById('history-view-reports').style.display = nextView === 'reports' ? 'flex' : 'none';

        if (nextView === 'calendar') loadCalendar();
        if (nextView === 'reports') loadReports();
    }

    function onFilterChange() {
        currentFilters.search = document.getElementById('history-search').value.trim();
        reloadHistoryList();
    }

    function onDateFilterChange() {
        currentFilters.dateFrom = document.getElementById('history-date-from').value;
        currentFilters.dateTo = document.getElementById('history-date-to').value;
        reloadHistoryList();
    }

    function onModeFilter(button) {
        document.querySelectorAll('[data-mode]').forEach(tab => tab.classList.remove('active'));
        button.classList.add('active');
        currentFilters.mode = button.dataset.mode || '';
        reloadHistoryList();
        loadCalendar();
        loadReports();
    }

    function clearHistoryFilters() {
        currentFilters.mode = '';
        currentFilters.search = '';
        currentFilters.dateFrom = '';
        currentFilters.dateTo = '';
        document.getElementById('history-search').value = '';
        document.getElementById('history-date-from').value = '';
        document.getElementById('history-date-to').value = '';
        document.querySelectorAll('[data-mode]').forEach(tab => tab.classList.remove('active'));
        const allTab = document.querySelector('[data-mode=""]');
        if (allTab) allTab.classList.add('active');
        reloadHistoryList();
        loadCalendar();
        loadReports();
    }

    function reloadHistoryList() {
        currentPage = 1;
        loadHistory(1);
        loadHistorySummary();
    }

    function renderCard(workout, container) {
        const dateStr = formatHumanDate(workout.date);
        const cfg = MODE_CONFIG[workout.mode] || { label: esc(workout.mode), icon: 'activity', badge: 'badge-blue' };
        const groups = groupSetsByExercise(workout.sets || workout.exercises || []);
        const exerciseChips = groups.slice(0, 3).map(g =>
            `<span style="background:var(--bg-muted);padding:0.2rem 0.5rem;border-radius:var(--radius-sm);font-size:0.6875rem;color:var(--text-secondary);">${esc(String(g.setCount))}× ${esc(g.name)}</span>`
        ).join('');
        const remaining = groups.length > 3 ? `<span style="font-size:0.6875rem;color:var(--text-muted);">+${groups.length - 3} más</span>` : '';

        const card = document.createElement('div');
        card.className = 'card card-hover animate-fade-in';
        card.style.cssText = 'padding:1rem 1.125rem; cursor:pointer;';
        card.onclick = () => openWorkoutDetail(workout.id);
        card.innerHTML = `
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="badge ${cfg.badge}">
                        <i data-lucide="${cfg.icon}" style="width:0.625rem;height:0.625rem;"></i>
                        ${cfg.label}
                    </span>
                </div>
                <button onclick="deleteWorkout(event,'${workout.id}')"
                        class="btn btn-ghost btn-icon"
                        aria-label="Eliminar entrenamiento"
                        style="color:var(--text-muted);padding:0.25rem;border-radius:var(--radius-sm);"
                        title="Eliminar">
                    <i data-lucide="trash-2" style="width:0.875rem;height:0.875rem;"></i>
                </button>
            </div>
            <div class="flex items-baseline gap-3 mb-2">
                <span style="font-size:0.875rem;font-weight:700;color:var(--text-primary);">${dateStr}</span>
                <span style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:0.25rem;">
                    <i data-lucide="clock" style="width:0.75rem;height:0.75rem;"></i>
                    ${toNumber(workout.duration_minutes)} min
                </span>
            </div>
            ${workout.notes ? `<p style="font-size:0.8125rem;color:var(--text-muted);font-style:italic;margin:0 0 0.625rem;
                          padding:0.5rem 0.75rem;background:var(--bg-surface);border-radius:var(--radius-md);
                          border-left:2px solid var(--border-medium);">&#8220;${esc(workout.notes)}&#8221;</p>` : ''}
            ${groups.length > 0
                ? `<div style="display:flex;flex-wrap:wrap;gap:0.375rem;margin-top:0.25rem;">${exerciseChips}${remaining}</div>`
                : `<p style="font-size:0.75rem;color:var(--text-muted);margin:0;">Sin ejercicios registrados</p>`}
        `;
        container.appendChild(card);
    }

    async function loadHistory(page = 1) {
        const container = document.getElementById('history-list');
        if (page === 1) container.innerHTML = '';

        const oldBtn = document.getElementById('load-more-btn');
        if (oldBtn) oldBtn.remove();

        try {
            const response = await apiCall(buildWorkoutQuery({ page, perPage: 25 }));
            const list = Array.isArray(response) ? response : (response.data || []);
            lastPage = response.last_page || 1;
            totalCount = response.total || list.length;

            if (page === 1 && list.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="padding-top:4rem;">
                        <div class="empty-state-icon"><i data-lucide="calendar-x" style="width:1.5rem;height:1.5rem;"></i></div>
                        <p class="empty-state-title">Sin entrenamientos</p>
                        <p class="empty-state-desc">No hay resultados con filtros actuales.</p>
                        <a href="/training" class="btn btn-primary btn-sm mt-4">
                            <i data-lucide="plus" style="width:0.875rem;height:0.875rem;"></i>
                            Empezar ahora
                        </a>
                    </div>`;
                document.getElementById('history-count').textContent = '0 entrenamientos';
                lucide.createIcons();
                return;
            }

            document.getElementById('history-count').textContent = `${totalCount} entrenamiento${totalCount !== 1 ? 's' : ''}`;
            list.forEach(workout => renderCard(workout, container));
            lucide.createIcons();

            if (page < lastPage) {
                const button = document.createElement('button');
                button.id = 'load-more-btn';
                button.className = 'btn btn-outline btn-block';
                button.style.marginTop = '0.75rem';
                button.textContent = 'Cargar más';
                button.onclick = () => {
                    currentPage += 1;
                    loadHistory(currentPage);
                };
                container.after(button);
            }
        } catch (error) {
            console.error(error);
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="wifi-off" style="width:1.5rem;height:1.5rem;"></i></div>
                    <p class="empty-state-title">Error de conexión</p>
                    <p class="empty-state-desc">No se pudo cargar historial.</p>
                </div>`;
            lucide.createIcons();
        }
    }

    async function loadHistorySummary() {
        try {
            const params = new URLSearchParams({ range: 'all' });
            if (currentFilters.mode) params.set('mode', currentFilters.mode);
            if (currentFilters.dateFrom) params.set('date_from', currentFilters.dateFrom);
            if (currentFilters.dateTo) params.set('date_to', currentFilters.dateTo);
            const response = await apiCall(`/stats/reports?${params.toString()}`);
            const summary = response.summary || {};
            document.getElementById('history-summary-workouts').textContent = toNumber(summary.total_workouts);
            document.getElementById('history-summary-minutes').textContent = toNumber(summary.total_minutes);
            document.getElementById('history-summary-days').textContent = toNumber(summary.active_days);
        } catch (error) {
            console.error('History summary error:', error);
        }
    }

    async function exportHistoryCsv() {
        try {
            const rows = await apiCall(buildWorkoutQuery({ all: 1 }));
            if (!Array.isArray(rows) || rows.length === 0) {
                showToast('No hay datos para exportar', 'error');
                return;
            }

            const csvRows = [['Fecha', 'Modo', 'Duración (min)', 'Notas', 'Ejercicios']];
            rows.forEach(workout => {
                const exercises = (workout.sets || []).map(set => `${set.name} (${set.sets || 0}x${set.reps || 0}${set.weight_kg ? ` @ ${set.weight_kg}kg` : ''})`).join(' | ');
                csvRows.push([
                    formatDateKey(parseLocalDate(workout.date)),
                    formatMode(workout.mode),
                    toNumber(workout.duration_minutes),
                    workout.notes || '',
                    exercises,
                ]);
            });

            downloadCsv(csvRows, 'historial-fitloop.csv');
            showToast('CSV generado');
        } catch (error) {
            console.error(error);
            showToast('Error al exportar historial', 'error');
        }
    }

    async function deleteWorkout(event, id) {
        event.stopPropagation();
        if (!confirm('¿Eliminar este entrenamiento?')) return;

        try {
            await apiCall(`/workouts/${id}`, 'DELETE');
            showToast('Entrenamiento eliminado');
            refreshHistoryData();
        } catch (error) {
            showToast('Error al eliminar', 'error');
        }
    }

    async function openWorkoutDetail(id) {
        const modal = document.getElementById('detail-modal');
        const content = document.getElementById('detail-content');
        modal.style.display = 'flex';
        content.innerHTML = `<div class="empty-state" style="padding:2rem 0;"><i data-lucide="loader-2" style="width:1.5rem;height:1.5rem;" class="animate-spin"></i></div>`;
        lucide.createIcons();
        try {
            const workout = await apiCall(`/workouts/${id}`);
            renderDetailContent(workout, content);
        } catch (error) {
            content.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:2rem 0;">Error al cargar</p>';
        }
    }

    function renderDetailContent(workout, container) {
        const dateStr = parseLocalDate(workout.date).toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const cfg = MODE_CONFIG[workout.mode] || { label: esc(workout.mode), icon: 'activity', badge: 'badge-blue' };
        const groups = groupSetsByExercise(workout.sets || []);

        container.innerHTML = `
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 style="font-size:1.0625rem;font-weight:700;margin:0 0 0.375rem;text-transform:capitalize;">${dateStr}</h3>
                    <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                        <span class="badge ${cfg.badge}">
                            <i data-lucide="${cfg.icon}" style="width:0.625rem;height:0.625rem;"></i>
                            ${cfg.label}
                        </span>
                        <span style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:0.25rem;">
                            <i data-lucide="clock" style="width:0.75rem;height:0.75rem;"></i>
                            <span id="detail-duration">${toNumber(workout.duration_minutes)} min</span>
                        </span>
                    </div>
                </div>
                <button onclick="closeDetailModal()" class="btn btn-ghost btn-icon" style="color:var(--text-muted);">
                    <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
                </button>
            </div>

            <div id="detail-notes-view">
                ${workout.notes ? `<div style="background:var(--bg-surface);border-radius:var(--radius-md);padding:0.75rem;margin-bottom:1rem;border-left:2px solid var(--border-medium);"><p style="font-size:0.8125rem;color:var(--text-muted);margin:0;font-style:italic;">&#8220;${esc(workout.notes)}&#8221;</p></div>` : ''}
            </div>
            <div id="detail-edit-form" style="display:none; margin-bottom:1rem;">
                <label style="font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;">Notas</label>
                <textarea id="edit-notes" class="input" style="width:100%;margin-top:0.25rem;min-height:4rem;resize:vertical;">${esc(workout.notes || '')}</textarea>
                <label style="font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-top:0.625rem;display:block;">Duración (min)</label>
                <input type="number" id="edit-duration" class="input" style="width:100%;margin-top:0.25rem;" value="${toNumber(workout.duration_minutes)}" min="1" max="600">
                <div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
                    <button onclick="saveWorkoutEdit('${workout.id}')" class="btn btn-primary btn-sm" style="flex:1;"><i data-lucide="check" style="width:0.875rem;height:0.875rem;"></i> Guardar</button>
                    <button onclick="cancelWorkoutEdit()" class="btn btn-outline btn-sm" style="flex:1;">Cancelar</button>
                </div>
            </div>

            ${groups.length > 0 ? `
                <div style="margin-bottom:1rem;">
                    <p style="font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.75rem;">${groups.length} ejercicio${groups.length !== 1 ? 's' : ''}</p>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;">
                        ${groups.map(g => `
                            <div style="background:var(--bg-surface);border-radius:var(--radius-md);padding:0.75rem;">
                                <p style="font-size:0.875rem;font-weight:600;color:var(--text-primary);margin:0 0 0.5rem;">${esc(g.name)} <span style="color:var(--text-muted);font-weight:400;">· ${g.setCount} serie${g.setCount !== 1 ? 's' : ''}</span></p>
                                <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                    ${g.sets.map((s, i) => `
                                        <div style="display:flex;gap:0.75rem;font-size:0.75rem;color:var(--text-secondary);align-items:center;">
                                            <span style="color:var(--text-muted);width:1.5rem;">${i + 1}</span>
                                            ${s.weight_kg ? `<span style="color:var(--color-primary);font-weight:600;">${s.weight_kg} kg</span>` : ''}
                                            ${s.reps ? `<span>${s.reps} reps</span>` : ''}
                                            ${s.rpe ? `<span style="color:var(--text-muted);">RPE ${s.rpe}</span>` : ''}
                                            ${s.time_seconds ? `<span style="color:var(--text-muted);">${s.time_seconds}s</span>` : ''}
                                            ${s.distance_m ? `<span style="color:var(--text-muted);">${s.distance_m}m</span>` : ''}
                                        </div>`).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>` : ''}

            <div style="display:flex;gap:0.75rem;" id="detail-action-btns">
                <button onclick="toggleEditForm()" class="btn btn-outline btn-sm" style="flex:1;"><i data-lucide="pencil" style="width:0.875rem;height:0.875rem;"></i> Editar</button>
                <button onclick="deleteWorkoutFromDetail('${workout.id}')" class="btn btn-danger btn-sm" style="flex:1;"><i data-lucide="trash-2" style="width:0.875rem;height:0.875rem;"></i> Eliminar</button>
            </div>
        `;
        lucide.createIcons();
    }

    function toggleEditForm() {
        const form = document.getElementById('detail-edit-form');
        const notes = document.getElementById('detail-notes-view');
        const buttons = document.getElementById('detail-action-btns');
        const visible = form.style.display !== 'none';
        form.style.display = visible ? 'none' : 'block';
        notes.style.display = visible ? '' : 'none';
        buttons.style.display = visible ? '' : 'none';
    }

    function cancelWorkoutEdit() {
        toggleEditForm();
    }

    async function saveWorkoutEdit(id) {
        const notes = document.getElementById('edit-notes').value.trim();
        const duration = parseInt(document.getElementById('edit-duration').value, 10);
        if (!duration || duration < 1) {
            showToast('Duración inválida', 'error');
            return;
        }
        try {
            await apiCall(`/workouts/${id}`, 'PUT', { notes, duration_minutes: duration });
            showToast('Guardado correctamente');
            openWorkoutDetail(id);
            refreshHistoryData();
        } catch (error) {
            showToast('Error al guardar', 'error');
        }
    }

    async function deleteWorkoutFromDetail(id) {
        if (!confirm('¿Eliminar este entrenamiento?')) return;
        try {
            await apiCall(`/workouts/${id}`, 'DELETE');
            closeDetailModal();
            showToast('Entrenamiento eliminado');
            refreshHistoryData();
        } catch (error) {
            showToast('Error al eliminar', 'error');
        }
    }

    function closeDetailModal() {
        document.getElementById('detail-modal').style.display = 'none';
    }

    document.getElementById('detail-modal').addEventListener('click', function (event) {
        if (event.target === this) closeDetailModal();
    });

    function initExerciseProgress() {
        const input = document.getElementById('exercise-progress-search');
        if (!input) return;

        input.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(progressDebounce);
            if (query.length < 2) {
                hideProgressSuggestions();
                return;
            }
            progressDebounce = setTimeout(() => loadProgressSuggestions(query), 300);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const query = this.value.trim();
                if (query.length > 0) loadExerciseProgress(query);
                hideProgressSuggestions();
            }
            if (event.key === 'Escape') hideProgressSuggestions();
        });

        document.addEventListener('click', function (event) {
            const box = document.getElementById('exercise-progress-suggestions');
            if (!box) return;
            if (!box.contains(event.target) && event.target !== input) hideProgressSuggestions();
        });
    }

    async function loadProgressSuggestions(query) {
        try {
            const response = await apiCall(`/exercises/suggestions?q=${encodeURIComponent(query)}`);
            renderProgressSuggestions(Array.isArray(response) ? response : []);
        } catch (error) {
            hideProgressSuggestions();
        }
    }

    function renderProgressSuggestions(items) {
        const box = document.getElementById('exercise-progress-suggestions');
        if (!box) return;
        if (!items.length) {
            hideProgressSuggestions();
            return;
        }
        box.innerHTML = items.map(name => `
            <button type="button" onclick="selectProgressSuggestion('${String(name).replace(/'/g, "\\'")}')"
                    style="display:block;width:100%;text-align:left;padding:0.625rem 0.75rem;border:none;background:transparent;color:var(--text-primary);font-size:0.8125rem;cursor:pointer;">
                ${esc(name)}
            </button>`).join('');
        box.style.display = 'block';
    }

    function hideProgressSuggestions() {
        const box = document.getElementById('exercise-progress-suggestions');
        if (!box) return;
        box.style.display = 'none';
        box.innerHTML = '';
    }

    function selectProgressSuggestion(name) {
        document.getElementById('exercise-progress-search').value = name;
        hideProgressSuggestions();
        loadExerciseProgress(name);
    }

    window.setProgressMetric = function (button) {
        document.querySelectorAll('#progress-metric-tabs .pill-tab').forEach(tab => tab.classList.remove('active'));
        button.classList.add('active');
        progressMetric = button.dataset.metric;
        if (progressRows.length > 0) {
            renderProgressChart(progressRows);
            renderProgressStats(progressRows);
        }
    };

    async function loadExerciseProgress(name) {
        const response = await apiCall(`/exercises/progress?name=${encodeURIComponent(name)}`);
        progressRows = Array.isArray(response) ? response : [];

        const canvas = document.getElementById('exercise-progress-chart');
        const empty = document.getElementById('progress-chart-empty');
        const stats = document.getElementById('progress-stats');

        if (!progressRows.length) {
            if (progressChart) {
                progressChart.destroy();
                progressChart = null;
            }
            canvas.style.display = 'none';
            stats.style.display = 'none';
            empty.style.display = 'flex';
            empty.textContent = 'Sin datos para este ejercicio.';
            return;
        }

        empty.style.display = 'none';
        canvas.style.display = 'block';
        stats.style.display = 'grid';
        renderProgressChart(progressRows);
        renderProgressStats(progressRows);
    }

    function toMetricValue(row, metric) {
        if (metric === 'volume') return toNumber(row.weight_kg) * toNumber(row.reps) * toNumber(row.sets || 1);
        if (metric === 'reps') return toNumber(row.reps);
        return toNumber(row.weight_kg);
    }

    function metricLabel(metric) {
        if (metric === 'volume') return 'Volumen';
        if (metric === 'reps') return 'Repeticiones';
        return 'Peso';
    }

    function renderProgressChart(rows) {
        const canvas = document.getElementById('exercise-progress-chart');
        const labels = rows.map(row => formatHumanDate(row.date, { day: 'numeric', month: 'short' }));
        const values = rows.map(row => toMetricValue(row, progressMetric));

        if (progressChart) progressChart.destroy();

        progressChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: metricLabel(progressMetric),
                    data: values,
                    borderColor: '#60a5fa',
                    backgroundColor: 'rgba(96,165,250,0.2)',
                    borderWidth: 2,
                    tension: 0.25,
                    fill: true,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                },
            },
        });
    }

    function renderProgressStats(rows) {
        const prWeight = rows.reduce((max, row) => Math.max(max, toNumber(row.weight_kg)), 0);
        const bestVolume = rows.reduce((max, row) => Math.max(max, toNumber(row.weight_kg) * toNumber(row.reps) * toNumber(row.sets || 1)), 0);
        document.getElementById('progress-stat-pr').textContent = `${Math.round(prWeight)} kg`;
        document.getElementById('progress-stat-best-volume').textContent = `${Math.round(bestVolume)} kg`;
        document.getElementById('progress-stat-sessions').textContent = rows.length;
    }

    function setCalendarPeriod(button) {
        document.querySelectorAll('[data-calendar-period]').forEach(tab => tab.classList.remove('active'));
        button.classList.add('active');
        calendarState.period = button.dataset.calendarPeriod;
        calendarState.selectedDate = '';
        if (calendarState.period === 'year') {
            const anchor = parseLocalDate(calendarState.anchorDate);
            calendarState.anchorDate = `${anchor.getFullYear()}-01-01`;
        }
        loadCalendar();
    }

    function shiftCalendar(delta) {
        const anchor = parseLocalDate(calendarState.anchorDate);
        if (calendarState.period === 'week') anchor.setDate(anchor.getDate() + (delta * 7));
        else if (calendarState.period === 'month') anchor.setMonth(anchor.getMonth() + delta, 1);
        else anchor.setFullYear(anchor.getFullYear() + delta, 0, 1);
        calendarState.anchorDate = formatDateKey(anchor);
        loadCalendar();
    }

    async function loadCalendar() {
        document.getElementById('calendar-skeleton').style.display = 'block';
        document.getElementById('calendar-grid-wrapper').style.display = 'none';
        document.getElementById('annual-heatmap-wrapper').style.display = 'none';
        document.getElementById('heatmap-container').style.display = 'none';
        document.getElementById('heatmap-summary').style.display = 'none';

        if (calendarState.period === 'year') {
            loadHeatmap();
            return;
        }

        try {
            const params = new URLSearchParams({ period: calendarState.period, date: calendarState.anchorDate });
            if (currentFilters.mode) params.set('mode', currentFilters.mode);
            const response = await apiCall(`/stats/calendar?${params.toString()}`);
            renderCalendar(response);
        } catch (error) {
            console.error('Calendar error:', error);
            document.getElementById('calendar-skeleton').style.display = 'none';
        }
    }

    function renderCalendar(data) {
        document.getElementById('calendar-label').textContent = currentFilters.mode ? `Modo: ${formatMode(currentFilters.mode)}` : 'Todos los modos';
        document.getElementById('calendar-period-title').textContent = data.label || '';
        document.getElementById('calendar-total-workouts').textContent = toNumber(data.summary?.total_workouts);
        document.getElementById('calendar-total-minutes').textContent = toNumber(data.summary?.total_minutes);
        document.getElementById('calendar-active-days').textContent = toNumber(data.summary?.active_days);
        document.getElementById('calendar-average-duration').textContent = toNumber(data.summary?.average_duration);

        const head = document.getElementById('calendar-weekday-head');
        const grid = document.getElementById('calendar-grid');
        head.innerHTML = WEEKDAY_SHORT.map(label => `<div style="text-align:center;font-size:0.6875rem;color:var(--text-muted);font-weight:700;">${label}</div>`).join('');
        grid.innerHTML = '';

        (data.days || []).forEach(day => {
            const button = document.createElement('button');
            const isSelected = day.date === calendarState.selectedDate;
            button.type = 'button';
            button.style.cssText = `border:1px solid ${isSelected ? 'rgba(245,158,11,0.6)' : 'var(--border-light)'};background:${day.workouts_count > 0 ? 'rgba(245,158,11,0.08)' : 'var(--bg-card)'};opacity:${day.in_period ? '1' : '0.55'};border-radius:var(--radius-lg);min-height:4.75rem;padding:0.5rem;text-align:left;display:flex;flex-direction:column;justify-content:space-between;box-shadow:${isSelected ? 'var(--shadow-primary)' : 'none'};`;
            button.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.375rem;">
                    <span style="font-size:0.85rem;font-weight:800;color:${day.is_today ? 'var(--color-primary)' : 'var(--text-primary)'};">${day.day_number}</span>
                    <span style="font-size:0.625rem;color:var(--text-muted);">${day.month_name}</span>
                </div>
                <div>
                    <div style="font-size:0.75rem;font-weight:700;color:var(--text-primary);">${day.workouts_count} entreno${day.workouts_count === 1 ? '' : 's'}</div>
                    <div style="font-size:0.6875rem;color:var(--text-muted);">${day.minutes} min</div>
                </div>`;
            button.onclick = () => selectCalendarDay(day.date, day.workouts_count);
            grid.appendChild(button);
        });

        document.getElementById('calendar-skeleton').style.display = 'none';
        document.getElementById('calendar-grid-wrapper').style.display = 'block';
    }

    async function selectCalendarDay(date, workoutsCount) {
        calendarState.selectedDate = date;
        if (calendarState.period !== 'year') loadCalendar();

        const title = document.getElementById('calendar-day-title');
        const list = document.getElementById('calendar-day-list');
        title.textContent = formatHumanDate(date, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

        list.innerHTML = `<div class="empty-state" style="padding:1.5rem 0;"><i data-lucide="loader-2" class="animate-spin" style="width:1.25rem;height:1.25rem;"></i></div>`;
        lucide.createIcons();

        try {
            const params = new URLSearchParams({ all: '1', date_from: date, date_to: date });
            if (currentFilters.mode) params.set('mode', currentFilters.mode);
            const [workoutsRaw, mealsRaw, waterRaw, supplementsRaw] = await Promise.all([
                apiCall(`/workouts?${params.toString()}`).catch(() => []),
                apiCall(`/meals?date=${encodeURIComponent(date)}`).catch(() => null),
                apiCall(`/water?date=${encodeURIComponent(date)}`).catch(() => null),
                apiCall(`/supplements?date=${encodeURIComponent(date)}`).catch(() => null),
            ]);

            renderCalendarDayList(
                Array.isArray(workoutsRaw) ? workoutsRaw : [],
                mealsRaw,
                waterRaw,
                supplementsRaw,
                workoutsCount,
            );
        } catch (error) {
            console.error(error);
            list.innerHTML = '<p style="color:var(--text-muted);margin:0;">No se pudo cargar detalle del día.</p>';
        }
    }

    function renderCalendarDayList(workouts, mealsRaw, waterRaw, supplementsRaw, workoutsCount = 0) {
        const list = document.getElementById('calendar-day-list');
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'column';
        wrapper.style.gap = '0.75rem';

        if (workouts.length) {
            workouts.forEach(workout => renderCard(workout, wrapper));
        } else if (!mealsRaw && !waterRaw && !supplementsRaw) {
            wrapper.innerHTML = '<p style="color:var(--text-muted);margin:0;">No hay datos de este día.</p>';
        } else {
            wrapper.innerHTML = `<div class="empty-state" style="padding:1rem 0;">
                <div class="empty-state-icon"><i data-lucide="moon-star" style="width:1.25rem;height:1.25rem;"></i></div>
                <p class="empty-state-title" style="font-size:0.875rem;">Día sin entreno</p>
                <p class="empty-state-desc" style="font-size:0.75rem;">Mostrando igualmente tu nutrición del día.</p>
            </div>`;
        }

        const nutritionBlock = renderDayNutritionBlock(mealsRaw, waterRaw, supplementsRaw, workoutsCount);
        if (nutritionBlock) {
            wrapper.insertAdjacentHTML('beforeend', nutritionBlock);
        }

        list.innerHTML = '';
        list.appendChild(wrapper);
        lucide.createIcons();
    }

    function renderDayNutritionBlock(mealsRaw, waterRaw, supplementsRaw, workoutsCount = 0) {
        const mealsCount = toNumber(mealsRaw?.count);
        const calories = Math.round(toNumber(mealsRaw?.totals?.calories));
        const protein = Math.round(toNumber(mealsRaw?.totals?.protein));
        const carbs = Math.round(toNumber(mealsRaw?.totals?.carbs));
        const fat = Math.round(toNumber(mealsRaw?.totals?.fat));
        const waterMl = toNumber(waterRaw?.total_ml);
        const waterGoal = toNumber(waterRaw?.goal_ml);
        const suppTaken = toNumber(supplementsRaw?.taken_count);
        const suppTotal = toNumber(supplementsRaw?.total_count);

        const hasNutrition = mealsCount > 0 || waterMl > 0 || suppTaken > 0;
        if (!hasNutrition && workoutsCount > 0) {
            return '';
        }

        const mealRows = mealsRaw?.meals ? Object.entries(mealsRaw.meals) : [];
        const mealListHtml = mealRows.length
            ? mealRows.map(([type, row]) => {
                const label = formatMealType(type);
                const kcal = Math.round(toNumber(row?.calories));
                const count = Array.isArray(row?.items) ? row.items.length : 0;
                return `<div style="display:flex;justify-content:space-between;gap:0.75rem;padding:0.35rem 0;border-bottom:1px solid var(--border-light);">
                    <span style="font-size:0.75rem;color:var(--text-primary);">${esc(label)}</span>
                    <span style="font-size:0.75rem;color:var(--text-muted);">${kcal} kcal · ${count} items</span>
                </div>`;
            }).join('')
            : '<p style="margin:0;color:var(--text-muted);font-size:0.75rem;">Sin comidas registradas.</p>';

        return `
            <div class="card" style="padding:1rem 1.125rem;">
                <div class="flex items-center justify-between" style="gap:0.75rem;flex-wrap:wrap;">
                    <p style="margin:0;font-size:0.9rem;font-weight:700;">Nutrición del día</p>
                    <a href="/nutrition" class="btn btn-ghost btn-sm">Abrir nutrición</a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.5rem;margin-top:0.75rem;">
                    <div class="stat-card" style="padding:0.625rem;"><div class="stat-value" style="font-size:0.9rem;">${calories}</div><div class="stat-label">kcal</div></div>
                    <div class="stat-card" style="padding:0.625rem;"><div class="stat-value" style="font-size:0.9rem;">${protein}g</div><div class="stat-label">Proteína</div></div>
                    <div class="stat-card" style="padding:0.625rem;"><div class="stat-value" style="font-size:0.9rem;">${waterMl}</div><div class="stat-label">Agua ml</div></div>
                </div>
                <p style="margin:0.625rem 0 0;font-size:0.75rem;color:var(--text-muted);">Macros: ${protein}P · ${carbs}C · ${fat}G · Comidas: ${mealsCount}</p>
                <p style="margin:0.25rem 0 0.75rem;font-size:0.75rem;color:var(--text-muted);">Hidratación: ${waterMl}/${waterGoal || 2000} ml · Suplementos: ${suppTaken}/${suppTotal || 4}</p>
                ${mealListHtml}
            </div>
        `;
    }

    function clearCalendarDaySelection() {
        calendarState.selectedDate = '';
        document.getElementById('calendar-day-title').textContent = 'Toca un día con actividad.';
        document.getElementById('calendar-day-list').innerHTML = `<div class="empty-state" style="padding:1.5rem 0;"><div class="empty-state-icon"><i data-lucide="calendar-search" style="width:1.25rem;height:1.25rem;"></i></div><p class="empty-state-title" style="font-size:0.875rem;">Sin día seleccionado</p><p class="empty-state-desc" style="font-size:0.75rem;">Selecciona un día para ver entrenamientos concretos.</p></div>`;
        if (calendarState.period !== 'year') loadCalendar();
        lucide.createIcons();
    }

    async function loadHeatmap() {
        const anchor = parseLocalDate(calendarState.anchorDate);
        const year = anchor.getFullYear();
        document.getElementById('calendar-period-title').textContent = String(year);
        document.getElementById('calendar-label').textContent = currentFilters.mode ? `Modo: ${formatMode(currentFilters.mode)}` : 'Todos los modos';
        try {
            const params = new URLSearchParams({ year: String(year) });
            if (currentFilters.mode) params.set('mode', currentFilters.mode);
            const response = await apiCall(`/stats/heatmap?${params.toString()}`);
            renderHeatmap(response.data || {}, year);
        } catch (error) {
            console.error('Heatmap error:', error);
            document.getElementById('calendar-skeleton').style.display = 'none';
        }
    }

    function renderHeatmap(data, year) {
        const grid = document.getElementById('heatmap-grid');
        const monthsEl = document.getElementById('heatmap-months');
        const jan1 = new Date(year, 0, 1);
        const jan1DOW = (jan1.getDay() + 6) % 7;
        const totalDays = ((year % 4 === 0 && year % 100 !== 0) || year % 400 === 0) ? 366 : 365;

        let totalWorkouts = 0;
        let activeDays = 0;
        let totalMinutes = 0;
        Object.values(data).forEach(entry => {
            totalWorkouts += entry.count;
            totalMinutes += toNumber(entry.minutes);
            activeDays += 1;
        });

        document.getElementById('calendar-total-workouts').textContent = totalWorkouts;
        document.getElementById('calendar-total-minutes').textContent = totalMinutes;
        document.getElementById('calendar-active-days').textContent = activeDays;
        document.getElementById('calendar-average-duration').textContent = totalWorkouts > 0 ? Math.round(totalMinutes / totalWorkouts) : 0;
        document.getElementById('heatmap-summary-text').textContent = activeDays > 0 ? `${totalWorkouts} entrenamientos en ${activeDays} días activos` : 'Sin entrenamientos este año';

        const weeks = [];
        let week = new Array(jan1DOW).fill(null);
        for (let index = 0; index < totalDays; index += 1) {
            const date = new Date(year, 0, index + 1);
            week.push({ date: formatDateKey(date), day: date });
            if (week.length === 7) {
                weeks.push(week);
                week = [];
            }
        }
        if (week.length > 0) {
            while (week.length < 7) week.push(null);
            weeks.push(week);
        }

        let lastMonth = -1;
        const monthPositions = [];
        weeks.forEach((column, columnIndex) => {
            const firstReal = column.find(cell => cell !== null);
            if (firstReal) {
                const month = firstReal.day.getMonth();
                if (month !== lastMonth) {
                    monthPositions.push({ columnIndex, month });
                    lastMonth = month;
                }
            }
        });

        monthsEl.innerHTML = '';
        const cellWidth = 13;
        monthPositions.forEach((position, index) => {
            const next = index + 1 < monthPositions.length ? monthPositions[index + 1].columnIndex : weeks.length;
            const span = document.createElement('span');
            span.style.cssText = `font-size:0.6rem;color:var(--text-muted);width:${(next - position.columnIndex) * cellWidth}px;flex-shrink:0;`;
            span.textContent = MONTH_NAMES[position.month];
            monthsEl.appendChild(span);
        });

        const tooltip = document.getElementById('heatmap-tooltip');
        const cellColor = (count) => {
            if (!count) return 'var(--bg-muted)';
            if (count === 1) return 'rgba(245,158,11,0.3)';
            if (count === 2) return 'rgba(245,158,11,0.55)';
            if (count === 3) return 'rgba(245,158,11,0.8)';
            return '#f59e0b';
        };

        grid.innerHTML = '';
        weeks.forEach(column => {
            const columnEl = document.createElement('div');
            columnEl.style.cssText = 'display:flex;flex-direction:column;gap:3px;flex-shrink:0;';
            column.forEach(cell => {
                const box = document.createElement('button');
                box.type = 'button';
                box.style.cssText = 'width:10px;height:10px;border-radius:2px;flex-shrink:0;border:none;padding:0;';
                if (!cell) {
                    box.style.background = 'transparent';
                    box.disabled = true;
                } else {
                    const entry = data[cell.date];
                    const count = entry ? entry.count : 0;
                    box.style.background = cellColor(count);
                    box.style.cursor = 'pointer';
                    box.onclick = () => selectCalendarDay(cell.date, count);
                    box.addEventListener('mouseenter', event => {
                        const label = cell.day.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'short' });
                        tooltip.textContent = count > 0 ? `${label} · ${count} entrenamiento${count > 1 ? 's' : ''}` : `${label} · Sin entrenar`;
                        tooltip.style.display = 'block';
                        tooltip.style.left = `${event.clientX + 10}px`;
                        tooltip.style.top = `${event.clientY - 32}px`;
                    });
                    box.addEventListener('mousemove', event => {
                        tooltip.style.left = `${event.clientX + 10}px`;
                        tooltip.style.top = `${event.clientY - 32}px`;
                    });
                    box.addEventListener('mouseleave', () => {
                        tooltip.style.display = 'none';
                    });
                }
                columnEl.appendChild(box);
            });
            grid.appendChild(columnEl);
        });

        document.getElementById('calendar-skeleton').style.display = 'none';
        document.getElementById('annual-heatmap-wrapper').style.display = 'block';
        document.getElementById('heatmap-summary').style.display = 'block';
        document.getElementById('heatmap-container').style.display = 'block';
    }

    function setReportRange(button) {
        document.querySelectorAll('#report-range-tabs .pill-tab').forEach(tab => tab.classList.remove('active'));
        button.classList.add('active');
        reportState.range = button.dataset.reportRange;
        reportState.dateFrom = '';
        reportState.dateTo = '';
        document.getElementById('report-date-from').value = '';
        document.getElementById('report-date-to').value = '';
        loadReports();
    }

    function applyReportCustomRange() {
        reportState.dateFrom = document.getElementById('report-date-from').value;
        reportState.dateTo = document.getElementById('report-date-to').value;
        loadReports();
    }

    function resetReportDates() {
        reportState.dateFrom = '';
        reportState.dateTo = '';
        document.getElementById('report-date-from').value = '';
        document.getElementById('report-date-to').value = '';
        loadReports();
    }

    async function loadReports() {
        try {
            const params = new URLSearchParams({ range: reportState.range });
            if (currentFilters.mode) params.set('mode', currentFilters.mode);
            if (reportState.dateFrom) params.set('date_from', reportState.dateFrom);
            if (reportState.dateTo) params.set('date_to', reportState.dateTo);
            const response = await apiCall(`/stats/reports?${params.toString()}`);
            currentReportData = response;
            renderReportSummary(response);
            renderNutritionReport(response.nutrition || {});
            renderReportComparison(response.comparison || null);
            renderModeReport(response.by_mode || []);
            renderTrendReport(response.by_month || [], response.by_weekday || []);
            renderTopExercises(response.top_exercises || []);
            renderRecentWorkouts(response.recent_workouts || []);
        } catch (error) {
            console.error('Reports error:', error);
        }
    }

    function renderReportSummary(data) {
        const summary = data.summary || {};
        document.getElementById('report-range-label').textContent = data.range_label || '';
        document.getElementById('report-total-workouts').textContent = toNumber(summary.total_workouts);
        document.getElementById('report-total-minutes').textContent = toNumber(summary.total_minutes);
        document.getElementById('report-active-days').textContent = toNumber(summary.active_days);
        document.getElementById('report-average-duration').textContent = toNumber(summary.average_duration);
        document.getElementById('report-total-sets').textContent = toNumber(summary.total_sets);
        document.getElementById('report-total-volume').textContent = Math.round(toNumber(summary.total_volume));
    }

    function renderNutritionReport(nutrition) {
        const summary = nutrition.summary || {};
        const hydration = nutrition.hydration || {};
        const supplements = nutrition.supplements || {};
        const byMealType = Array.isArray(nutrition.by_meal_type) ? nutrition.by_meal_type : [];
        const byDay = Array.isArray(nutrition.by_day) ? nutrition.by_day : [];

        document.getElementById('report-nutrition-calories').textContent = Math.round(toNumber(summary.total_calories));
        document.getElementById('report-nutrition-protein').textContent = Math.round(toNumber(summary.total_protein));
        document.getElementById('report-nutrition-water').textContent = toNumber(hydration.total_ml);
        document.getElementById('report-nutrition-avg-calories').textContent = Math.round(toNumber(summary.average_calories));

        if (summary.goal_daily_calories) {
            const pct = summary.goal_progress_pct !== null && summary.goal_progress_pct !== undefined
                ? ` (${Math.round(toNumber(summary.goal_progress_pct))}%)`
                : '';
            document.getElementById('report-nutrition-goal').textContent = `${toNumber(summary.goal_daily_calories)}${pct}`;
        } else {
            document.getElementById('report-nutrition-goal').textContent = 'Sin objetivo';
        }

        document.getElementById('report-nutrition-supplements').textContent = `${Math.round(toNumber(supplements.progress_pct))}%`;

        const mealTypeList = document.getElementById('report-mealtype-list');
        if (!byMealType.length) {
            mealTypeList.innerHTML = '<p style="margin:0;color:var(--text-muted);font-size:0.75rem;">Sin comidas en el periodo.</p>';
        } else {
            mealTypeList.innerHTML = byMealType
                .sort((a, b) => toNumber(b.calories) - toNumber(a.calories))
                .map(item => `
                    <div style="display:flex;justify-content:space-between;gap:0.75rem;padding:0.4rem 0;border-bottom:1px solid var(--border-light);">
                        <span style="font-size:0.75rem;color:var(--text-primary);">${esc(formatMealType(item.meal_type))}</span>
                        <span style="font-size:0.75rem;color:var(--text-muted);">${Math.round(toNumber(item.calories))} kcal · ${toNumber(item.entries)} regs</span>
                    </div>
                `).join('');
        }

        const nutritionCanvas = document.getElementById('report-nutrition-chart');
        if (reportNutritionChart) {
            reportNutritionChart.destroy();
            reportNutritionChart = null;
        }

        const trendRows = byDay.slice(-14);
        if (!trendRows.length) {
            return;
        }

        reportNutritionChart = new Chart(nutritionCanvas, {
            type: 'line',
            data: {
                labels: trendRows.map(row => formatHumanDate(row.date, { day: 'numeric', month: 'short' })),
                datasets: [
                    {
                        label: 'Kcal',
                        data: trendRows.map(row => toNumber(row.calories)),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245,158,11,0.15)',
                        fill: true,
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 2,
                    },
                    {
                        label: 'Agua (ml)',
                        data: trendRows.map(row => toNumber(row.water_ml)),
                        borderColor: '#60a5fa',
                        backgroundColor: 'rgba(96,165,250,0.08)',
                        fill: false,
                        tension: 0.25,
                        borderWidth: 1.75,
                        pointRadius: 1.5,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#9ca3af', boxWidth: 10, font: { size: 10 } } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                },
            },
        });
    }

    function renderReportComparison(comparison) {
        const labelEl = document.getElementById('report-comparison-label');
        const gridEl = document.getElementById('report-comparison-grid');

        if (!comparison) {
            labelEl.textContent = 'Sin comparación disponible para este rango.';
            gridEl.innerHTML = '<p style="margin:0;color:var(--text-muted);grid-column:1/-1;">Usa un rango con fechas definidas para comparar contra periodo anterior.</p>';
            return;
        }

        const previous = comparison.previous_summary || {};
        const delta = comparison.delta || {};
        const pct = comparison.change_pct || {};

        labelEl.textContent = `${comparison.label || 'Comparativa'}: ${comparison.previous_range?.date_from || '-'} a ${comparison.previous_range?.date_to || '-'}`;

        const rows = [
            {
                title: 'Entrenos',
                previous: previous.total_workouts ?? 0,
                delta: delta.total_workouts ?? 0,
                pct: pct.total_workouts,
            },
            {
                title: 'Minutos',
                previous: previous.total_minutes ?? 0,
                delta: delta.total_minutes ?? 0,
                pct: pct.total_minutes,
            },
            {
                title: 'Días activos',
                previous: previous.active_days ?? 0,
                delta: delta.active_days ?? 0,
                pct: pct.active_days,
            },
            {
                title: 'Media min',
                previous: previous.average_duration ?? 0,
                delta: delta.average_duration ?? 0,
                pct: pct.average_duration,
            },
            {
                title: 'Kcal totales',
                previous: comparison.previous_nutrition?.total_calories ?? 0,
                delta: delta.nutrition_total_calories ?? 0,
                pct: pct.nutrition_total_calories,
            },
            {
                title: 'Proteína total',
                previous: comparison.previous_nutrition?.total_protein ?? 0,
                delta: delta.nutrition_total_protein ?? 0,
                pct: pct.nutrition_total_protein,
            },
            {
                title: 'Agua total (ml)',
                previous: comparison.previous_nutrition?.water_total_ml ?? 0,
                delta: delta.nutrition_water_total_ml ?? 0,
                pct: pct.nutrition_water_total_ml,
            },
        ];

        gridEl.innerHTML = rows.map((row) => {
            const trendColor = row.delta > 0 ? 'var(--color-teal)' : (row.delta < 0 ? '#ef4444' : 'var(--text-muted)');
            const pctText = row.pct === null || row.pct === undefined
                ? 'n/a'
                : `${formatSigned(row.pct)}%`;

            return `
                <div style="background:var(--bg-surface);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:0.75rem;">
                    <p style="margin:0 0 0.375rem;font-size:0.75rem;color:var(--text-muted);">${row.title}</p>
                    <p style="margin:0 0 0.25rem;font-size:0.8125rem;">Antes: <strong>${row.previous}</strong></p>
                    <p style="margin:0;color:${trendColor};font-size:0.8125rem;font-weight:700;">${formatSigned(row.delta)} (${pctText})</p>
                </div>
            `;
        }).join('');
    }

    function formatSigned(value) {
        const num = toNumber(value);
        if (num > 0) return `+${num}`;
        return String(num);
    }

    function renderModeReport(items) {
        const canvas = document.getElementById('report-mode-chart');
        const list = document.getElementById('report-mode-list');
        list.innerHTML = '';
        if (reportModeChart) reportModeChart.destroy();
        if (!items.length) {
            list.innerHTML = '<p style="margin:0;color:var(--text-muted);">Sin datos para este rango.</p>';
            return;
        }

        reportModeChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: items.map(item => item.label),
                datasets: [{
                    data: items.map(item => item.workouts),
                    backgroundColor: items.map(item => MODE_CONFIG[item.mode]?.color || '#94a3b8'),
                    borderWidth: 0,
                }],
            },
            options: { responsive: true, plugins: { legend: { display: false } }, cutout: '68%' },
        });

        list.innerHTML = items.map(item => `
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="width:0.625rem;height:0.625rem;border-radius:9999px;background:${MODE_CONFIG[item.mode]?.color || '#94a3b8'};"></span>
                    <span style="font-size:0.8125rem;">${esc(item.label)}</span>
                </div>
                <span style="font-size:0.75rem;color:var(--text-muted);">${item.workouts} entrenos · ${item.percentage}%</span>
            </div>`).join('');
    }

    function renderTrendReport(byMonth, byWeekday) {
        const canvas = document.getElementById('report-trend-chart');
        const useMonth = byMonth.length > 1;
        const labels = useMonth ? byMonth.map(item => item.label) : byWeekday.map(item => item.label);
        const values = useMonth ? byMonth.map(item => item.workouts) : byWeekday.map(item => item.workouts);
        if (reportTrendChart) reportTrendChart.destroy();
        reportTrendChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label: 'Entrenos', data: values, backgroundColor: 'rgba(245,158,11,0.75)', borderRadius: 8 }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 }, precision: 0 } },
                },
            },
        });
    }

    function renderTopExercises(items) {
        const container = document.getElementById('report-top-exercises');
        if (!items.length) {
            container.innerHTML = '<p style="margin:0;color:var(--text-muted);">Sin ejercicios en este rango.</p>';
            return;
        }

        container.innerHTML = items.map(item => `
            <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:0.75rem;padding:0.875rem;background:var(--bg-surface);border-radius:var(--radius-lg);margin-bottom:0.625rem;">
                <div>
                    <p style="margin:0 0 0.25rem;font-weight:700;">${esc(item.name)}</p>
                    <p style="margin:0;font-size:0.75rem;color:var(--text-muted);">${item.sessions} sesiones · ${item.total_sets} series · ${item.total_reps} reps</p>
                </div>
                <div style="text-align:right;">
                    <p style="margin:0;font-size:0.8125rem;font-weight:700;color:var(--color-primary);">${Math.round(item.total_volume)} kg</p>
                    <p style="margin:0;font-size:0.6875rem;color:var(--text-muted);">PR ${Math.round(item.max_weight)} kg</p>
                </div>
            </div>`).join('');
    }

    function renderRecentWorkouts(items) {
        const container = document.getElementById('report-recent-workouts');
        if (!items.length) {
            container.innerHTML = '<p style="margin:0;color:var(--text-muted);">Sin entrenamientos en este rango.</p>';
            return;
        }

        container.innerHTML = items.map(item => `
            <button type="button" onclick="openWorkoutDetail('${item.id}')" class="card card-hover" style="display:block;width:100%;padding:0.875rem 1rem;text-align:left;margin-bottom:0.625rem;">
                <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:center;">
                    <div>
                        <p style="margin:0 0 0.25rem;font-size:0.875rem;font-weight:700;">${formatHumanDate(item.date, { weekday: 'short', day: 'numeric', month: 'short' })}</p>
                        <p style="margin:0;font-size:0.75rem;color:var(--text-muted);">${esc(item.mode_label)} · ${item.duration_minutes} min</p>
                    </div>
                    <i data-lucide="chevron-right" style="width:1rem;height:1rem;color:var(--text-muted);"></i>
                </div>
            </button>`).join('');
        lucide.createIcons();
    }

    async function exportReportSummaryCsv() {
        if (!currentReportData) {
            showToast('Informe no cargado', 'error');
            return;
        }

        const rows = [
            ['Rango', currentReportData.range_label || ''],
            ['Entrenos', currentReportData.summary?.total_workouts || 0],
            ['Minutos', currentReportData.summary?.total_minutes || 0],
            ['Días activos', currentReportData.summary?.active_days || 0],
            ['Media minutos', currentReportData.summary?.average_duration || 0],
            ['Series', currentReportData.summary?.total_sets || 0],
            ['Volumen', currentReportData.summary?.total_volume || 0],
            ['Kcal totales (nutrición)', currentReportData.nutrition?.summary?.total_calories || 0],
            ['Media kcal/día', currentReportData.nutrition?.summary?.average_calories || 0],
            ['Proteína total (g)', currentReportData.nutrition?.summary?.total_protein || 0],
            ['Agua total (ml)', currentReportData.nutrition?.hydration?.total_ml || 0],
            ['Suplementos tomados', currentReportData.nutrition?.supplements?.taken_count || 0],
            [],
            ['Modo', 'Entrenos', 'Minutos', '%'],
            ...(currentReportData.by_mode || []).map(item => [item.label, item.workouts, item.minutes, item.percentage]),
            [],
            ['Ejercicio', 'Sesiones', 'Series', 'Reps', 'PR', 'Volumen'],
            ...(currentReportData.top_exercises || []).map(item => [item.name, item.sessions, item.total_sets, item.total_reps, item.max_weight, item.total_volume]),
        ];

        downloadCsv(rows, 'informe-fitloop-resumen.csv');
        showToast('Resumen exportado');
    }

    async function exportReportDetailCsv() {
        try {
            const params = new URLSearchParams({ all: '1' });
            if (currentFilters.mode) params.set('mode', currentFilters.mode);
            if (reportState.dateFrom) params.set('date_from', reportState.dateFrom);
            if (reportState.dateTo) params.set('date_to', reportState.dateTo);
            if (!reportState.dateFrom && !reportState.dateTo && reportState.range !== 'all') {
                const resolved = resolveQuickRange(reportState.range);
                params.set('date_from', resolved.dateFrom);
                params.set('date_to', resolved.dateTo);
            }
            const workouts = await apiCall(`/workouts?${params.toString()}`);
            if (!Array.isArray(workouts) || !workouts.length) {
                showToast('Sin detalle para exportar', 'error');
                return;
            }

            const rows = [['Fecha', 'Modo', 'Duración', 'Notas', 'Ejercicio', 'Series', 'Reps', 'Peso kg']];
            workouts.forEach(workout => {
                const sets = workout.sets || [];
                if (!sets.length) {
                    rows.push([formatDateKey(parseLocalDate(workout.date)), formatMode(workout.mode), workout.duration_minutes, workout.notes || '', '', '', '', '']);
                    return;
                }
                sets.forEach(set => {
                    rows.push([
                        formatDateKey(parseLocalDate(workout.date)),
                        formatMode(workout.mode),
                        workout.duration_minutes,
                        workout.notes || '',
                        set.name || '',
                        set.sets || '',
                        set.reps || '',
                        set.weight_kg || '',
                    ]);
                });
            });

            downloadCsv(rows, 'informe-fitloop-detalle.csv');
            showToast('Detalle exportado');
        } catch (error) {
            console.error(error);
            showToast('Error al exportar detalle', 'error');
        }
    }

    function resolveQuickRange(range) {
        const end = parseLocalDate(todayString());
        const start = parseLocalDate(todayString());
        if (range === '7d') start.setDate(start.getDate() - 6);
        else if (range === '90d') start.setDate(start.getDate() - 89);
        else if (range === '365d') start.setDate(start.getDate() - 364);
        else start.setDate(start.getDate() - 29);
        return { dateFrom: formatDateKey(start), dateTo: formatDateKey(end) };
    }

    function downloadCsv(rows, fileName) {
        const content = rows.map(row => row.map(csvValue).join(',')).join('\n');
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function csvValue(value) {
        const normalized = String(value ?? '').replace(/"/g, '""');
        return `"${normalized}"`;
    }

    function refreshHistoryData() {
        reloadHistoryList();
        loadCalendar();
        loadReports();
    }

    loadHistory();
    loadHistorySummary();
    loadCalendar();
    loadReports();
    initExerciseProgress();
    lucide.createIcons();
</script>
@endpush