@extends('layouts.app')

@section('title', 'Añadir comida')

@section('content')
<div class="page-container">

    {{-- ── CABECERA ────────────────────────────────────────────────── --}}
    <header class="page-header sticky-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <a href="/nutrition" class="btn btn-ghost btn-icon">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title">Añadir comida</h1>
            </div>
        </div>
        <button class="btn btn-ghost btn-icon" id="createFoodBtn" title="Crear alimento">
            <i data-lucide="plus-circle"></i>
        </button>
    </header>

    {{-- ── SELECTOR DE TIPO DE COMIDA ────────────────────────────── --}}
    <section style="padding:1rem 1rem 0;">
        <div class="tabs-container" id="mealTypeTabs">
            <button class="tab-btn active" data-type="breakfast">Desayuno</button>
            <button class="tab-btn" data-type="lunch">Almuerzo</button>
            <button class="tab-btn" data-type="dinner">Cena</button>
            <button class="tab-btn" data-type="snack">Snack</button>
        </div>
    </section>

    {{-- ── SELECTOR MODO: Alimentos / Recetas ────────────────────── --}}
    <section style="padding:0.75rem 1rem 0;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
            <button id="modeFoodsBtn"
                    onclick="setMode('foods')"
                    class="btn btn-primary btn-sm"
                    style="justify-content:center;gap:0.375rem;">
                <i data-lucide="search" style="width:0.875rem;height:0.875rem;"></i>
                Alimentos
            </button>
            <button id="modeRecipesBtn"
                    onclick="setMode('recipes')"
                    class="btn btn-outline btn-sm"
                    style="justify-content:center;gap:0.375rem;">
                <i data-lucide="book-open" style="width:0.875rem;height:0.875rem;"></i>
                Recetas
            </button>
        </div>
    </section>

    {{-- ════════ PANEL DE ALIMENTOS ════════════════════════════════ --}}
    <div id="panelFoods">

        {{-- ── BUSCADOR DE ALIMENTOS ──────────────────────────────── --}}
        <section style="padding:0.75rem 1rem 0;">
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--color-muted);width:1rem;height:1rem;pointer-events:none;"></i>
                <input
                    type="text"
                    id="foodSearch"
                    class="input"
                    placeholder="Buscar alimento (pollo, arroz...)"
                    style="padding-left:2.5rem;"
                    autocomplete="off"
                >
            </div>

            {{-- Fila de categorías --}}
            <div id="categoriesRow" style="display:flex;gap:0.5rem;margin-top:0.75rem;overflow-x:auto;padding-bottom:0.25rem;">
                <!-- Se rellenan dinámicamente -->
            </div>

            {{-- Filtros de macros --}}
            <div style="display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;" id="macroFiltersRow">
                <button class="pill-tab macro-filter" data-filter="" style="font-size:0.72rem;">Todos</button>
                <button class="pill-tab macro-filter" data-filter="high_protein" style="font-size:0.72rem;">💪 Alto en proteína</button>
                <button class="pill-tab macro-filter" data-filter="low_carbs" style="font-size:0.72rem;">🥩 Bajo en carbos</button>
                <button class="pill-tab macro-filter" data-filter="low_fat" style="font-size:0.72rem;">🥗 Bajo en grasa</button>
                <button class="pill-tab macro-filter" data-filter="low_cal" style="font-size:0.72rem;">🔥 Bajo en calorías</button>
            </div>
        </section>

        {{-- ── RESULTADOS DE BÚSQUEDA ─────────────────────────────── --}}
        <section style="padding:1rem 1rem 0;" id="searchSection">
            <div id="foodCount" style="font-size:0.75rem;color:var(--color-muted);margin-bottom:0.5rem;display:none;"></div>
            <div id="searchResults"><!-- Resultados --></div>
        </section>

        {{-- ── ENTRADA MANUAL ─────────────────────────────────────── --}}
        <section style="padding:0.75rem 1rem 0;">
            <button id="toggleManual" class="btn btn-ghost"
                    style="width:100%;justify-content:center;font-size:0.85rem;">
                <i data-lucide="pencil" style="width:0.9rem;height:0.9rem;"></i>
                <span style="margin-left:0.4rem;">Añadir entrada manual (sin guardar)</span>
            </button>
            <div id="manualForm" style="display:none;margin-top:0.75rem;">
                <div class="card" style="padding:1rem;">
                    <div class="form-group">
                        <label class="form-label">Nombre del alimento</label>
                        <input type="text" id="manualName" class="input" placeholder="Ej: Tortilla casera">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-top:0.75rem;">
                        <div class="form-group">
                            <label class="form-label">Calorías (kcal)</label>
                            <input type="number" id="manualCalories" class="input" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Proteína (g)</label>
                            <input type="number" id="manualProtein" class="input" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Carbos (g)</label>
                            <input type="number" id="manualCarbs" class="input" placeholder="0" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Grasa (g)</label>
                            <input type="number" id="manualFat" class="input" placeholder="0" min="0">
                        </div>
                    </div>
                    <button class="btn btn-primary btn-block" id="addManualBtn" style="margin-top:1rem;">
                        Añadir entrada manual
                    </button>
                </div>
            </div>
        </section>

    </div>{{-- fin #panelFoods --}}

    {{-- ════════ PANEL DE RECETAS ═════════════════════════════════ --}}
    <div id="panelRecipes" style="display:none;">

        {{-- Buscador de recetas --}}
        <section style="padding:0.75rem 1rem 0;">
            <div style="position:relative;">
                <i data-lucide="search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--color-muted);width:1rem;height:1rem;pointer-events:none;"></i>
                <input
                    type="text"
                    id="recipeSearch"
                    class="input"
                    placeholder="Buscar receta (pollo, pasta...)"
                    style="padding-left:2.5rem;"
                    autocomplete="off"
                >
            </div>
            {{-- Filtros de categoría --}}
            <div id="recipeCatsRow" style="display:flex;gap:0.5rem;margin-top:0.75rem;overflow-x:auto;padding-bottom:0.25rem;">
                <button class="pill-tab active" onclick="filterRecipesByCategory('')">Todas</button>
                <button class="pill-tab" onclick="filterRecipesByCategory('desayuno')">🌅 Desayuno</button>
                <button class="pill-tab" onclick="filterRecipesByCategory('almuerzo')">☀️ Almuerzo</button>
                <button class="pill-tab" onclick="filterRecipesByCategory('cena')">🌙 Cena</button>
                <button class="pill-tab" onclick="filterRecipesByCategory('snack')">🍎 Snack</button>
            </div>
        </section>

        {{-- Skeleton / listado de recetas --}}
        <section style="padding:1rem 1rem 0;" id="recipeSection">
            <div id="recipeCount" style="font-size:0.75rem;color:var(--color-muted);margin-bottom:0.5rem;display:none;"></div>
            <div id="recipeResults">
                <div class="skeleton-card"></div>
                <div class="skeleton-card" style="margin-top:0.5rem;"></div>
            </div>
        </section>

    </div>{{-- fin #panelRecipes --}}

    <div style="height:4rem;"></div>
</div>

{{-- ── BOTTOM SHEET: CANTIDAD (para alimentos) ──────────────────── --}}
<div class="modal-backdrop" id="quantityBackdrop" style="display:none;" onclick="closeQuantityModal()"></div>
<div class="bottom-sheet" id="quantitySheet" style="display:none;">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 id="sheetFoodName" class="sheet-title">Alimento</h3>
        <div id="sheetFoodMacros" style="font-size:0.75rem;color:var(--color-muted);margin-top:0.25rem;"></div>
    </div>
    <div class="sheet-body">
        <div class="form-group">
            <label class="form-label" id="quantityLabel">Cantidad (g)</label>
            <input type="number" id="quantityInput" class="input" value="100" min="1" max="5000" step="10">
        </div>
        <div id="quantityPresets" style="display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;">
            {{-- Los botones de cantidad rápida se generan dinámicamente según la unidad --}}
        </div>
        <div id="macrosPreview" class="card" style="margin-top:1rem;padding:0.75rem;">
            {{-- Fila 1: macronutrientes principales --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);text-align:center;gap:0.5rem;">
                <div><span class="preview-value" id="previewCalories">0</span><span class="preview-label">kcal</span></div>
                <div><span class="preview-value" id="previewProtein">0g</span><span class="preview-label">prot</span></div>
                <div><span class="preview-value" id="previewCarbs">0g</span><span class="preview-label">carbs</span></div>
                <div><span class="preview-value" id="previewFat">0g</span><span class="preview-label">grasa</span></div>
            </div>
            {{-- Fila 2: micronutrientes --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;text-align:center;gap:0.5rem;margin-top:0.5rem;padding-top:0.5rem;border-top:1px solid rgba(255,255,255,0.06);">
                <div style="display:flex;align-items:center;justify-content:center;gap:0.3rem;">
                    <i data-lucide="leaf" style="width:0.75rem;height:0.75rem;color:#34d399;flex-shrink:0;"></i>
                    <span class="preview-value" id="previewFiber" style="color:#34d399;font-size:0.875rem;">0g</span>
                    <span class="preview-label">fibra</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:center;gap:0.3rem;">
                    <i data-lucide="candy" style="width:0.75rem;height:0.75rem;color:#fb923c;flex-shrink:0;"></i>
                    <span class="preview-value" id="previewSugar" style="color:#fb923c;font-size:0.875rem;">0g</span>
                    <span class="preview-label">azúcar</span>
                </div>
            </div>
        </div>
        <button class="btn btn-primary btn-block" id="confirmAddBtn" style="margin-top:1rem;">
            Añadir a <span id="confirmMealLabel">la comida</span>
        </button>
    </div>
</div>

{{-- ── BOTTOM SHEET: CONFIRMAR AÑADIR RECETA ────────────────────── --}}
<div class="modal-backdrop" id="recipeSheetBackdrop" style="display:none;" onclick="closeRecipeSheet()"></div>
<div class="bottom-sheet" id="recipeSheet" style="display:none;">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 id="recipeSheetName" class="sheet-title">Receta</h3>
        <div id="recipeSheetMeta" style="font-size:0.75rem;color:var(--color-muted);margin-top:0.25rem;"></div>
    </div>
    <div class="sheet-body">
        {{-- Info de la receta --}}
        <div id="recipeSheetPreview" class="card" style="padding:0.875rem;margin-bottom:1rem;">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);text-align:center;gap:0.5rem;">
                <div><span class="preview-value" id="rPreviewCalories">0</span><span class="preview-label">kcal</span></div>
                <div><span class="preview-value" id="rPreviewProtein">0g</span><span class="preview-label">prot</span></div>
                <div><span class="preview-value" id="rPreviewCarbs">0g</span><span class="preview-label">carbs</span></div>
                <div><span class="preview-value" id="rPreviewFat">0g</span><span class="preview-label">grasa</span></div>
            </div>
        </div>
        {{-- Selector de porciones --}}
        <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label">Número de porciones</label>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-top:0.4rem;">
                <button class="btn btn-outline btn-icon" onclick="changeServings(-1)">
                    <i data-lucide="minus" style="width:1rem;height:1rem;"></i>
                </button>
                <span id="servingsCount" style="font-size:1.25rem;font-weight:700;color:var(--text-primary);min-width:2rem;text-align:center;">1</span>
                <button class="btn btn-outline btn-icon" onclick="changeServings(1)">
                    <i data-lucide="plus" style="width:1rem;height:1rem;"></i>
                </button>
                <span style="font-size:0.8rem;color:var(--text-muted);">porción(es)</span>
            </div>
        </div>
        <button class="btn btn-primary btn-block" id="confirmRecipeBtn" onclick="addRecipeAsMeal()">
            Añadir a <span id="confirmRecipeMealLabel">la comida</span>
        </button>
    </div>
</div>

{{-- ── BOTTOM SHEET: CREAR ALIMENTO PERSONALIZADO ──────────────── --}}
<div class="modal-backdrop" id="createFoodBackdrop" style="display:none;" onclick="closeCreateFoodModal()"></div>
<div class="bottom-sheet" id="createFoodSheet" style="display:none;max-height:90dvh;">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Nuevo alimento</h3>
        <p style="font-size:0.78rem;color:var(--color-muted);margin-top:0.25rem;">Los valores son por cada 100g del alimento</p>
    </div>
    <div class="sheet-body">
        <div class="form-group">
            <label class="form-label">Nombre *</label>
            <input type="text" id="cfName" class="input" placeholder="Ej: Macarrones cocidos">
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Categoría</label>
            <select id="cfCategory" class="input" style="appearance:auto;">
                <option value="Preparados">Preparados</option>
                <option value="Carnes y aves">Carnes y aves</option>
                <option value="Pescados y mariscos">Pescados y mariscos</option>
                <option value="Lácteos y huevos">Lácteos y huevos</option>
                <option value="Cereales y harinas">Cereales y harinas</option>
                <option value="Legumbres">Legumbres</option>
                <option value="Verduras y hortalizas">Verduras y hortalizas</option>
                <option value="Frutas">Frutas</option>
                <option value="Frutos secos y semillas">Frutos secos y semillas</option>
                <option value="Snacks y dulces">Snacks y dulces</option>
                <option value="Bebidas">Bebidas</option>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-top:0.75rem;">
            <div class="form-group">
                <label class="form-label">Calorías (kcal) *</label>
                <input type="number" id="cfCalories" class="input" placeholder="0" min="0" step="0.1">
            </div>
            <div class="form-group">
                <label class="form-label">Proteína (g)</label>
                <input type="number" id="cfProtein" class="input" placeholder="0" min="0" step="0.1">
            </div>
            <div class="form-group">
                <label class="form-label">Carbohidratos (g)</label>
                <input type="number" id="cfCarbs" class="input" placeholder="0" min="0" step="0.1">
            </div>
            <div class="form-group">
                <label class="form-label">Grasa (g)</label>
                <input type="number" id="cfFat" class="input" placeholder="0" min="0" step="0.1">
            </div>
            <div class="form-group">
                <label class="form-label">Fibra (g)</label>
                <input type="number" id="cfFiber" class="input" placeholder="0" min="0" step="0.1">
            </div>
        </div>
        <div style="display:flex;gap:0.75rem;margin-top:1rem;">
            <button class="btn btn-ghost btn-block" onclick="closeCreateFoodModal()">Cancelar</button>
            <button class="btn btn-primary btn-block" id="saveFoodBtn">Guardar alimento</button>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Preview de macros ── */
.preview-value { display:block; font-size:1rem; font-weight:700; color:var(--color-primary); }
.preview-label { display:block; font-size:0.65rem; color:var(--color-muted); }

/* ── Resultado de búsqueda de alimentos ── */
.food-item-card { display:flex; justify-content:space-between; align-items:center; padding:0.875rem; cursor:pointer; }
.food-item-card + .food-item-card { border-top:1px solid var(--border-color,rgba(255,255,255,.06)); }
.food-item-name  { font-size:0.9rem; font-weight:500; color:var(--color-foreground); }
.food-item-meta  { font-size:0.72rem; color:var(--color-muted); margin-top:0.2rem; }
.food-item-kcal  { font-size:0.85rem; font-weight:600; color:var(--color-primary); white-space:nowrap; margin-left:0.5rem; }

/* ── Tarjeta de receta en el log ── */
.log-recipe-card {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.875rem; cursor: pointer;
}
.log-recipe-card + .log-recipe-card { border-top: 1px solid rgba(255,255,255,.06); }
.log-recipe-img {
    width: 3rem; height: 3rem; border-radius: 0.5rem;
    object-fit: cover; flex-shrink: 0;
    background: var(--bg-muted);
}
.log-recipe-img-placeholder {
    width: 3rem; height: 3rem; border-radius: 0.5rem; flex-shrink: 0;
    background: var(--bg-muted); display: flex; align-items: center;
    justify-content: center; font-size: 1.25rem;
}
.log-recipe-name { font-size: 0.9rem; font-weight: 500; color: var(--color-foreground); }
.log-recipe-meta { font-size: 0.72rem; color: var(--color-muted); margin-top: 0.2rem; }
.log-recipe-kcal { font-size: 0.85rem; font-weight: 600; color: var(--color-primary); white-space: nowrap; }

/* ── Badge "Mío" ── */
.badge-mine { font-size:0.6rem; padding:0.1rem 0.35rem; border-radius:9999px; background:rgba(245,158,11,.2); color:var(--color-primary); margin-left:0.4rem; font-weight:600; vertical-align:middle; }

/* ── Filtro activo ── */
.macro-filter.active { background:var(--color-primary) !important; color:#000 !important; }

/* ── Botones de modo activo/inactivo ── */
.mode-active   { /* hereda de btn-primary */ }
.mode-inactive { /* hereda de btn-outline */ }
</style>
@endpush

@push('scripts')
<script>
// ── Estado ─────────────────────────────────────────────────────────────────
let selectedMealType  = 'breakfast';
let selectedFood      = null;
let selectedRecipe    = null;
let selectedServings  = 1;
let searchTimeout     = null;
let recipeSearchTimeout = null;
let activeCategory    = '';
let activeMacroFilter = '';
let activeRecipeCat   = '';
let allRecipes        = [];   // Cache local de recetas cargadas
let currentMode       = 'foods'; // 'foods' | 'recipes'

// ── Inicialización ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadInitialFoods();
    loadRecipes();
    setupTabs();
    setupSearch();
    setupMacroFilters();
    setupManualForm();
    setupQuantitySheet();
    setupCreateFoodSheet();
    setupRecipeSearch();
    lucide.createIcons();
});

// ── Cambio de modo (Alimentos / Recetas) ─────────────────────────────────
function setMode(mode) {
    currentMode = mode;
    const panelFoods   = document.getElementById('panelFoods');
    const panelRecipes = document.getElementById('panelRecipes');
    const btnFoods     = document.getElementById('modeFoodsBtn');
    const btnRecipes   = document.getElementById('modeRecipesBtn');

    if (mode === 'foods') {
        panelFoods.style.display   = 'block';
        panelRecipes.style.display = 'none';
        btnFoods.className   = 'btn btn-primary btn-sm';
        btnRecipes.className = 'btn btn-outline btn-sm';
        btnFoods.style.justifyContent = btnRecipes.style.justifyContent = 'center';
        btnFoods.style.gap = btnRecipes.style.gap = '0.375rem';
    } else {
        panelFoods.style.display   = 'none';
        panelRecipes.style.display = 'block';
        btnFoods.className   = 'btn btn-outline btn-sm';
        btnRecipes.className = 'btn btn-primary btn-sm';
        btnFoods.style.justifyContent = btnRecipes.style.justifyContent = 'center';
        btnFoods.style.gap = btnRecipes.style.gap = '0.375rem';
    }
}

// ── Tabs de tipo de comida ─────────────────────────────────────────────────
function setupTabs() {
    document.querySelectorAll('#mealTypeTabs .tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#mealTypeTabs .tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedMealType = btn.dataset.type;
            // Actualizar labels de confirmación en ambos sheets
            document.getElementById('confirmMealLabel').textContent       = btn.textContent.trim();
            document.getElementById('confirmRecipeMealLabel').textContent = btn.textContent.trim();
        });
    });
    document.getElementById('confirmMealLabel').textContent       = 'el Desayuno';
    document.getElementById('confirmRecipeMealLabel').textContent = 'el Desayuno';
}

// ── Carga las categorías del catálogo ────────────────────────────────────
async function loadCategories() {
    try {
        const data = await apiCall('/foods/categories');
        const row  = document.getElementById('categoriesRow');
        row.innerHTML = `<button class="pill-tab active" onclick="filterByCategory('')">Todos</button>` +
            data.categories.map(cat => `
                <button class="pill-tab" onclick="filterByCategory('${escapeHtml(cat)}')">${escapeHtml(cat)}</button>
            `).join('');
        lucide.createIcons();
    } catch(e) {}
}

// ── Carga alimentos iniciales (sin filtro) ───────────────────────────────
async function loadInitialFoods() {
    await searchFoods('');
}

// ── Filtra por categoría ─────────────────────────────────────────────────
function filterByCategory(category) {
    activeCategory = category;
    document.getElementById('foodSearch').value = '';
    document.querySelectorAll('#categoriesRow .pill-tab').forEach(p => {
        p.classList.toggle('active', p.textContent.trim() === (category || 'Todos'));
    });
    searchFoods('', activeCategory, activeMacroFilter);
}

// ── Filtros de macros ────────────────────────────────────────────────────
function setupMacroFilters() {
    document.querySelectorAll('.macro-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.macro-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeMacroFilter = btn.dataset.filter;
            searchFoods(document.getElementById('foodSearch').value, activeCategory, activeMacroFilter);
        });
    });
    document.querySelector('.macro-filter[data-filter=""]').classList.add('active');
}

