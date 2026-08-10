@extends('layouts.app')

@section('content')
<div class="p-6 pb-24">
    <div class="card" style="padding:1.25rem;">
        <p style="font-size:0.75rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-muted);margin:0 0 0.5rem;">
            Acceso legacy
        </p>
        <h1 style="font-size:1.1rem;font-weight:800;margin:0 0 0.5rem;">Esta pantalla fue unificada</h1>
        <p style="font-size:0.875rem;color:var(--text-muted);margin:0 0 1rem;line-height:1.5;">
            El registro manual se integró en Entrenar para mantener una sola experiencia.
        </p>
        <a href="/training" class="btn btn-primary">Ir a Entrenar</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function redirectToTraining() {
    const target = '/training' + (window.location.search || '');
    if (window.location.pathname === '/log') {
        window.location.replace(target);
    }
})();
</script>
@endpush
