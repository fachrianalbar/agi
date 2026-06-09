{{-- ============================================================
     AGENTS PAGE
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'AI Agents')
@section('page-title', 'AI Agents')

@section('content')
<div class="page-section active" id="page-agents">
  <div class="page-container">

    {{-- Page Header --}}
    <div class="page-header">
      <div>
        <h1 class="page-header-title">AI Agents</h1>
        <p class="page-header-subtitle">Manage, monitor, and deploy your AI agents</p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
          Filter
        </button>
        <button class="btn btn-primary btn-sm" onclick="document.getElementById('createAgentBtn').click()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          New Agent
        </button>
      </div>
    </div>

    {{-- Agent Stats Summary --}}
    <div class="stat-cards">
      <x-stat-card label="Total Agents" :value="$stats['total'] ?? 0" icon="blue" id="agentsStatTotal">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Active" :value="$stats['active'] ?? 0" icon="green" id="agentsStatActive">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Idle" :value="$stats['idle'] ?? 0" icon="orange" id="agentsStatIdle">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Errors" :value="$stats['error'] ?? 0" icon="purple" id="agentsStatError">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </x-slot>
      </x-stat-card>
    </div>

    {{-- Full Agents Table --}}
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title">All Agents</h3>
          <p class="card-subtitle">Complete list of deployed AI agents</p>
        </div>
        <div class="tabs" id="agentsFilterTabs">
          <button class="tab-item active">All</button>
          <button class="tab-item">Active</button>
          <button class="tab-item">Idle</button>
          <button class="tab-item">Error</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th></th>
              <th>Agent</th>
              <th>Status</th>
              <th>Model</th>
              <th>Type</th>
              <th>Tasks</th>
              <th>Uptime</th>
              <th>Last Active</th>
            </tr>
          </thead>
          <tbody id="agentsFullTableBody">
            @forelse($agents as $agent)
              <tr>
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

  </div>
</div>
@endsection
