@extends('layouts.app')

@section('title', 'Recetas')

@section('content')
<div class="page-container">

    {{-- ── CABECERA ────────────────────────────────────────────────── --}}
    <header class="page-header sticky-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <a href="/nutrition" class="btn btn-ghost btn-icon">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title">Recetas</h1>
                <p style="font-size:0.75rem;color:var(--color-muted);" id="recipeCount">Cargando...</p>
            </div>
        </div>
        <button class="btn btn-ghost btn-icon" id="createRecipeBtn" title="Crear receta">
            <i data-lucide="plus-circle"></i>
        </button>
    </header>

    {{-- ── CALORÍAS RESTANTES HOY ─────────────────────────────────── --}}
    <section style="padding:1rem 1rem 0;">
        <div class="card" style="padding:0.9rem 1rem;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05));">
            <div>
                <div style="font-size:0.75rem;color:var(--color-muted);">Calorías disponibles hoy</div>
                <div style="font-size:1.4rem;font-weight:700;color:var(--color-primary);" id="remainingToday">–</div>
            </div>
            <button class="btn btn-outline btn-sm" id="filterByRemainingBtn">
                Filtrar recetas
            </button>
        </div>
    </section>

    {{-- ── FILTROS ─────────────────────────────────────────────────── --}}
    <section style="padding:0.75rem 1rem 0;">
        <div style="overflow-x:auto;padding-bottom:0.25rem;">
            <div style="display:flex;gap:0.5rem;min-width:max-content;">
                <button class="pill-tab active" data-category="">Todas</button>
                <button class="pill-tab" data-category="desayuno">Desayuno</button>
                <button class="pill-tab" data-category="almuerzo">Almuerzo</button>
                <button class="pill-tab" data-category="cena">Cena</button>
                <button class="pill-tab" data-category="snack">Snack</button>
                <button class="pill-tab" data-category="__mine__">⭐ Mis recetas</button>
            </div>
        </div>

        <div style="position:relative;margin-top:0.75rem;">
            <i data-lucide="search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--color-muted);width:1rem;height:1rem;pointer-events:none;"></i>
            <input type="text" id="recipeSearch" class="input" placeholder="Buscar receta..." style="padding-left:2.5rem;">
        </div>
    </section>

    {{-- ── LISTA DE RECETAS ────────────────────────────────────────── --}}
    <section style="padding:0.75rem 1rem 6rem;">
        <div id="recipesSkeleton">
            <div class="skeleton-card"></div>
            <div class="skeleton-card" style="margin-top:0.75rem;"></div>
            <div class="skeleton-card" style="margin-top:0.75rem;"></div>
        </div>
        <div id="recipesGrid" style="display:none;"></div>
        <div id="recipesEmpty" class="empty-state" style="display:none;">
            <div class="empty-state-icon"><i data-lucide="chef-hat"></i></div>
            <p class="empty-state-title">Sin recetas</p>
            <p class="empty-state-desc">No hay recetas que coincidan con tu búsqueda.</p>
        </div>
    </section>

</div>

{{-- ── MODAL DETALLE RECETA ────────────────────────────────────────── --}}
<div class="modal-backdrop" id="recipeBackdrop" style="display:none;" onclick="closeRecipeModal()"></div>
<div class="bottom-sheet" id="recipeSheet" style="display:none;max-height:90dvh;">
    <div class="sheet-handle"></div>
    <div id="recipeDetail"></div>
</div>

