<ul class="toc-list">
    @foreach ($items as $item)
        <li class="toc-item" data-depth="{{ $item['depth'] }}">
            <a class="toc-link" href="#{{ $item['anchor_id'] }}" data-anchor="{{ $item['anchor_id'] }}">
                <span class="toc-text">
                @if (! empty($item['section_number']) && $item['depth'] === 0)
                    {{ $item['section_number'] }}
                @endif
                {{ $item['title'] }}</span>
            </a>

            @if (count($item['children']) > 0)
                @include('laws.partials.toc', ['items' => $item['children']])
            @endif
        </li>
    @endforeach
</ul>