// ── Buscador de alimentos con debounce ───────────────────────────────────
function setupSearch() {
    const input = document.getElementById('foodSearch');
    input.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchFoods(input.value, activeCategory, activeMacroFilter), 300);
    });
}

// ── Busca alimentos y renderiza ──────────────────────────────────────────
async function searchFoods(query, category = '', macroFilter = '') {
    const container = document.getElementById('searchResults');
    container.innerHTML = '<div class="skeleton-card"></div>';
    document.getElementById('foodCount').style.display = 'none';
    try {
        let endpoint = `/foods?limit=100`;
        if (query)    endpoint += `&search=${encodeURIComponent(query)}`;
        if (category) endpoint += `&category=${encodeURIComponent(category)}`;

        const data = await apiCall(endpoint);
        let foods = data.foods;

        if (macroFilter === 'high_protein') {
            foods = foods.filter(f => f.protein_per_100g >= 15);
        } else if (macroFilter === 'low_carbs') {
            foods = foods.filter(f => f.carbs_per_100g <= 10);
        } else if (macroFilter === 'low_fat') {
            foods = foods.filter(f => f.fat_per_100g <= 5);
        } else if (macroFilter === 'low_cal') {
            foods = foods.filter(f => f.calories_per_100g <= 80);
        }

        renderFoods(foods);
    } catch(e) {
        container.innerHTML = '<p style="color:var(--color-muted);padding:1rem;text-align:center;">Error al cargar alimentos.</p>';
    }
}

