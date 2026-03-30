@switch($tag)
    @case('h2')
        <h2 class="node-title">{{ $title }}</h2>
        @break
    @case('h3')
        <h3 class="node-title">{{ $title }}</h3>
        @break
    @case('h4')
        <h4 class="node-title">{{ $title }}</h4>
        @break
    @default
        <h5 class="node-title">{{ $title }}</h5>
@endswitch
