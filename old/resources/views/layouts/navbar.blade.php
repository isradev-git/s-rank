<nav class="navbar">
    <div class="navbar-container">

        <a href="/" class="nav-item {{ request()->is('/') ? 'active' : '' }}" id="nav-home">
            <i data-lucide="home" style="width:1.375rem;height:1.375rem;"></i>
            <span>Inicio</span>
        </a>

        <a href="/history" class="nav-item {{ request()->is('history') ? 'active' : '' }}" id="nav-history">
            <i data-lucide="calendar" style="width:1.375rem;height:1.375rem;"></i>
            <span>Historial</span>
        </a>

        {{-- FAB central --}}
        <a href="/training" class="nav-item {{ request()->is('training') || request()->is('log') ? 'active' : '' }}" id="nav-log" style="position:relative;">
            <div class="nav-fab">
                <i data-lucide="plus" style="width:1.375rem;height:1.375rem;"></i>
            </div>
            <span>Entrenar</span>
        </a>

        <a href="/nutrition" class="nav-item {{ request()->is('nutrition*') ? 'active' : '' }}" id="nav-nutrition">
            <i data-lucide="salad" style="width:1.375rem;height:1.375rem;"></i>
            <span>Nutrición</span>
        </a>

        <a href="/profile" class="nav-item {{ request()->is('profile') ? 'active' : '' }}" id="nav-profile">
            <i data-lucide="user" style="width:1.375rem;height:1.375rem;"></i>
            <span>Perfil</span>
        </a>

    </div>
</nav>