// ── Renderiza la lista de alimentos ─────────────────────────────────────
function renderFoods(foods) {
    const container = document.getElementById('searchResults');
    const countEl   = document.getElementById('foodCount');

    if (!foods.length) {
        container.innerHTML = '<p style="color:var(--color-muted);padding:1rem;text-align:center;">No se encontraron alimentos.</p>';
        countEl.style.display = 'none';
        return;
    }

    countEl.textContent = `${foods.length} alimento${foods.length !== 1 ? 's' : ''}`;
    countEl.style.display = 'block';

    container.innerHTML = `<div class="card">` + foods.map(food => `
        <div class="food-item-card" onclick='openQuantitySheet(${JSON.stringify(food)})'>
            <div style="flex:1;min-width:0;">
                <div class="food-item-name">
                    ${escapeHtml(food.name)}
                    ${food.user_id ? '<span class="badge-mine">Mío</span>' : ''}
                </div>
                <div class="food-item-meta">${food.protein_per_100g}g P · ${food.carbs_per_100g}g C · ${food.fat_per_100g}g G · por 100${food.unit || 'g'}</div>
            </div>
            <span class="food-item-kcal">${food.calories_per_100g} kcal</span>
        </div>
    `).join('') + `</div>`;
}

// ── Bottom sheet para seleccionar cantidad ───────────────────────────────
function openQuantitySheet(food) {
    selectedFood = food;
    const unit = food.unit || 'g';

    document.getElementById('sheetFoodName').textContent   = food.name;
    document.getElementById('sheetFoodMacros').textContent =
        `${food.calories_per_100g} kcal · ${food.protein_per_100g}g prot · ${food.carbs_per_100g}g carbs · ${food.fat_per_100g}g grasa — por 100${unit}`;
    document.getElementById('quantityLabel').textContent   = unit === 'ml' ? 'Cantidad (ml)' : 'Cantidad (g)';

    const presets = unit === 'ml' ? [100, 150, 200, 250, 330, 500] : [50, 100, 150, 200, 250];
    document.getElementById('quantityPresets').innerHTML = presets
        .map(v => `<button class="btn btn-ghost btn-sm" onclick="setQuantity(${v})">${v}${unit}</button>`)
        .join('');

    document.getElementById('quantityInput').value = '100';
    updatePreview();
    document.getElementById('quantityBackdrop').style.display = 'block';
    document.getElementById('quantitySheet').style.display    = 'block';
    setTimeout(() => document.getElementById('quantityInput').focus(), 100);
}

