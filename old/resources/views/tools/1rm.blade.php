@extends('layouts.app')

@section('content')
<div class="p-6 pb-24 space-y-6">
    <div class="flex items-center gap-3">
        <a href="/" class="text-zinc-400 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </a>
        <h1 class="text-2xl font-bold">Calculadora 1RM</h1>
    </div>

    <div class="card p-6 space-y-6">
        <div class="space-y-4">
            <div>
                <label class="text-xs text-muted font-bold uppercase tracking-wider">Peso levantado (kg)</label>
                <input type="number" id="rm-weight" class="input mt-1 text-lg font-bold" placeholder="E.g. 100">
            </div>
            <div>
                <label class="text-xs text-muted font-bold uppercase tracking-wider">Repeticiones realizadas</label>
                <input type="number" id="rm-reps" class="input mt-1 text-lg font-bold" placeholder="E.g. 5">
            </div>

            <button onclick="calculateOneRM()" class="btn btn-primary w-full py-3 font-bold transition-colors">
                Calcular
            </button>

            <p id="rm-error" class="hidden text-center text-sm" style="color:var(--color-warning, #eab308);"></p>
        </div>

        <div id="rm-result" class="hidden text-center space-y-2 border-t border-white/10 pt-6">
            <p class="text-muted">Tu 1RM estimado es:</p>
            <p class="text-4xl font-bold text-blue-500"><span id="rm-value">0</span> kg</p>

            <div class="grid grid-cols-3 gap-2 mt-6 text-sm">
                <div class="bg-white/5 p-2 rounded">
                    <p class="text-muted text-xs">95%</p>
                    <p class="font-bold text-white" id="p-95">0</p>
                </div>
                <div class="bg-white/5 p-2 rounded">
                    <p class="text-muted text-xs">90%</p>
                    <p class="font-bold text-white" id="p-90">0</p>
                </div>
                <div class="bg-white/5 p-2 rounded">
                    <p class="text-muted text-xs">85%</p>
                    <p class="font-bold text-white" id="p-85">0</p>
                </div>
                <div class="bg-white/5 p-2 rounded">
                    <p class="text-muted text-xs">80%</p>
                    <p class="font-bold text-white" id="p-80">0</p>
                </div>
                <div class="bg-white/5 p-2 rounded">
                    <p class="text-muted text-xs">75%</p>
                    <p class="font-bold text-white" id="p-75">0</p>
                </div>
                <div class="bg-white/5 p-2 rounded">
                    <p class="text-muted text-xs">70%</p>
                    <p class="font-bold text-white" id="p-70">0</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    auth.check();

    function calculateOneRM() {
        const weight = parseFloat(document.getElementById('rm-weight').value);
        const reps   = parseInt(document.getElementById('rm-reps').value, 10);
        const errorEl = document.getElementById('rm-error');

        errorEl.textContent = '';
        errorEl.classList.add('hidden');

        if (!weight || weight <= 0) {
            errorEl.textContent = 'Introduce un peso mayor que 0.';
            errorEl.classList.remove('hidden');
            return;
        }
        if (!reps || reps <= 0 || !Number.isInteger(reps)) {
            errorEl.textContent = 'Introduce un número de repeticiones válido (entero positivo).';
            errorEl.classList.remove('hidden');
            return;
        }
        if (reps === 1) {
            errorEl.textContent = 'Con 1 repetición el resultado ES tu 1RM directamente.';
            errorEl.classList.remove('hidden');
            document.getElementById('rm-value').innerText = Math.round(weight);
            document.getElementById('rm-result').classList.remove('hidden');
            updatePercentages(Math.round(weight));
            return;
        }
        if (reps > 30) {
            errorEl.textContent = 'Aviso: con más de 30 repeticiones la estimación de Epley es menos precisa.';
            errorEl.classList.remove('hidden');
        }

        // Fórmula de Epley: 1RM = weight × (1 + reps / 30)
        const oneRM = Math.round(weight * (1 + reps / 30));
        document.getElementById('rm-value').innerText = oneRM;
        updatePercentages(oneRM);
        document.getElementById('rm-result').classList.remove('hidden');
    }

    function updatePercentages(oneRM) {
        document.getElementById('p-95').innerText = Math.round(oneRM * 0.95) + ' kg';
        document.getElementById('p-90').innerText = Math.round(oneRM * 0.90) + ' kg';
        document.getElementById('p-85').innerText = Math.round(oneRM * 0.85) + ' kg';
        document.getElementById('p-80').innerText = Math.round(oneRM * 0.80) + ' kg';
        document.getElementById('p-75').innerText = Math.round(oneRM * 0.75) + ' kg';
        document.getElementById('p-70').innerText = Math.round(oneRM * 0.70) + ' kg';
    }
</script>
@endpush
