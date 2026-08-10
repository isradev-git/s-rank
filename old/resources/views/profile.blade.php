@extends('layouts.app')

@section('content')
<div class="pb-28">

    {{-- ─── Header ──────────────────────────────────── --}}
    <div style="position:sticky; top:0; z-index:30; background:rgba(10,10,11,0.92);
                backdrop-filter:blur(16px); border-bottom:1px solid var(--border-light);
                padding:1rem 1.25rem;">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/" class="btn btn-ghost btn-icon" aria-label="Volver al inicio" style="color:var(--text-muted);">
                    <i data-lucide="arrow-left" style="width:1.25rem;height:1.25rem;"></i>
                </a>
                <h1 style="font-size:1.25rem; font-weight:800; margin:0;">Perfil</h1>
            </div>
            <button onclick="auth.logout()" class="btn btn-sm btn-outline-danger" style="font-size:0.75rem;">
                <i data-lucide="log-out" style="width:0.875rem;height:0.875rem;"></i>
                Salir
            </button>
        </div>
    </div>

    {{-- ─── Avatar Section ──────────────────────────── --}}
    <div style="background:linear-gradient(180deg, rgba(245,158,11,0.06) 0%, transparent 100%);
                padding:2rem 1.5rem 1.5rem; text-align:center; position:relative;">
        <div id="profile-initial" class="avatar-lg" style="margin:0 auto; font-size:2rem;">-</div>
        <h2 style="font-size:1.25rem; font-weight:800; margin-top:0.75rem; margin-bottom:0.125rem; color:var(--color-primary);"
            id="profile-name">Cargando...</h2>
        <p style="font-size:0.8125rem; color:var(--text-muted); margin:0;" id="profile-email">...</p>
        <span class="badge badge-primary" style="margin-top:0.625rem; display:inline-flex;">Miembro</span>
    </div>

    <div class="p-5 space-y-5">

        {{-- ─── Stats ───────────────────────────────── --}}
        <div class="card" style="padding:1.25rem;">
            <div class="grid grid-cols-3">
                <div style="text-align:center;">
                    <div class="stat-value" style="font-size:1.5rem;" id="stat-workouts">–</div>
                    <div class="stat-label">Entrenos</div>
                </div>
                <div style="text-align:center; border-left:1px solid var(--border-light); border-right:1px solid var(--border-light);">
                    <div class="stat-value" style="font-size:1.5rem;" id="stat-minutes">–</div>
                    <div class="stat-label">Minutos</div>
                </div>
                <div style="text-align:center;">
                    <div class="stat-value" style="font-size:1.5rem; color:var(--color-primary);" id="stat-streak">–</div>
                    <div class="stat-label">🔥 Racha</div>
                </div>
            </div>
        </div>

        {{-- ─── Personal Data ────────────────────────── --}}
        <form onsubmit="updateProfile(event)" class="card" style="padding:1.5rem;">
            <div class="flex items-center gap-2 mb-5">
                <div class="icon-wrap icon-wrap-sm" style="background:var(--color-primary-subtle);">
                    <i data-lucide="user" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
                </div>
                <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Datos Personales</h3>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="input-label">Sexo</label>
                        <select id="p-gender" class="select">
                            <option value="male">Hombre</option>
                            <option value="female">Mujer</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="input-label">Edad</label>
                        <input type="number" id="p-age" class="input" placeholder="25" min="10" max="100">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="input-label">Objetivo</label>
                        <select id="p-main-goal" class="select">
                            <option value="strength">Fuerza</option>
                            <option value="muscle">Hipertrofia</option>
                            <option value="endurance">Resistencia</option>
                            <option value="health">Salud</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="input-label">Peso (kg)</label>
                        <input type="number" step="0.1" id="p-weight" class="input" placeholder="70">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="input-label">Altura (cm)</label>
                        <input type="number" id="p-height" class="input" placeholder="175">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="input-label" style="margin-bottom:0;">Meta Semanal</label>
                        <span style="font-size:1.25rem; font-weight:800; color:var(--color-primary);" id="goal-val">3</span>
                    </div>
                    <input type="range" min="1" max="7" id="p-goal" class="range-slider"
                           oninput="document.getElementById('goal-val').textContent = this.value">
                    <div class="flex justify-between mt-1" style="padding:0 2px;">
                        <span style="font-size:0.6875rem; color:var(--text-muted);">1 día</span>
                        <span style="font-size:0.6875rem; color:var(--text-muted);">7 días</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-5" style="height:3rem; font-size:0.9375rem;">
                <i data-lucide="save" style="width:1rem;height:1rem;"></i>
                Guardar Cambios
            </button>
        </form>

        {{-- ─── Weight Evolution Chart ──────────────── --}}
        <div class="card" style="padding:1.5rem;">
            <div class="flex items-center gap-2 mb-5">
                <div class="icon-wrap icon-wrap-sm" style="background:var(--color-blue-subtle);">
                    <i data-lucide="trending-up" style="width:0.875rem;height:0.875rem;color:var(--color-blue);"></i>
                </div>
                <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Evolución de Peso</h3>
            </div>
            <div style="height:12rem; position:relative;">
                <canvas id="weightChart"></canvas>
                <p id="chart-empty" style="display:none; position:absolute; inset:0; align-items:center;
                   justify-content:center; font-size:0.8125rem; color:var(--text-muted);">
                    Sin datos de peso aún
                </p>
            </div>
        </div>

        {{-- ─── Informe de salud (médico/nutricionista) ────────────────── --}}
        <a href="/informe-salud" class="card card-interactive" style="padding:1.25rem;display:flex;align-items:center;gap:1rem;text-decoration:none;color:inherit;">
            <div class="icon-wrap icon-wrap-md" style="background:rgba(16,185,129,0.12);flex-shrink:0;">
                <i data-lucide="file-text" style="width:1.1rem;height:1.1rem;color:var(--color-teal);"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <h3 style="font-size:0.9375rem;font-weight:700;margin:0 0 0.125rem;">Informe de salud</h3>
                <p style="font-size:0.75rem;color:var(--text-muted);margin:0;">Peso, IMC, nutrición y actividad en PDF para tu médico o nutricionista.</p>
            </div>
            <i data-lucide="chevron-right" style="width:1.1rem;height:1.1rem;color:var(--text-muted);flex-shrink:0;"></i>
        </a>

        {{-- ─── Heatmap de Actividad ────────────────── --}}
        <div class="card" style="padding:1.5rem;">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.1);">
                        <i data-lucide="calendar-days" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
                    </div>
                    <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Actividad <span id="heatmap-year" style="color:var(--color-primary);"></span></h3>
                </div>
                <div style="display:flex;gap:0.25rem;">
                    <button onclick="changeHeatmapYear(-1)" class="btn btn-ghost btn-icon" style="width:1.75rem;height:1.75rem;" aria-label="Año anterior">
                        <i data-lucide="chevron-left" style="width:0.9rem;height:0.9rem;"></i>
                    </button>
                    <button onclick="changeHeatmapYear(1)" class="btn btn-ghost btn-icon" style="width:1.75rem;height:1.75rem;" id="heatmap-next-btn" aria-label="Año siguiente">
                        <i data-lucide="chevron-right" style="width:0.9rem;height:0.9rem;"></i>
                    </button>
                </div>
            </div>
            <div id="heatmap-container" style="overflow-x:auto; padding-bottom:0.25rem;">
                <div id="heatmap-grid" style="display:grid; grid-template-rows:repeat(7,10px); grid-auto-flow:column; gap:2px; width:max-content;"></div>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;">
                <span style="font-size:0.6875rem;color:var(--text-muted);">Menos</span>
                <div style="display:flex;gap:2px;">
                    <div style="width:10px;height:10px;border-radius:2px;background:var(--bg-muted);"></div>
                    <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,0.25);"></div>
                    <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,0.5);"></div>
                    <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,0.75);"></div>
                    <div style="width:10px;height:10px;border-radius:2px;background:rgba(245,158,11,1);"></div>
                </div>
                <span style="font-size:0.6875rem;color:var(--text-muted);">Más</span>
                <span id="heatmap-total" style="font-size:0.6875rem;color:var(--text-muted);margin-left:auto;"></span>
            </div>
        </div>

        {{-- ─── Mis Récords ─────────────────────────── --}}
        <div class="card" style="padding:1.5rem;">
            <div class="flex items-center gap-2 mb-4">
                <div class="icon-wrap icon-wrap-sm" style="background:rgba(251,191,36,0.12);">
                    <i data-lucide="trophy" style="width:0.875rem;height:0.875rem;color:#fbbf24;"></i>
                </div>
                <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Mis Récords</h3>
            </div>
            <div id="records-skeleton">
                <div class="skeleton skeleton-text" style="width:60%;margin-bottom:0.5rem;"></div>
                <div class="skeleton skeleton-text" style="width:80%;margin-bottom:0.5rem;"></div>
                <div class="skeleton skeleton-text" style="width:50%;"></div>
            </div>
            <div id="records-empty" style="display:none;" class="empty-state" style="padding:1rem 0;">
                <div class="empty-state-icon"><i data-lucide="dumbbell"></i></div>
                <p class="empty-state-title" style="font-size:0.875rem;">Sin récords aún</p>
                <p class="empty-state-desc" style="font-size:0.75rem;">Registra entrenamientos con peso para ver tus PRs aquí.</p>
            </div>
            <div id="records-list" style="display:none;">
                <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                    <thead>
                        <tr style="color:var(--text-muted);font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.05em;">
                            <th style="text-align:left;padding:0 0 0.5rem;font-weight:600;">Ejercicio</th>
                            <th style="text-align:right;padding:0 0 0.5rem;font-weight:600;">PR</th>
                            <th style="text-align:right;padding:0 0 0.5rem;font-weight:600;">Reps</th>
                            <th style="text-align:right;padding:0 0 0.5rem;font-weight:600;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="records-tbody"></tbody>
                </table>
            </div>
        </div>

        {{-- ─── Logros ──────────────────────────────── --}}
        <div class="card" style="padding:1.5rem;">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="icon-wrap icon-wrap-sm" style="background:rgba(167,139,250,0.12);">
                        <i data-lucide="award" style="width:0.875rem;height:0.875rem;color:#a78bfa;"></i>
                    </div>
                    <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Logros</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span id="achievements-counter" style="font-size:0.75rem;font-weight:700;color:var(--text-muted);"></span>
                    <a href="/logros" class="section-link" style="font-size:0.75rem;">Ver todos →</a>
                </div>
            </div>
            <div id="achievements-skeleton">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.75rem;">
                    <div class="skeleton" style="height:5rem;border-radius:0.75rem;"></div>
                    <div class="skeleton" style="height:5rem;border-radius:0.75rem;"></div>
                    <div class="skeleton" style="height:5rem;border-radius:0.75rem;"></div>
                </div>
            </div>
            <div id="achievements-grid" style="display:none;grid-template-columns:repeat(3,1fr);gap:0.75rem;"></div>
        </div>

        {{-- ─── Change Password ────────────────────── --}}
        <div class="card" style="padding:1.5rem;">
            <div class="flex items-center gap-2 mb-5">
                <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.1);">
                    <i data-lucide="lock" style="width:0.875rem;height:0.875rem;color:var(--color-primary);"></i>
                </div>
                <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Cambiar Contraseña</h3>
            </div>
            <div class="space-y-3">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="input-label">Contraseña actual</label>
                    <input type="password" id="cp-current" class="input" placeholder="••••••••" autocomplete="current-password">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="input-label">Nueva contraseña</label>
                    <input type="password" id="cp-new" class="input" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="input-label">Confirmar nueva contraseña</label>
                    <input type="password" id="cp-confirm" class="input" placeholder="Repite la nueva contraseña" autocomplete="new-password">
                </div>
                <p id="cp-error" style="display:none; font-size:0.8125rem; color:var(--color-danger); margin:0;"></p>
            </div>
            <button onclick="changePassword()" class="btn btn-primary btn-block mt-4" style="height:3rem;">
                <i data-lucide="lock" style="width:1rem;height:1rem;"></i>
                Actualizar Contraseña
            </button>
        </div>

        {{-- ─── Panel Admin: Gestión de Usuarios ─── --}}
        <div id="admin-panel" style="display:none;">
            <div class="card" style="padding:1.5rem;">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="icon-wrap icon-wrap-sm" style="background:rgba(167,139,250,0.12);">
                            <i data-lucide="users" style="width:0.875rem;height:0.875rem;color:#a78bfa;"></i>
                        </div>
                        <h3 style="font-size:0.9375rem; font-weight:700; margin:0;">Gestión de Usuarios</h3>
                        <span class="badge badge-purple" style="font-size:0.6rem;">ADMIN</span>
                    </div>
                    <button onclick="openCreateUserModal()" class="btn btn-sm btn-primary">
                        <i data-lucide="user-plus" style="width:0.875rem;height:0.875rem;"></i>
                        Nuevo
                    </button>
                </div>

                <div id="users-skeleton">
                    <div class="skeleton skeleton-text" style="width:80%;margin-bottom:0.5rem;"></div>
                    <div class="skeleton skeleton-text" style="width:65%;margin-bottom:0.5rem;"></div>
                    <div class="skeleton skeleton-text" style="width:75%;"></div>
                </div>
                <div id="users-list" style="display:none;"></div>
            </div>
        </div>

        {{-- ─── Danger Zone ─────────────────────────── --}}
        <div class="danger-zone">
            <div class="flex items-center gap-2 mb-3">
                <i data-lucide="alert-triangle" style="width:1rem;height:1rem;color:var(--color-danger);"></i>
                <h3 style="font-size:0.875rem; font-weight:700; color:var(--color-danger); margin:0; text-transform:uppercase; letter-spacing:0.04em;">Zona de Peligro</h3>
            </div>
            <p style="font-size:0.8125rem; color:var(--text-muted); margin-bottom:1rem;">Las siguientes acciones son irreversibles.</p>
            <button onclick="deleteAccount()" class="btn btn-outline-danger btn-block btn-sm">
                <i data-lucide="trash-2" style="width:0.875rem;height:0.875rem;"></i>
                Eliminar Cuenta Permanentemente
            </button>
        </div>

    </div>
