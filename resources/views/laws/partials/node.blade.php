@php
    $translation = $node['translation'];
    $title = $translation?->title;
    $bodyHtml = $translation?->body_html;
    $headingTag = match (min($node['depth'], 3)) {
        0 => 'h2',
        1 => 'h3',
        2 => 'h4',
        default => 'h5',
    };

    $youtubeIdFromUrl = function (?string $url): ?string {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/youtube\.com\/watch\?v=([A-Za-z0-9_-]+)/',
            '/youtu\.be\/([A-Za-z0-9_-]+)/',
            '/youtube\.com\/embed\/([A-Za-z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    };
@endphp

<article class="node" data-type="{{ $node['node_type'] }}">
    @if ($node['node_type'] === 'section' && $title)
        @if ($headingTag === 'h2')
            <h2 class="node-title">{{ $title }}</h2>
        @elseif ($headingTag === 'h3')
            <h3 class="node-title">{{ $title }}</h3>
        @elseif ($headingTag === 'h4')
            <h4 class="node-title">{{ $title }}</h4>
        @else
            <h5 class="node-title">{{ $title }}</h5>
        @endif
    @elseif ($title)
        <h3 class="node-title">{{ $title }}</h3>
    @endif

    @if ($bodyHtml)
        <div class="node-body">{!! $bodyHtml !!}</div>
    @endif

    @if (in_array($node['node_type'], ['image', 'video_group'], true) && count($node['media_assets']) > 0)
        <div class="media-grid">
            @foreach ($node['media_assets'] as $mediaAsset)
                @if ($node['node_type'] === 'image' && $mediaAsset->file_path)
                    <figure class="media-frame">
                        <img src="{{ asset($mediaAsset->file_path) }}" alt="{{ $mediaAsset->caption ?? $title ?? 'Illustration' }}">
                        @if ($mediaAsset->caption || $mediaAsset->credit)
                            <figcaption class="media-caption">
                                {{ $mediaAsset->caption }}
                                @if ($mediaAsset->credit)
                                    (Credit: {{ $mediaAsset->credit }})
                                @endif
                            </figcaption>
                        @endif
                    </figure>
                @endif

                @if ($node['node_type'] === 'video_group')
                    @php
                        $youtubeId = $youtubeIdFromUrl($mediaAsset->external_url);
                    @endphp

                    @if ($youtubeId)
                        <figure class="media-frame">
                            <iframe
                                src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                title="{{ $mediaAsset->caption ?? 'YouTube video' }}"
                                loading="lazy"
                                allowfullscreen
                            ></iframe>
                            @if ($mediaAsset->caption)
                                <figcaption class="media-caption">{{ $mediaAsset->caption }}</figcaption>
                            @endif
                        </figure>
                    @endif
                @endif
            @endforeach
        </div>
    @endif

    @foreach ($node['children'] as $child)
        @include('laws.partials.node', ['node' => $child])
    @endforeach
</article>
