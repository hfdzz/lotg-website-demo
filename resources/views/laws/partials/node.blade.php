<article class="node" data-type="{{ $node['node_type'] }}" data-depth="{{ $node['depth'] }}">
    @php
        $imageItems = collect($node['media_items'])->where('kind', 'image')->values();
        $videoItems = collect($node['media_items'])->where('kind', 'video')->values();
        $resourceItems = collect($node['resource_items'] ?? [])->values();
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

    @if ($resourceItems->isNotEmpty())
        <div class="resource-list-block">
            <ul class="resource-list">
                @foreach ($resourceItems as $resourceItem)
                    <li class="resource-item">
                        <a
                            class="resource-link"
                            href="{{ $resourceItem['url'] }}"
                            @if ($resourceItem['is_external']) target="_blank" rel="noreferrer" @endif
                        >
                            {{ $resourceItem['label'] }}
                        </a>
                        <span class="resource-meta">{{ $resourceItem['meta'] }}</span>
                        @if ($resourceItem['credit'])
                            <span class="resource-meta">| {{ $resourceItem['credit'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach ($node['children'] as $child)
        @include('laws.partials.node', ['node' => $child])
    @endforeach
</article>