{{-- ── MODAL CREAR RECETA ───────────────────────────────────────────── --}}
<div class="modal-backdrop" id="createRecipeBackdrop" style="display:none;"></div>
<div class="bottom-sheet" id="createRecipeSheet" style="display:none;max-height:95dvh;">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Crear receta</h3>
        <p style="font-size:0.78rem;color:var(--color-muted);margin-top:0.25rem;">Las calorías se calculan automáticamente al añadir ingredientes</p>
    </div>
    <div class="sheet-body">

        {{-- Datos básicos --}}
        <div class="form-group">
            <label class="form-label">Nombre de la receta *</label>
            <input type="text" id="crName" class="input" placeholder="Ej: Macarrones con carne picada">
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Descripción (opcional)</label>
            <input type="text" id="crDescription" class="input" placeholder="Breve descripción del plato">
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Foto (opcional)</label>
            <button type="button" class="photo-upload-btn" onclick="triggerCreateRecipePhotoUpload()">
                <i data-lucide="image-plus" style="width:1rem;height:1rem;"></i>
                Añadir foto de portada
            </button>
            <div id="createPhotoPreviewWrap" style="display:none;margin-top:0.6rem;position:relative;">
                <img id="createPhotoPreview" class="create-photo-preview" alt="Vista previa de receta">
                <button type="button" class="btn btn-danger btn-sm" onclick="clearCreateRecipePhoto()" style="position:absolute;right:0.5rem;bottom:0.5rem;">
                    Quitar foto
                </button>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-top:0.75rem;">
            <div class="form-group">
                <label class="form-label">Categoría</label>
                <select id="crCategory" class="input" style="appearance:auto;">
                    <option value="almuerzo">Almuerzo</option>
                    <option value="desayuno">Desayuno</option>
                    <option value="cena">Cena</option>
                    <option value="snack">Snack</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Porciones</label>
                <input type="number" id="crServings" class="input" value="1" min="1" max="20">
            </div>
            <div class="form-group">
                <label class="form-label">Prep. (min)</label>
                <input type="number" id="crPrepTime" class="input" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Cocción (min)</label>
                <input type="number" id="crCookTime" class="input" placeholder="0" min="0">
            </div>
        </div>

        {{-- Sección de ingredientes --}}
        <div style="margin-top:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <label class="form-label" style="margin:0;">Ingredientes</label>
                <button class="btn btn-ghost btn-sm" id="addIngredientBtn">
                    <i data-lucide="plus" style="width:0.8rem;height:0.8rem;"></i> Añadir
                </button>
            </div>

            {{-- Buscador de alimentos para ingredientes --}}
            <div style="position:relative;margin-bottom:0.5rem;" id="ingredientSearchBox" style="display:none;">
                <i data-lucide="search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--color-muted);width:0.9rem;height:0.9rem;pointer-events:none;"></i>
                <input type="text" id="ingredientSearch" class="input" placeholder="Buscar en catálogo..." style="padding-left:2.3rem;font-size:0.85rem;" autocomplete="off">
            </div>
            <div id="ingredientSuggestions" style="display:none;" class="card"></div>

            {{-- Lista de ingredientes añadidos --}}
            <div id="ingredientsList" style="margin-top:0.5rem;"></div>

            {{-- Totales calculados automáticamente --}}
            <div id="recipeTotals" class="card" style="margin-top:0.75rem;padding:0.75rem;display:none;">
                <div style="font-size:0.72rem;color:var(--color-muted);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:.05em;">Total del plato completo</div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);text-align:center;gap:0.5rem;">
                    <div><span id="totCal" style="display:block;font-size:1rem;font-weight:700;color:var(--color-primary);">0</span><span style="font-size:0.65rem;color:var(--color-muted);">kcal</span></div>
                    <div><span id="totProt" style="display:block;font-size:1rem;font-weight:700;">0g</span><span style="font-size:0.65rem;color:var(--color-muted);">prot</span></div>
                    <div><span id="totCarbs" style="display:block;font-size:1rem;font-weight:700;">0g</span><span style="font-size:0.65rem;color:var(--color-muted);">carbs</span></div>
                    <div><span id="totFat" style="display:block;font-size:1rem;font-weight:700;">0g</span><span style="font-size:0.65rem;color:var(--color-muted);">grasa</span></div>
                </div>
                <div id="perServingNote" style="font-size:0.72rem;color:var(--color-muted);margin-top:0.5rem;text-align:center;"></div>
            </div>
        </div>

        {{-- Instrucciones --}}
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Instrucciones (opcional)</label>
            <textarea id="crInstructions" class="input" rows="4" placeholder="1. Cocer la pasta al dente...&#10;2. Sofreír la carne picada...&#10;3. Mezclar y servir..." style="resize:vertical;line-height:1.5;"></textarea>
        </div>

        <div style="display:flex;gap:0.75rem;margin-top:1rem;">
            <button class="btn btn-ghost btn-block" onclick="closeCreateRecipeModal()">Cancelar</button>
            <button class="btn btn-primary btn-block" id="saveRecipeBtn">Guardar receta</button>
        </div>
    </div>
</div>

{{-- ── MODAL PEDIR GRAMOS ──────────────────────────────────────────────── --}}
<div class="modal-backdrop" id="gramsBackdrop" style="display:none;z-index:200;"></div>
<div id="gramsDialog" class="custom-dialog" style="display:none;">
    <p class="custom-dialog-title">Añadir ingrediente</p>
    <p id="gramsDialogText" class="custom-dialog-body"></p>
    <input type="number" id="gramsInput" class="input" value="100" min="1" max="9999" style="margin-bottom:1rem;" autocomplete="off">
    <div style="display:flex;gap:0.75rem;">
        <button class="btn btn-ghost btn-block" id="gramsCancelBtn">Cancelar</button>
        <button class="btn btn-primary btn-block" id="gramsConfirmBtn">Añadir</button>
    </div>
</div>

{{-- ── MODAL CONFIRMAR ACCIÓN ──────────────────────────────────────────── --}}
<div class="modal-backdrop" id="confirmBackdrop" style="display:none;z-index:200;"></div>
<div id="confirmDialog" class="custom-dialog" style="display:none;">
    <p class="custom-dialog-title" id="confirmDialogTitle">¿Confirmar acción?</p>
    <p class="custom-dialog-body" id="confirmDialogBody"></p>
    <div style="display:flex;gap:0.75rem;margin-top:0.25rem;">
        <button class="btn btn-ghost btn-block" id="confirmCancelBtn">Cancelar</button>
        <button class="btn btn-danger btn-block" id="confirmOkBtn">Eliminar</button>
    </div>
</div>

{{-- ── MODAL ORIGEN DE FOTO (CÁMARA / GALERÍA) ─────────────────────────── --}}
<div class="modal-backdrop" id="photoSourceBackdrop" style="display:none;z-index:220;" onclick="closePhotoSourcePicker()"></div>
<div class="bottom-sheet" id="photoSourceSheet" style="display:none;z-index:221;max-height:60dvh;">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Añadir foto</h3>
        <p style="font-size:0.78rem;color:var(--color-muted);margin-top:0.2rem;">Elige desde dónde quieres subirla</p>
    </div>
    <div class="sheet-body" style="padding-top:0.25rem;">
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
            <button class="btn btn-primary btn-block" onclick="pickPhotoSource('camera')">
                <i data-lucide="camera" style="width:1rem;height:1rem;"></i>
                Hacer foto ahora
            </button>
            <button class="btn btn-outline btn-block" onclick="pickPhotoSource('gallery')">
                <i data-lucide="image" style="width:1rem;height:1rem;"></i>
                Elegir de galería
            </button>
            <button class="btn btn-ghost btn-block" onclick="closePhotoSourcePicker()">Cancelar</button>
        </div>
    </div>
</div>

{{-- Input file oculto para foto de receta (se usa desde el detalle) --}}
<input type="file" id="recipePhotoInputGallery" accept="image/*"
       style="display:none;" onchange="onRecipePhotoSelected(this)">
<input type="file" id="recipePhotoInputCamera" accept="image/*" capture="environment"
       style="display:none;" onchange="onRecipePhotoSelected(this)">
