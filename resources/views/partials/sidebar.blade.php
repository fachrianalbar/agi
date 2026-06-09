{{-- ============================================================
     SIDEBAR PARTIAL
     ============================================================ --}}
<aside class="sidebar" id="sidebar">

  {{-- Brand --}}
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
        <path d="M2 17l10 5 10-5"/>
        <path d="M2 12l10 5 10-5"/>
      </svg>
    </div>
    <span class="sidebar-brand-text">Agentix</span>
  </div>

  {{-- Navigation --}}
  <nav class="sidebar-nav">
    <div class="sidebar-nav-section">Main Menu</div>

    <x-nav-item route="dashboard" icon="dashboard" :active="request()->routeIs('dashboard')">
      Dashboard
    </x-nav-item>

    <x-nav-item route="agents" icon="agents" :badge="$agentCount ?? 0" :active="request()->routeIs('agents*')">
      AI Agents
    </x-nav-item>

    <x-nav-item route="analytics" icon="analytics" :active="request()->routeIs('analytics')">
      Analytics
    </x-nav-item>

    <x-nav-item route="activity" icon="activity" :badge="3" :active="request()->routeIs('activity')">
      Activity
    </x-nav-item>

    <div class="sidebar-nav-section">Workspace</div>

    <x-nav-item route="settings" icon="settings" :active="request()->routeIs('settings')">
      Settings
    </x-nav-item>
  </nav>

  {{-- Sidebar Footer --}}
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user-avatar">
        {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'AK' }}
      </div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">{{ Auth::user()->name ?? 'Alex Kim' }}</div>
        <div class="sidebar-user-role">{{ Auth::user()->role ?? 'Administrator' }}</div>
      </div>
    </div>
  </div>
</aside>
