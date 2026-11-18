@php($u = auth()->user())
<div class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 text-sm">
        <nav class="flex flex-wrap gap-1" aria-label="Navigasi panel">
            @php($tautan = match (true) {
                $u->isOperator() => [['dataset.index', 'Dataset OPD', 'dataset.*|pratinjau.*'], ['dataset.create', 'Unggah dataset', 'dataset.create']],
                $u->isWalidata() => [['pemeriksaan.index', 'Pemeriksaan', 'pemeriksaan.*|dataset.show']],
                $u->isAdmin() => [['pengguna.index', 'Pengguna', 'pengguna.*']],
                default => [],
            })
            @foreach($tautan as [$rute, $label, $pola])
                @php($aktif = collect(explode('|', $pola))->contains(fn ($p) => request()->routeIs($p)) && ! ($rute === 'dataset.index' && request()->routeIs('dataset.create')))
                <a href="{{ route($rute) }}" class="rounded-md px-3 py-1.5 font-medium {{ $aktif ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <p class="text-slate-500">
            {{ $u->name }} · {{ $u->role->label() }}@if($u->opd) · {{ $u->opd->singkatan }}@endif
        </p>
    </div>
</div>
