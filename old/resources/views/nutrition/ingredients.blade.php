@extends('layouts.app')

@section('title', 'Ingredientes')

@section('content')
<div class="page-container">

    {{-- ── CABECERA ────────────────────────────────────────────────── --}}
    <header class="page-header sticky-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <a href="/nutrition" class="btn btn-ghost btn-icon">
                <i data-lucide="arrow-left"></i>
            </a>
            <div>
                <h1 class="page-title">Ingredientes</h1>
                <p style="font-size:0.75rem;color:var(--color-muted);" id="totalCount">Cargando...</p>
            </div>
        </div>
        <button class="btn btn-primary btn-sm" id="addIngredientBtn">
            <i data-lucide="plus" style="width:1rem;height:1rem;"></i>
            Añadir
        </button>
    </header>

    {{-- ── BUSCADOR Y FILTROS ──────────────────────────────────────── --}}
    <section style="padding:1rem 1rem 0;">
        {{-- Barra de búsqueda --}}
        <div style="position:relative;">
            <i data-lucide="search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--color-muted);width:1rem;height:1rem;pointer-events:none;"></i>
            <input type="text" id="searchInput" class="input" placeholder="Buscar ingrediente..." style="padding-left:2.5rem;">
        </div>

        {{-- Filtro por categoría --}}
        <div style="overflow-x:auto;padding:0.75rem 0 0.25rem;scrollbar-width:none;">
            <div id="categoryTabs" style="display:flex;gap:0.5rem;min-width:max-content;">
                <button class="pill-tab active" data-category="">Todas</button>
                {{-- Las categorías se generan dinámicamente desde JS --}}
            </div>
        </div>
    </section>

    {{-- ── LISTA DE INGREDIENTES ──────────────────────────────────── --}}
    <section style="padding:0.75rem 1rem 6rem;">

        {{-- Skeleton de carga --}}
        <div id="skeleton">
            @for ($i = 0; $i < 8; $i++)
            <div class="skeleton-card" style="height:56px;margin-bottom:0.5rem;"></div>
            @endfor
        </div>

        {{-- Lista real --}}
        <div id="ingredientsList" style="display:none;"></div>

        {{-- Estado vacío --}}
        <div id="emptyState" class="empty-state" style="display:none;">
            <div class="empty-state-icon"><i data-lucide="package-open"></i></div>
            <p class="empty-state-title">Sin resultados</p>
            <p class="empty-state-desc">No hay ingredientes que coincidan con tu búsqueda.</p>
        </div>

        {{-- Paginación --}}
        <div id="pagination" style="display:none;justify-content:center;gap:0.5rem;margin-top:1rem;padding-top:0.5rem;"></div>

    </section>
</div>

