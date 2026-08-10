<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0a0a0b">
    <title>S-RANK</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- CSS Design System (styles.css imports variables, base, layout, components) -->
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

    <!-- API & Utils -->
    <script>const API_BASE = '/api';</script>
    <script src="{{ asset('assets/js/utils.js') }}"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0a0a0b;
            color: #fafafa;
        }
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 9999px; }
    </style>

    @stack('styles')
</head>

<body class="min-h-screen pb-20">
    <div class="max-w-md mx-auto min-h-screen relative md:border-x" style="border-color:var(--border-light); background:var(--bg-background); box-shadow:0 0 60px rgba(0,0,0,0.8);">

        @yield('content')

    </div>

    <!-- Global Toast -->
    <div id="toast-container"
         style="position:fixed; top:1.25rem; left:50%; transform:translateX(-50%); z-index:9999;
                display:flex; flex-direction:column; gap:0.5rem; width:calc(100% - 2rem); max-width:22rem;
                pointer-events:none;"></div>

    <script>
        lucide.createIcons();

        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast     = document.createElement('div');

            const config = {
                success: { bg: 'rgba(34,197,94,0.12)',  border: 'rgba(34,197,94,0.25)',  icon: 'check-circle',    text: '#22c55e' },
                error:   { bg: 'rgba(239,68,68,0.12)',  border: 'rgba(239,68,68,0.25)',  icon: 'alert-circle',    text: '#ef4444' },
                info:    { bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.25)', icon: 'info',            text: '#3b82f6' },
            };
            const c = config[type] || config.success;

            toast.style.cssText = `
                pointer-events:auto;
                display:flex; align-items:center; gap:0.625rem;
                padding:0.875rem 1rem;
                border-radius:0.875rem;
                border:1px solid ${c.border};
                background:${c.bg};
                backdrop-filter:blur(20px);
                box-shadow:0 8px 24px rgba(0,0,0,0.4);
                transform:translateY(-10px) scale(0.97);
                opacity:0;
                transition:all 0.25s cubic-bezier(0.34,1.56,0.64,1);
            `;

            toast.innerHTML = `
                <i data-lucide="${c.icon}" style="width:1.125rem;height:1.125rem;color:${c.text};flex-shrink:0;"></i>
                <p style="font-size:0.875rem;font-weight:600;color:#fafafa;margin:0;line-height:1.3;">${message}</p>
            `;

            container.appendChild(toast);
            lucide.createIcons();

            requestAnimationFrame(() => requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0) scale(1)';
                toast.style.opacity   = '1';
            }));

            setTimeout(() => {
                toast.style.transform = 'translateY(-8px) scale(0.97)';
                toast.style.opacity   = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3200);
        };
    </script>

    @stack('scripts')

    {{-- FEAT-8: Service Worker --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
