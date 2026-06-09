{{-- ============================================================
     TOP NAVBAR PARTIAL
     ============================================================ --}}
<header class="navbar">
  <div class="navbar-left">
    {{-- Mobile menu --}}
    <button class="mobile-menu-btn" id="mobileMenuBtn" title="Menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    {{-- Breadcrumb --}}
    <nav class="navbar-breadcrumb">
      <span>@yield('page-title', 'Dashboard')</span>
    </nav>
  </div>

  {{-- Search --}}
  <div class="search-wrapper">
    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" class="search-input" id="searchInput" placeholder="Search agents, analytics, settings...">
    <span class="search-shortcut">⌘K</span>
  </div>

  {{-- Right Actions --}}
  <div class="navbar-right">

    {{-- Create Agent --}}
    <button class="btn btn-primary btn-sm" id="createAgentBtn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      New Agent
    </button>

    <div class="navbar-divider"></div>

    {{-- Notifications --}}
    <div class="dropdown">
      <button class="navbar-icon-btn" id="notificationBtn" title="Notifications">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <span class="badge-dot"></span>
      </button>
      <div class="dropdown-menu" id="notificationDropdown" style="min-width: 320px;">
        <div style="padding: 10px 12px; font-size: 13px; font-weight: 600; color: var(--color-text-primary);">Notifications</div>
        <div style="padding: 14px 12px; text-align: center; color: var(--color-text-muted); font-size: 13px;">
          <div style="font-size: 24px; margin-bottom: 4px;">🔔</div>
          You're all caught up!
        </div>
      </div>
    </div>

    {{-- User Menu --}}
    <div class="dropdown">
      <button class="navbar-icon-btn" id="userMenuBtn" title="User menu" style="gap: 8px; width: auto; padding: 4px 8px;">
        <div class="sidebar-user-avatar" style="width: 28px; height: 28px; font-size: 11px;">
          {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'AK' }}
        </div>
      </button>
      <div class="dropdown-menu" id="userDropdown">
        <div style="padding: 10px 12px; border-bottom: 1px solid var(--color-border-light);">
          <div style="font-size: 13px; font-weight: 600;">{{ Auth::user()->name ?? 'Alex Kim' }}</div>
          <div style="font-size: 11px; color: var(--color-text-muted);">{{ Auth::user()->email ?? 'alex@agentix.ai' }}</div>
        </div>
        <button class="dropdown-item">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Profile
        </button>
        <button class="dropdown-item" onclick="window.location='{{ route('settings') }}'">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
          Settings
        </button>
        <div class="dropdown-divider"></div>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="dropdown-item danger">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Sign Out
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
