@extends('layouts.app')

@push('styles')
<style>
    .timer-page {
        min-height: 80dvh;
        display: flex;
        flex-direction: column;
        align-items: center;
        /* justify-content: center; */
        padding-top: 2rem;
    }

    .timer-circle-container {
        position: relative;
        width: 280px;
        height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 3rem;
    }

    /* Glass Effect for Ring Background */
    .timer-bg-ring {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: radial-gradient(closest-side, rgba(255,183,3,0.08) 0%, rgba(0,0,0,0) 100%);
    }

    .timer-text {
        font-size: 4rem;
        font-weight: 800;
        font-family: monospace;
        letter-spacing: -2px;
        line-height: 1;
        z-index: 10;
        text-shadow: 0 4px 12px rgba(0,0,0,0.5);
    }

    .timer-status {
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--color-primary);
        margin-top: 0.5rem;
        text-align: center;
    }

    .controls-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
        width: 100%;
        max-width: 400px;
        margin-bottom: 1.5rem;
    }

    .btn-pill {
        background-color: rgba(255, 255, 255, 0.05); /* bg-muted substitute */
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #a1a1aa; /* text-muted */
        border-radius: 0.5rem;
        padding: 0.75rem 0;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn-pill:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-color: var(--color-primary);
    }

    .btn-pill:active {
        transform: scale(0.95);
    }

    .custom-input-wrapper {
        background-color: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        max-width: 400px;
        margin-bottom: 2rem;
    }

    .custom-input {
        background: transparent;
        border: none;
        color: white;
        font-size: 1.25rem;
        font-weight: 700;
        width: 80px;
        text-align: right;
        outline: none;
    }

    .main-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        width: 100%;
        max-width: 400px;
    }

    .btn-main {
        padding: 1rem;
        border-radius: 0.75rem;
        font-size: 1.125rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="p-6 pb-24">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="/" class="text-zinc-400 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </a>
        <h1 class="text-xl font-bold">Temporizador</h1>
    </div>

    <div class="timer-page">
        <!-- Circular Timer -->
        <div class="timer-circle-container">
            <div class="timer-bg-ring"></div>
            
            <!-- SVG Ring -->
            <svg class="absolute inset-0 w-full h-full -rotate-90 pointer-events-none" viewBox="0 0 110 110" style="overflow: visible;">
                <!-- Track -->
                <circle cx="55" cy="55" r="48" fill="transparent" stroke="rgba(255,255,255,0.05)" stroke-width="4" />
                <!-- Progress -->
                <circle id="progress-ring" cx="55" cy="55" r="48" fill="transparent" stroke="var(--color-primary)" 
                        stroke-width="4" stroke-linecap="round" stroke-dasharray="301" stroke-dashoffset="0"
                        style="transition: stroke-dashoffset 0.1s linear; filter: drop-shadow(0 0 6px var(--color-primary));"></circle>
            </svg>

            <div class="flex-col items-center justify-center relative z-10 text-center">
                <div id="timer-display" class="timer-text text-white">00:00</div>
                <div id="timer-status" class="timer-status">LISTO</div>
            </div>
        </div>

        <!-- Quick Controls -->
        <div class="controls-grid">
            <button onclick="addTime(30)" class="btn-pill">+30s</button>
            <button onclick="addTime(60)" class="btn-pill">+1m</button>
            <button onclick="addTime(90)" class="btn-pill">1m30</button>
            <button onclick="addTime(120)" class="btn-pill">+2m</button>
        </div>

        <!-- Custom Input -->
        <div class="custom-input-wrapper">
            <span class="text-xs font-bold text-muted uppercase tracking-wider">Personalizado</span>
            <div class="flex items-center gap-2">
                <input type="number" id="custom-seconds" class="custom-input" placeholder="0" value="60">
                <span class="text-xs font-bold text-muted">SEG</span>
                <button onclick="setCustomTime()" class="btn btn-primary p-2 ml-2 rounded-lg" style="padding: 0.4rem;">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Main Buttons -->
        <div class="main-actions">
            <button id="btn-toggle" onclick="toggleTimer()" 
                    class="btn btn-primary btn-main shadow-lg">
                <i data-lucide="play" class="fill-current w-6 h-6"></i> Iniciar
            </button>
            
            <button onclick="resetTimer()" 
                    class="btn btn-outline btn-main">
                <i data-lucide="rotate-ccw" class="w-6 h-6"></i> Reset
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Config
    const CIRCLE_RADIUS = 48;
    const CIRCLE_CIRCUMFERENCE = 2 * Math.PI * CIRCLE_RADIUS; // ~301

    let totalSeconds = 60;
    let remainingSeconds = 60;
    let timerInterval = null;
    let isRunning = false;
    let endTime = null;

    // Elements
    const displayEl = document.getElementById('timer-display');
    const statusEl  = document.getElementById('timer-status');
    const ringEl    = document.getElementById('progress-ring');
    const btnToggle = document.getElementById('btn-toggle');

    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.18, 0.36].forEach(t => {
                const osc = ctx.createOscillator();
                const g   = ctx.createGain();
                osc.connect(g); g.connect(ctx.destination);
                osc.frequency.value = 880;
                g.gain.setValueAtTime(0.35, ctx.currentTime + t);
                g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + t + 0.15);
                osc.start(ctx.currentTime + t);
                osc.stop(ctx.currentTime + t + 0.15);
            });
        } catch(e) {}
    }

    // Init
    ringEl.style.strokeDasharray = CIRCLE_CIRCUMFERENCE;
    ringEl.style.strokeDashoffset = 0;
    lucide.createIcons();
    updateDisplay();

    function updateDisplay() {
        // Ceiling the total seconds first to avoid 1:60 issues
        const totalCeil = Math.ceil(remainingSeconds);
        
        const m = Math.floor(totalCeil / 60).toString().padStart(2, '0');
        const s = (totalCeil % 60).toString().padStart(2, '0');
        
        displayEl.innerText = `${m}:${s}`;

        if (totalSeconds > 0) {
            const progress = Math.max(0, remainingSeconds / totalSeconds);
            const offset = CIRCLE_CIRCUMFERENCE - (progress * CIRCLE_CIRCUMFERENCE);
            ringEl.style.strokeDashoffset = offset;
        }
    }

    window.addTime = function(seconds) {
        if (isRunning) return; 
        totalSeconds = seconds;
        remainingSeconds = seconds;
        
        // Reset UI
        ringEl.style.strokeDashoffset = 0; 
        statusEl.innerText = 'LISTO';
        statusEl.style.color = '#a1a1aa'; // var(--text-muted) in hex
        displayEl.style.color = 'white';
        
        updateDisplay();
    }

    window.setCustomTime = function() {
        const val = Number(document.getElementById('custom-seconds').value);
        if (val > 0) addTime(val);
    }

    window.toggleTimer = function() {
        if (isRunning) pauseTimer();
        else startTimer();
    }

    function startTimer() {
        if (remainingSeconds <= 0) return;

        isRunning = true;
        const now = Date.now();
        const durationMs = remainingSeconds * 1000;
        endTime = now + durationMs;

        statusEl.innerText = 'EN CURSO';
        statusEl.style.color = 'var(--color-primary)';
        
        btnToggle.className = 'btn btn-outline btn-main';
        btnToggle.innerHTML = '<i data-lucide="pause" class="fill-current w-6 h-6"></i> Pausa';
        lucide.createIcons();

        timerInterval = setInterval(() => {
            const current = Date.now();
            const leftMs = endTime - current;
            remainingSeconds = leftMs / 1000;

            if (remainingSeconds <= 0) {
                remainingSeconds = 0;
                completeTimer();
            } else {
                updateDisplay();
            }
        }, 16);
    }

    function pauseTimer() {
        isRunning = false;
        clearInterval(timerInterval);
        remainingSeconds = Math.ceil(remainingSeconds);
        
        statusEl.innerText = 'PAUSADO';
        statusEl.style.color = 'rgb(234, 179, 8)'; // Warning yellow
        
        btnToggle.className = 'btn btn-primary btn-main shadow-lg';
        btnToggle.innerHTML = '<i data-lucide="play" class="fill-current w-6 h-6"></i> Continuar';
        lucide.createIcons();
    }

    window.resetTimer = function() {
        pauseTimer();
        remainingSeconds = totalSeconds;
        
        statusEl.innerText = 'LISTO';
        statusEl.style.color = '#a1a1aa';
        
        displayEl.style.color = 'white';
        ringEl.style.strokeDashoffset = 0;
        
        btnToggle.className = 'btn btn-primary btn-main shadow-lg';
        btnToggle.innerHTML = '<i data-lucide="play" class="fill-current w-6 h-6"></i> Iniciar';
        
        updateDisplay();
        lucide.createIcons();
    }

    function completeTimer() {
        pauseTimer();
        remainingSeconds = 0;
        updateDisplay();
        
        statusEl.innerText = 'TERMINADO';
        statusEl.style.color = '#4ade80'; // var(--color-success) hex approx
        displayEl.style.color = '#4ade80';
        
        ringEl.style.strokeDashoffset = CIRCLE_CIRCUMFERENCE;
        
        if (navigator.vibrate) navigator.vibrate([200, 100, 200, 100, 500]);
        try { playBeep(); } catch(e) {}

        btnToggle.innerHTML = '<i data-lucide="rotate-ccw" class="w-6 h-6"></i> Reiniciar';
        btnToggle.onclick = function() {
            resetTimer();
            btnToggle.onclick = toggleTimer; // restore
        };
        lucide.createIcons();
    }
</script>
@endpush
