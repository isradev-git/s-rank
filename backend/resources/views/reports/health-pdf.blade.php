<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 24px 32px; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #1a1a1a; font-size: 11px; line-height: 1.45; }
    h1 { font-size: 18px; margin: 0; }
    h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #b45309;
         border-bottom: 1.5px solid #f59e0b; padding-bottom: 3px; margin: 18px 0 8px; }
    .brand { color: #b45309; font-weight: bold; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; }
    .grid td { padding: 6px 8px; border: 1px solid #e2e2e2; vertical-align: top; }
    .grid .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; color: #888; display: block; }
    .grid .val { font-size: 13px; font-weight: bold; }
    .data th { background: #f5f5f5; text-align: left; padding: 4px 6px; border: 1px solid #e2e2e2; font-size: 9px; text-transform: uppercase; color: #555; }
    .data td { padding: 4px 6px; border: 1px solid #e2e2e2; }
    .data td.num { text-align: right; }
    .footer { margin-top: 22px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 9px; color: #999; }
    .tag { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; }
</style>
</head>
<body>

    <table style="margin-bottom:4px;">
        <tr>
            <td><h1><span class="brand">FitLoop</span> · Informe de salud</h1></td>
            <td style="text-align:right;" class="muted">Generado: {{ $r['range']['generated'] }}</td>
        </tr>
    </table>
    <p class="muted" style="margin:0;">Periodo: <strong>{{ $r['range']['label'] }}</strong> ({{ $r['range']['days'] }} días)</p>

    {{-- ── Paciente ── --}}
    <h2>Paciente</h2>
    <table class="grid">
        <tr>
            <td><span class="lbl">Nombre</span><span class="val">{{ $r['patient']['name'] }}</span></td>
            <td><span class="lbl">Edad</span><span class="val">{{ $r['patient']['age'] ?? '—' }}</span></td>
            <td><span class="lbl">Sexo</span><span class="val">{{ $r['patient']['gender'] }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Altura</span><span class="val">{{ $r['patient']['height'] ? $r['patient']['height'].' cm' : '—' }}</span></td>
            <td><span class="lbl">Peso actual</span><span class="val">{{ $r['patient']['weight'] ? $r['patient']['weight'].' kg' : '—' }}</span></td>
            <td><span class="lbl">IMC</span><span class="val">{{ $r['patient']['imc'] ?? '—' }}@if($r['patient']['imc_cat']) <span class="muted" style="font-weight:normal;font-size:10px;">({{ $r['patient']['imc_cat'] }})</span>@endif</span></td>
        </tr>
    </table>

    {{-- ── Peso ── --}}
    <h2>Evolución de peso</h2>
    @if ($r['weight']['start'] !== null)
        <table class="grid" style="margin-bottom:8px;">
            <tr>
                <td><span class="lbl">Peso inicial</span><span class="val">{{ $r['weight']['start'] }} kg</span></td>
                <td><span class="lbl">Peso final</span><span class="val">{{ $r['weight']['end'] }} kg</span></td>
                <td><span class="lbl">Cambio</span><span class="val">{{ $r['weight']['change'] === null ? '—' : ($r['weight']['change'] > 0 ? '+' : '').$r['weight']['change'].' kg' }}</span></td>
            </tr>
        </table>
        @if (count($r['weight']['logs']) > 1)
            <table class="data">
                <thead><tr><th>Fecha</th><th>Peso (kg)</th></tr></thead>
                <tbody>
                    @foreach ($r['weight']['logs'] as $w)
                        <tr><td>{{ \Carbon\Carbon::parse($w['date'])->format('d/m/Y') }}</td><td class="num">{{ $w['weight'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <p class="muted">Sin registros de peso en el periodo.</p>
    @endif

    {{-- ── Nutrición ── --}}
    <h2>Nutrición — media diaria</h2>
    <p class="muted" style="margin:0 0 8px;">
        {{ $r['nutrition']['logged_days'] }} de {{ $r['nutrition']['period_days'] }} días con registro
        @if ($r['nutrition']['goal_kcal']) · objetivo {{ number_format($r['nutrition']['goal_kcal'], 0, ',', '.') }} kcal/día @endif
        @if ($r['nutrition']['adherence'] !== null) · adherencia {{ $r['nutrition']['adherence'] }}% @endif
    </p>
    <table class="grid">
        <tr>
            <td><span class="lbl">Calorías</span><span class="val">{{ number_format($r['nutrition']['avg_kcal'], 0, ',', '.') }} kcal</span></td>
            <td><span class="lbl">Proteína</span><span class="val">{{ $r['nutrition']['avg_protein'] }} g</span></td>
            <td><span class="lbl">Carbohidratos</span><span class="val">{{ $r['nutrition']['avg_carbs'] }} g</span></td>
            <td><span class="lbl">Grasas</span><span class="val">{{ $r['nutrition']['avg_fat'] }} g</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Fibra</span><span class="val">{{ $r['nutrition']['avg_fiber'] }} g</span></td>
            <td><span class="lbl">Azúcar</span><span class="val">{{ $r['nutrition']['avg_sugar'] }} g</span></td>
            <td colspan="2"><span class="lbl">Agua</span><span class="val">{{ number_format($r['nutrition']['avg_water'], 0, ',', '.') }} ml/día</span></td>
        </tr>
    </table>

    @if (count($r['nutrition']['days']))
        <table class="data" style="margin-top:8px;">
            <thead><tr><th>Fecha</th><th>kcal</th><th>Prot.</th><th>Carb.</th><th>Grasa</th><th>Fibra</th><th>Azúcar</th></tr></thead>
            <tbody>
                @foreach ($r['nutrition']['days'] as $d)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($d['date'])->format('d/m/Y') }}</td>
                        <td class="num">{{ number_format($d['kcal'], 0, ',', '.') }}</td>
                        <td class="num">{{ $d['protein'] }}</td>
                        <td class="num">{{ $d['carbs'] }}</td>
                        <td class="num">{{ $d['fat'] }}</td>
                        <td class="num">{{ $d['fiber'] }}</td>
                        <td class="num">{{ $d['sugar'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── Actividad física ── --}}
    <h2>Actividad física</h2>
    <table class="grid" style="margin-bottom:8px;">
        <tr>
            <td><span class="lbl">Entrenos</span><span class="val">{{ $r['workouts']['total'] }}</span></td>
            <td><span class="lbl">Minutos totales</span><span class="val">{{ $r['workouts']['minutes'] }}</span></td>
            <td><span class="lbl">Días activos</span><span class="val">{{ $r['workouts']['active_days'] }}</span></td>
            <td><span class="lbl">Media por sesión</span><span class="val">{{ $r['workouts']['avg_min'] }} min</span></td>
        </tr>
    </table>
    @if (count($r['workouts']['by_mode']))
        <table class="data">
            <thead><tr><th>Tipo</th><th>Entrenos</th><th>Minutos</th></tr></thead>
            <tbody>
                @foreach ($r['workouts']['by_mode'] as $m)
                    <tr><td>{{ $m['label'] }}</td><td class="num">{{ $m['count'] }}</td><td class="num">{{ $m['minutes'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Informe generado automáticamente por FitLoop a partir de los registros del usuario. Los valores nutricionales son estimaciones basadas en los alimentos registrados y no sustituyen una valoración profesional.
    </div>

</body>
</html>