function closeQuantityModal() {
    document.getElementById('quantityBackdrop').style.display = 'none';
    document.getElementById('quantitySheet').style.display    = 'none';
    selectedFood = null;
}

// ── Calcula macros en tiempo real según cantidad ─────────────────────────
function updatePreview() {
    if (!selectedFood) return;
    const grams  = parseFloat(document.getElementById('quantityInput').value) || 0;
    const factor = grams / 100;
    document.getElementById('previewCalories').textContent = Math.round(selectedFood.calories_per_100g * factor);
    document.getElementById('previewProtein').textContent  = `${Math.round(selectedFood.protein_per_100g * factor)}g`;
    document.getElementById('previewCarbs').textContent    = `${Math.round(selectedFood.carbs_per_100g * factor)}g`;
    document.getElementById('previewFat').textContent      = `${Math.round(selectedFood.fat_per_100g * factor)}g`;
    document.getElementById('previewFiber').textContent    = `${Math.round((selectedFood.fiber_per_100g || 0) * factor)}g`;
    document.getElementById('previewSugar').textContent    = `${Math.round((selectedFood.sugar_per_100g || 0) * factor)}g`;
    lucide.createIcons();
}

function setQuantity(g) {
    document.getElementById('quantityInput').value = g;
    updatePreview();
}

