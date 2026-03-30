<article class="node" data-type="{{ $node['node_type'] }}" data-depth="{{ $node['depth'] }}">
    @if ($node['title'])
        @include('laws.partials.title', ['tag' => $node['heading_tag'], 'title' => $node['title'], 'anchorId' => $node['anchor_id']])
    @endif

    @if ($node['body_html'])
        <div class="node-body">{!! $node['body_html'] !!}</div>
    @endif

    @if (count($node['media_items']) > 0)
        <div class="media-grid">
            @foreach ($node['media_items'] as $mediaItem)
                @if ($mediaItem['kind'] === 'image')
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
                @endif

                @if ($mediaItem['kind'] === 'video')
                    <figure class="media-frame">
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
                @endif
            @endforeach
        </div>
    @endif

    @foreach ($node['children'] as $child)
        @include('laws.partials.node', ['node' => $child])
    @endforeach
</article>
