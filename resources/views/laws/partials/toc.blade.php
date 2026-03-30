<ul class="toc-list">
    @foreach ($items as $item)
        <li class="toc-item" data-depth="{{ $item['depth'] }}">
            <a class="toc-link" href="#{{ $item['anchor_id'] }}" data-anchor="{{ $item['anchor_id'] }}">{{ $item['title'] }}</a>

            @if (count($item['children']) > 0)
                @include('laws.partials.toc', ['items' => $item['children']])
            @endif
        </li>
    @endforeach
</ul>