<input type="file" id="createRecipePhotoInputGallery" accept="image/*"
       style="display:none;" onchange="onCreateRecipePhotoSelected(this)">
<input type="file" id="createRecipePhotoInputCamera" accept="image/*" capture="environment"
    style="display:none;" onchange="onCreateRecipePhotoSelected(this)">

@endsection

@push('styles')
<style>
/* ── Tarjeta de receta ── */
.recipe-card {
    display:flex;
    flex-direction:column;
    padding:0;
    overflow:hidden;
    cursor:pointer;
    margin-bottom:0.75rem;
}
/* Imagen de portada de la tarjeta */
.recipe-card-img {
    width:100%;
    height:9rem;
    object-fit:cover;
    background:var(--bg-muted);
    display:block;
}
.recipe-card-body  { padding:1rem; flex:1; }
.recipe-title      { font-size:0.95rem; font-weight:600; color:var(--color-foreground); margin-bottom:0.3rem; }
.recipe-desc       { font-size:0.78rem; color:var(--color-muted); margin-bottom:0.75rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.recipe-macros     { display:flex; gap:0.5rem; flex-wrap:wrap; }
.recipe-macro-chip { font-size:0.7rem; padding:0.2rem 0.5rem; border-radius:9999px; background:var(--bg-muted); color:var(--color-muted); }
.recipe-macro-chip.kcal { background:rgba(245,158,11,.15); color:var(--color-primary); font-weight:600; }
.recipe-meta       { display:flex; gap:0.75rem; margin-top:0.75rem; }
.recipe-meta-item  { display:flex; align-items:center; gap:0.3rem; font-size:0.72rem; color:var(--color-muted); }
.recipe-meta-item i{ width:0.8rem; height:0.8rem; }

/* ── Difficulty badges ── */
.diff-fácil  { background:rgba(74,222,128,.15); color:#4ade80; }
.diff-media  { background:rgba(251,191,36,.15); color:#fbbf24; }
.diff-difícil{ background:rgba(248,113,113,.15); color:#f87171; }

/* ── Receta propia ── */
.badge-mine { font-size:0.6rem; padding:0.1rem 0.35rem; border-radius:9999px; background:rgba(245,158,11,.2); color:var(--color-primary); margin-left:0.4rem; font-weight:600; }

/* ── Detalle receta ── */
.recipe-detail-img   { width:100%; height:12rem; object-fit:cover; border-radius:0; }
.recipe-detail-title   { font-size:1.1rem; font-weight:700; color:var(--color-foreground); margin-bottom:0.5rem; }
.recipe-detail-macros  { display:grid; grid-template-columns:repeat(4,1fr); gap:0.5rem; margin:1rem 0; }
.detail-macro          { text-align:center; background:var(--bg-muted); border-radius:0.5rem; padding:0.5rem; }
.detail-macro-val      { display:block; font-size:0.95rem; font-weight:700; color:var(--color-foreground); }
.detail-macro-label    { display:block; font-size:0.65rem; color:var(--color-muted); }
.recipe-section-title  { font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin:1rem 0 0.5rem; }
.ingredient-row        { display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.85rem; border-bottom:1px solid rgba(255,255,255,.04); }
.ingredient-name       { color:var(--color-foreground); }
.ingredient-qty        { color:var(--color-muted); }
.instruction-step      { display:flex; gap:0.75rem; margin-bottom:0.75rem; }
.step-num              { flex-shrink:0; width:1.5rem; height:1.5rem; border-radius:50%; background:rgba(245,158,11,.2); color:var(--color-primary); font-size:0.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; margin-top:0.1rem; }
.step-text             { font-size:0.85rem; color:var(--color-foreground); line-height:1.5; }

/* ── Ingrediente en la lista del creador ── */
.ingredient-chip {
    display:flex; align-items:center; justify-content:space-between;
    background:var(--bg-muted); border-radius:0.5rem; padding:0.625rem 0.875rem;
    margin-bottom:0.5rem;
}
.ingredient-chip-name { font-size:0.85rem; color:var(--color-foreground); font-weight:500; }
.ingredient-chip-info { font-size:0.72rem; color:var(--color-muted); margin-top:0.1rem; }

/* ── Sugerencias de búsqueda ── */
.ingredient-suggestion {
    padding:0.75rem 0.875rem; cursor:pointer; font-size:0.85rem;
    border-bottom:1px solid rgba(255,255,255,.04);
    display:flex; justify-content:space-between; align-items:center;
}
.ingredient-suggestion:last-child { border-bottom:none; }
.ingredient-suggestion:hover { background:var(--bg-muted); }
.suggestion-kcal { font-size:0.75rem; color:var(--color-primary); font-weight:600; }

/* ── Botón añadir foto ── */
.photo-upload-btn {
    width:100%; padding:0.65rem;
    border:2px dashed var(--bg-muted);
    border-radius:0.75rem;
    background:transparent;
    color:var(--color-muted);
    cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:0.5rem;
    font-size:0.82rem;
    transition:var(--transition-fast);
}
.photo-upload-btn:hover { border-color:var(--color-primary); color:var(--color-primary); }

/* ── Espaciado de bottom-sheets en recetas ── */
#createRecipeSheet .sheet-body {
    padding: 0.5rem 1.25rem 2rem;
}

#photoSourceSheet .sheet-body {
    padding: 0.5rem 1.25rem 1rem;
}

/* ── Vista previa de foto al crear receta ── */
.create-photo-preview {
    width:100%;
    height:9rem;
    object-fit:cover;
    border-radius:0.75rem;
    border:1px solid var(--border-light);
    background:var(--bg-muted);
    display:block;
}
</style>
@endpush

@push('scripts')
<script>
let allRecipes        = [];
let filteredRecipes   = [];
let activeCategory    = '';
let filterByRemaining = false;
let remainingCalories = Infinity;
let searchTimeout     = null;

// ── Estado del creador de recetas ─────────────────────────────────────────
let recipeIngredients = [];   // [{name, quantity_str, grams, food}]
let ingredientSearchTimeout = null;
let createRecipePhotoFile = null;
let createRecipePhotoPreviewUrl = null;
let photoSourceTarget = null;

// ── ID de la receta abierta en el detalle (para subir foto) ───────────────
let currentDetailRecipeId   = null;
let currentDetailRecipeIsMe = false;

document.addEventListener('DOMContentLoaded', async () => {
    await loadRemainingCalories();
    await loadRecipes();
    setupFilters();
    setupCreateRecipe();
    lucide.createIcons();
});

// ── Calorías restantes hoy ────────────────────────────────────────────────
async function loadRemainingCalories() {
    try {
        const today = new Date().toISOString().split('T')[0];
        const [goalData, mealsData] = await Promise.all([
            apiCall('/nutrition/goal'),
            apiCall('/meals?date=' + today),
        ]);
        const goal     = goalData.goal.daily_calories ?? 2000;
        const consumed = mealsData.totals?.calories ?? 0;
        remainingCalories = Math.max(0, goal - consumed);
        document.getElementById('remainingToday').textContent =
            Math.round(remainingCalories).toLocaleString('es') + ' kcal';
    } catch(e) {
        document.getElementById('remainingToday').textContent = '– kcal';
    }
}

// ── Carga todas las recetas ───────────────────────────────────────────────
async function loadRecipes() {
    try {
        const data = await apiCall('/recipes');
        allRecipes = data.recipes;
        applyFilters();
    } catch(e) {
        document.getElementById('recipesSkeleton').style.display = 'none';
        document.getElementById('recipesEmpty').style.display    = 'block';
    }
}

// ── Configura los filtros interactivos ────────────────────────────────────
function setupFilters() {
    document.querySelectorAll('.pill-tab[data-category]').forEach(pill => {
        pill.addEventListener('click', () => {
            document.querySelectorAll('.pill-tab[data-category]').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCategory = pill.dataset.category;
            applyFilters();
        });
    });

    document.getElementById('recipeSearch').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 250);
    });

    document.getElementById('filterByRemainingBtn').addEventListener('click', () => {
        filterByRemaining = !filterByRemaining;
        const btn = document.getElementById('filterByRemainingBtn');
        btn.classList.toggle('btn-primary', filterByRemaining);
        btn.classList.toggle('btn-outline', !filterByRemaining);
        btn.textContent = filterByRemaining ? 'Mostrar todas' : 'Filtrar recetas';
        applyFilters();
    });
}

