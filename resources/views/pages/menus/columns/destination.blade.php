@if($menu->route_name || $menu->url)
  <code class="code-label">{{ $menu->route_name ?: $menu->url }}</code>
@else
  <span class="badge badge-neutral">Placeholder</span>
@endif
