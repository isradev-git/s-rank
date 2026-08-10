@extends('layouts.app')

@section('content')
<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100svh; padding:1.5rem;">
    <div style="width:100%; max-width:28rem;">

        {{-- Logo --}}
        <div style="text-align:center; margin-bottom:2rem;">
            <div style="display:inline-flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                <div style="background:var(--color-primary); width:6px; height:32px; border-radius:3px;"></div>
                <h1 style="font-size:2rem; font-weight:900; font-style:italic; letter-spacing:-0.04em; color:var(--color-primary); margin:0; text-transform:uppercase;">FitLoop</h1>
            </div>
            <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">Tu compañero de entrenamiento.</p>
        </div>

        <div class="card" style="padding:1.75rem;">
            <h2 style="font-size:1.125rem; font-weight:700; margin:0 0 1.5rem; text-align:center;">Iniciar sesión</h2>

            <form id="login-form" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="login-email" required class="input" placeholder="correo@ejemplo.com" autocomplete="email">
                </div>
                <div class="form-group" style="margin-top:0.875rem;">
                    <label class="form-label">Contraseña</label>
                    <input type="password" id="login-password" required class="input" placeholder="••••••••" autocomplete="current-password">
                </div>

                <div id="login-error-msg"
                     style="display:none; margin-top:0.75rem; padding:0.75rem; border-radius:0.5rem;
                            background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2);
                            font-size:0.8125rem; color:#ef4444; text-align:center;"></div>

                <button type="submit" id="login-btn" class="btn btn-primary btn-block" style="margin-top:1.25rem; height:3rem; font-size:1rem;">
                    Entrar
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    // Limpiar sesión local al llegar al login
    auth.removeToken();
    localStorage.removeItem('user');

    async function handleLogin(e) {
        e.preventDefault();

        const email    = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;
        const btn      = document.getElementById('login-btn');
        const errorDiv = document.getElementById('login-error-msg');

        errorDiv.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Entrando...';

        try {
            const data = await apiCall('/auth/login', 'POST', { email, password }, {
                skipRedirect401: true,
                suppressErrorAlert: true,
            });

            if (data) {
                auth.setToken(data.access_token);
                auth.setUser({ name: data.user_name, is_admin: data.is_admin });
                window.location.href = '/';
            }
        } catch (error) {
            errorDiv.textContent   = 'Credenciales incorrectas. Verifica tu email y contraseña.';
            errorDiv.style.display = 'block';
            btn.disabled    = false;
            btn.textContent = 'Entrar';
        }
    }
</script>
@endsection