function setupQuantitySheet() {
    document.getElementById('quantityInput').addEventListener('input', updatePreview);
    document.getElementById('confirmAddBtn').addEventListener('click', addFoodFromCatalog);
}

// ── Añade el alimento del catálogo a la comida ────────────────────────────
async function addFoodFromCatalog() {
    if (!selectedFood) return;
    const btn   = document.getElementById('confirmAddBtn');
    const grams = parseFloat(document.getElementById('quantityInput').value);
    if (!grams || grams <= 0) {
        showToast('Introduce una cantidad válida', 'error'); return;
    }
    btn.disabled = true;
    btn.textContent = 'Añadiendo...';
    try {
        await apiCall('/meals', 'POST', {
            meal_type:      selectedMealType,
            food_item_id:   selectedFood.id,
            quantity_grams: grams,
        });
        showToast('¡Añadido!', 'success');
        closeQuantityModal();
        setTimeout(() => window.location.href = '/nutrition', 800);
    } catch (e) {
        // apiCall ya muestra el toast de error
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Añadir a <span id="confirmMealLabel">la comida</span>';
    }
}

// ── Formulario de entrada manual ─────────────────────────────────────────
function setupManualForm() {
    document.getElementById('toggleManual').addEventListener('click', () => {
        const form = document.getElementById('manualForm');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });

    document.getElementById('addManualBtn').addEventListener('click', async () => {
        const name = document.getElementById('manualName').value.trim();
        if (!name) { showToast('Escribe el nombre del alimento', 'error'); return; }
        const btn = document.getElementById('addManualBtn');
        btn.disabled = true;
        try {
            await apiCall('/meals', 'POST', {
                meal_type:        selectedMealType,
                custom_food_name: name,
                calories: parseFloat(document.getElementById('manualCalories').value) || 0,
                protein:  parseFloat(document.getElementById('manualProtein').value)  || 0,
                carbs:    parseFloat(document.getElementById('manualCarbs').value)    || 0,
                fat:      parseFloat(document.getElementById('manualFat').value)      || 0,
            });
            showToast('¡Añadido!', 'success');
            setTimeout(() => window.location.href = '/nutrition', 800);
        } catch(e) {
            // apiCall ya muestra el toast de error
        } finally {
            btn.disabled = false;
        }
    });
}

