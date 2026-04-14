<div class="law-jump-card {{ $class ?? '' }}">
    @unless (! empty($compact ?? false))
        <h2 class="law-jump-title">{{ __('site.laws.jump_title') }}</h2>
    @endunless

    <form class="law-jump-form {{ ! empty($compact ?? false) ? 'law-jump-form-compact' : '' }}" method="get" action="{{ route('laws.jump') }}" data-nav-pending-form>
        <input type="hidden" name="lang" value="{{ $language }}">
        <input type="hidden" name="edition" value="{{ $law->edition_id }}">

        <label>
            <span class="sr-only">{{ __('site.laws.jump_label') }}</span>
            <select name="law" aria-label="{{ __('site.laws.jump_label') }}" data-nav-pending-control data-nav-submit-on-change onchange="this.form.submit()">
                @foreach ($jumpLaws as $jumpLaw)
                    <option value="{{ $jumpLaw->id }}" @selected($jumpLaw->id === $law->id)>
                        {{ __('site.laws.law_number', ['number' => $jumpLaw->law_number]) }}: {{ $jumpLaw->displayTitle($language) }}
                    </option>
                @endforeach
            </select>
        </label>
    </form>
</div>
