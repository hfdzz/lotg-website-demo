<section class="card edition-switcher">
    <form action="{{ route('admin.editions.go') }}" method="get" class="stack-form">
        <input type="hidden" name="target" value="{{ $editionSwitcherTarget ?? 'laws' }}">
        <label>
            <div class="law-meta">Working edition</div>
            <select name="edition" onchange="this.form.submit()">
                @if (! $selectedEdition)
                    <option value="">No active edition selected</option>
                @endif
                @foreach ($editions as $edition)
                    <option value="{{ $edition->id }}" @selected($selectedEdition?->id === $edition->id)>{{ $edition->name }}@if ($edition->is_active) (active) @endif</option>
                @endforeach
            </select>
        </label>
    </form>
</section>