// ── Crear alimento personalizado ─────────────────────────────────────────
function setupCreateFoodSheet() {
    document.getElementById('createFoodBtn').addEventListener('click', openCreateFoodModal);
    document.getElementById('saveFoodBtn').addEventListener('click', saveCustomFood);
}

function openCreateFoodModal() {
    ['cfName','cfCalories','cfProtein','cfCarbs','cfFat','cfFiber'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('createFoodBackdrop').style.display = 'block';
    document.getElementById('createFoodSheet').style.display    = 'block';
    setTimeout(() => document.getElementById('cfName').focus(), 100);
}

function closeCreateFoodModal() {
    document.getElementById('createFoodBackdrop').style.display = 'none';
    document.getElementById('createFoodSheet').style.display    = 'none';
}

async function saveCustomFood() {
    const name     = document.getElementById('cfName').value.trim();
    const calories = parseFloat(document.getElementById('cfCalories').value);

    if (!name)                        { showToast('El nombre es obligatorio', 'error'); return; }
    if (isNaN(calories) || calories < 0) { showToast('Introduce las calorías por 100g', 'error'); return; }

    const btn = document.getElementById('saveFoodBtn');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        await apiCall('/foods', 'POST', {
            name,
            category:           document.getElementById('cfCategory').value,
            calories_per_100g:  calories,
            protein_per_100g:   parseFloat(document.getElementById('cfProtein').value)  || 0,
            carbs_per_100g:     parseFloat(document.getElementById('cfCarbs').value)    || 0,
            fat_per_100g:       parseFloat(document.getElementById('cfFat').value)      || 0,
            fiber_per_100g:     parseFloat(document.getElementById('cfFiber').value)    || 0,
        });
        showToast('Alimento guardado en tu catálogo', 'success');
        closeCreateFoodModal();
        await loadInitialFoods();
    } catch(e) {
        // apiCall ya muestra el toast de error
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar alimento';
    }
}