{{-- ── MODAL: EDITAR / CREAR INGREDIENTE ─────────────────────── --}}
<div class="modal-backdrop" id="formBackdrop" style="display:none;" onclick="closeForm()"></div>
<div class="bottom-sheet" id="formSheet" style="display:none;max-height:95dvh;">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="formTitle">Añadir ingrediente</h3>
        <p style="font-size:0.78rem;color:var(--color-muted);margin-top:0.2rem;">
            Los ingredientes que añadas estarán disponibles para todos los usuarios de la app.
        </p>
    </div>
    <div class="sheet-body">

        <input type="hidden" id="editingId">

        {{-- Nombre y marca --}}
        <div class="form-group">
            <label class="form-label">Nombre *</label>
            <input type="text" id="fName" class="input" placeholder="Ej: Pechuga de pollo cocida">
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Marca <span style="color:var(--color-muted);font-weight:400;">(opcional)</span></label>
            <input type="text" id="fBrand" class="input" placeholder="Ej: Hacendado, Mercadona...">
        </div>
        {{-- Unidad de medida --}}
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Unidad de medida</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
                <label class="unit-option" id="unitG">
                    <input type="radio" name="fUnit" value="g" checked style="display:none;">
                    <span class="unit-icon">⚖️</span>
                    <span class="unit-label">Por 100g</span>
                    <span class="unit-desc">Sólidos, alimentos</span>
                </label>
                <label class="unit-option" id="unitMl">
                    <input type="radio" name="fUnit" value="ml" style="display:none;">
                    <span class="unit-icon">🧴</span>
                    <span class="unit-label">Por 100ml</span>
                    <span class="unit-desc">Líquidos, bebidas</span>
                </label>
            </div>
        </div>

        <div class="form-group" style="margin-top:0.75rem;">
            <label class="form-label">Categoría</label>
            <select id="fCategory" class="input">
                <option value="">Selecciona categoría...</option>
                <option value="aceites">Aceites y grasas</option>
                <option value="arroz">Arroz</option>
                <option value="aves">Aves (pollo, pavo…)</option>
                <option value="bebidas">Bebidas</option>
                <option value="bebidas_alcoholicas">Bebidas alcohólicas</option>
                <option value="cafe">Café e infusiones</option>
                <option value="carnes">Carnes</option>
                <option value="cereales">Cereales</option>
                <option value="comida_rapida">Comida rápida</option>
                <option value="dulces">Dulces</option>
                <option value="embutidos">Embutidos</option>
                <option value="ensaladas">Ensaladas</option>
                <option value="especias">Especias y hierbas</option>
                <option value="frutas">Frutas</option>
                <option value="frutos_secos">Frutos secos</option>
                <option value="harinas">Harinas y masas</option>
                <option value="helados">Helados</option>
                <option value="lacteos">Lácteos</option>
                <option value="pan">Pan y bollería</option>
                <option value="pasta">Pasta</option>
                <option value="patatas">Patatas</option>
                <option value="pescados">Pescados y mariscos</option>
                <option value="pizza">Pizza</option>
                <option value="platos_preparados">Platos preparados</option>
                <option value="postres">Postres</option>
                <option value="quesos">Quesos</option>
                <option value="reposteria">Repostería</option>
                <option value="salsas">Salsas y untables</option>
                <option value="setas">Setas y hongos</option>
                <option value="snacks">Snacks</option>
                <option value="suplementos">Suplementos</option>
                <option value="sushi">Sushi</option>
                <option value="vegetal">Vegetal y vegano</option>
                <option value="verduras">Verduras</option>
                <option value="yogur">Yogur</option>
            </select>
        </div>

        {{-- Separador macros --}}
        <div style="margin:1.25rem 0 0.75rem;display:flex;align-items:center;gap:0.75rem;">
            <div style="flex:1;height:1px;background:var(--bg-muted);"></div>
            <span id="macroSeparatorLabel" style="font-size:0.75rem;color:var(--color-muted);white-space:nowrap;">Valores por 100g</span>
            <div style="flex:1;height:1px;background:var(--bg-muted);"></div>
        </div>

        {{-- Grid de macros --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--color-primary);display:inline-block;"></span>
                    Calorías (kcal) *
                </label>
                <input type="number" id="fCalories" class="input" placeholder="0" min="0" max="9999" step="0.1">
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#60a5fa;display:inline-block;"></span>
                    Proteínas (g)
                </label>
                <input type="number" id="fProtein" class="input" placeholder="0" min="0" max="999" step="0.1">
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f97316;display:inline-block;"></span>
                    Carbohidratos (g)
                </label>
                <input type="number" id="fCarbs" class="input" placeholder="0" min="0" max="999" step="0.1">
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#facc15;display:inline-block;"></span>
                    Grasas (g)
                </label>
                <input type="number" id="fFat" class="input" placeholder="0" min="0" max="999" step="0.1">
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#34d399;display:inline-block;"></span>
                    Fibra (g)
                </label>
                <input type="number" id="fFiber" class="input" placeholder="0" min="0" max="999" step="0.1">
            </div>

            <div class="form-group">
                <label class="form-label" style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#e879f9;display:inline-block;"></span>
                    Azúcares (g)
                </label>
                <input type="number" id="fSugar" class="input" placeholder="0" min="0" max="999" step="0.1">
            </div>

        </div>

        {{-- Botones de acción --}}
        <div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:1.25rem;">
            <button class="btn btn-primary btn-block" id="saveBtn" onclick="saveIngredient()">
                Guardar ingrediente
            </button>
            <button class="btn btn-danger btn-block" id="deleteBtn" style="display:none;" onclick="deleteIngredient()">
                Eliminar ingrediente
            </button>
            <button class="btn btn-ghost btn-block" onclick="closeForm()">Cancelar</button>
        </div>

    </div>
</div>

@endsection

@push('styles')
<style>
/* ── Filas de la lista ── */
.ingredient-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--bg-card);
    border-radius: 0.75rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: var(--transition-fast);
    border: 1px solid transparent;
}
.ingredient-row:hover  { background: var(--bg-muted); border-color: var(--bg-muted); }
.ingredient-row:active { transform: scale(0.99); }