// ── Aplica todos los filtros activos ──────────────────────────────────────
function applyFilters() {
    const search = document.getElementById('recipeSearch').value.toLowerCase().trim();

    filteredRecipes = allRecipes.filter(r => {
        // Filtro "Mis recetas": solo las que tienen user_id (no son del sistema)
        if (activeCategory === '__mine__' && !r.user_id) return false;
        // Filtro normal de categoría
        if (activeCategory && activeCategory !== '__mine__' && r.category !== activeCategory) return false;
        if (filterByRemaining && r.calories_per_serving > remainingCalories) return false;
        if (search && !r.name.toLowerCase().includes(search)) return false;
        return true;
    });

    renderRecipes();
}

// ── Renderiza las tarjetas de recetas ─────────────────────────────────────
function renderRecipes() {
    document.getElementById('recipesSkeleton').style.display = 'none';
    const grid  = document.getElementById('recipesGrid');
    const empty = document.getElementById('recipesEmpty');

    document.getElementById('recipeCount').textContent =
        `${filteredRecipes.length} receta${filteredRecipes.length !== 1 ? 's' : ''}`;

    if (!filteredRecipes.length) {
        grid.style.display  = 'none';
        empty.style.display = 'block';
        lucide.createIcons();
        return;
    }

    empty.style.display = 'none';
    grid.style.display  = 'block';

    const categoryEmoji = { desayuno:'☀️', almuerzo:'🍽️', cena:'🌙', snack:'🍎' };
    const totalTime = r => r.prep_time_min + r.cook_time_min;

    // Calculamos la URL de imagen: image_path tiene prioridad sobre image_url (legacy)
    const imgSrc = r => {
        if (r.image_path) return '/storage/' + r.image_path;
        if (r.image_url)  return r.image_url;
        return null;
    };

    grid.innerHTML = filteredRecipes.map(r => {
        const img = imgSrc(r);
        const imgHtml = img
            ? `<img class="recipe-card-img" src="${escapeHtml(img)}" alt="${escapeHtml(r.name)}" loading="lazy">`
            : '';

        return `
        <div class="recipe-card card card-interactive" onclick='openRecipeDetail(${JSON.stringify(r)})'>
            ${imgHtml}
            <div class="recipe-card-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;margin-bottom:0.4rem;">
                    <h3 class="recipe-title">
                        ${escapeHtml(r.name)}
                        ${r.user_id ? '<span class="badge-mine">Mía</span>' : ''}
                    </h3>
                    <span class="badge diff-${r.difficulty}" style="flex-shrink:0;">${r.difficulty}</span>
                </div>
                ${r.description ? `<p class="recipe-desc">${escapeHtml(r.description)}</p>` : ''}
                <div class="recipe-macros">
                    <span class="recipe-macro-chip kcal">${r.calories_per_serving} kcal</span>
                    <span class="recipe-macro-chip">${Math.round(r.protein_per_serving)}g prot</span>
                    <span class="recipe-macro-chip">${Math.round(r.carbs_per_serving)}g carbs</span>
                    <span class="recipe-macro-chip">${Math.round(r.fat_per_serving)}g grasa</span>
                </div>
                <div class="recipe-meta">
                    <span class="recipe-meta-item">
                        <i data-lucide="clock"></i> ${totalTime(r)} min
                    </span>
                    <span class="recipe-meta-item">
                        <i data-lucide="users"></i> ${r.servings} porción${r.servings !== 1 ? 'es' : ''}
                    </span>
                    <span class="recipe-meta-item">
                        ${categoryEmoji[r.category] ?? ''} ${capitalizeFirst(r.category)}
                    </span>
                </div>
            </div>
        </div>`;
    }).join('');

    lucide.createIcons();
}

