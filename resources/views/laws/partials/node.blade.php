<article class="node" data-type="{{ $node['node_type'] }}" data-depth="{{ $node['depth'] }}">
    @php
        $imageItems = collect($node['media_items'])->where('kind', 'image')->values();
        $videoItems = collect($node['media_items'])->where('kind', 'video')->values();
    @endphp

    @if ($node['title'])
        @include('laws.partials.title', ['tag' => $node['heading_tag'], 'title' => $node['title'], 'anchorId' => $node['anchor_id']])
    @endif

    @if ($node['body_html'])
        <div class="node-body">{!! $node['body_html'] !!}</div>
    @endif

    @if ($imageItems->isNotEmpty())
        <div class="media-grid">
            @foreach ($imageItems as $mediaItem)
                <figure class="media-frame">
                    <img src="{{ $mediaItem['src'] }}" alt="{{ $mediaItem['caption'] ?? $node['title'] ?? 'Illustration' }}">
                    @if ($mediaItem['caption'] || $mediaItem['credit'])
                        <figcaption class="media-caption">
                            {{ $mediaItem['caption'] }}
                            @if ($mediaItem['credit'])
                                (Credit: {{ $mediaItem['credit'] }})
                            @endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    @endif

    @if ($videoItems->isNotEmpty())
        <div class="video-stack">
            @foreach ($videoItems as $mediaItem)
                <figure class="media-frame video-frame">
                    <iframe
                        src="{{ $mediaItem['src'] }}"
                        title="{{ $mediaItem['caption'] ?? 'YouTube video' }}"
                        loading="lazy"
                        allowfullscreen
                    ></iframe>
                    @if ($mediaItem['caption'])
                        <figcaption class="media-caption">{{ $mediaItem['caption'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    @endif

    @foreach ($node['children'] as $child)
        @include('laws.partials.node', ['node' => $child])
    @endforeach
</article>