// ══════════════════════════════════════════════════════════════════════════
// MÓDULO DE RECETAS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Carga todas las recetas desde la API y las guarda en caché.
 * Se llama una vez al inicializar.
 */
async function loadRecipes() {
    try {
        const data = await apiCall('/recipes');
        allRecipes = data.recipes || [];
        renderRecipes(allRecipes);
    } catch (e) {
        document.getElementById('recipeResults').innerHTML =
            '<p style="color:var(--color-muted);padding:1rem;text-align:center;">Error al cargar recetas.</p>';
    }
}

/**
 * Configura el buscador de recetas con debounce.
 */
function setupRecipeSearch() {
    document.getElementById('recipeSearch').addEventListener('input', (e) => {
        clearTimeout(recipeSearchTimeout);
        recipeSearchTimeout = setTimeout(() => applyRecipeFilters(), 300);
    });
}

/**
 * Filtra recetas por categoría y actualiza el visual de las pills.
 */
function filterRecipesByCategory(cat) {
    activeRecipeCat = cat;
    document.querySelectorAll('#recipeCatsRow .pill-tab').forEach(p => {
        const label = p.textContent.trim().replace(/^[^\w]+ ?/, '').toLowerCase();
        p.classList.toggle('active',
            cat === '' ? p.textContent.includes('Todas') : label.startsWith(cat)
        );
    });
    applyRecipeFilters();
}

/**
 * Aplica filtros de búsqueda + categoría sobre el caché local de recetas.
 */
function applyRecipeFilters() {
    const query = document.getElementById('recipeSearch').value.toLowerCase().trim();
    let filtered = allRecipes;

    if (activeRecipeCat) {
        filtered = filtered.filter(r => (r.category || '').toLowerCase() === activeRecipeCat);
    }
    if (query) {
        filtered = filtered.filter(r =>
            r.name.toLowerCase().includes(query) ||
            (r.description || '').toLowerCase().includes(query)
        );
    }
    renderRecipes(filtered);
}

/**
 * Render de la lista de recetas.
 * Muestra foto (si tiene), nombre, macros por porción y tiempo.
 */