// ── Abre el detalle de una receta en el bottom sheet ──────────────────────
async function openRecipeDetail(recipe) {
    // Guardamos datos para la subida de foto
    currentDetailRecipeId   = recipe.id;
    currentDetailRecipeIsMe = !!recipe.user_id;

    document.getElementById('recipeBackdrop').style.display = 'block';
    document.getElementById('recipeSheet').style.display    = 'block';
    document.getElementById('recipeDetail').innerHTML = `
        <div style="padding:1rem;">
            <div class="skeleton-title" style="height:1.5rem;margin-bottom:1rem;"></div>
            <div class="skeleton-card"></div>
        </div>`;

    try {
        const data = await apiCall('/recipes/' + recipe.id);
        renderRecipeDetail(data.recipe);
    } catch(e) {
        renderRecipeDetail(recipe);
    }
}

// ── Renderiza el detalle completo en el bottom sheet ──────────────────────
function renderRecipeDetail(r) {
    // Actualizamos el estado con los datos del detalle completo
    currentDetailRecipeId   = r.id;
    currentDetailRecipeIsMe = !!r.user_id;

    const ingredients = typeof r.ingredients === 'string'
        ? JSON.parse(r.ingredients)
        : (r.ingredients || []);

    const steps = (r.instructions || '').split('\n').filter(s => s.trim());

    // URL de imagen: image_path tiene prioridad (subida por usuario), luego image_url (legacy)
    const imgSrc = r.image_path
        ? '/storage/' + r.image_path
        : (r.image_url || null);

    // Cabecera con imagen si existe
    const imgHtml = imgSrc
        ? `<img class="recipe-detail-img" src="${escapeHtml(imgSrc)}" alt="${escapeHtml(r.name)}">`
        : '';

    // Botón de foto: solo para recetas propias del usuario
    const photoBtn = r.user_id
        ? `<button class="photo-upload-btn" onclick="triggerRecipePhotoUpload()" style="margin-top:0.75rem;">
               <i data-lucide="camera" style="width:1rem;height:1rem;"></i>
               ${imgSrc ? 'Cambiar foto' : 'Añadir foto'}
           </button>`
        : '';

    const deleteBtn = r.user_id
        ? `<button class="btn btn-danger btn-sm" onclick="deleteRecipe(${r.id})" style="margin-top:0.5rem;width:100%;">
               <i data-lucide="trash-2" style="width:0.85rem;height:0.85rem;"></i> Eliminar receta
           </button>`
        : '';

    document.getElementById('recipeDetail').innerHTML = `
        ${imgHtml}
        <div style="padding:1rem 1rem 2rem;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;">
                <h2 class="recipe-detail-title">${escapeHtml(r.name)}</h2>
                ${r.user_id ? '<span class="badge-mine">Mía</span>' : ''}
            </div>
            ${r.description ? `<p style="font-size:0.85rem;color:var(--color-muted);">${escapeHtml(r.description)}</p>` : ''}

            <div class="recipe-detail-macros">
                <div class="detail-macro">
                    <span class="detail-macro-val" style="color:var(--color-primary);">${r.calories_per_serving}</span>
                    <span class="detail-macro-label">kcal</span>
                </div>
                <div class="detail-macro">
                    <span class="detail-macro-val">${Math.round(r.protein_per_serving)}g</span>
                    <span class="detail-macro-label">Proteína</span>
                </div>
                <div class="detail-macro">
                    <span class="detail-macro-val">${Math.round(r.carbs_per_serving)}g</span>
                    <span class="detail-macro-label">Carbos</span>
                </div>
                <div class="detail-macro">
                    <span class="detail-macro-val">${Math.round(r.fat_per_serving)}g</span>
                    <span class="detail-macro-label">Grasa</span>
                </div>
            </div>

            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                <span style="font-size:0.8rem;color:var(--color-muted);">⏱ ${r.prep_time_min + r.cook_time_min} min total</span>
                <span style="font-size:0.8rem;color:var(--color-muted);">🍽 ${r.servings} porción${r.servings !== 1 ? 'es' : ''}</span>
                <span class="badge diff-${r.difficulty}">${r.difficulty}</span>
            </div>

            ${ingredients.length ? `
                <p class="recipe-section-title">Ingredientes</p>
                <div style="background:var(--bg-muted);border-radius:0.75rem;padding:0.5rem 0.75rem;">
                    ${ingredients.map(ing => `
                        <div class="ingredient-row">
                            <span class="ingredient-name">${escapeHtml(ing.name)}</span>
                            <span class="ingredient-qty">${escapeHtml(ing.quantity)}</span>
                        </div>
                    `).join('')}
                </div>
            ` : ''}

            ${steps.length ? `
                <p class="recipe-section-title">Preparación</p>
                ${steps.map((step, i) => `
                    <div class="instruction-step">
                        <div class="step-num">${i + 1}</div>
                        <p class="step-text">${escapeHtml(step.replace(/^\d+\.\s*/, ''))}</p>
                    </div>
                `).join('')}
            ` : ''}

            ${photoBtn}
            ${deleteBtn}
        </div>
    `;
    lucide.createIcons();
}

// ── Foto de receta ────────────────────────────────────────────────────────

/**
 * Abre el selector de archivo para la foto de la receta.
 * El input file está fuera del bottom-sheet (global en la página) para
 * evitar problemas con el z-index en iOS.
 */