.ingredient-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.6rem;
    background: var(--bg-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}
.ingredient-info   { flex: 1; min-width: 0; }
.ingredient-name   { font-size: 0.875rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ingredient-cat    { font-size: 0.72rem; color: var(--color-muted); margin-top: 0.1rem; }
.ingredient-macros { display: flex; gap: 0.5rem; align-items: center; flex-shrink: 0; }
.macro-pill {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.15rem 0.4rem;
    border-radius: 999px;
    white-space: nowrap;
}
.macro-cal  { background: rgba(245,158,11,.15); color: var(--color-primary); }
.macro-pro  { background: rgba(96,165,250,.15);  color: #60a5fa; }

/* ── Badge sistema vs personal ── */
.badge-sistema   { background: rgba(52,211,153,.15); color: #34d399; }
.badge-personal  { background: rgba(249,115,22,.15); color: #f97316; }

/* ── Selector de unidad g/ml ── */
.unit-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    padding: 0.75rem 0.5rem;
    border-radius: 0.75rem;
    border: 2px solid var(--bg-muted);
    background: var(--bg-muted);
    cursor: pointer;
    transition: var(--transition-fast);
    text-align: center;
}
.unit-option:has(input:checked) {
    border-color: var(--color-primary);
    background: rgba(245,158,11,.1);
}
.unit-icon  { font-size: 1.4rem; line-height: 1; }
.unit-label { font-size: 0.85rem; font-weight: 600; color: var(--color-foreground); }
.unit-desc  { font-size: 0.68rem; color: var(--color-muted); }

/* ── Paginación ── */
.page-btn {
    width: 2.2rem; height: 2.2rem;
    border-radius: 0.5rem;
    border: 1px solid var(--bg-muted);
    background: var(--bg-card);
    color: var(--color-foreground);
    font-size: 0.85rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition-fast);
}
.page-btn:hover   { background: var(--bg-muted); }
.page-btn.active  { background: var(--color-primary); border-color: var(--color-primary); color: #000; font-weight: 700; }
.page-btn:disabled { opacity: 0.35; cursor: not-allowed; }

/* ── Modales ── */
.modal-backdrop { position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:100;backdrop-filter:blur(3px); }
.bottom-sheet   {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: var(--bg-card);
    border-radius: 1.25rem 1.25rem 0 0;
    z-index: 101;
    overflow-y: auto;
    animation: slideUp 0.25s ease;
}
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
.sheet-handle { width:2.5rem;height:4px;background:var(--bg-muted);border-radius:2px;margin:0.75rem auto 0; }
.sheet-header { padding: 1rem 1.25rem 0.5rem; }
.sheet-title  { font-size: 1.1rem; font-weight: 700; }
.sheet-body   { padding: 0.5rem 1.25rem 2rem; }

/* Forzamos el espaciado lateral en los sheets de ingredientes */
#formSheet .sheet-body {
    padding: 0.5rem 1.25rem 2rem;
}

</style>
@endpush

@push('scripts')
<script>
// ── Estado global ─────────────────────────────────────────────────────────
let currentPage     = 1;
let currentSearch   = '';
let currentCategory = '';
let debounceTimer   = null;

// ── Actualiza el label del separador de macros según la unidad elegida ───
function updateUnitUI() {
    const unit = document.querySelector('input[name="fUnit"]:checked')?.value || 'g';
    const label = unit === 'ml' ? 'Valores por 100ml' : 'Valores por 100g';
    document.getElementById('macroSeparatorLabel').textContent = label;

    // Resaltar visualmente el bloque seleccionado (por si :has() no está soportado en Android)
    document.getElementById('unitG').style.borderColor  = unit === 'g'  ? 'var(--color-primary)' : 'var(--bg-muted)';
    document.getElementById('unitMl').style.borderColor = unit === 'ml' ? 'var(--color-primary)' : 'var(--bg-muted)';
    document.getElementById('unitG').style.background   = unit === 'g'  ? 'rgba(245,158,11,.1)' : 'var(--bg-muted)';
    document.getElementById('unitMl').style.background  = unit === 'ml' ? 'rgba(245,158,11,.1)' : 'var(--bg-muted)';
}

// ── Inicialización ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    lucide.createIcons();

    // Listener para los radios de unidad
    document.querySelectorAll('input[name="fUnit"]').forEach(radio => {
        radio.addEventListener('change', updateUnitUI);
    });

    // El tab "Todas" está hardcodeado en el HTML — le añadimos su listener aquí
    // category='' significa sin filtro → devuelve todo
    const tabTodas = document.querySelector('#categoryTabs .pill-tab[data-category=""]');
    if (tabTodas) {
        tabTodas.addEventListener('click', () => selectCategory('', tabTodas));
    }

    // Cargamos el resto de categorías desde la API y generamos sus tabs
    await loadCategories();

    // Primera carga de la lista
    await loadIngredients();

    // Eventos
    document.getElementById('addIngredientBtn').addEventListener('click', () => openForm());
    document.getElementById('searchInput').addEventListener('input', e => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentSearch = e.target.value.trim();
            currentPage   = 1;
            loadIngredients();
        }, 350);
    });
});