</div>
{{-- Modal: Crear usuario (solo admin) --}}
<div id="create-user-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.7);align-items:flex-end;justify-content:center;">
    <div style="background:var(--bg-card);border-radius:1.25rem 1.25rem 0 0;width:100%;max-width:48rem;padding:1.5rem;padding-bottom:calc(1.5rem + env(safe-area-inset-bottom));">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <h3 style="font-size:1rem;font-weight:700;margin:0;">Crear nuevo usuario</h3>
            <button onclick="closeCreateUserModal()" class="btn btn-ghost btn-icon btn-sm">
                <i data-lucide="x" style="width:1.125rem;height:1.125rem;"></i>
            </button>
        </div>
        <div class="form-group">
            <label class="input-label">Nombre</label>
            <input type="text" id="cu-name" class="input" placeholder="Nombre completo">
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="input-label">Email</label>
            <input type="email" id="cu-email" class="input" placeholder="email@ejemplo.com">
        </div>
        <div class="form-group" style="margin-top:0.75rem;">
            <label class="input-label">Contraseña</label>
            <input type="password" id="cu-password" class="input" placeholder="Mínimo 8 caracteres con letras y números">
        </div>
        <div style="display:flex;gap:0.75rem;margin-top:1.25rem;">
            <button class="btn btn-ghost btn-block" onclick="closeCreateUserModal()">Cancelar</button>
            <button class="btn btn-primary btn-block" onclick="saveNewUser()">Crear usuario</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    auth.check();

    const $ = id => document.getElementById(id);

    async function loadData() {
        try {
            const [user, stats] = await Promise.all([
                apiCall('/user'),
                apiCall('/stats?t=' + Date.now()),
            ]);

            if (user) {
                $('profile-name').textContent    = user.name;
                $('profile-email').textContent   = user.email;
                $('profile-initial').textContent = user.name.charAt(0).toUpperCase();
                $('p-gender').value     = user.gender || 'male';
                $('p-age').value        = user.age || '';
                $('p-main-goal').value  = user.main_goal || 'strength';
                $('p-weight').value     = user.weight || '';
                $('p-height').value     = user.height || '';
                $('p-goal').value       = user.weekly_goal || 3;
                $('goal-val').textContent = user.weekly_goal || 3;

                // Usamos el flag directo de la API (no depende del localStorage)
                if (user.is_admin) {
                    $('admin-panel').style.display = 'block';
                    loadUsers();
                }
            }

            if (stats) {
                $('stat-workouts').textContent = stats.total_workouts || 0;
                $('stat-minutes').textContent  = stats.total_minutes  || 0;
                $('stat-streak').textContent   = (stats.weekly_streak || 0) + '🔥';
            }

            loadChart();
        } catch(e) {
            console.error(e);
        }
    }

    async function updateProfile(e) {
        e.preventDefault();
        try {
            await apiCall('/user/profile', 'PUT', {
                gender:      $('p-gender').value,
                age:         $('p-age').value    ? Number($('p-age').value)    : null,
                main_goal:   $('p-main-goal').value,
                weight:      $('p-weight').value ? Number($('p-weight').value) : null,
                height:      $('p-height').value ? Number($('p-height').value) : null,
                weekly_goal: Number($('p-goal').value),
            });
            showToast('Perfil actualizado');
            loadData();
        } catch(err) {
            showToast('Error al actualizar: ' + err.message, 'error');
        }
    }

    async function changePassword() {
        const current = $('cp-current').value.trim();
        const newPwd  = $('cp-new').value.trim();
        const confirm = $('cp-confirm').value.trim();
        const errEl   = $('cp-error');

        errEl.style.display = 'none';

        if (!current || !newPwd || !confirm) {
            errEl.textContent = 'Todos los campos son obligatorios.';
            errEl.style.display = 'block';
            return;
        }
        if (newPwd.length < 8) {
            errEl.textContent = 'La nueva contraseña debe tener al menos 8 caracteres.';
            errEl.style.display = 'block';
            return;
        }
        if (newPwd !== confirm) {
            errEl.textContent = 'Las contraseñas no coinciden.';
            errEl.style.display = 'block';
            return;
        }

        try {
            await apiCall('/user/password', 'PUT', {
                current_password:      current,
                new_password:          newPwd,
                new_password_confirmation: confirm,
            });
            showToast('Contraseña actualizada. Inicia sesión de nuevo.');
            $('cp-current').value = '';
            $('cp-new').value = '';
            $('cp-confirm').value = '';
            setTimeout(() => auth.logout(), 2500);
        } catch(err) {
            errEl.textContent = err.message || 'Error al actualizar la contraseña.';
            errEl.style.display = 'block';
        }
    }

    async function deleteAccount() {
        if (!confirm('¿ESTÁS SEGURO? Esto eliminará tu cuenta y todos tus datos. No se puede deshacer.')) return;
        try {
            await apiCall('/user', 'DELETE');
            showToast('Cuenta eliminada. Hasta luego.');
            setTimeout(() => auth.logout(), 2000);
        } catch(err) {
            showToast('Error: ' + err.message, 'error');
        }
    }

    async function loadChart() {
        try {
            const history = await apiCall('/user/weight-history');
            if (!history || !Array.isArray(history) || history.length === 0) {
                $('chart-empty').style.display = 'flex';
                return;
            }

            const labels = history.map(h => new Date(h.date).toLocaleDateString('es-ES', { day:'numeric', month:'short' }));
            const data   = history.map(h => h.weight);

            const ctx = $('weightChart').getContext('2d');
            const grad = ctx.createLinearGradient(0, 0, 0, 180);
            grad.addColorStop(0, 'rgba(245,158,11,0.25)');
            grad.addColorStop(1, 'rgba(245,158,11,0)');

            if (window._wChart) window._wChart.destroy();
            window._wChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        data,
                        borderColor: '#f59e0b',
                        backgroundColor: grad,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#18181b',
                        pointBorderColor: '#f59e0b',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#27272a',
                            titleColor: '#fafafa',
                            bodyColor: '#a1a1aa',
                            borderColor: '#3f3f46',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: { label: ctx => `${ctx.raw} kg` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#71717a', font: { size: 10 } } },
                        y: { grid: { color: '#27272a' }, ticks: { color: '#71717a', font: { size: 10 }, callback: v => v + 'kg' } }
                    }
                }
            });
        } catch(e) { console.error(e); }
    }

    // ── Heatmap ───────────────────────────────────────────────────────────────
    let heatmapYear = new Date().getFullYear();

    async function loadHeatmap() {
        $('heatmap-year').textContent = heatmapYear;
        $('heatmap-next-btn').style.opacity  = heatmapYear >= new Date().getFullYear() ? '0.3' : '1';
        $('heatmap-next-btn').disabled       = heatmapYear >= new Date().getFullYear();

        try {
            const res  = await apiCall('/stats/heatmap?year=' + heatmapYear);
            renderHeatmap(res.data || {});
        } catch(e) { console.error(e); }
    }

    function renderHeatmap(data) {
        const grid = $('heatmap-grid');
        grid.innerHTML = '';

        // Día 1 del año en el día de la semana correcto (Lunes=0 ... Dom=6)
        const jan1     = new Date(heatmapYear, 0, 1);
        const startDow = (jan1.getDay() + 6) % 7; // lunes = 0
        const isLeap   = (heatmapYear % 4 === 0 && heatmapYear % 100 !== 0) || heatmapYear % 400 === 0;
        const totalDays = isLeap ? 366 : 365;
        const totalCells = Math.ceil((totalDays + startDow) / 7) * 7;

        let totalWorkouts = 0;

        for (let i = 0; i < totalCells; i++) {
            const cell = document.createElement('div');
            cell.style.cssText = 'width:10px;height:10px;border-radius:2px;';

            const dayOffset = i - startDow;
            if (dayOffset < 0 || dayOffset >= totalDays) {
                cell.style.background = 'transparent';
            } else {
                const d    = new Date(heatmapYear, 0, 1 + dayOffset);
                const key  = d.toISOString().split('T')[0];
                const info = data[key];
                if (info) {
                    totalWorkouts += info.count;
                    const intensity = Math.min(info.count / 3, 1);
                    const alpha     = 0.2 + (intensity * 0.8);
                    cell.style.background = `rgba(245,158,11,${alpha.toFixed(2)})`;
                    cell.title = `${key}: ${info.count} entreno${info.count > 1 ? 's' : ''}, ${info.minutes} min`;
                } else {
                    cell.style.background = 'var(--bg-muted)';
                    cell.title = key;
                }
            }
            grid.appendChild(cell);
        }

        $('heatmap-total').textContent = totalWorkouts
            ? `${totalWorkouts} entreno${totalWorkouts > 1 ? 's' : ''} en ${heatmapYear}`
            : `Sin actividad en ${heatmapYear}`;
    }

    window.changeHeatmapYear = function(delta) {
        const currentYear = new Date().getFullYear();
        heatmapYear = Math.max(2020, Math.min(currentYear, heatmapYear + delta));
        loadHeatmap();
    };

    // ── Récords Personales ────────────────────────────────────────────────────
    async function loadRecords() {
        try {
            const records = await apiCall('/exercises/records');
            $('records-skeleton').style.display = 'none';

            if (!records || records.length === 0) {
                $('records-empty').style.display = 'flex';
                return;
            }

            const tbody = $('records-tbody');
            tbody.innerHTML = records.map(r => {
                const date = r.date ? new Date(r.date).toLocaleDateString('es-ES', { day:'2-digit', month:'short', year:'2-digit' }) : '–';
                const reps = r.reps ? `×${r.reps}` : '–';
                return `<tr style="border-top:1px solid var(--border-light);">
                    <td style="padding:0.5rem 0;font-weight:500;color:var(--text-primary);">${escapeHtml(r.name)}</td>
                    <td style="padding:0.5rem 0;text-align:right;font-weight:700;color:#fbbf24;">${r.max_weight} kg</td>
                    <td style="padding:0.5rem 0;text-align:right;color:var(--text-muted);">${reps}</td>
                    <td style="padding:0.5rem 0;text-align:right;color:var(--text-muted);font-size:0.75rem;">${date}</td>
                </tr>`;
            }).join('');

            $('records-list').style.display = 'block';
        } catch(e) {
            $('records-skeleton').style.display = 'none';
            $('records-empty').style.display = 'flex';
            console.error(e);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Logros ────────────────────────────────────────────────────────────────
    async function loadAchievements() {
        try {
            const res = await apiCall('/achievements');
            $('achievements-skeleton').style.display = 'none';

            $('achievements-counter').textContent = `${res.unlocked_count} / ${res.total_count}`;

            const grid  = $('achievements-grid');
            grid.style.display = 'grid';

            grid.innerHTML = res.achievements.map(a => {
                const locked = !a.unlocked;
                return `<div style="
                    border-radius:0.75rem;
                    border:1px solid ${locked ? 'var(--border-light)' : 'rgba(245,158,11,0.25)'};
                    background:${locked ? 'var(--bg-card)' : 'rgba(245,158,11,0.05)'};
                    padding:0.875rem 0.5rem;
                    text-align:center;
                    opacity:${locked ? '0.45' : '1'};
                    transition:opacity 0.2s;
                " title="${escapeHtml(a.description)}">
                    <div style="
                        width:2rem;height:2rem;border-radius:50%;
                        background:${locked ? 'var(--bg-muted)' : `${a.color}22`};
                        margin:0 auto 0.5rem;
                        display:flex;align-items:center;justify-content:center;
                    ">
                        <i data-lucide="${a.icon}" style="width:1rem;height:1rem;color:${locked ? 'var(--text-muted)' : a.color};"></i>
                    </div>
                    <p style="font-size:0.6875rem;font-weight:700;color:${locked ? 'var(--text-muted)' : 'var(--text-primary)'};margin:0;line-height:1.3;">${escapeHtml(a.name)}</p>
                </div>`;
            }).join('');

            lucide.createIcons();
        } catch(e) {
            $('achievements-skeleton').style.display = 'none';
            console.error(e);
        }
    }

    // ── Admin Panel ─────────────────────────────────────────────────────────
    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    async function loadUsers() {
        $('users-skeleton').style.display = 'block';
        $('users-list').style.display = 'none';
        try {
            const data = await apiCall('/admin/users');
            renderUsers(data.users || []);
        } catch(e) {
            $('users-skeleton').innerHTML = '<p style="font-size:0.8rem;color:var(--text-muted);">Error al cargar usuarios.</p>';
        }
    }

    function renderUsers(users) {
        $('users-skeleton').style.display = 'none';
        $('users-list').style.display = 'block';
        var html = '';
        users.forEach(function(u) {
            var adminBadge = u.is_admin
                ? '<span style="font-size:0.6rem;background:rgba(167,139,250,0.2);color:#a78bfa;padding:0.1rem 0.35rem;border-radius:999px;margin-left:0.25rem;">ADMIN</span>'
                : '';
            var deleteBtn = !u.is_admin
                ? '<button onclick="deleteUser(\'' + u.id + '\')" class="btn btn-ghost btn-icon btn-sm" style="color:var(--color-danger);"><i data-lucide="trash-2" style="width:1rem;height:1rem;"></i></button>'
                : '';
            html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0;border-bottom:1px solid var(--border-light);">'
                  + '<div>'
                  + '<div style="font-size:0.875rem;font-weight:600;">' + esc(u.name) + adminBadge + '</div>'
                  + '<div style="font-size:0.75rem;color:var(--text-muted);">' + esc(u.email) + '</div>'
                  + '</div>'
                  + deleteBtn
                  + '</div>';
        });
        $('users-list').innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function openCreateUserModal() {
        $('create-user-modal').style.display = 'flex';
    }
    function closeCreateUserModal() {
        $('create-user-modal').style.display = 'none';
        $('cu-name').value = '';
        $('cu-email').value = '';
        $('cu-password').value = '';
    }

    async function saveNewUser() {
        const name     = $('cu-name').value.trim();
        const email    = $('cu-email').value.trim();
        const password = $('cu-password').value;
        if (!name || !email || !password) { showToast('Rellena todos los campos', 'error'); return; }
        try {
            await apiCall('/admin/users', 'POST', { name, email, password });
            showToast('Usuario ' + name + ' creado');
            closeCreateUserModal();
            loadUsers();
        } catch(e) { /* apiCall muestra el toast de error */ }
    }

    async function deleteUser(userId) {
        if (!confirm('¿Eliminar este usuario? Esta acción es irreversible.')) return;
        try {
            await apiCall('/admin/users/' + userId, 'DELETE');
            showToast('Usuario eliminado');
            loadUsers();
        } catch(e) { /* apiCall muestra el toast de error */ }
    }

    loadData();
    loadHeatmap();
    loadRecords();
    loadAchievements();
</script>
@endpush
