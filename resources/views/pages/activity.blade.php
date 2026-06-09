{{-- ============================================================
     ACTIVITY PAGE
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')

@section('content')
<div class="page-section active" id="page-activity">
  <div class="page-container">

    {{-- Page Header --}}
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Activity Log</h1>
        <p class="page-header-subtitle">Complete history of actions and system events</p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
          Filter
        </button>
        <button class="btn btn-ghost btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </button>
      </div>
    </div>

    {{-- Activity Table --}}
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Events</h3>
        <div class="tabs" id="activityFilterTabs">
          <button class="tab-item active">All</button>
          <button class="tab-item">Deployments</button>
          <button class="tab-item">Alerts</button>
          <button class="tab-item">System</button>
        </div>
      </div>
      <div class="timeline" id="activityTimelineFull" style="padding: 0 8px;">
        @php
        $activities = [
          ['dot' => 'green',  'title' => 'Customer Support AI deployed',          'desc' => 'New version v2.4.1 pushed to production',              'time' => '2 minutes ago'],
          ['dot' => 'blue',   'title' => 'Code Review Bot completed scan',         'desc' => 'Reviewed 34 pull requests with 98.2% accuracy',       'time' => '15 minutes ago'],
          ['dot' => 'purple', 'title' => 'Weekly analytics report generated',      'desc' => 'Agent performance report for week 23 is ready',        'time' => '1 hour ago'],
          ['dot' => 'orange', 'title' => 'Security Auditor flagged issue',         'desc' => 'Potential vulnerability detected in module auth-service','time' => '3 hours ago'],
          ['dot' => 'green',  'title' => 'Translation Engine scaled up',           'desc' => 'Auto-scaled to handle increased traffic (+240%)',      'time' => '5 hours ago'],
          ['dot' => 'blue',   'title' => 'New model fine-tuning complete',         'desc' => 'Custom model v3.1 is now available for all agents',    'time' => '8 hours ago'],
          ['dot' => 'purple', 'title' => 'System maintenance window scheduled',     'desc' => 'Database optimization planned for Sunday 03:00 UTC',   'time' => '12 hours ago'],
          ['dot' => 'green',  'title' => 'Agent configuration backup completed',   'desc' => 'All agent configs backed up to S3 successfully',        'time' => '18 hours ago'],
          ['dot' => 'blue',   'title' => 'SSL certificate auto-renewed',           'desc' => 'Wildcard certificate renewed for *.agentix.ai',         'time' => '22 hours ago'],
          ['dot' => 'orange', 'title' => 'Rate limit threshold reached',           'desc' => 'API rate limit hit 80% — consider scaling up',          'time' => '1 day ago'],
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

      {{-- Pagination --}}
      <div class="pagination" style="padding: 16px 8px 0;">
        <span>Showing <strong>10</strong> of <strong>42</strong> events</span>
        <div class="pagination-buttons">
          <button class="pagination-btn" disabled>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button class="pagination-btn active">1</button>
          <button class="pagination-btn">2</button>
          <button class="pagination-btn">3</button>
          <button class="pagination-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