function triggerRecipePhotoUpload() {
    openPhotoSourcePicker('detail');
}

/**
 * Cuando el usuario selecciona una foto, la subimos inmediatamente.
 * No esperamos a guardar porque las recetas ya existen en BD cuando
 * se abre el detalle.
 */
async function onRecipePhotoSelected(input) {
    const file = input.files[0];
    if (!file || !currentDetailRecipeId) return;

    // Indicamos al usuario que estamos subiendo
    showToast('Subiendo foto...', 'info');

    try {
        const result = await uploadRecipeImage(currentDetailRecipeId, file);

        // Actualizamos la imagen en el detalle sin recargar todo
        const existingImg = document.querySelector('.recipe-detail-img');
        if (existingImg) {
            existingImg.src = result.image_url;
        } else {
            // No había imagen antes: insertamos al inicio del detail
            const detailEl = document.getElementById('recipeDetail');
            const newImg   = document.createElement('img');
            newImg.className = 'recipe-detail-img';
            newImg.src       = result.image_url;
            newImg.alt       = '';
            detailEl.insertBefore(newImg, detailEl.firstChild);
        }

        // Actualizamos la receta en el array local para que la tarjeta también se actualice
        const idx = allRecipes.findIndex(r => r.id === currentDetailRecipeId);
        if (idx !== -1) {
            allRecipes[idx].image_path = result.image_path;
        }

        // Actualizamos el texto del botón de foto
        const photoBtn = document.querySelector('.photo-upload-btn');
        if (photoBtn) {
            photoBtn.innerHTML = `
                <i data-lucide="camera" style="width:1rem;height:1rem;"></i>
                Cambiar foto
            `;
            lucide.createIcons();
        }

        showToast('Foto actualizada ✓', 'success');
    } catch(e) {
        showToast('No se pudo subir la foto. Intenta de nuevo.', 'error');
        console.error(e);
    }
}

function triggerCreateRecipePhotoUpload() {
    openPhotoSourcePicker('create');
}

function openPhotoSourcePicker(target) {
    photoSourceTarget = target;
    document.getElementById('photoSourceBackdrop').style.display = 'block';
    document.getElementById('photoSourceSheet').style.display = 'block';
    lucide.createIcons();
}

function closePhotoSourcePicker() {
    document.getElementById('photoSourceBackdrop').style.display = 'none';
    document.getElementById('photoSourceSheet').style.display = 'none';
    photoSourceTarget = null;
}

function pickPhotoSource(source) {
    const map = {
        detail: {
            gallery: 'recipePhotoInputGallery',
            camera:  'recipePhotoInputCamera',
        },
        create: {
            gallery: 'createRecipePhotoInputGallery',
            camera:  'createRecipePhotoInputCamera',
        },
    };

    const targetMap = map[photoSourceTarget];
    if (!targetMap) {
        closePhotoSourcePicker();
        return;
    }

    const inputId = source === 'camera' ? targetMap.camera : targetMap.gallery;
    const input = document.getElementById(inputId);
    if (!input) {
        closePhotoSourcePicker();
        return;
    }

    input.value = '';
    closePhotoSourcePicker();
    input.click();
}

function onCreateRecipePhotoSelected(input) {
    const file = input.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        showToast('La foto no puede superar 2MB', 'error');
        input.value = '';
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showToast('Formato no permitido. Usa JPG, PNG o WEBP', 'error');
        input.value = '';
        return;
    }

    createRecipePhotoFile = file;
    renderCreateRecipePhotoPreview();
}

function renderCreateRecipePhotoPreview() {
    const wrap = document.getElementById('createPhotoPreviewWrap');
    const img  = document.getElementById('createPhotoPreview');

    if (createRecipePhotoPreviewUrl) {
        URL.revokeObjectURL(createRecipePhotoPreviewUrl);
        createRecipePhotoPreviewUrl = null;
    }

    if (!createRecipePhotoFile) {
        wrap.style.display = 'none';
        img.removeAttribute('src');
        return;
    }

    createRecipePhotoPreviewUrl = URL.createObjectURL(createRecipePhotoFile);
    img.src = createRecipePhotoPreviewUrl;
    wrap.style.display = 'block';
}

function clearCreateRecipePhoto() {
    createRecipePhotoFile = null;
    document.getElementById('createRecipePhotoInputGallery').value = '';
    document.getElementById('createRecipePhotoInputCamera').value = '';
    renderCreateRecipePhotoPreview();
}