// ── Carga las categorías y genera los tabs de filtro ─────────────────────
async function loadCategories() {
    try {
        const data = await apiCall('/foods/categories');
        const tabs = document.getElementById('categoryTabs');

        // Etiquetas legibles para cada categoría
        const catLabels = {
            aceites:'Aceites', arroz:'Arroz', aves:'Aves', bebidas:'Bebidas',
            bebidas_alcoholicas:'Bebidas alcohólicas', cafe:'Café', carnes:'Carnes',
            cereales:'Cereales', comida_rapida:'Comida rápida', dulces:'Dulces',
            embutidos:'Embutidos', ensaladas:'Ensaladas', especias:'Especias',
            frutas:'Frutas', frutos_secos:'Frutos secos', harinas:'Harinas',
            helados:'Helados', lacteos:'Lácteos', pan:'Pan', pasta:'Pasta',
            patatas:'Patatas', pescados:'Pescados', pizza:'Pizza',
            platos_preparados:'Platos preparados', postres:'Postres', quesos:'Quesos',
            reposteria:'Repostería', salsas:'Salsas', setas:'Setas', snacks:'Snacks',
            suplementos:'Suplementos', sushi:'Sushi', vegetal:'Vegetal', verduras:'Verduras',
            yogur:'Yogur',
        };

        data.categories.forEach(cat => {
            const btn = document.createElement('button');
            btn.className        = 'pill-tab';
            btn.dataset.category = cat;
            // Usamos la etiqueta del mapa o generamos una automática
            btn.textContent = catLabels[cat]
                || (cat.charAt(0).toUpperCase() + cat.slice(1).replace(/_/g, ' '));
            btn.addEventListener('click', () => selectCategory(cat, btn));
            tabs.appendChild(btn);
        });
    } catch (e) {
        console.error('Error cargando categorías:', e);
    }
}

