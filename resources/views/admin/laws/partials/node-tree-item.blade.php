<article class="result-card tree-node" style="margin-left: {{ $node['depth'] * 1.25 }}rem;">
    <p class="eyebrow">{{ strtoupper($node['node_type']) }}</p>
    <h3><a href="{{ route('admin.nodes.edit', ['law' => $law, 'node' => $node['id'], 'edition' => request('edition', $law->edition_id)]) }}">{{ $node['title'] }}</a></h3>
    <p class="law-meta">
        Sort: {{ $node['sort_order'] }} |
        Published: {{ $node['is_published'] ? 'yes' : 'no' }} |
        Depth: {{ $node['depth'] }} |
        Children: {{ $node['child_count'] }}
    </p>
    <p class="law-meta"><a class="result-link" href="{{ route('admin.nodes.edit', ['law' => $law, 'node' => $node['id'], 'edition' => request('edition', $law->edition_id)]) }}">Edit this node</a></p>
</article>

@if ($node['children'])
    <div class="tree-node-children">
        @foreach ($node['children'] as $child)
            @include('admin.laws.partials.node-tree-item', ['law' => $law, 'node' => $child])
        @endforeach
    </div>
@endif
