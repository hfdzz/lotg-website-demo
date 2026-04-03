@switch($tag)
    @case('h2')
        <h2 id="{{ $anchorId }}" class="node-title">
            <span class="node-title-text">
            @if (! empty($sectionNumber))
                {{ $sectionNumber }}
            @endif
            {{ $title }}</span>
        </h2>
        @break
    @case('h3')
        <h3 id="{{ $anchorId }}" class="node-title">
            <span class="node-title-text">{{ $title }}</span>
        </h3>
        @break
    @case('h4')
        <h4 id="{{ $anchorId }}" class="node-title">
            <span class="node-title-text">{{ $title }}</span>
        </h4>
        @break
    @default
        <h5 id="{{ $anchorId }}" class="node-title">
            <span class="node-title-text">{{ $title }}</span>
        </h5>
@endswitch
