@extends('layouts.app')

@section('title', 'Nutrición')

@section('content')
<div class="page-container">

    {{-- ── CABECERA ────────────────────────────────────────────────── --}}
    <header class="page-header sticky-header">
        <div class="header-content">
            <div>
                <h1 class="page-title">Nutrición</h1>
                <p class="page-subtitle" id="headerDate">Hoy</p>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                {{-- Navegación de fechas --}}
                <button class="btn btn-ghost btn-icon" id="btnPrevDay" title="Día anterior">
                    <i data-lucide="chevron-left"></i>
                </button>
                <button class="btn btn-ghost btn-icon" id="btnNextDay" title="Día siguiente">
                    <i data-lucide="chevron-right"></i>
                </button>
                <button class="btn btn-ghost btn-icon" onclick="openGoalWizard()" title="Configurar objetivo nutricional">
                    <i data-lucide="sliders-horizontal"></i>
                </button>
                <a href="/nutrition/log" class="btn btn-primary btn-icon" title="Añadir comida">
                    <i data-lucide="plus"></i>
                </a>
            </div>
        </div>
    </header>

    {{-- ── ANILLO DE CALORÍAS ──────────────────────────────────────── --}}
    <section class="calories-ring-section">
        <div class="calories-ring-card card">
            <div class="ring-container">
                <svg class="calories-ring" viewBox="0 0 120 120" width="140" height="140">
                    <circle class="ring-bg"       cx="60" cy="60" r="52" />
                    <circle class="ring-progress" cx="60" cy="60" r="52" id="caloriesRingArc" />
                </svg>
                <div class="ring-center">
                    <span class="ring-value" id="caloriesConsumed">0</span>
                    <span class="ring-unit">kcal</span>
                    <span class="ring-label">consumidas</span>
                </div>
            </div>
            <div class="calories-info">
                <div class="cal-stat">
                    <span class="cal-stat-value" id="caloriesGoal">2.000</span>
                    <span class="cal-stat-label">Objetivo</span>
                </div>
                <div class="cal-stat cal-stat-center">
                    <span class="cal-stat-value" id="caloriesRemaining">2.000</span>
                    <span class="cal-stat-label" id="remainingLabel">Restantes</span>
                </div>
                <div class="cal-stat">
                    <span class="cal-stat-value" id="caloriesBurned">–</span>
                    <span class="cal-stat-label">Quemadas</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ── ACTIVIDAD DIARIA (RELOJ) ─────────────────────────────── --}}
    <section style="padding:0.75rem 1.25rem 0;">
        <div class="card" style="padding:1rem 1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.12);">
                        <i data-lucide="footprints" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
                    </div>
                    <span style="font-size:0.8rem;font-weight:700;color:var(--text-primary);">Actividad diaria</span>
                </div>
                <button onclick="openActivityModal()" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">
                    Editar
                </button>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-top:0.75rem;">
                <div style="padding:0.75rem;border-radius:0.75rem;background:var(--bg-muted);">
                    <p style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.35rem;">Pasos</p>
                    <p style="font-size:1.15rem;font-weight:800;color:var(--text-primary);margin:0;">
                        <span id="activitySteps">0</span>
                    </p>
                </div>
                <div style="padding:0.75rem;border-radius:0.75rem;background:var(--bg-muted);">
                    <p style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.35rem;">Kcal reloj</p>
                    <p style="font-size:1.15rem;font-weight:800;color:var(--color-primary);margin:0;">
                        <span id="activityCalories">0</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ── MACROS ──────────────────────────────────────────────────── --}}
    <section class="macros-section">
        <div class="macros-grid">
            <div class="macro-card card">
                <div class="macro-icon macro-protein"><i data-lucide="beef"></i></div>
                <div class="macro-info">
                    <span class="macro-value" id="proteinConsumed">0g</span>
                    <span class="macro-label">Proteína</span>
                    <div class="progress-container" style="margin-top:0.4rem;">
                        <div class="progress-bar" id="proteinBar" style="background:var(--color-primary);width:0%"></div>
                    </div>
                    <span class="macro-goal" id="proteinGoal">/ 150g</span>
                </div>
            </div>
            <div class="macro-card card">
                <div class="macro-icon macro-carbs"><i data-lucide="wheat"></i></div>
                <div class="macro-info">
                    <span class="macro-value" id="carbsConsumed">0g</span>
                    <span class="macro-label">Carbos</span>
                    <div class="progress-container" style="margin-top:0.4rem;">
                        <div class="progress-bar" id="carbsBar" style="background:#60a5fa;width:0%"></div>
                    </div>
                    <span class="macro-goal" id="carbsGoal">/ 250g</span>
                </div>
            </div>
            <div class="macro-card card">
                <div class="macro-icon macro-fat"><i data-lucide="droplet"></i></div>
                <div class="macro-info">
                    <span class="macro-value" id="fatConsumed">0g</span>
                    <span class="macro-label">Grasa</span>
                    <div class="progress-container" style="margin-top:0.4rem;">
                        <div class="progress-bar" id="fatBar" style="background:#f87171;width:0%"></div>
                    </div>
                    <span class="macro-goal" id="fatGoal">/ 65g</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ── MICRONUTRIENTES (Fibra + Azúcar) ──────────────────────── --}}
    <section class="micros-section" style="padding:0.75rem 1.25rem 0;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            {{-- Fibra --}}
            <div class="card" style="padding:0.875rem 1rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(52,211,153,0.12);flex-shrink:0;">
                        <i data-lucide="leaf" style="width:0.875rem;height:0.875rem;color:#34d399;"></i>
                    </div>
                    <span style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);">Fibra</span>
                </div>
                <div style="display:flex;align-items:baseline;gap:0.25rem;margin-bottom:0.4rem;">
                    <span style="font-size:1.1rem;font-weight:800;color:var(--text-primary);" id="fiberConsumed">0</span>
                    <span style="font-size:0.75rem;color:var(--text-muted);">g</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" id="fiberBar" style="background:#34d399;width:0%;transition:width 0.5s ease;"></div>
                </div>
                <span style="font-size:0.7rem;color:var(--text-muted);margin-top:0.3rem;display:block;" id="fiberGoalLabel">/ 25g objetivo</span>
            </div>
            {{-- Azúcar --}}
            <div class="card" style="padding:0.875rem 1rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(251,146,60,0.12);flex-shrink:0;">
                        <i data-lucide="candy" style="width:0.875rem;height:0.875rem;color:#fb923c;"></i>
                    </div>
                    <span style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);">Azúcar</span>
                </div>
                <div style="display:flex;align-items:baseline;gap:0.25rem;margin-bottom:0.4rem;">
                    <span style="font-size:1.1rem;font-weight:800;color:var(--text-primary);" id="sugarConsumed">0</span>
                    <span style="font-size:0.75rem;color:var(--text-muted);">g</span>
                </div>
                <div class="progress-container">
                    {{-- La OMS recomienda <50g/día. La barra se pone roja si superas 50g --}}
                    <div class="progress-bar" id="sugarBar" style="background:#fb923c;width:0%;transition:width 0.5s ease;"></div>
                </div>
                <span style="font-size:0.7rem;color:var(--text-muted);margin-top:0.3rem;display:block;">/ 50g límite OMS</span>
            </div>
        </div>
    </section>

    {{-- ── AGUA / HIDRATACIÓN ─────────────────────────────────────── --}}
    <section style="padding:0.75rem 1.25rem 0;">
        <div class="card" style="padding:1rem 1.25rem;">
            {{-- Cabecera del widget --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(96,165,250,0.12);">
                        <i data-lucide="droplets" style="width:0.875rem;height:0.875rem;color:#60a5fa;"></i>
                    </div>
                    <span style="font-size:0.8rem;font-weight:700;color:var(--text-primary);">Hidratación</span>
                </div>
                <button onclick="openWaterGoalModal()"
                        style="background:none;border:none;cursor:pointer;padding:0.25rem;"
                        title="Cambiar objetivo">
                    <i data-lucide="settings-2" style="width:0.875rem;height:0.875rem;color:var(--text-muted);"></i>
                </button>
            </div>

            {{-- Barra de progreso + contador --}}
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;">
                <div style="flex:1;">
                    <div class="progress-container" style="height:0.625rem;border-radius:999px;">
                        <div id="waterBar" class="progress-bar"
                             style="background:#60a5fa;width:0%;border-radius:999px;transition:width 0.5s ease;height:100%;"></div>
                    </div>
                </div>
                <span style="font-size:0.8rem;font-weight:700;color:#60a5fa;white-space:nowrap;">
                    <span id="waterTotal">0</span>&nbsp;/&nbsp;<span id="waterGoal">2000</span>&nbsp;ml
                </span>
            </div>

            {{-- Vasos de agua visuales --}}
            <div id="waterGlasses" style="display:flex;gap:0.375rem;flex-wrap:wrap;margin-bottom:0.75rem;">
                {{-- Se generan con JS --}}
            </div>

            {{-- Botones rápidos para añadir --}}
            <div class="water-quick-actions">
                <button onclick="addWater(150)" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">
                    <i data-lucide="plus" style="width:0.75rem;height:0.75rem;"></i>
                    150 ml
                </button>
                <button onclick="addWater(200)" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">
                    <i data-lucide="plus" style="width:0.75rem;height:0.75rem;"></i>
                    200 ml
                </button>
                <button onclick="addWater(250)" class="btn btn-primary btn-sm" style="font-size:0.75rem;">
                    <i data-lucide="plus" style="width:0.75rem;height:0.75rem;"></i>
                    Vaso (250 ml)
                </button>
                <button onclick="addWater(500)" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">
                    <i data-lucide="plus" style="width:0.75rem;height:0.75rem;"></i>
                    500 ml
                </button>
            </div>
        </div>
    </section>

    {{-- ── SUPLEMENTOS NOCTURNOS ─────────────────────────────────── --}}
    <section style="padding:0.75rem 1.25rem 0;">
        <div class="card" style="padding:1rem 1.25rem;">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;">
                <div class="icon-wrap icon-wrap-sm" style="background:rgba(167,139,250,0.14);">
                    <i data-lucide="pill" style="width:0.875rem;height:0.875rem;color:#a78bfa;"></i>
                </div>
                <span style="font-size:0.8rem;font-weight:700;color:var(--text-primary);">Suplementos nocturnos</span>
            </div>

            <div id="supplementChecklist" style="display:grid;gap:0.5rem;"></div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin-top:0.75rem;">
                <span id="supplementProgressLabel" style="font-size:0.75rem;color:var(--text-muted);">0/4 tomados hoy</span>
                <button onclick="resetSupplementChecklist()" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">
                    Reiniciar
                </button>
            </div>
        </div>
    </section>

    {{-- Modal cambio objetivo agua --}}
    <div id="waterGoalBackdrop" class="modal-backdrop" style="display:none;" onclick="closeWaterGoalModal()"></div>
    <div id="waterGoalDialog" class="custom-dialog" style="display:none;">
        <p class="custom-dialog-title">Objetivo de agua diario</p>
        <p class="custom-dialog-body">Introduce tu objetivo en ml (recomendación OMS: 2000 ml)</p>
        <input type="number" id="waterGoalInput" class="input" min="500" max="6000" step="100"
               value="2000" style="margin-bottom:1rem;">
        <div style="display:flex;gap:0.75rem;">
            <button class="btn btn-ghost btn-block" onclick="closeWaterGoalModal()">Cancelar</button>
            <button class="btn btn-primary btn-block" onclick="saveWaterGoal()">Guardar</button>
        </div>
    </div>

    {{-- Modal actividad diaria --}}
    <div id="activityBackdrop" class="modal-backdrop" style="display:none;" onclick="closeActivityModal()"></div>
    <div id="activityDialog" class="custom-dialog" style="display:none;">
        <p class="custom-dialog-title">Actividad diaria</p>
        <p class="custom-dialog-body">Introduce pasos y calorías quemadas según tu reloj.</p>

        <label class="form-label" style="margin-bottom:0.35rem;display:block;">Pasos</label>
        <input type="number" id="activityStepsInput" class="input" min="0" max="150000" step="1" value="0" style="margin-bottom:0.75rem;">

        <label class="form-label" style="margin-bottom:0.35rem;display:block;">Calorías quemadas (kcal)</label>
        <input type="number" id="activityCaloriesInput" class="input" min="0" max="10000" step="1" value="0" style="margin-bottom:1rem;">

        <div style="display:flex;gap:0.75rem;">
            <button class="btn btn-ghost btn-block" onclick="closeActivityModal()">Cancelar</button>
            <button class="btn btn-primary btn-block" onclick="saveActivity()">Guardar</button>
        </div>
    </div>

    {{-- ── BANNER PRIMER USO (objetivo no configurado) ─────────────── --}}
    <section id="goal-setup-banner" style="display:none; padding:0.75rem 1.25rem 0;">
        <div class="card" style="padding:1rem 1.25rem; border:1px solid rgba(96,165,250,0.3);
                                  background:rgba(96,165,250,0.06);">
            <div style="display:flex; align-items:center; gap:0.875rem;">
                <div class="icon-wrap icon-wrap-md" style="background:rgba(96,165,250,0.15); flex-shrink:0;">
                    <i data-lucide="target" style="width:1.1rem;height:1.1rem;color:#60a5fa;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <p style="font-size:0.875rem;font-weight:700;margin:0 0 0.25rem;color:var(--text-primary);line-height:1.3;">Configura tu objetivo</p>
                    <p style="font-size:0.75rem;color:rgba(255,255,255,0.74);margin:0;line-height:1.45;">Calcula tus calorías y macros diarias</p>
                </div>
            </div>
            <button onclick="openGoalWizard()" class="btn btn-primary btn-block"
                    style="margin-top:1rem; height:2.625rem; font-size:0.875rem; font-weight:700;">
                <i data-lucide="calculator" style="width:0.875rem;height:0.875rem;"></i>
                Calcular mi objetivo
            </button>
        </div>
    </section>

    {{-- ── COMIDAS DEL DÍA ────────────────────────────────────────── --}}
    <section class="meals-section" style="padding:1rem 1.25rem 1rem;">
        <div class="section-header">
            <h2 class="section-title">Comidas de hoy</h2>
            <a href="/nutrition/log" class="section-link">+ Añadir</a>
        </div>

        {{-- Skeleton mientras carga --}}
        <div id="mealsSkeleton">
            <div class="skeleton-card"></div>
            <div class="skeleton-card" style="margin-top:0.75rem;"></div>
        </div>

        {{-- Contenido real de las comidas --}}
        <div id="mealsContent" style="display:none;">
            <!-- Se rellena con JS -->
        </div>

        {{-- Estado vacío --}}
        <div id="mealsEmpty" class="empty-state" style="display:none;">
            <div class="empty-state-icon"><i data-lucide="salad"></i></div>
            <p class="empty-state-title">Sin registros hoy</p>
            <p class="empty-state-desc">Empieza añadiendo lo que has desayunado.</p>
            <a href="/nutrition/log" class="btn btn-primary" style="margin-top:1rem;">Añadir comida</a>
        </div>
    </section>

    {{-- ── ACCESOS RÁPIDOS ─────────────────────────────────────────── --}}
    <section style="padding:0 1.25rem 6rem;">
        <div class="section-header">
            <h2 class="section-title">Accesos rápidos</h2>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;">
            <a href="/nutrition/recipes" class="card card-interactive quick-link-card">
                <i data-lucide="book-open" style="color:var(--color-primary);margin-bottom:0.5rem;"></i>
                <span>Recetas</span>
            </a>
            <a href="/nutrition/history" class="card card-interactive quick-link-card">
                <i data-lucide="bar-chart-2" style="color:#60a5fa;margin-bottom:0.5rem;"></i>
                <span>Historial</span>
            </a>
            <a href="/nutrition/ingredients" class="card card-interactive quick-link-card">
                <i data-lucide="package" style="color:#34d399;margin-bottom:0.5rem;"></i>
                <span>Ingredientes</span>
            </a>
        </div>
    </section>

</div>

{{-- ── MODAL CONFIRMAR ELIMINAR COMIDA ──────────────────────────── --}}
<div class="modal-backdrop" id="deleteMealBackdrop" style="display:none;z-index:200;"></div>
<div id="deleteMealDialog" class="custom-dialog" style="display:none;">
    <p class="custom-dialog-title">Eliminar entrada</p>
    <p class="custom-dialog-body">¿Eliminar esta entrada del registro?</p>
    <div style="display:flex;gap:0.75rem;">
        <button class="btn btn-ghost btn-block" id="deleteMealCancelBtn">Cancelar</button>
        <button class="btn btn-danger btn-block" id="deleteMealOkBtn">Eliminar</button>
    </div>
</div>

{{-- ── WIZARD CONFIGURACIÓN NUTRICIONAL ─────────────────────────── --}}
<div id="goal-wizard"
     role="dialog" aria-modal="true" aria-labelledby="wizard-title"
     style="display:none; position:fixed; inset:0; z-index:9100;
            background:rgba(0,0,0,0.85); backdrop-filter:blur(8px);
            align-items:flex-end; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:1.5rem 1.5rem 0 0;
                border-top:1px solid var(--border-medium); width:100%; max-width:48rem;
                padding-bottom:env(safe-area-inset-bottom); max-height:90dvh; overflow-y:auto;">

        {{-- Handle --}}
        <div style="width:2.5rem;height:3px;background:var(--bg-muted);border-radius:9999px;margin:0.875rem auto 0;"></div>

        {{-- Cabecera wizard --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem 0.75rem;">
            <div style="display:flex;align-items:center;gap:0.625rem;">
                <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.12);">
                    <i data-lucide="calculator" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
                </div>
                <h2 id="wizard-title" style="font-size:1rem;font-weight:800;margin:0;">Calcular objetivo</h2>
            </div>
            <button onclick="closeGoalWizard()" class="btn btn-ghost btn-icon" aria-label="Cerrar">
                <i data-lucide="x" style="width:1.25rem;height:1.25rem;"></i>
            </button>
        </div>

        {{-- Indicador de pasos --}}
        <div style="display:flex;align-items:center;gap:6px;padding:0 1.25rem 1rem;">
            <div id="wizard-dot-1" style="height:0.5rem;border-radius:9999px;background:var(--bg-muted);transition:all 0.25s;"></div>
            <div id="wizard-dot-2" style="height:0.5rem;width:0.5rem;border-radius:9999px;background:var(--bg-muted);transition:all 0.25s;"></div>
            <div id="wizard-dot-3" style="height:0.5rem;width:0.5rem;border-radius:9999px;background:var(--bg-muted);transition:all 0.25s;"></div>
        </div>

        {{-- Paso 1: Género + Actividad --}}
        <div id="wizard-step-1" style="padding:0 1.25rem;">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin:0 0 0.75rem;">Paso 1 de 3 · Sexo y nivel de actividad</p>

            {{-- Selector de género --}}
            <p style="font-size:0.875rem;color:var(--text-primary);margin:0 0 0.5rem;font-weight:600;">¿Cuál es tu sexo biológico?</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.25rem;">
                <button id="wizard-gender-male" onclick="selectWizardGender('male')"
                        class="wizard-option" style="justify-content:center;gap:0.5rem;">
                    <span style="font-size:1.25rem;">♂</span>
                    <span style="font-size:0.875rem;font-weight:700;">Hombre</span>
                </button>
                <button id="wizard-gender-female" onclick="selectWizardGender('female')"
                        class="wizard-option" style="justify-content:center;gap:0.5rem;">
                    <span style="font-size:1.25rem;">♀</span>
                    <span style="font-size:0.875rem;font-weight:700;">Mujer</span>
                </button>
            </div>

            <p style="font-size:0.875rem;color:var(--text-primary);margin:0 0 0.75rem;font-weight:600;">¿Cuánto te mueves normalmente?</p>
            <div id="wizard-activity-list" style="display:flex;flex-direction:column;gap:0.5rem;"></div>
        </div>

        {{-- Paso 2: Objetivo --}}
        <div id="wizard-step-2" style="padding:0 1.25rem;display:none;">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin:0 0 0.75rem;">Paso 2 de 3 · Objetivo</p>
            <p style="font-size:0.875rem;color:var(--text-primary);margin:0 0 1rem;line-height:1.5;">¿Cuál es tu objetivo principal?</p>
            <div id="wizard-goal-list" style="display:flex;flex-direction:column;gap:0.5rem;"></div>
        </div>

        {{-- Paso 3: Preview --}}
        <div id="wizard-step-3" style="padding:0 1.25rem;display:none;">
            <p style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin:0 0 0.75rem;">Paso 3 de 3 · Tu plan</p>
            <div id="wizard-preview"></div>
            <div id="wizard-no-profile" style="display:none;">
                <div class="empty-state">
                    <div class="empty-state-icon"><i data-lucide="user-x" style="color:var(--text-muted);"></i></div>
                    <p class="empty-state-title">Perfil incompleto</p>
                    <p class="empty-state-desc">Añade tu peso, altura y edad en el perfil para calcular tus calorías.</p>
                    <a href="/profile" class="btn btn-primary btn-sm mt-3">Ir al perfil</a>
                </div>
            </div>
        </div>

        {{-- Botones de navegación --}}
        <div style="display:flex;gap:0.75rem;padding:1.25rem;">
            <button id="wizard-btn-prev" onclick="wizardPrev()" class="btn btn-outline btn-block" style="display:none;">
                ← Atrás
            </button>
            <button id="wizard-btn-next" onclick="wizardNext()" class="btn btn-primary btn-block">
                Siguiente →
            </button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Cabecera ── */
.header-content { display:flex; justify-content:space-between; align-items:center; }
.page-subtitle   { font-size:0.8rem; color:var(--color-muted); margin-top:0.2rem; }

/* ── Anillo de calorías ── */
.calories-ring-section { padding: 1rem 1.25rem 0; }
.calories-ring-card    { display:flex; flex-direction:column; align-items:center; gap:1rem; padding:1.5rem; }
.ring-container        { position:relative; display:flex; align-items:center; justify-content:center; }
.calories-ring         { transform:rotate(-90deg); }
.ring-bg               { fill:none; stroke:var(--bg-muted); stroke-width:10; }
.ring-progress         {
    fill:none;
    stroke:var(--color-primary);
    stroke-width:10;
    stroke-linecap:round;
    stroke-dasharray: 326.7;   /* 2 * π * 52 */
    stroke-dashoffset: 326.7;  /* Empieza vacío */
    transition: stroke-dashoffset 0.8s ease;
}
.ring-center  { position:absolute; text-align:center; }
.ring-value   { display:block; font-size:1.8rem; font-weight:700; color:var(--color-foreground); line-height:1; }
.ring-unit    { display:block; font-size:0.7rem; color:var(--color-primary); font-weight:600; }
.ring-label   { display:block; font-size:0.65rem; color:var(--color-muted); }

.calories-info        { display:flex; width:100%; justify-content:space-around; }
.cal-stat             { text-align:center; }
.cal-stat-value       { display:block; font-size:1.1rem; font-weight:700; color:var(--color-foreground); }
.cal-stat-label       { display:block; font-size:0.7rem; color:var(--color-muted); margin-top:0.2rem; }
.cal-stat-center .cal-stat-value { color:var(--color-primary); }

/* ── Macros ── */
.macros-section  { padding:0.75rem 1.25rem 0; }
.macros-grid     { display:grid; grid-template-columns:repeat(3,1fr); gap:0.5rem; }
.macro-card      { display:flex; flex-direction:column; align-items:center; padding:0.75rem 0.5rem; text-align:center; }
.macro-icon      { width:2rem; height:2rem; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-bottom:0.4rem; }
.macro-icon i    { width:1rem; height:1rem; }
.macro-protein   { background:rgba(245,158,11,.15); color:var(--color-primary); }
.macro-carbs     { background:rgba(96,165,250,.15); color:#60a5fa; }
.macro-fat       { background:rgba(248,113,113,.15); color:#f87171; }
.macro-value     { font-size:0.95rem; font-weight:700; color:var(--color-foreground); }
.macro-label     { font-size:0.65rem; color:var(--color-muted); display:block; }
.macro-goal      { font-size:0.6rem; color:var(--color-muted); display:block; margin-top:0.2rem; }

/* ── Meal cards ── */
.meal-group      { margin-bottom:1rem; }
.meal-group-title {
    font-size:0.75rem; font-weight:600; text-transform:uppercase;
    color:var(--color-muted); letter-spacing:0.05em; margin-bottom:0.5rem;
    display:flex; justify-content:space-between; align-items:center;
}
.meal-item       { display:flex; justify-content:space-between; align-items:center; padding:0.75rem; }
.meal-item + .meal-item { border-top:1px solid var(--border-color,rgba(255,255,255,.06)); }
.meal-name       { font-size:0.9rem; font-weight:500; color:var(--color-foreground); }
.meal-grams      { font-size:0.75rem; color:var(--color-muted); }
.meal-calories   { font-size:0.9rem; font-weight:600; color:var(--color-primary); }
.meal-delete-btn { background:none; border:none; cursor:pointer; color:var(--color-danger,#f87171); padding:0.25rem; margin-left:0.5rem; opacity:0.6; }
.meal-delete-btn:hover { opacity:1; }

/* ── Quick links ── */
.quick-link-card { display:flex; flex-direction:column; align-items:center; padding:1.25rem 0.5rem; text-align:center; font-size:0.85rem; font-weight:500; color:var(--color-foreground); text-decoration:none; }
.quick-link-card i { width:1.5rem; height:1.5rem; }

/* ── Wizard opciones ── */
.wizard-option {
    display:flex; align-items:center; gap:0.75rem; padding:0.875rem 1rem;
    background:var(--bg-muted); border:1.5px solid transparent;
    border-radius:0.875rem; cursor:pointer; width:100%; transition:all 0.15s;
}
.wizard-option.selected { border-color:var(--color-primary); background:rgba(245,158,11,0.08); }
.wizard-option-icon {
    width:2.25rem; height:2.25rem; border-radius:0.625rem;
    background:rgba(255,255,255,0.06); display:flex; align-items:center;
    justify-content:center; flex-shrink:0; color:var(--text-muted);
}
.wizard-check {
    width:1.25rem; height:1.25rem; border-radius:50%;
    background:rgba(245,158,11,0.15); align-items:center; justify-content:center;
    flex-shrink:0;
}

/* ── Vasos de agua ── */
.water-glass {
    width: 2.25rem; height: 2.75rem;
    background: var(--bg-muted);
    border: 1.5px solid rgba(96,165,250,0.2);
    border-radius: 0.375rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; position: relative; overflow: hidden;
    transition: border-color 0.2s, transform 0.1s;
    padding: 0;
}
.water-glass:hover { border-color: rgba(96,165,250,0.5); transform: scale(1.05); }
.water-glass:active { transform: scale(0.95); }
.water-glass-filled { border-color: rgba(96,165,250,0.6); }
.water-glass-fill {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: rgba(96,165,250,0.35);
    transition: height 0.4s ease;
}
.water-glass i { color: rgba(96,165,250,0.7); }
.water-glass-filled i { color: #60a5fa; }

.water-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.water-quick-actions .btn {
    width: 100%;
    min-width: 0;
    justify-content: center;
}
</style>
@endpush

@push('scripts')
<script>
// ── Estado global de la vista ──────────────────────────────────────────────
let currentDate   = new Date();
let nutritionGoal = { daily_calories:2000, target_protein:150, target_carbs:250, target_fat:65, target_fiber:25 };
let activityData  = { steps: 0, calories_burned: 0 };

// ── Inicialización ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await loadGoal();
    await loadMeals();
    await loadActivity();
    await loadWater();
    await loadSupplementChecklist();
    updateDateHeader();
    lucide.createIcons();

    // Navegación entre días
    document.getElementById('btnPrevDay').addEventListener('click', () => changeDay(-1));
    document.getElementById('btnNextDay').addEventListener('click', () => changeDay(+1));

    // Modal de confirmación para eliminar comida
    const backdrop      = document.getElementById('deleteMealBackdrop');
    const cancelBtn     = document.getElementById('deleteMealCancelBtn');

    if (backdrop) backdrop.addEventListener('click', closeMealDeleteDialog);
    if (cancelBtn) cancelBtn.addEventListener('click', closeMealDeleteDialog);
});

// ── Carga el objetivo nutricional del usuario ──────────────────────────────
async function loadGoal() {
    try {
        const data = await apiCall('/nutrition/goal');
        if (data && data.goal) {
            nutritionGoal = data.goal;
        }
        updateGoalUI();
        // Ocultar el banner si ya tiene objetivo configurado
        const banner = document.getElementById('goal-setup-banner');
        if (banner) banner.style.display = 'none';
    } catch (e) {
        console.error('Error cargando objetivo:', e);
        // Si falla (no tiene objetivo), mostrar banner
        const banner = document.getElementById('goal-setup-banner');
        if (banner) banner.style.display = 'block';
    }
}

// ── Actualiza los textos de objetivo en la UI ──────────────────────────────
function updateGoalUI() {
    document.getElementById('caloriesGoal').textContent  = nutritionGoal.daily_calories.toLocaleString('es');
    document.getElementById('proteinGoal').textContent   = `/ ${nutritionGoal.target_protein}g`;
    document.getElementById('carbsGoal').textContent     = `/ ${nutritionGoal.target_carbs}g`;
    document.getElementById('fatGoal').textContent       = `/ ${nutritionGoal.target_fat}g`;
    // Actualizar objetivo de fibra si existe en el objetivo guardado
    const fiberGoal = nutritionGoal.target_fiber || 25;
    const fiberLabel = document.getElementById('fiberGoalLabel');
    if (fiberLabel) fiberLabel.textContent = `/ ${fiberGoal}g objetivo`;
}

// ── Carga las comidas del día seleccionado ─────────────────────────────────
async function loadMeals() {
    showSkeleton();
    try {
        const dateStr = formatDate(currentDate);
        const data    = await apiCall(`/meals?date=${dateStr}`);
        renderMeals(data);
        updateCaloriesRing(data.totals.calories, data.calories_burned || 0);
        updateMacros(data.totals);
    } catch (e) {
        console.error('Error cargando comidas:', e);
        hideSkeleton();
    }
}

// ── Renderiza los grupos de comidas ───────────────────────────────────────
function renderMeals(data) {
    hideSkeleton();
    const content = document.getElementById('mealsContent');
    const empty   = document.getElementById('mealsEmpty');

    if (data.count === 0) {
        content.style.display = 'none';
        empty.style.display   = 'block';
        return;
    }

    empty.style.display   = 'none';
    content.style.display = 'block';

    const mealLabels = {
        breakfast: 'Desayuno',
        lunch:     'Almuerzo',
        dinner:    'Cena',
        snack:     'Snack'
    };
    const mealOrder = ['breakfast', 'lunch', 'dinner', 'snack'];

    content.innerHTML = mealOrder
        .filter(type => data.meals[type])
        .map(type => {
            const group = data.meals[type];
            const items = group.items.map(item => `
                <div class="meal-item">
                    <div>
                        <div class="meal-name">${escapeHtml(item.food_item ? item.food_item.name : (item.custom_food_name || 'Manual'))}</div>
                        <div class="meal-grams">${item.quantity_grams}g · ${item.protein}g prot · ${item.carbs}g carbs${item.fiber > 0 ? ' · ' + item.fiber + 'g fibra' : ''}</div>
                    </div>
                    <div style="display:flex;align-items:center;">
                        <span class="meal-calories">${Math.round(item.calories)} kcal</span>
                        <button class="meal-delete-btn" onclick="confirmDeleteMeal('${item.uuid}')" title="Eliminar">
                            <i data-lucide="trash-2" style="width:0.85rem;height:0.85rem;"></i>
                        </button>
                    </div>
                </div>
            `).join('');

            return `
                <div class="meal-group">
                    <div class="meal-group-title">
                        <span>${mealLabels[type] || type}</span>
                        <span style="color:var(--color-primary);">${Math.round(group.calories)} kcal</span>
                    </div>
                    <div class="card">${items}</div>
                </div>
            `;
        }).join('');

    lucide.createIcons(); // Re-inicializar iconos en el contenido nuevo
}

// ── Actualiza el anillo de calorías ───────────────────────────────────────
function updateCaloriesRing(consumed, burned = 0) {
    const goal       = nutritionGoal.daily_calories;
    const pct        = Math.min(consumed / goal, 1);
    const circumference = 2 * Math.PI * 52;  // 326.7
    const offset     = circumference - (pct * circumference);
    const ring       = document.getElementById('caloriesRingArc');
    // Restantes se calcula solo contra el objetivo base, sin sumar quemadas.
    const remaining  = goal - consumed;

    ring.style.strokeDashoffset = offset;
    ring.style.stroke = consumed > goal ? '#f87171' : 'var(--color-primary)';

    document.getElementById('caloriesConsumed').textContent  = Math.round(consumed).toLocaleString('es');
    document.getElementById('caloriesRemaining').textContent = Math.abs(Math.round(remaining)).toLocaleString('es');
    document.getElementById('remainingLabel').textContent    = remaining < 0 ? 'Exceso' : 'Restantes';
    // Mostrar calorías quemadas
    const burnedEl = document.getElementById('caloriesBurned');
    if (burnedEl) burnedEl.textContent = burned > 0 ? Math.round(burned).toLocaleString('es') : '–';
}

// ── Actualiza las barras de macros y micronutrientes ───────────────────────
function updateMacros(totals) {
    setMacro('protein', totals.protein, nutritionGoal.target_protein);
    setMacro('carbs',   totals.carbs,   nutritionGoal.target_carbs);
    setMacro('fat',     totals.fat,     nutritionGoal.target_fat);

    // Fibra: objetivo del perfil o 25g por defecto
    const fiberGoal = nutritionGoal.target_fiber || 25;
    setMicroFiber(totals.fiber || 0, fiberGoal);

    // Azúcar: límite OMS 50g/día (se pone rojo si supera)
    setMicroSugar(totals.sugar || 0);
}

function setMacro(name, value, goal) {
    const pct = goal > 0 ? Math.min((value / goal) * 100, 100) : 0;
    document.getElementById(`${name}Consumed`).textContent = `${Math.round(value)}g`;
    document.getElementById(`${name}Bar`).style.width      = `${pct}%`;
}

// Fibra: barra verde, objetivo dinámico del perfil
function setMicroFiber(value, goal) {
    const el = document.getElementById('fiberConsumed');
    const bar = document.getElementById('fiberBar');
    if (!el || !bar) return;

    el.textContent = Math.round(value);
    const pct = goal > 0 ? Math.min((value / goal) * 100, 100) : 0;
    bar.style.width = `${pct}%`;
    // Color: verde si ≥ objetivo, amarillo si está cerca (>70%), gris si lejos
    if (pct >= 100) bar.style.background = '#34d399';
    else if (pct >= 70) bar.style.background = 'var(--color-primary)';
    else bar.style.background = '#34d399';
}

// Azúcar: barra naranja que se vuelve roja si supera el límite OMS (50g)
function setMicroSugar(value) {
    const el = document.getElementById('sugarConsumed');
    const bar = document.getElementById('sugarBar');
    if (!el || !bar) return;

    el.textContent = Math.round(value);
    const limit = 50; // límite OMS
    const pct = Math.min((value / limit) * 100, 100);
    bar.style.width = `${pct}%`;

    // Rojo si supera el 80% del límite, naranja si está entre 50-80%, verde si está bien
    if (value > limit) {
        bar.style.background = '#f87171';
        el.style.color = '#f87171';
    } else if (pct >= 80) {
        bar.style.background = '#fb923c';
        el.style.color = '#fb923c';
    } else {
        bar.style.background = '#fb923c';
        el.style.color = 'var(--text-primary)';
    }
}

// ── Confirmar y eliminar una comida ──────────────────────────────────────
let pendingDeleteUuid = null;

function confirmDeleteMeal(uuid) {
    pendingDeleteUuid = uuid;
    const backdrop = document.getElementById('deleteMealBackdrop');
    const dialog   = document.getElementById('deleteMealDialog');
    if (backdrop) backdrop.style.display = 'block';
    if (dialog)   dialog.style.display   = 'block';

    document.getElementById('deleteMealOkBtn').onclick = async () => {
        const uuidToDelete = pendingDeleteUuid;
        closeMealDeleteDialog();
        await deleteMeal(uuidToDelete);
    };
}

function closeMealDeleteDialog() {
    const backdrop = document.getElementById('deleteMealBackdrop');
    const dialog   = document.getElementById('deleteMealDialog');
    if (backdrop) backdrop.style.display = 'none';
    if (dialog)   dialog.style.display   = 'none';
    pendingDeleteUuid = null;
}

async function deleteMeal(uuid) {
    try {
        await apiCall(`/meals/${uuid}`, 'DELETE');
        showToast('Entrada eliminada', 'success');
        loadMeals();
    } catch (e) {
        showToast('Error al eliminar', 'error');
    }
}

// ── Navegación entre días ─────────────────────────────────────────────────
function changeDay(offset) {
    currentDate.setDate(currentDate.getDate() + offset);
    updateDateHeader();
    loadMeals();
    loadActivity();
    loadWater();
    loadSupplementChecklist();
}

function updateDateHeader() {
    const today     = new Date();
    const yesterday = new Date(); yesterday.setDate(yesterday.getDate() - 1);
    let label;
    if (sameDay(currentDate, today)) {
        label = 'Hoy';
    } else if (sameDay(currentDate, yesterday)) {
        label = 'Ayer';
    } else {
        label = currentDate.toLocaleDateString('es-ES', { weekday:'short', day:'numeric', month:'short' });
    }
    document.getElementById('headerDate').textContent = label;
    // Ocultamos "siguiente" si ya estamos en hoy
    document.getElementById('btnNextDay').style.opacity = sameDay(currentDate, today) ? '0.3' : '1';
    document.getElementById('btnNextDay').disabled      = sameDay(currentDate, today);
}

// ── Helpers ───────────────────────────────────────────────────────────────
function sameDay(a, b) {
    return a.getFullYear() === b.getFullYear() &&
           a.getMonth()    === b.getMonth()    &&
           a.getDate()     === b.getDate();
}
function formatDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
function showSkeleton() {
    document.getElementById('mealsSkeleton').style.display = 'block';
    document.getElementById('mealsContent').style.display  = 'none';
    document.getElementById('mealsEmpty').style.display    = 'none';
}
function hideSkeleton() {
    document.getElementById('mealsSkeleton').style.display = 'none';
}

// ── MÓDULO ACTIVIDAD DIARIA (PASOS + KCAL RELOJ) ───────────────────────
async function loadActivity() {
    try {
        const dateStr = formatDate(currentDate);
        const data = await apiCall(`/activity?date=${dateStr}`);
        activityData = {
            steps: data.steps || 0,
            calories_burned: data.calories_burned || 0,
        };
        renderActivity();
    } catch (e) {
        console.error('Error cargando actividad diaria:', e);
    }
}

function renderActivity() {
    const stepsEl = document.getElementById('activitySteps');
    const calEl = document.getElementById('activityCalories');
    if (stepsEl) stepsEl.textContent = Number(activityData.steps || 0).toLocaleString('es');
    if (calEl) calEl.textContent = Number(activityData.calories_burned || 0).toLocaleString('es');
}

function openActivityModal() {
    const stepsInput = document.getElementById('activityStepsInput');
    const caloriesInput = document.getElementById('activityCaloriesInput');
    if (stepsInput) stepsInput.value = activityData.steps || 0;
    if (caloriesInput) caloriesInput.value = activityData.calories_burned || 0;

    document.getElementById('activityBackdrop').style.display = 'block';
    document.getElementById('activityDialog').style.display = 'block';
}

function closeActivityModal() {
    document.getElementById('activityBackdrop').style.display = 'none';
    document.getElementById('activityDialog').style.display = 'none';
}

async function saveActivity() {
    const steps = parseInt(document.getElementById('activityStepsInput')?.value || '0', 10);
    const calories = parseInt(document.getElementById('activityCaloriesInput')?.value || '0', 10);

    if (steps < 0 || calories < 0 || Number.isNaN(steps) || Number.isNaN(calories)) {
        showToast('Introduce valores validos.', 'error');
        return;
    }

    try {
        const dateStr = formatDate(currentDate);
        const data = await apiCall('/activity', 'PUT', {
            date: dateStr,
            steps: steps,
            calories_burned: calories,
        });

        activityData = {
            steps: data.steps || 0,
            calories_burned: data.calories_burned || 0,
        };
        renderActivity();
        closeActivityModal();

        // Recalcula "Quemadas" y balance diario de calorias
        await loadMeals();
        showToast('Actividad diaria guardada', 'success');
    } catch (e) {
        showToast('No se pudo guardar la actividad', 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Checklist diaria de suplementos ───────────────────────────────────────
const SUPPLEMENT_ITEMS = [
    { key:'multivitaminas', name:'Multivitaminas', dose:'1 pastilla', badgeClass:'badge-primary' },
    { key:'omega3',         name:'Omega 3',        dose:'1 capsula',  badgeClass:'badge-blue' },
    { key:'vitamina_d',     name:'Vitamina D3',    dose:'1 pastilla', badgeClass:'badge-warning' },
    { key:'magnesio',       name:'Magnesio',       dose:'1 pastilla', badgeClass:'badge-teal' },
];
let supplementState = {};

async function fetchSupplementState() {
    try {
        const dateStr = formatDate(currentDate);
        const data = await apiCall(`/supplements?date=${dateStr}`);
        return (data.items || []).reduce((acc, item) => {
            acc[item.key] = !!item.taken;
            return acc;
        }, {});
    } catch (e) {
        console.warn('No se pudo cargar checklist de suplementos:', e);
    }
    return {};
}

async function loadSupplementChecklist() {
    const wrap = document.getElementById('supplementChecklist');
    const progress = document.getElementById('supplementProgressLabel');
    if (!wrap || !progress) return;

    supplementState = await fetchSupplementState();
    renderSupplementChecklistFromState();
}

async function toggleSupplement(key, checked) {
    const previous = !!supplementState[key];
    supplementState[key] = checked;
    renderSupplementChecklistFromState();

    try {
        await apiCall('/supplements', 'PUT', {
            date: formatDate(currentDate),
            supplement_key: key,
            taken: checked,
        });
    } catch (e) {
        supplementState[key] = previous;
        renderSupplementChecklistFromState();
        showToast('Error al actualizar suplemento', 'error');
    }
}

function renderSupplementChecklistFromState() {
    const wrap = document.getElementById('supplementChecklist');
    const progress = document.getElementById('supplementProgressLabel');
    if (!wrap || !progress) return;

    const doneCount = SUPPLEMENT_ITEMS.filter(item => !!supplementState[item.key]).length;

    wrap.innerHTML = SUPPLEMENT_ITEMS.map(item => {
        const checked = !!supplementState[item.key];
        return `
            <label style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;padding:0.625rem 0.75rem;border-radius:0.625rem;background:var(--bg-muted);cursor:pointer;${checked ? 'border:1px solid rgba(52,211,153,0.45);' : 'border:1px solid transparent;'}">
                <div style="display:flex;align-items:center;gap:0.625rem;min-width:0;">
                    <input type="checkbox" ${checked ? 'checked' : ''}
                           onchange="toggleSupplement('${item.key}', this.checked)"
                           style="width:1rem;height:1rem;accent-color:#34d399;cursor:pointer;flex-shrink:0;" />
                    <span style="font-size:0.8rem;font-weight:600;color:var(--text-primary);${checked ? 'text-decoration:line-through;opacity:0.72;' : ''}">${item.name}</span>
                </div>
                <span class="badge ${item.badgeClass}" style="flex-shrink:0;">${item.dose}</span>
            </label>`;
    }).join('');

    progress.textContent = `${doneCount}/${SUPPLEMENT_ITEMS.length} tomados hoy`;
}

async function resetSupplementChecklist() {
    try {
        await apiCall('/supplements', 'DELETE', { date: formatDate(currentDate) });
        supplementState = {};
        renderSupplementChecklistFromState();
        showToast('Checklist reiniciada', 'success');
    } catch (e) {
        showToast('Error al reiniciar checklist', 'error');
    }
}

// ── Wizard de configuración nutricional ───────────────────────────────────
let wizardStep     = 1;
let wizardActivity = 'moderate';
let wizardGoalType = 'maintain';
let wizardGender   = 'male';   // 'male' | 'female' — se inicializa desde perfil
let wizardUser     = null;

const ACTIVITY_FACTORS = {
    sedentary:   { label: 'Sedentario',     desc: 'Poco o ningún ejercicio',          icon: 'armchair',    factor: 1.2   },
    light:       { label: 'Ligero',         desc: 'Ejercicio 1-3 días/semana',         icon: 'walk',        factor: 1.375 },
    moderate:    { label: 'Moderado',       desc: 'Ejercicio 3-5 días/semana',         icon: 'bike',        factor: 1.55  },
    active:      { label: 'Activo',         desc: 'Ejercicio intenso 6-7 días/semana', icon: 'dumbbell',    factor: 1.725 },
    very_active: { label: 'Muy activo',     desc: 'Deporte + trabajo físico',          icon: 'zap',         factor: 1.9   },
};

const GOAL_TYPES = {
    lose_weight:  { label: 'Perder peso',    desc: 'Déficit calórico de 500 kcal',  icon: 'trending-down', color: '#60a5fa', adj: -500, macros: [0.35, 0.40, 0.25] },
    maintain:     { label: 'Mantener peso',  desc: 'Calorías de mantenimiento',     icon: 'minus',         color: '#a78bfa', adj:    0, macros: [0.30, 0.45, 0.25] },
    gain_muscle:  { label: 'Ganar músculo',  desc: 'Superávit calórico de 300 kcal',icon: 'trending-up',   color: '#34d399', adj: +300, macros: [0.30, 0.50, 0.20] },
};

async function openGoalWizard() {
    wizardStep     = 1;
    wizardActivity = 'moderate';
    wizardGoalType = 'maintain';
    // Precargamos el género del perfil para que el cálculo sea correcto desde el principio
    if (!wizardUser) {
        try {
            const data = await apiCall('/profile');
            wizardUser = data.user;
        } catch (e) { /* noop */ }
    }
    wizardGender = wizardUser?.gender || 'male';
    document.getElementById('goal-wizard').style.display = 'flex';
    renderWizardStep();
    lucide.createIcons();
}

function closeGoalWizard() {
    document.getElementById('goal-wizard').style.display = 'none';
}

function renderWizardStep() {
    // Actualiza indicadores de paso
    [1,2,3].forEach(s => {
        const dot = document.getElementById(`wizard-dot-${s}`);
        dot.style.background = s === wizardStep ? 'var(--color-primary)' : (s < wizardStep ? 'rgba(245,158,11,0.4)' : 'var(--bg-muted)');
        dot.style.width  = s === wizardStep ? '1.5rem' : '0.5rem';
    });

    document.getElementById('wizard-step-1').style.display = wizardStep === 1 ? 'block' : 'none';
    document.getElementById('wizard-step-2').style.display = wizardStep === 2 ? 'block' : 'none';
    document.getElementById('wizard-step-3').style.display = wizardStep === 3 ? 'block' : 'none';

    document.getElementById('wizard-btn-prev').style.display = wizardStep > 1 ? 'block' : 'none';
    document.getElementById('wizard-btn-next').textContent   = wizardStep < 3 ? 'Siguiente →' : 'Guardar objetivo';
    document.getElementById('wizard-btn-next').style.background = wizardStep === 3 ? 'var(--color-primary)' : '';

    if (wizardStep === 1) renderActivityStep();
    if (wizardStep === 2) renderGoalStep();
    if (wizardStep === 3) renderPreviewStep();

    lucide.createIcons();
}

function renderActivityStep() {
    // Sincronizar botones de género con el estado actual
    const male   = document.getElementById('wizard-gender-male');
    const female = document.getElementById('wizard-gender-female');
    if (male)   male.classList.toggle('selected',   wizardGender === 'male');
    if (female) female.classList.toggle('selected', wizardGender === 'female');

    const container = document.getElementById('wizard-activity-list');
    container.innerHTML = Object.entries(ACTIVITY_FACTORS).map(([key, cfg]) => `
        <button class="wizard-option ${wizardActivity === key ? 'selected' : ''}"
                onclick="selectActivity('${key}')"
                data-key="${key}">
            <div class="wizard-option-icon">
                <i data-lucide="${cfg.icon}" style="width:1.1rem;height:1.1rem;"></i>
            </div>
            <div style="flex:1;text-align:left;">
                <span style="font-size:0.875rem;font-weight:700;display:block;color:var(--text-primary);">${cfg.label}</span>
                <span style="font-size:0.75rem;color:rgba(255,255,255,0.72);">${cfg.desc}</span>
            </div>
            <div class="wizard-check" style="display:${wizardActivity === key ? 'flex' : 'none'};">
                <i data-lucide="check" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
            </div>
        </button>
    `).join('');
    lucide.createIcons();
}

function selectActivity(key) {
    wizardActivity = key;
    renderActivityStep();
}

function selectWizardGender(gender) {
    wizardGender = gender;
    // Actualizar resaltado visual de los botones de género
    const male   = document.getElementById('wizard-gender-male');
    const female = document.getElementById('wizard-gender-female');
    if (male)   male.classList.toggle('selected',   gender === 'male');
    if (female) female.classList.toggle('selected', gender === 'female');
}

function renderGoalStep() {
    const container = document.getElementById('wizard-goal-list');
    container.innerHTML = Object.entries(GOAL_TYPES).map(([key, cfg]) => `
        <button class="wizard-option ${wizardGoalType === key ? 'selected' : ''}"
                onclick="selectGoalType('${key}')"
                data-key="${key}">
            <div class="wizard-option-icon" style="background:${cfg.color}22;color:${cfg.color};">
                <i data-lucide="${cfg.icon}" style="width:1.1rem;height:1.1rem;"></i>
            </div>
            <div style="flex:1;text-align:left;">
                <span style="font-size:0.875rem;font-weight:700;display:block;color:var(--text-primary);">${cfg.label}</span>
                <span style="font-size:0.75rem;color:rgba(255,255,255,0.72);">${cfg.desc}</span>
            </div>
            <div class="wizard-check" style="display:${wizardGoalType === key ? 'flex' : 'none'};">
                <i data-lucide="check" style="width:0.875rem;height:0.875rem;color:${cfg.color};"></i>
            </div>
        </button>
    `).join('');
    lucide.createIcons();
}

function selectGoalType(key) {
    wizardGoalType = key;
    renderGoalStep();
}

async function renderPreviewStep() {
    const preview   = document.getElementById('wizard-preview');
    const noProfile = document.getElementById('wizard-no-profile');

    // Cargamos el perfil del usuario si no lo tenemos aún
    if (!wizardUser) {
        try {
            const data = await apiCall('/profile');
            wizardUser = data.user;
        } catch (e) {
            console.error('Error cargando perfil:', e);
        }
    }

    const result = calculateNutritionGoal();
    if (!result) {
        preview.style.display   = 'none';
        noProfile.style.display = 'block';
        return;
    }

    noProfile.style.display = 'none';
    preview.style.display   = 'block';

    const goalCfg = GOAL_TYPES[wizardGoalType];
    preview.innerHTML = `
        <div style="text-align:center; margin-bottom:1.25rem;">
            <div style="font-size:2.5rem; font-weight:900; color:var(--color-primary);">${result.daily_calories}</div>
            <div style="font-size:0.875rem; color:rgba(255,255,255,0.6);">kcal / día</div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.625rem; margin-bottom:1rem;">
            <div class="card" style="padding:0.75rem; text-align:center;">
                <div style="font-size:1.25rem; font-weight:800; color:var(--color-primary);">${result.target_protein}g</div>
                <div style="font-size:0.7rem; color:rgba(255,255,255,0.6); margin-top:0.2rem;">Proteína</div>
            </div>
            <div class="card" style="padding:0.75rem; text-align:center;">
                <div style="font-size:1.25rem; font-weight:800; color:#60a5fa;">${result.target_carbs}g</div>
                <div style="font-size:0.7rem; color:rgba(255,255,255,0.6); margin-top:0.2rem;">Carbos</div>
            </div>
            <div class="card" style="padding:0.75rem; text-align:center;">
                <div style="font-size:1.25rem; font-weight:800; color:#f87171;">${result.target_fat}g</div>
                <div style="font-size:0.7rem; color:rgba(255,255,255,0.6); margin-top:0.2rem;">Grasa</div>
            </div>
        </div>
        <div class="card" style="padding:0.75rem; display:flex; align-items:center; gap:0.75rem; margin-bottom:0.75rem;">
            <div style="width:1.5rem; height:1.5rem; border-radius:50%; background:${goalCfg.color}22; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i data-lucide="${goalCfg.icon}" style="width:0.875rem;height:0.875rem;color:${goalCfg.color};"></i>
            </div>
            <div>
                <div style="font-size:0.875rem; font-weight:700; color:var(--text-primary);">${goalCfg.label}</div>
                <div style="font-size:0.75rem; color:rgba(255,255,255,0.6);">${goalCfg.desc}</div>
            </div>
        </div>
        <div style="font-size:0.7rem; color:rgba(255,255,255,0.4); text-align:center;">
            Mifflin-St Jeor · ${wizardGender === 'female' ? 'Mujer' : 'Hombre'} · ${wizardUser.weight}kg · ${wizardUser.height}cm · ${wizardUser.age} años
        </div>
    `;
    lucide.createIcons();
}

function calculateNutritionGoal() {
    const user     = wizardUser;
    const activity = ACTIVITY_FACTORS[wizardActivity];
    const goalCfg  = GOAL_TYPES[wizardGoalType];

    if (!user || !user.weight || !user.height || !user.age) {
        return null;
    }

    // Mifflin-St Jeor diferenciada por sexo (igual que NutritionGoal::calculateRecommended)
    // Hombre: +5 / Mujer: -161
    const genderConstant = (wizardGender === 'female') ? -161 : 5;
    const bmr  = (10 * user.weight) + (6.25 * user.height) - (5 * user.age) + genderConstant;
    let tdee   = Math.round(bmr * activity.factor);
    const cals = Math.max(1200, tdee + goalCfg.adj);

    const [pPct, cPct, fPct] = goalCfg.macros;
    return {
        daily_calories: cals,
        target_protein: Math.round((cals * pPct) / 4),  // 4 kcal/g proteína
        target_carbs:   Math.round((cals * cPct) / 4),  // 4 kcal/g carbos
        target_fat:     Math.round((cals * fPct) / 9),  // 9 kcal/g grasa
        target_fiber:   25,                              // Recomendación estándar
        goal_type:      wizardGoalType,
    };
}

async function wizardNext() {
    if (wizardStep < 3) {
        wizardStep++;
        renderWizardStep();
    } else {
        // Guardar objetivo
        const result = calculateNutritionGoal();
        if (!result) {
            showToast('Completa tu perfil primero', 'error');
            return;
        }
        try {
            await apiCall('/nutrition/goal', 'POST', result);
            nutritionGoal = { ...nutritionGoal, ...result };
            updateGoalUI();
            closeGoalWizard();
            showToast('Objetivo guardado correctamente', 'success');
            // Ocultar banner si estaba visible
            const banner = document.getElementById('goal-setup-banner');
            if (banner) banner.style.display = 'none';
        } catch (e) {
            showToast('Error al guardar objetivo', 'error');
        }
    }
}

function wizardPrev() {
    if (wizardStep > 1) {
        wizardStep--;
        renderWizardStep();
    }
}

// ── MÓDULO DE AGUA ────────────────────────────────────────────────────────
// Estado interno del módulo
let waterData = { total_ml: 0, goal_ml: 2000, entries: [] };
let pendingWaterDeleteIds = new Set();

/**
 * Carga el resumen de agua del día actual desde la API.
 * Se llama al inicio y cada vez que cambiamos de día.
 */
async function loadWater() {
    try {
        const dateStr = formatDate(currentDate);
        const data    = await apiCall(`/water?date=${dateStr}`);
        waterData = data;
        renderWater();
        // Sincronizamos el input del modal con el objetivo real
        const inp = document.getElementById('waterGoalInput');
        if (inp) inp.value = data.goal_ml;
    } catch (e) {
        console.error('Error cargando agua:', e);
    }
}

/**
 * Dibuja el widget de agua: barra, contador y vasos visuales.
 * Cada vaso = 250ml. Los vasos llenos se colorean en azul.
 */
function renderWater() {
    const { total_ml, goal_ml, entries } = waterData;
    const pct = goal_ml > 0 ? Math.min((total_ml / goal_ml) * 100, 100) : 0;

    // Barra de progreso
    const bar = document.getElementById('waterBar');
    if (bar) {
        bar.style.width      = `${pct}%`;
        // Verde si superó el objetivo, azul si está en camino
        bar.style.background = total_ml >= goal_ml ? '#4ade80' : '#60a5fa';
    }

    // Contador de texto
    const totalEl = document.getElementById('waterTotal');
    const goalEl  = document.getElementById('waterGoal');
    if (totalEl) totalEl.textContent = total_ml;
    if (goalEl)  goalEl.textContent  = goal_ml;

    // Vasos visuales: mostramos cuántos vasos de 250ml ha bebido
    // El último vaso puede estar "medio" lleno si la cantidad no es múltiplo de 250
    const glassesEl   = document.getElementById('waterGlasses');
    if (!glassesEl) return;

    // Calculamos vasos totales que representan el objetivo
    const glassSize   = 250;
    const totalGlasses = Math.ceil(goal_ml / glassSize);  // vasos necesarios para el objetivo
    const filledMl    = total_ml;

    let html = '';
    for (let i = 0; i < Math.min(totalGlasses, 12); i++) {
        const consumed = Math.min(filledMl - (i * glassSize), glassSize);
        const fillPct  = Math.max(0, Math.min((consumed / glassSize) * 100, 100));
        // Si hay entrada concreta para este vaso, guardamos su ID para poder borrarlo
        const entry    = entries[i];
        const entryId  = entry ? entry.id : null;
        const isDeleting = entryId ? pendingWaterDeleteIds.has(entryId) : false;

        html += `
            <button class="water-glass ${fillPct > 0 ? 'water-glass-filled' : ''}"
                    onclick="${entryId ? (isDeleting ? '' : `removeWaterEntry(${entryId})`) : `addWater(250)`}"
                    title="${entryId ? 'Quitar este vaso' : 'Añadir vaso'}"
                    ${isDeleting ? 'disabled' : ''}
                    style="position:relative;overflow:hidden;">
                <div class="water-glass-fill" style="height:${fillPct}%;"></div>
                <i data-lucide="droplet" style="position:relative;z-index:1;width:1rem;height:1rem;"></i>
            </button>`;
    }
    glassesEl.innerHTML = html;
    lucide.createIcons();
}

/**
 * Añade una cantidad de agua para el día actual.
 * @param {number} ml - Cantidad en mililitros (ej: 250)
 */
async function addWater(ml) {
    try {
        const dateStr = formatDate(currentDate);
        const data    = await apiCall('/water', 'POST', { amount_ml: ml, date: dateStr });
        // Actualizamos el estado local sin recargar toda la página
        waterData.total_ml = data.total_ml;
        waterData.goal_ml  = data.goal_ml;
        waterData.entries.push(data.entry);
        renderWater();
        showToast(`+${ml} ml añadidos`, 'success');
    } catch (e) {
        showToast('Error al añadir agua', 'error');
    }
}

/**
 * Elimina una entrada específica de agua (un "vaso").
 * El usuario puede hacer click en el vaso lleno para quitarlo.
 * @param {number} id - ID de la entrada en water_logs
 */
async function removeWaterEntry(id) {
    if (pendingWaterDeleteIds.has(id)) return;
    pendingWaterDeleteIds.add(id);
    renderWater();

    try {
        const data = await apiCall(`/water/${id}`, 'DELETE');
        waterData.total_ml = data.total_ml;
        waterData.goal_ml  = data.goal_ml;
        // Quitamos la entrada del array local
        waterData.entries = waterData.entries.filter(e => e.id !== id);
        showToast('Entrada eliminada', 'success');
    } catch (e) {
        // Si ya no existe (doble click o estado desfasado), resincronizamos sin mostrar error duro
        const msg = String(e?.message || '').toLowerCase();
        if (msg.includes('no query results') || msg.includes('no encontrada') || msg.includes('404')) {
            await loadWater();
            return;
        }
        showToast('Error al eliminar', 'error');
    } finally {
        pendingWaterDeleteIds.delete(id);
        renderWater();
    }
}

/**
 * Abre el modal para cambiar el objetivo diario de agua.
 */
function openWaterGoalModal() {
    const inp = document.getElementById('waterGoalInput');
    if (inp) inp.value = waterData.goal_ml;
    document.getElementById('waterGoalBackdrop').style.display = 'block';
    document.getElementById('waterGoalDialog').style.display   = 'block';
}

function closeWaterGoalModal() {
    document.getElementById('waterGoalBackdrop').style.display = 'none';
    document.getElementById('waterGoalDialog').style.display   = 'none';
}

/**
 * Guarda el nuevo objetivo diario de agua.
 */
async function saveWaterGoal() {
    const inp   = document.getElementById('waterGoalInput');
    const ml    = parseInt(inp?.value);
    if (!ml || ml < 500 || ml > 6000) {
        showToast('El objetivo debe estar entre 500 y 6000 ml', 'error');
        return;
    }
    try {
        await apiCall('/water/goal', 'PUT', { goal_ml: ml });
        waterData.goal_ml = ml;
        renderWater();
        closeWaterGoalModal();
        showToast('Objetivo actualizado', 'success');
    } catch (e) {
        showToast('Error al guardar objetivo', 'error');
    }
}
</script>
@endpush