function renderRecipes(recipes) {
    const container = document.getElementById('recipeResults');
    const countEl   = document.getElementById('recipeCount');

    if (!recipes.length) {
        container.innerHTML = `
            <div class="empty-state" style="padding:2rem;">
                <div class="empty-state-icon"><i data-lucide="book-open"></i></div>
                <p class="empty-state-title">Sin recetas</p>
                <p class="empty-state-desc">No hay recetas que coincidan con la búsqueda.</p>
            </div>`;
        countEl.style.display = 'none';
        lucide.createIcons();
        return;
    }

    countEl.textContent   = `${recipes.length} receta${recipes.length !== 1 ? 's' : ''}`;
    countEl.style.display = 'block';

    const catEmoji = { desayuno:'🌅', almuerzo:'☀️', cena:'🌙', snack:'🍎' };
    const diffColor = { fácil:'#4ade80', media:'var(--color-primary)', difícil:'#f87171' };

    container.innerHTML = `<div class="card">` + recipes.map(recipe => {
        const imgHtml = (recipe.image_path || recipe.image_url)
            ? `<img src="${escapeHtml(recipe.image_path ? '/storage/nutrition/' + recipe.image_path : recipe.image_url)}"
                    class="log-recipe-img" alt="${escapeHtml(recipe.name)}"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
               <div class="log-recipe-img-placeholder" style="display:none;">
                   ${catEmoji[recipe.category] || '🍽️'}
               </div>`
            : `<div class="log-recipe-img-placeholder">${catEmoji[recipe.category] || '🍽️'}</div>`;

        const totalTime = (recipe.prep_time_min || 0) + (recipe.cook_time_min || 0);

        return `
            <div class="log-recipe-card" onclick='openRecipeSheet(${JSON.stringify(recipe)})'>
                ${imgHtml}
                <div style="flex:1;min-width:0;">
                    <div class="log-recipe-name">
                        ${escapeHtml(recipe.name)}
                        ${recipe.user_id ? '<span class="badge-mine">Mía</span>' : ''}
                    </div>
                    <div class="log-recipe-meta">
                        ${recipe.protein_per_serving}g P · ${recipe.carbs_per_serving}g C · ${recipe.fat_per_serving}g G
                        ${totalTime > 0 ? ` · ⏱ ${totalTime}min` : ''}
                        <span style="color:${diffColor[recipe.difficulty] || 'var(--color-muted)'};">
                            · ${recipe.difficulty || ''}
                        </span>
                    </div>
                </div>
                <span class="log-recipe-kcal">${recipe.calories_per_serving} kcal</span>
            </div>`;
    }).join('') + `</div>`;

    lucide.createIcons();
}

/**
 * Abre el bottom sheet para confirmar añadir una receta como comida.
 */
function openRecipeSheet(recipe) {
    selectedRecipe  = recipe;
    selectedServings = 1;

    document.getElementById('recipeSheetName').textContent = recipe.name;
    document.getElementById('recipeSheetMeta').textContent =
        `${recipe.servings} porción(es) · ${(recipe.prep_time_min || 0) + (recipe.cook_time_min || 0)} min · ${recipe.difficulty}`;

    updateRecipePreview();

    document.getElementById('recipeSheetBackdrop').style.display = 'block';
    document.getElementById('recipeSheet').style.display         = 'block';
    lucide.createIcons();
}

function closeRecipeSheet() {
    document.getElementById('recipeSheetBackdrop').style.display = 'none';
    document.getElementById('recipeSheet').style.display         = 'none';
    selectedRecipe  = null;
    selectedServings = 1;
}

/**
 * Actualiza los valores del preview de receta según las porciones seleccionadas.
 */
function updateRecipePreview() {
    if (!selectedRecipe) return;
    const s = selectedServings;
    document.getElementById('servingsCount').textContent      = s;
    document.getElementById('rPreviewCalories').textContent   = Math.round(selectedRecipe.calories_per_serving * s);
    document.getElementById('rPreviewProtein').textContent    = `${Math.round(selectedRecipe.protein_per_serving * s)}g`;
    document.getElementById('rPreviewCarbs').textContent      = `${Math.round(selectedRecipe.carbs_per_serving * s)}g`;
    document.getElementById('rPreviewFat').textContent        = `${Math.round(selectedRecipe.fat_per_serving * s)}g`;
}

/**
 * Cambia el número de porciones (mínimo 1).
 */
function changeServings(delta) {
    selectedServings = Math.max(1, selectedServings + delta);
    updateRecipePreview();
}

/**
 * Añade la receta seleccionada como entrada de comida en el log.
 * Las recetas se registran como entrada manual con los macros calculados.
 */
async function addRecipeAsMeal() {
    if (!selectedRecipe) return;
    const btn = document.getElementById('confirmRecipeBtn');
    btn.disabled = true;
    btn.textContent = 'Añadiendo...';

    const s = selectedServings;
    try {
        await apiCall('/meals', 'POST', {
            meal_type:        selectedMealType,
            custom_food_name: `${selectedRecipe.name}${s > 1 ? ` (×${s})` : ''}`,
            quantity_grams:   100,  // Placeholder — las recetas no se miden en gramos
            calories: Math.round(selectedRecipe.calories_per_serving * s),
            protein:  Math.round(selectedRecipe.protein_per_serving  * s),
            carbs:    Math.round(selectedRecipe.carbs_per_serving     * s),
            fat:      Math.round(selectedRecipe.fat_per_serving       * s),
            fiber:    Math.round((selectedRecipe.fiber_per_serving || 0) * s),
        });
        showToast('¡Receta añadida!', 'success');
        closeRecipeSheet();
        setTimeout(() => window.location.href = '/nutrition', 800);
    } catch (e) {
        // apiCall ya muestra el toast de error
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Añadir a <span id="confirmRecipeMealLabel">la comida</span>';
    }
}

// ── Helper ───────────────────────────────────────────────────────────────
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
