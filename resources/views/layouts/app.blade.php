<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Dashboard') — Agentix</title>

  {{-- Favicon --}}
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/icons/favicon.svg') }}">

  {{-- Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  {{-- Tailwind CSS via CDN --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            espresso:      { DEFAULT: '#4E2C23', light: '#6B3F33', dark: '#3A1F18' },
            'burnt-peach': { DEFAULT: '#E2725B', hover: '#D06048', light: '#FDE8E3' },
            'soft-apricot':{ DEFAULT: '#FFDAB9', light: '#FFF0E0' },
            bg:             { DEFAULT: '#FFF4EC', white: '#FFFFFF' },
          },
          fontFamily: {
            sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
          },
          borderRadius: {
            'sm': '8px',
            'md': '12px',
            'lg': '16px',
            'xl': '20px',
          },
        },
      },
    }
  </script>

  {{-- Custom Styles --}}
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">

  {{-- Stack for page-specific styles --}}
  @stack('styles')
</head>
<body class="bg-[#FFF4EC] text-espresso antialiased">

  {{-- ============================================================
       APP LAYOUT
       ============================================================ --}}
  <div class="app-layout" id="appLayout">

    {{-- Mobile Sidebar Overlay --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar Partial --}}
    @include('partials.sidebar')

    {{-- Sidebar Toggle --}}
    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    </button>

    {{-- ============================================================
         MAIN CONTENT
         ============================================================ --}}
    <main class="main-content" id="mainContent">

      {{-- Navbar Partial --}}
      @include('partials.navbar')

      {{-- Page Content --}}
      @yield('content')

    </main>
  </div>

  {{-- ============================================================
       CREATE / EDIT AGENT MODAL
       ============================================================ --}}
  <x-modal id="agentModal" title="Create New Agent">
    <form id="agentForm" method="POST" action="{{ route('agents.store') }}">
      @csrf
      <input type="hidden" name="id" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Agent Name</label>
          <input type="text" name="name" class="form-input" placeholder="e.g. Customer Support AI" required>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
              <option value="">Select type...</option>
              <option value="Conversational">Conversational</option>
              <option value="Automation">Automation</option>
              <option value="Analytics">Analytics</option>
              <option value="Generative">Generative</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Model</label>
            <select name="model" class="form-select" required>
              <option value="">Select model...</option>
              <option value="Claude Opus 4">Claude Opus 4</option>
              <option value="Claude Sonnet 4">Claude Sonnet 4</option>
              <option value="Claude Haiku 4">Claude Haiku 4</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-textarea" placeholder="Describe what this agent does..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Initial Status</label>
          <select name="status" class="form-select">
            <option value="idle">Idle (not deployed)</option>
            <option value="active">Active (deploy immediately)</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary cancel-modal-btn">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Agent</button>
      </div>
    </form>
  </x-modal>

  {{-- Toast Container --}}
  <x-toast />

  {{-- ============================================================
       SCRIPTS
       ============================================================ --}}
  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

  {{-- App JS --}}
  <script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>

  {{-- Stack for page-specific scripts --}}
  @stack('scripts')

</body>
</html>