// ── Cambia la categoría activa ────────────────────────────────────────────
// Esta función funciona tanto para el tab "Todas" (category='') como para el resto
function selectCategory(category, btn) {
    // Quitar clase active de todos los tabs
    document.querySelectorAll('#categoryTabs .pill-tab').forEach(b => b.classList.remove('active'));
    // Marcar el tab pulsado como activo
    btn.classList.add('active');
    currentCategory = category;
    currentPage     = 1;
    loadIngredients();
}

// ── Carga la lista paginada de ingredientes ───────────────────────────────
async function loadIngredients() {
    showSkeleton(true);

    try {
        const params = new URLSearchParams({
            page: currentPage,
            ...(currentSearch   && { search: currentSearch }),
            ...(currentCategory && { category: currentCategory }),
        });

        const data = await apiCall(`/foods/all?${params}`);

        renderIngredients(data.foods);
        renderPagination(data.current_page, data.last_page, data.total);

        document.getElementById('totalCount').textContent =
            `${data.total.toLocaleString()} ingredientes en total`;

    } catch (e) {
        console.error('Error cargando ingredientes:', e);
    }

    showSkeleton(false);
}

// ── Renderiza la lista de ingredientes en el DOM ──────────────────────────
function renderIngredients(foods) {
    const list = document.getElementById('ingredientsList');
    const empty = document.getElementById('emptyState');

    if (!foods || foods.length === 0) {
        list.style.display  = 'none';
        empty.style.display = 'flex';
        lucide.createIcons();
        return;
    }

    empty.style.display = 'none';
    list.style.display  = 'block';

    // Mapa de emojis por categoría
    const catEmoji = {
        aceites:'🫒', arroz:'🍚', aves:'🍗', bebidas:'🥤', bebidas_alcoholicas:'🍺',
        cafe:'☕', carnes:'🥩', cereales:'🌾', comida_rapida:'🍔', dulces:'🍬',
        embutidos:'🌭', ensaladas:'🥗', especias:'🧂', frutas:'🍎', frutos_secos:'🥜',
        harinas:'🌾', helados:'🍦', lacteos:'🥛', pan:'🍞', pasta:'🍝',
        patatas:'🥔', pescados:'🐟', pizza:'🍕', platos_preparados:'🍱', postres:'🍮',
        quesos:'🧀', reposteria:'🧁', salsas:'🥫', setas:'🍄', snacks:'🍿',
        suplementos:'💊', sushi:'🍣', vegetal:'🌿', verduras:'🥦', yogur:'🫙',
    };

    list.innerHTML = foods.map(food => {
        const emoji    = catEmoji[food.category] || '🥘';
        const isSys    = food.is_verified && food.user_id === null;
        const badgeHtml = isSys
            ? '<span class="badge badge-sm badge-sistema" style="font-size:0.65rem;padding:0.1rem 0.4rem;">Sistema</span>'
            : '<span class="badge badge-sm badge-personal" style="font-size:0.65rem;padding:0.1rem 0.4rem;">Personal</span>';

        // Unidad: mostramos si es g o ml junto a las calorías
        const unit     = food.unit || 'g';
        const unitBadge = unit === 'ml'
            ? '<span style="font-size:0.62rem;color:#38bdf8;background:rgba(56,189,248,.12);padding:0.1rem 0.35rem;border-radius:999px;font-weight:600;">ml</span>'
            : '';

        // Proteínas solo si tienen datos (> 0)
        const proHtml = food.protein_per_100g > 0
            ? `<span class="macro-pill macro-pro">${food.protein_per_100g}g P</span>`
            : '';

        const catLabel = (food.category || 'sin categoría').replace(/_/g, ' ');

        const imageSrc = resolveFoodImageSrc(food);

        // Si el alimento tiene foto, mostramos miniatura; si no, el emoji de categoría
        const iconHtml = imageSrc
            ? `<img src="${escapeHtml(imageSrc)}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:0.6rem;">`
            : emoji;

        return `
        <div class="ingredient-row" onclick="openForm(${JSON.stringify(food).replace(/"/g, '&quot;')})">
            <div class="ingredient-icon" style="${imageSrc ? 'overflow:hidden;padding:0;' : ''}">${iconHtml}</div>
            <div class="ingredient-info">
                <div class="ingredient-name">${escapeHtml(food.name)}</div>
                <div class="ingredient-cat">${catLabel}${food.brand ? ' · ' + escapeHtml(food.brand) : ''} ${badgeHtml} ${unitBadge}</div>
            </div>
            <div class="ingredient-macros">
                <span class="macro-pill macro-cal">${food.calories_per_100g}/${unit}</span>
                ${proHtml}
            </div>
        </div>`;
    }).join('');
}

