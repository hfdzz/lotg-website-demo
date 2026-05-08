<article class="node" data-type="{{ $node['node_type'] }}" data-depth="{{ $node['depth'] }}">
    @if (! empty($node['collapse']['enabled']))
        <details class="node-collapse" @if (empty($node['collapse']['starts_collapsed'])) open @endif>
            <summary class="node-collapse-summary">
                @if ($node['title'])
                    @include('laws.partials.title', [
                        'tag' => $node['heading_tag'],
                        'title' => $node['title'],
                        'anchorId' => $node['anchor_id'],
                        'sectionNumber' => $node['section_number'] ?? null,
                    ])
                @else
                    <span class="node-collapse-label">{{ $node['collapse']['label'] }}</span>
                @endif
            </summary>
            <div class="node-collapse-content">
                @include('laws.partials.node-content', ['node' => $node])
            </div>
        </details>
    @else
        @if ($node['title'])
            @include('laws.partials.title', [
                'tag' => $node['heading_tag'],
                'title' => $node['title'],
                'anchorId' => $node['anchor_id'],
                'sectionNumber' => $node['section_number'] ?? null,
            ])
        @endif

        @include('laws.partials.node-content', ['node' => $node])
    @endif
</article>