async function uploadRecipeImage(recipeId, file) {
    const formData = new FormData();
    formData.append('image', file);

    const token = auth.getToken();
    const headers = {
        'Accept': 'application/json',
    };
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const response = await fetch(`/api/recipes/${recipeId}/image`, {
        method: 'POST',
        credentials: 'include',
        headers,
        body: formData,
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        throw new Error(data.message || 'No se pudo subir la imagen');
    }

    return data;
}

// ── Elimina una receta propia ─────────────────────────────────────────────
async function deleteRecipe(id) {
    if (!await showConfirmModal('¿Eliminar esta receta? Esta acción no se puede deshacer.')) return;
    try {
        await apiCall('/recipes/' + id, 'DELETE');
        showToast('Receta eliminada', 'success');
        closeRecipeModal();
        await loadRecipes();
    } catch(e) {}
}

function closeRecipeModal() {
    document.getElementById('recipeBackdrop').style.display = 'none';
    document.getElementById('recipeSheet').style.display    = 'none';
    closePhotoSourcePicker();
    currentDetailRecipeId   = null;
    currentDetailRecipeIsMe = false;
}

// ═══════════════════════════════════════════════════════════════════════════
// CREADOR DE RECETAS
// ═══════════════════════════════════════════════════════════════════════════

function setupCreateRecipe() {
    document.getElementById('createRecipeBtn').addEventListener('click', openCreateRecipeModal);
    document.getElementById('addIngredientBtn').addEventListener('click', toggleIngredientSearch);
    document.getElementById('saveRecipeBtn').addEventListener('click', saveRecipe);

    // Buscador de ingredientes con debounce
    document.getElementById('ingredientSearch').addEventListener('input', () => {
        clearTimeout(ingredientSearchTimeout);
        const q = document.getElementById('ingredientSearch').value.trim();
        if (q.length < 2) {
            document.getElementById('ingredientSuggestions').style.display = 'none';
            return;
        }
        ingredientSearchTimeout = setTimeout(() => searchIngredients(q), 300);
    });

    // Actualizar totales al cambiar porciones
    document.getElementById('crServings').addEventListener('input', updateRecipeTotals);
}

function openCreateRecipeModal() {
    // Resetear estado
    recipeIngredients = [];
    ['crName','crDescription','crInstructions'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('crServings').value  = '1';
    document.getElementById('crPrepTime').value  = '';
    document.getElementById('crCookTime').value  = '';
    document.getElementById('ingredientSearch').value = '';
    document.getElementById('ingredientSearchBox').style.display = 'none';
    document.getElementById('ingredientSuggestions').style.display = 'none';
    createRecipePhotoFile = null;
    renderCreateRecipePhotoPreview();
    renderIngredientsList();

    document.getElementById('createRecipeBackdrop').style.display = 'block';
    document.getElementById('createRecipeSheet').style.display    = 'block';
    lucide.createIcons();
}

function closeCreateRecipeModal() {
    document.getElementById('createRecipeBackdrop').style.display = 'none';
    document.getElementById('createRecipeSheet').style.display    = 'none';
    closePhotoSourcePicker();
}

// ── Muestra/oculta el buscador de ingredientes ───────────────────────────
function toggleIngredientSearch() {
    const box = document.getElementById('ingredientSearchBox');
    const isHidden = box.style.display === 'none' || box.style.display === '';
    box.style.display = isHidden ? 'block' : 'none';
    if (isHidden) setTimeout(() => document.getElementById('ingredientSearch').focus(), 50);
}

// ── Busca alimentos del catálogo para usar como ingrediente ──────────────
async function searchIngredients(query) {
    try {
        const data = await apiCall('/foods?limit=8&search=' + encodeURIComponent(query));
        const box  = document.getElementById('ingredientSuggestions');
        if (!data.foods.length) { box.style.display = 'none'; return; }

        box.style.display = 'block';
        box.innerHTML = data.foods.map(f => `
            <div class="ingredient-suggestion" onclick='selectIngredient(${JSON.stringify(f)})'>
                <div>
                    <div style="font-weight:500;">${escapeHtml(f.name)}</div>
                    <div style="font-size:0.7rem;color:var(--color-muted);">${f.protein_per_100g}g P · ${f.carbs_per_100g}g C · ${f.fat_per_100g}g G</div>
                </div>
                <span class="suggestion-kcal">${f.calories_per_100g} kcal/100g</span>
            </div>
        `).join('');
    } catch(e) {}
}

// ── Cuando el usuario toca un alimento de las sugerencias ────────────────
// Le preguntamos cuántos gramos quiere añadir a la receta
async function selectIngredient(food) {
    const grams = await showGramsModal(food.name);
    if (grams === null) return;  // Canceló
    const g = parseFloat(grams);
    if (isNaN(g) || g <= 0) { showToast('Introduce una cantidad válida', 'error'); return; }

    const factor = g / 100;
    recipeIngredients.push({
        name:     food.name,
        quantity: g + 'g',
        grams:    g,
        calories: food.calories_per_100g * factor,
        protein:  food.protein_per_100g  * factor,
        carbs:    food.carbs_per_100g    * factor,
        fat:      food.fat_per_100g      * factor,
    });

    // Limpiamos el buscador
    document.getElementById('ingredientSearch').value = '';
    document.getElementById('ingredientSuggestions').style.display = 'none';
    document.getElementById('ingredientSearchBox').style.display = 'none';

    renderIngredientsList();
    updateRecipeTotals();
}

// ── Renderiza la lista de ingredientes añadidos ──────────────────────────
function renderIngredientsList() {
    const container = document.getElementById('ingredientsList');
    if (!recipeIngredients.length) {
        container.innerHTML = '<p style="font-size:0.8rem;color:var(--color-muted);text-align:center;padding:0.5rem 0;">Pulsa "+ Añadir" para buscar ingredientes en el catálogo</p>';
        return;
    }
    container.innerHTML = recipeIngredients.map((ing, i) => `
        <div class="ingredient-chip">
            <div>
                <div class="ingredient-chip-name">${escapeHtml(ing.name)}</div>
                <div class="ingredient-chip-info">${ing.quantity} · ${Math.round(ing.calories)} kcal · ${Math.round(ing.protein)}g P</div>
            </div>
            <button class="btn btn-ghost btn-icon" onclick="removeIngredient(${i})" style="color:var(--color-muted);padding:0.25rem;">
                <i data-lucide="x" style="width:0.9rem;height:0.9rem;"></i>
            </button>
        </div>
    `).join('');
    lucide.createIcons();
}

// ── Elimina un ingrediente de la lista ───────────────────────────────────
function removeIngredient(index) {
    recipeIngredients.splice(index, 1);
    renderIngredientsList();
    updateRecipeTotals();
}

// ── Recalcula y muestra los totales de la receta ─────────────────────────
// Los totales son del PLATO COMPLETO. Por porción = total / porciones.
function updateRecipeTotals() {
    const totalsDiv = document.getElementById('recipeTotals');

    if (!recipeIngredients.length) {
        totalsDiv.style.display = 'none';
        return;
    }

    const total = recipeIngredients.reduce((acc, ing) => ({
        calories: acc.calories + ing.calories,
        protein:  acc.protein  + ing.protein,
        carbs:    acc.carbs    + ing.carbs,
        fat:      acc.fat      + ing.fat,
    }), { calories: 0, protein: 0, carbs: 0, fat: 0 });

    const servings = parseInt(document.getElementById('crServings').value) || 1;

    document.getElementById('totCal').textContent  = Math.round(total.calories);
    document.getElementById('totProt').textContent = Math.round(total.protein)  + 'g';
    document.getElementById('totCarbs').textContent= Math.round(total.carbs)    + 'g';
    document.getElementById('totFat').textContent  = Math.round(total.fat)      + 'g';

    if (servings > 1) {
        document.getElementById('perServingNote').textContent =
            `Por porción (${servings}): ${Math.round(total.calories/servings)} kcal · ${Math.round(total.protein/servings)}g P · ${Math.round(total.carbs/servings)}g C · ${Math.round(total.fat/servings)}g G`;
    } else {
        document.getElementById('perServingNote').textContent = '';
    }

    totalsDiv.style.display = 'block';
}

// ── Guarda la receta en la API ────────────────────────────────────────────
async function saveRecipe() {
    const name = document.getElementById('crName').value.trim();
    if (!name) { showToast('El nombre es obligatorio', 'error'); return; }
    if (!recipeIngredients.length) { showToast('Añade al menos un ingrediente', 'error'); return; }

    const servings = parseInt(document.getElementById('crServings').value) || 1;

    // Calculamos los totales del plato
    const total = recipeIngredients.reduce((acc, ing) => ({
        calories: acc.calories + ing.calories,
        protein:  acc.protein  + ing.protein,
        carbs:    acc.carbs    + ing.carbs,
        fat:      acc.fat      + ing.fat,
    }), { calories: 0, protein: 0, carbs: 0, fat: 0 });

    // La API almacena valores POR PORCIÓN
    const caloriesPerServing = total.calories / servings;
    const proteinPerServing  = total.protein  / servings;
    const carbsPerServing    = total.carbs    / servings;
    const fatPerServing      = total.fat      / servings;

    const btn = document.getElementById('saveRecipeBtn');
    btn.disabled    = true;
    btn.textContent = 'Guardando...';

    try {
        const createData = await apiCall('/recipes', 'POST', {
            name:                  name,
            description:           document.getElementById('crDescription').value.trim() || null,
            category:              document.getElementById('crCategory').value,
            calories_per_serving:  Math.round(caloriesPerServing),
            protein_per_serving:   Math.round(proteinPerServing  * 10) / 10,
            carbs_per_serving:     Math.round(carbsPerServing    * 10) / 10,
            fat_per_serving:       Math.round(fatPerServing      * 10) / 10,
            servings:              servings,
            prep_time_min:         parseInt(document.getElementById('crPrepTime').value) || 0,
            cook_time_min:         parseInt(document.getElementById('crCookTime').value) || 0,
            // Ingredientes: guardamos nombre y cantidad como texto
            ingredients: recipeIngredients.map(ing => ({
                name:     ing.name,
                quantity: ing.quantity,
            })),
            instructions: document.getElementById('crInstructions').value.trim() || null,
            difficulty:   'fácil',
        });

        if (createRecipePhotoFile && createData?.recipe?.id) {
            btn.textContent = 'Subiendo foto...';
            await uploadRecipeImage(createData.recipe.id, createRecipePhotoFile);
            clearCreateRecipePhoto();
        }

        showToast('¡Receta guardada!', 'success');
        closeCreateRecipeModal();
        await loadRecipes();
    } catch(e) {
        // apiCall ya muestra el toast de error
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Guardar receta';
    }
}

// ── Diálogo de confirmación ───────────────────────────────────────────────
function showConfirmModal(message) {
    return new Promise(resolve => {
        document.getElementById('confirmDialogBody').textContent = message;
        document.getElementById('confirmBackdrop').style.display = 'block';
        document.getElementById('confirmDialog').style.display   = 'block';

        const ok     = document.getElementById('confirmOkBtn');
        const cancel = document.getElementById('confirmCancelBtn');

        const cleanup = (val) => {
            document.getElementById('confirmBackdrop').style.display = 'none';
            document.getElementById('confirmDialog').style.display   = 'none';
            ok.replaceWith(ok.cloneNode(true));
            cancel.replaceWith(cancel.cloneNode(true));
            resolve(val);
        };

        document.getElementById('confirmOkBtn').addEventListener('click', () => cleanup(true),  { once: true });
        document.getElementById('confirmCancelBtn').addEventListener('click', () => cleanup(false), { once: true });
    });
}

// ── Diálogo para pedir gramos ─────────────────────────────────────────────
function showGramsModal(foodName) {
    return new Promise(resolve => {
        document.getElementById('gramsDialogText').textContent = `¿Cuántos gramos de "${foodName}"?`;
        document.getElementById('gramsInput').value = '100';
        document.getElementById('gramsBackdrop').style.display = 'block';
        document.getElementById('gramsDialog').style.display   = 'block';
        setTimeout(() => document.getElementById('gramsInput').select(), 50);

        const confirm = document.getElementById('gramsConfirmBtn');
        const cancel  = document.getElementById('gramsCancelBtn');

        const cleanup = (val) => {
            document.getElementById('gramsBackdrop').style.display = 'none';
            document.getElementById('gramsDialog').style.display   = 'none';
            confirm.replaceWith(confirm.cloneNode(true));
            cancel.replaceWith(cancel.cloneNode(true));
            resolve(val);
        };

        document.getElementById('gramsConfirmBtn').addEventListener('click', () => {
            cleanup(document.getElementById('gramsInput').value);
        }, { once: true });

        document.getElementById('gramsCancelBtn').addEventListener('click', () => {
            cleanup(null);
        }, { once: true });
    });
}

// ── Utilidades ────────────────────────────────────────────────────────────
function capitalizeFirst(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