// ── Renderiza los botones de paginación ───────────────────────────────────
function renderPagination(current, last, total) {
    const pag = document.getElementById('pagination');

    if (last <= 1) {
        pag.style.display = 'none';
        return;
    }
    // 'flex' en vez de 'block' para que funcione el gap/justify-content del layout
    pag.style.display = 'flex';

    // Mostramos máximo 5 páginas alrededor de la actual
    let pages = [];
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
    }

    pag.innerHTML = `
        <button class="page-btn" onclick="goPage(${current - 1})" ${current === 1 ? 'disabled' : ''}>
            <i data-lucide="chevron-left" style="width:1rem;height:1rem;"></i>
        </button>
        ${pages.map(p => `
            <button class="page-btn ${p === current ? 'active' : ''}" onclick="goPage(${p})">${p}</button>
        `).join('')}
        <button class="page-btn" onclick="goPage(${current + 1})" ${current === last ? 'disabled' : ''}>
            <i data-lucide="chevron-right" style="width:1rem;height:1rem;"></i>
        </button>
    `;
    lucide.createIcons();
}

function goPage(page) {
    currentPage = page;
    loadIngredients();
    // Scroll al inicio de la lista
    document.getElementById('ingredientsList').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Abre el formulario de crear / editar ──────────────────────────────────
function openForm(food = null) {
    const isEdit = food !== null;

    // Título dinámico según si es edición o creación
    document.getElementById('formTitle').textContent = isEdit
        ? 'Editar ingrediente'
        : 'Añadir ingrediente';

    const deleteBtn = document.getElementById('deleteBtn');
    deleteBtn.style.display = isEdit ? 'block' : 'none';

    // Rellenar campos si es edición
    document.getElementById('editingId').value  = isEdit ? food.id    : '';
    document.getElementById('fName').value      = isEdit ? food.name  : '';
    document.getElementById('fBrand').value     = isEdit ? (food.brand    || '') : '';
    document.getElementById('fCalories').value  = isEdit ? food.calories_per_100g : '';
    document.getElementById('fProtein').value   = isEdit ? (food.protein_per_100g || '') : '';
    document.getElementById('fCarbs').value     = isEdit ? (food.carbs_per_100g   || '') : '';
    document.getElementById('fFat').value       = isEdit ? (food.fat_per_100g     || '') : '';
    document.getElementById('fFiber').value     = isEdit ? (food.fiber_per_100g   || '') : '';
    document.getElementById('fSugar').value     = isEdit ? (food.sugar_per_100g   || '') : '';

    // Seleccionar categoría en el <select>
    const catSelect = document.getElementById('fCategory');
    catSelect.value = isEdit ? (food.category || '') : '';

    // Seleccionar la unidad (g o ml) con el radio correcto
    const unit = isEdit ? (food.unit || 'g') : 'g';
    document.querySelectorAll('input[name="fUnit"]').forEach(r => {
        r.checked = (r.value === unit);
    });
    // Actualizar el aspecto visual del selector y el label del separador
    updateUnitUI();

    document.getElementById('formBackdrop').style.display = 'block';
    document.getElementById('formSheet').style.display    = 'block';
    lucide.createIcons();
}

function resolveFoodImageSrc(food) {
    if (!food) return '';

    if (food.image_url && typeof food.image_url === 'string' && food.image_url.trim() !== '') {
        return food.image_url;
    }

    if (!food.image_path || typeof food.image_path !== 'string') {
        return '';
    }

    const raw = food.image_path.trim();
    if (raw === '') return '';

    if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
    if (raw.startsWith('/storage/')) return raw;
    if (raw.startsWith('storage/')) return '/' + raw;

    return '/storage/' + raw.replace(/^\/+/, '');
}

function closeForm() {
    document.getElementById('formBackdrop').style.display = 'none';
    document.getElementById('formSheet').style.display    = 'none';
}

// ── Guarda el ingrediente (crea o edita) ──────────────────────────────────
async function saveIngredient() {
    const id       = document.getElementById('editingId').value;
    const name     = document.getElementById('fName').value.trim();
    const calories = parseFloat(document.getElementById('fCalories').value);

    // Validaciones básicas
    if (!name) {
        showToast('El nombre es obligatorio.', 'error');
        return;
    }
    if (isNaN(calories) || calories < 0) {
        showToast('Las calorías son obligatorias y deben ser un número positivo.', 'error');
        return;
    }

    const btn = document.getElementById('saveBtn');
    btn.disabled     = true;
    btn.textContent  = 'Guardando...';

    // Construimos el payload con todos los campos
    const payload = {
        name,
        brand:             document.getElementById('fBrand').value.trim()    || null,
        category:          document.getElementById('fCategory').value         || null,
        unit:              document.querySelector('input[name="fUnit"]:checked')?.value || 'g',
        calories_per_100g: calories,
        protein_per_100g:  parseFloat(document.getElementById('fProtein').value) || 0,
        carbs_per_100g:    parseFloat(document.getElementById('fCarbs').value)   || 0,
        fat_per_100g:      parseFloat(document.getElementById('fFat').value)     || 0,
        fiber_per_100g:    parseFloat(document.getElementById('fFiber').value)   || 0,
        sugar_per_100g:    parseFloat(document.getElementById('fSugar').value)   || 0,
    };

    try {
        let savedFoodId = id;

        if (id) {
            // Editar alimento existente → PUT /api/foods/{id}
            await apiCall(`/foods/${id}`, 'PUT', payload);
        } else {
            // Crear alimento nuevo → POST /api/foods (con from_ingredients=true → va al sistema)
            const res = await apiCall('/foods', 'POST', { ...payload, from_ingredients: true });
            savedFoodId = res.food.id; // guardamos el ID del nuevo alimento para subir la foto
        }

        showToast(id ? 'Ingrediente actualizado ✓' : 'Ingrediente añadido al catálogo ✓', 'success');
        closeForm();
        await loadIngredients(); // Recargamos la lista para ver el cambio

    } catch (e) {
        showToast(e.message || 'Error al guardar el ingrediente. Inténtalo de nuevo.', 'error');
        console.error(e);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Guardar ingrediente';
    }
}

async function deleteIngredient() {
    const id = document.getElementById('editingId').value;
    if (!id) return;

    const ok = confirm('¿Seguro que quieres eliminar este ingrediente? Esta acción no se puede deshacer.');
    if (!ok) return;

    const btn = document.getElementById('deleteBtn');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';

    try {
        const res = await apiCall(`/foods/${id}`, 'DELETE');
        showToast(res?.message || 'Ingrediente eliminado ✓', 'success');
        closeForm();
        await loadIngredients();
    } catch (e) {
        showToast(e.message || 'No se pudo eliminar el ingrediente.', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Eliminar ingrediente';
    }
}

// ── Utilidades ────────────────────────────────────────────────────────────
function showSkeleton(show) {
    document.getElementById('skeleton').style.display         = show ? 'block' : 'none';
    document.getElementById('ingredientsList').style.display  = show ? 'none'  : 'block';
    document.getElementById('emptyState').style.display       = 'none';
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
    