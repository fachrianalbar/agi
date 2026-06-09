{{-- ============================================================
     SETTINGS PAGE
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="page-section active" id="page-settings">
  <div class="page-container">

    {{-- Page Header --}}
    <div class="page-header">
      <div>
        <h1 class="page-header-title">Settings</h1>
        <p class="page-header-subtitle">Configure your workspace and agent preferences</p>
      </div>
    </div>

    {{-- Workspace Settings --}}
    <div class="settings-section">
      <h3 class="settings-section-title">Workspace</h3>
      <div class="settings-card">
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Workspace Name</div>
            <div class="settings-row-desc">Used across the dashboard and notifications</div>
          </div>
          <input type="text" class="form-input" value="{{ config('app.name', 'Agentix') }}" style="max-width: 260px;">
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Default AI Model</div>
            <div class="settings-row-desc">Model used when creating new agents</div>
          </div>
          <select class="form-select" style="max-width: 260px;">
            <option>Claude Opus 4</option>
            <option>Claude Sonnet 4</option>
            <option>Claude Haiku 4</option>
          </select>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Timezone</div>
            <div class="settings-row-desc">All timestamps will use this timezone</div>
          </div>
          <select class="form-select" style="max-width: 260px;">
            <option>UTC-8 (Pacific Time)</option>
            <option>UTC-5 (Eastern Time)</option>
            <option>UTC+0 (London)</option>
            <option>UTC+7 (Jakarta)</option>
            <option>UTC+8 (Singapore)</option>
          </select>
        </div>
      </div>
    </div>

    {{-- Agent Defaults --}}
    <div class="settings-section">
      <h3 class="settings-section-title">Agent Defaults</h3>
      <div class="settings-card">
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Auto-deploy Agents</div>
            <div class="settings-row-desc">Automatically deploy agents when created</div>
          </div>
          <label class="toggle">
            <input type="checkbox" data-setting="autoDeploy" checked>
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Agent Notifications</div>
            <div class="settings-row-desc">Receive alerts for agent status changes</div>
          </div>
          <label class="toggle">
            <input type="checkbox" data-setting="notifications" checked>
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Analytics Collection</div>
            <div class="settings-row-desc">Collect usage data for performance insights</div>
          </div>
          <label class="toggle">
            <input type="checkbox" data-setting="analytics" checked>
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Max Retry Attempts</div>
            <div class="settings-row-desc">Number of retries before marking agent as error</div>
          </div>
          <select class="form-select" style="max-width: 140px;">
            <option>3</option>
            <option>5</option>
            <option>7</option>
            <option>10</option>
          </select>
        </div>
      </div>
    </div>

    {{-- API & Security --}}
    <div class="settings-section">
      <h3 class="settings-section-title">API & Security</h3>
      <div class="settings-card">
        <div class="settings-row">
          <div>
            <div class="settings-row-label">API Key</div>
            <div class="settings-row-desc">Used for programmatic access to agent APIs</div>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <input type="text" class="form-input" value="agx_live_••••••••••••••••••••" style="max-width: 240px; font-family: monospace; font-size: 13px;" readonly>
            <button class="btn btn-secondary btn-sm">Regenerate</button>
          </div>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Two-Factor Authentication</div>
            <div class="settings-row-desc">Add an extra layer of security to your account</div>
          </div>
          <label class="toggle">
            <input type="checkbox" data-setting="twoFactor">
            <span class="toggle-slider"></span>
          </label>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Session Timeout</div>
            <div class="settings-row-desc">Auto sign-out after inactivity period</div>
          </div>
          <select class="form-select" style="max-width: 180px;">
            <option>30 minutes</option>
            <option>1 hour</option>
            <option>4 hours</option>
            <option>Never</option>
          </select>
        </div>
      </div>
    </div>

    {{-- Danger Zone --}}
    <div class="settings-section">
      <h3 class="settings-section-title" style="color: var(--color-danger);">Danger Zone</h3>
      <div class="settings-card" style="border-color: #F5C6C6;">
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Reset All Agents</div>
            <div class="settings-row-desc">Stop and reset all running agents. This cannot be undone.</div>
          </div>
          <button class="btn btn-danger btn-sm">Reset All Agents</button>
        </div>
        <div class="settings-row">
          <div>
            <div class="settings-row-label">Delete Workspace</div>
            <div class="settings-row-desc">Permanently delete this workspace and all associated data.</div>
          </div>
          <button class="btn btn-danger btn-sm">Delete Workspace</button>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
