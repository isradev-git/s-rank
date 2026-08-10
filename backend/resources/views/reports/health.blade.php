@extends('layouts.app')

@section('title', 'Informe de salud')

@php
    $today = \Carbon\Carbon::today();
    $quick = [
        '7'  => ['7 días',  $today->copy()->subDays(6)],
        '30' => ['30 días', $today->copy()->subDays(29)],
        '90' => ['90 días', $today->copy()->subDays(89)],
    ];
    $pdfHref = '/informe-salud/pdf?date_from=' . $r['range']['from'] . '&date_to=' . $r['range']['to'];
    $imcColor = match ($r['patient']['imc_cat']) {
        'Normopeso' => '#4ade80',
        'Sobrepeso' => '#fbbf24',
        'Obesidad', 'Bajo peso' => '#f87171',
        default => 'var(--text-muted)',
    };
@endphp

@section('content')
<div class="pb-24">

    <div style="position:sticky; top:0; z-index:30; background:rgba(10,10,11,0.92); backdrop-filter:blur(16px);
                border-bottom:1px solid var(--border-light); padding:1rem 1.25rem;">
        <div class="flex items-center gap-3">
            <a href="/profile" class="btn btn-ghost btn-icon" aria-label="Volver" style="color:var(--text-muted);">
                <i data-lucide="arrow-left" style="width:1.25rem;height:1.25rem;"></i>
            </a>
            <div>
                <h1 style="font-size:1.25rem; font-weight:800; margin:0; color:var(--text-primary);">Informe de salud</h1>
                <p style="font-size:0.6875rem; color:var(--text-muted); margin:0;">{{ $r['range']['label'] }}</p>
            </div>
        </div>
    </div>

    <div class="p-5" style="display:flex;flex-direction:column;gap:1rem;">

        {{-- ── Rango de fechas + descarga ─────────────────────────────── --}}
        <div class="card" style="padding:1.25rem;">
            <form method="GET" action="/informe-salud">
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.625rem;">
                    <div>
                        <label for="date_from" style="display:block;font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Desde</label>
                        <input type="date" id="date_from" name="date_from" class="input" value="{{ $r['range']['from'] }}">
                    </div>
                    <div>
                        <label for="date_to" style="display:block;font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.25rem;">Hasta</label>
                        <input type="date" id="date_to" name="date_to" class="input" value="{{ $r['range']['to'] }}">
                    </div>
                </div>
                <div style="display:flex;gap:0.5rem;margin-top:0.75rem;flex-wrap:wrap;">
                    @foreach ($quick as $q)
                        <a href="/informe-salud?date_from={{ $q[1]->toDateString() }}&date_to={{ $today->toDateString() }}"
                           class="pill-tab">{{ $q[0] }}</a>
                    @endforeach
                    <button type="submit" class="btn btn-outline btn-sm" style="margin-left:auto;">
                        <i data-lucide="calendar-check" style="width:0.875rem;height:0.875rem;"></i> Aplicar
                    </button>
                </div>
            </form>
            <a href="{{ $pdfHref }}" class="btn btn-primary btn-block" style="margin-top:0.875rem;">
                <i data-lucide="file-down" style="width:1rem;height:1rem;"></i> Descargar PDF para el médico
            </a>
        </div>

        {{-- ── Paciente ───────────────────────────────────────────────── --}}
        <div class="card" style="padding:1.25rem;">
            <div class="section-header" style="margin-bottom:0.75rem;"><h3 class="section-title" style="margin:0;">Paciente</h3></div>
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.625rem;">
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['patient']['name'] }}</span><span class="stat-label">Nombre</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['patient']['age'] ?? '—' }}</span><span class="stat-label">Edad</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['patient']['gender'] }}</span><span class="stat-label">Sexo</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['patient']['height'] ? $r['patient']['height'].' cm' : '—' }}</span><span class="stat-label">Altura</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['patient']['weight'] ? $r['patient']['weight'].' kg' : '—' }}</span><span class="stat-label">Peso actual</span></div>
                <div class="stat-card" style="padding:0.75rem;">
                    <span class="stat-value" style="font-size:0.95rem;color:{{ $imcColor }};">{{ $r['patient']['imc'] ?? '—' }}</span>
                    <span class="stat-label">IMC{{ $r['patient']['imc_cat'] ? ' · '.$r['patient']['imc_cat'] : '' }}</span>
                </div>
            </div>
        </div>

        {{-- ── Peso ───────────────────────────────────────────────────── --}}
        <div class="card" style="padding:1.25rem;">
            <div class="section-header" style="margin-bottom:0.75rem;"><h3 class="section-title" style="margin:0;">Evolución de peso</h3></div>
            @if ($r['weight']['start'] !== null)
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.625rem;">
                    <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['weight']['start'] }} kg</span><span class="stat-label">Inicial</span></div>
                    <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['weight']['end'] }} kg</span><span class="stat-label">Final</span></div>
                    @php $ch = $r['weight']['change']; $chColor = $ch === null ? 'var(--text-muted)' : ($ch < 0 ? '#4ade80' : ($ch > 0 ? '#f87171' : 'var(--text-muted)')); @endphp
                    <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;color:{{ $chColor }};">{{ $ch === null ? '—' : ($ch > 0 ? '+' : '').$ch.' kg' }}</span><span class="stat-label">Cambio</span></div>
                </div>
                <canvas id="weightChart" style="margin-top:1rem;max-height:10rem;"></canvas>
            @else
                <div class="empty-state" style="padding:1.5rem 0;">
                    <div class="empty-state-icon"><i data-lucide="scale" style="width:1.25rem;height:1.25rem;"></i></div>
                    <p class="empty-state-title" style="font-size:0.875rem;">Sin registros de peso</p>
                    <p class="empty-state-desc" style="font-size:0.75rem;">Registra tu peso en el perfil para ver la evolución.</p>
                </div>
            @endif
        </div>

        {{-- ── Nutrición ──────────────────────────────────────────────── --}}
        <div class="card" style="padding:1.25rem;">
            <div class="section-header" style="margin-bottom:0.25rem;"><h3 class="section-title" style="margin:0;">Nutrición — media diaria</h3></div>
            <p style="margin:0 0 0.875rem;font-size:0.72rem;color:var(--text-muted);">
                {{ $r['nutrition']['logged_days'] }} de {{ $r['nutrition']['period_days'] }} días con registro
                @if ($r['nutrition']['adherence'] !== null)
                    · adherencia objetivo <strong style="color:{{ $r['nutrition']['adherence'] >= 80 && $r['nutrition']['adherence'] <= 110 ? '#4ade80' : '#fbbf24' }};">{{ $r['nutrition']['adherence'] }}%</strong>
                @endif
            </p>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0.625rem;">
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ number_format($r['nutrition']['avg_kcal'], 0, ',', '.') }}</span><span class="stat-label">kcal/día</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['nutrition']['avg_protein'] }} g</span><span class="stat-label">Proteína</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['nutrition']['avg_carbs'] }} g</span><span class="stat-label">Carbohidratos</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['nutrition']['avg_fat'] }} g</span><span class="stat-label">Grasas</span></div>
                <div class="stat-card" style="padding:0.75rem;border:1px solid rgba(52,211,153,0.2);"><span class="stat-value" style="font-size:0.95rem;color:#34d399;">{{ $r['nutrition']['avg_fiber'] }} g</span><span class="stat-label">Fibra</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['nutrition']['avg_sugar'] }} g</span><span class="stat-label">Azúcar</span></div>
            </div>
            <p style="margin:0.875rem 0 0;font-size:0.72rem;color:var(--text-muted);">Agua media: <strong>{{ number_format($r['nutrition']['avg_water'], 0, ',', '.') }} ml/día</strong>@if($r['nutrition']['goal_kcal']) · objetivo: <strong>{{ number_format($r['nutrition']['goal_kcal'], 0, ',', '.') }} kcal/día</strong>@endif</p>
        </div>

        {{-- ── Entrenos ───────────────────────────────────────────────── --}}
        <div class="card" style="padding:1.25rem;">
            <div class="section-header" style="margin-bottom:0.75rem;"><h3 class="section-title" style="margin:0;">Actividad física</h3></div>
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0.5rem;">
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['workouts']['total'] }}</span><span class="stat-label">Entrenos</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['workouts']['minutes'] }}</span><span class="stat-label">Minutos</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['workouts']['active_days'] }}</span><span class="stat-label">Días activos</span></div>
                <div class="stat-card" style="padding:0.75rem;"><span class="stat-value" style="font-size:0.95rem;">{{ $r['workouts']['avg_min'] }}</span><span class="stat-label">Media min</span></div>
            </div>
            @if (count($r['workouts']['by_mode']))
                <div style="display:flex;flex-direction:column;gap:0.5rem;margin-top:1rem;">
                    @foreach ($r['workouts']['by_mode'] as $m)
                        <div class="flex items-center justify-between" style="font-size:0.8125rem;">
                            <span>{{ $m['label'] }}</span>
                            <span style="color:var(--text-muted);">{{ $m['count'] }} entrenos · {{ $m['minutes'] }} min</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    auth.check();
    lucide.createIcons();

    @if ($r['weight']['start'] !== null)
    (function () {
        const logs = @json($r['weight']['logs']);
        const canvas = document.getElementById('weightChart');
        if (!canvas || !logs.length) return;
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: logs.map(l => {
                    const [y, m, d] = String(l.date).split('T')[0].split('-');
                    return new Date(y, m - 1, d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
                }),
                datasets: [{
                    label: 'Peso (kg)', data: logs.map(l => l.weight),
                    borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.15)',
                    borderWidth: 2, tension: 0.25, fill: true, pointRadius: 3,
                }],
            },
            options: {
                responsive: true, plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                },
            },
        });
    })();
    @endif
</script>
@endpush
