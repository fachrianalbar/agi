{{-- ============================================================
     DASHBOARD PAGE
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="page-section active" id="page-dashboard">
  <div class="page-container">

    {{-- Page Header --}}
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Dashboard</h1>
        <p class="page-header-subtitle">Overview of your AI agent ecosystem</p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('createAgentBtn').click()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Agent
        </button>
      </div>
    </div>

    {{-- Quick Actions --}}
    <div class="quick-actions">
      <div class="quick-action-card" onclick="document.getElementById('createAgentBtn').click()">
        <div class="quick-action-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        </div>
        <div>
          <div class="quick-action-label">Deploy Agent</div>
          <div class="quick-action-desc">Create and launch a new agent</div>
        </div>
      </div>
      <div class="quick-action-card" onclick="window.appNavigate('analytics')">
        <div class="quick-action-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div>
          <div class="quick-action-label">View Reports</div>
          <div class="quick-action-desc">Analytics & performance data</div>
        </div>
      </div>
      <div class="quick-action-card">
        <div class="quick-action-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div>
          <div class="quick-action-label">Help Center</div>
          <div class="quick-action-desc">Documentation & guides</div>
        </div>
      </div>
      <div class="quick-action-card">
        <div class="quick-action-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div>
          <div class="quick-action-label">System Health</div>
          <div class="quick-action-desc">Monitor infrastructure</div>
        </div>
      </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="stat-cards">
      <x-stat-card label="Total Agents" :value="$stats['total'] ?? 0" icon="blue" change="+2 this month" changeType="up" id="statTotalAgents">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Active Agents" :value="$stats['active'] ?? 0" icon="green" change="98% uptime" changeType="up" id="statActiveAgents">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Tasks Completed" :value="number_format($stats['tasks'] ?? 0)" icon="orange" change="+12.5%" changeType="up" id="statTasksCompleted">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Avg. Uptime" :value="($stats['uptime'] ?? '0') . '%'" icon="purple" change="-0.2%" changeType="down" id="statUptime">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
        </x-slot>
      </x-stat-card>
    </div>

    {{-- Content Grid: Charts + Table --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">

      {{-- Agent Table --}}
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
          <div>
            <h3 class="card-title">AI Agents</h3>
            <p class="card-subtitle">Manage and monitor your deployed agents</p>
          </div>
          <a href="{{ route('agents.index') }}" class="btn btn-ghost btn-sm">View All →</a>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Agent</th>
                <th>Status</th>
                <th>Model</th>
                <th>Type</th>
                <th>Tasks</th>
                <th>Uptime</th>
                <th>Last Active</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="agentsTableBody">
              @forelse($agents as $agent)
                <tr>
                  <td>
                    <div class="agent-info">
                      <div class="agent-avatar" style="background: {{ $agent['color'] }}">
                        {{ strtoupper(substr($agent['name'], 0, 1)) }}
                      </div>
                      <div>
                        <div class="agent-name">{{ $agent['name'] }}</div>
                        <div class="agent-email">{{ $agent['model'] }}</div>
                      </div>
                    </div>
                  </td>
                  <td><x-badge type="{{ $agent['status'] === 'active' ? 'success' : ($agent['status'] === 'idle' ? 'warning' : ($agent['status'] === 'error' ? 'danger' : 'neutral')) }}">{{ ucfirst($agent['status']) }}</x-badge></td>
                  <td>{{ $agent['model'] }}</td>
                  <td>{{ $agent['type'] }}</td>
                  <td>{{ number_format($agent['tasksCompleted']) }}</td>
                  <td>{{ $agent['uptime'] }}</td>
                  <td>{{ $agent['lastActive'] }}</td>
                  <td>
                    <div class="table-actions">
                      <button class="table-action-btn" title="Edit" onclick="window.editAgent('{{ $agent['id'] }}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <button class="table-action-btn" title="Delete" onclick="window.deleteAgent('{{ $agent['id'] }}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8">
                    <div class="empty-state">
                      <div class="empty-state-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
                      </div>
                      <div class="empty-state-title">No agents yet</div>
                      <div class="empty-state-desc">Create your first AI agent to get started.</div>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="pagination">
          <span>Showing <strong>{{ count($agents) }}</strong> agents</span>
          <div class="pagination-buttons">
            <button class="pagination-btn" disabled>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="pagination-btn active">1</button>
            <button class="pagination-btn" disabled>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>
      </div>

      {{-- Chart: Task Volume --}}
      <x-chart-card title="Task Volume" subtitle="Daily tasks processed (last 7 days)" canvasId="chartTaskVolume" :tabs="['7d', '30d', '90d']" />

      {{-- Chart: Avg. Response Time --}}
      <x-chart-card title="Avg. Response Time" subtitle="Milliseconds over time" canvasId="chartResponseTime" />

    </div>

    {{-- Activity Timeline --}}
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">Recent Activity</h3>
          <p class="card-subtitle">Latest events across your workspace</p>
        </div>
        <a href="{{ route('activity') }}" class="btn btn-ghost btn-sm">View All →</a>
      </div>
      <div class="timeline" id="activityTimeline">
        @php
        $activities = [
          ['dot' => 'green',  'title' => 'Customer Support AI deployed',          'desc' => 'New version v2.4.1 pushed to production',              'time' => '2 minutes ago'],
          ['dot' => 'blue',   'title' => 'Code Review Bot completed scan',         'desc' => 'Reviewed 34 pull requests with 98.2% accuracy',       'time' => '15 minutes ago'],
          ['dot' => 'purple', 'title' => 'Weekly analytics report generated',      'desc' => 'Agent performance report for week 23 is ready',        'time' => '1 hour ago'],
          ['dot' => 'orange', 'title' => 'Security Auditor flagged issue',         'desc' => 'Potential vulnerability detected in module auth-service','time' => '3 hours ago'],
          ['dot' => 'green',  'title' => 'Translation Engine scaled up',           'desc' => 'Auto-scaled to handle increased traffic (+240%)',       'time' => '5 hours ago'],
          ['dot' => 'blue',   'title' => 'New model fine-tuning complete',         'desc' => 'Custom model v3.1 is now available for all agents',     'time' => '8 hours ago'],
          ['dot' => 'purple', 'title' => 'System maintenance window scheduled',    'desc' => 'Database optimization planned for Sunday 03:00 UTC',    'time' => '12 hours ago'],
        ];
        @endphp
        @foreach($activities as $a)
          <div class="timeline-item">
            <div class="timeline-dot {{ $a['dot'] }}">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
            </div>
            <div class="timeline-content">
              <div class="timeline-title">{{ $a['title'] }}</div>
              <div class="timeline-desc">{{ $a['desc'] }}</div>
              <div class="timeline-time">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ $a['time'] }}
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Re-render charts when dashboard becomes active
    if (typeof window.initChartTaskVolume === 'function') {
      window.initChartTaskVolume();
      window.initChartResponseTime();
    }
  });
</script>
@endpush
