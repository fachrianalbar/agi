{{-- ============================================================
     ANALYTICS PAGE
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
<div class="page-section active" id="page-analytics">
  <div class="page-container">

    {{-- Page Header --}}
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Analytics</h1>
        <p class="page-header-subtitle">Performance metrics and insights across all agents</p>
      </div>
      <div class="page-header-actions">
        <div class="tabs" id="analyticsRangeTabs">
          <button class="tab-item active">7 Days</button>
          <button class="tab-item">30 Days</button>
          <button class="tab-item">90 Days</button>
          <button class="tab-item">1 Year</button>
        </div>
        <button class="btn btn-secondary btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export CSV
        </button>
      </div>
    </div>

    {{-- Key Metrics --}}
    <div class="stat-cards">
      <x-stat-card label="Total Tasks" value="18,642" icon="blue" change="+12.5% vs last period" changeType="up">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Avg. Response Time" value="245ms" icon="green" change="+8ms slower" changeType="down">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Success Rate" value="94.2%" icon="purple" change="+2.1% improvement" changeType="up">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-6"/></svg>
        </x-slot>
      </x-stat-card>

      <x-stat-card label="Active Users" value="1,247" icon="orange" change="+18.3% growth" changeType="up">
        <x-slot:iconSlot>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </x-slot>
      </x-stat-card>
    </div>

    {{-- Charts Grid --}}
    <div class="charts-grid">
      <x-chart-card title="Task Distribution by Agent" canvasId="chartTaskDistribution" />
      <x-chart-card title="Response Time Trend" canvasId="chartResponseTrend" />
      <x-chart-card title="Success vs Error Rate" canvasId="chartSuccessRate" :small="true" />
      <x-chart-card title="Daily Active Users" canvasId="chartDailyUsers" :small="true" />
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.initChartTaskDistribution === 'function') {
      window.initChartTaskDistribution();
      window.initChartResponseTrend();
      window.initChartSuccessRate();
      window.initChartDailyUsers();
    }
  });
</script>
@endpush
