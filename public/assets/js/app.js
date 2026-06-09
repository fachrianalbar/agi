/* ============================================================
   AI Agent Admin Dashboard — Application JavaScript (Laravel)
   ============================================================ */

(function () {
  'use strict';

  // ----- Route Map -----
  var routes = {
    dashboard: '/',
    agents:    '/agents',
    analytics: '/analytics',
    activity:  '/activity',
    settings:  '/settings',
  };

  // Expose navigation for onclick handlers
  window.appNavigate = function (page) {
    window.location.href = routes[page] || '/';
  };

  function detectCurrentPage() {
    var path = window.location.pathname;
    if (path === '/' || path === '') return 'dashboard';
    if (path.indexOf('/agents') === 0) return 'agents';
    if (path.indexOf('/analytics') === 0) return 'analytics';
    if (path.indexOf('/activity') === 0) return 'activity';
    if (path.indexOf('/settings') === 0) return 'settings';
    return 'dashboard';
  }

  // Highlight active nav item
  (function () {
    var current = detectCurrentPage();
    document.querySelectorAll('.sidebar-nav-item[data-page]').forEach(function (item) {
      item.classList.toggle('active', item.dataset.page === current);
    });
  })();

  // ----- DOM -----
  var sidebar         = document.getElementById('sidebar');
  var sidebarToggle   = document.getElementById('sidebarToggle');
  var mobileMenuBtn   = document.getElementById('mobileMenuBtn');
  var sidebarOverlay  = document.getElementById('sidebarOverlay');
  var searchInput     = document.getElementById('searchInput');
  var userMenuBtn     = document.getElementById('userMenuBtn');
  var userDropdown    = document.getElementById('userDropdown');
  var notificationBtn = document.getElementById('notificationBtn');
  var notifDropdown   = document.getElementById('notificationDropdown');
  var createAgentBtn  = document.getElementById('createAgentBtn');
  var agentModal      = document.getElementById('agentModal');

  // ----- Sidebar -----
  var sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (sidebarCollapsed) document.body.classList.add('sidebar-collapsed');

  function toggleSidebar() {
    sidebarCollapsed = !sidebarCollapsed;
    document.body.classList.toggle('sidebar-collapsed', sidebarCollapsed);
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
  }

  function openMobileSidebar() {
    sidebar.classList.add('mobile-open');
    sidebarOverlay.classList.add('show');
  }

  function closeMobileSidebar() {
    sidebar.classList.remove('mobile-open');
    sidebarOverlay.classList.remove('show');
  }

  if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
  if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileSidebar);
  if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeMobileSidebar);

  // ----- Sidebar Nav Items (SPA fallback if data-page exists) -----
  document.querySelectorAll('.sidebar-nav-item[data-page]').forEach(function (item) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      var page = item.dataset.page;
      if (page) window.appNavigate(page);
    });
  });

  // ----- Dropdowns -----
  if (userMenuBtn) {
    userMenuBtn.addEventListener('click', function () {
      if (userDropdown) userDropdown.classList.toggle('show');
    });
  }
  if (notificationBtn) {
    notificationBtn.addEventListener('click', function () {
      if (notifDropdown) notifDropdown.classList.toggle('show');
    });
  }
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.dropdown')) {
      document.querySelectorAll('.dropdown-menu.show').forEach(function (m) { m.classList.remove('show'); });
    }
  });

  // ----- Modal -----
  function openAgentModal() {
    if (agentModal) {
      agentModal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeAgentModal() {
    if (agentModal) {
      agentModal.classList.remove('show');
      document.body.style.overflow = '';
    }
  }

  if (createAgentBtn) createAgentBtn.addEventListener('click', openAgentModal);

  // Close modal via cancel/close buttons
  document.querySelectorAll('.cancel-modal-btn, .close-modal-btn').forEach(function (btn) {
    btn.addEventListener('click', closeAgentModal);
  });

  // Close modal on overlay click
  if (agentModal) {
    agentModal.addEventListener('click', function (e) {
      if (e.target === agentModal) closeAgentModal();
    });
  }

  // ----- Search -----
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      var query = this.value.toLowerCase().trim();
      var tables = [
        document.getElementById('agentsTableBody'),
        document.getElementById('agentsFullTableBody'),
      ];
      tables.forEach(function (tbody) {
        if (!tbody) return;
        tbody.querySelectorAll('tr').forEach(function (row) {
          row.style.display = row.textContent.toLowerCase().indexOf(query) !== -1 ? '' : 'none';
        });
      });
    });
  }

  // ----- Agent Filter Tabs -----
  var filterTabs = document.getElementById('agentsFilterTabs');
  if (filterTabs) {
    filterTabs.addEventListener('click', function (e) {
      var tab = e.target.closest('.tab-item');
      if (!tab) return;
      filterTabs.querySelectorAll('.tab-item').forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      var status = tab.textContent.trim().toLowerCase();
      var tables = [
        document.getElementById('agentsFullTableBody'),
        document.getElementById('agentsTableBody'),
      ];
      tables.forEach(function (tbody) {
        if (!tbody) return;
        tbody.querySelectorAll('tr').forEach(function (row) {
          if (status === 'all') { row.style.display = ''; return; }
          var badge = row.querySelector('.badge');
          var text = badge ? badge.textContent.trim().toLowerCase() : '';
          row.style.display = text.indexOf(status) !== -1 ? '' : 'none';
        });
      });
    });
  }

  // ----- Generic Tab Switching -----
  document.querySelectorAll('.tabs').forEach(function (group) {
    group.addEventListener('click', function (e) {
      var tab = e.target.closest('.tab-item');
      if (!tab) return;
      group.querySelectorAll('.tab-item').forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
    });
  });

  // ----- Settings Toggles -----
  document.querySelectorAll('.toggle input[data-setting]').forEach(function (input) {
    input.addEventListener('change', function () {
      var setting = this.dataset.setting;
      console.log('Setting changed:', setting, this.checked);
    });
  });

  // ----- Keyboard Shortcuts -----
  document.addEventListener('keydown', function (e) {
    var isInput = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT';
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault();
      if (searchInput) searchInput.focus();
    }
    if (e.key === 'Escape' && !isInput) {
      closeAgentModal();
      document.querySelectorAll('.dropdown-menu.show').forEach(function (m) { m.classList.remove('show'); });
      closeMobileSidebar();
    }
    if ((e.metaKey || e.ctrlKey) && e.key === 'b') {
      e.preventDefault();
      toggleSidebar();
    }
  });

  // ----- Window Resize -----
  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) closeMobileSidebar();
  });

  // ----- Toast Auto-dismiss -----
  setTimeout(function () {
    document.querySelectorAll('.toast').forEach(function (t) {
      t.style.animation = 'fadeOut 0.3s ease-out forwards';
      setTimeout(function () { t.remove(); }, 300);
    });
  }, 4000);

  // ============================================================
  //  CHART.JS INITIALIZATION
  // ============================================================

  var chartInstances = {};
  var C = {
    peach:      '#E2725B',
    espresso:   '#4E2C23',
    apricot:    '#FFDAB9',
    purple:     '#7C3AED',
    blue:       '#3B82C4',
    green:      '#2D8B5E',
    red:        '#D14343',
    orange:     '#C7821A',
    border:     '#F0DDD0',
    textMuted:  '#A08980',
  };

  function getCtx(id) {
    var canvas = document.getElementById(id);
    if (!canvas) return null;
    var parent = canvas.parentElement;
    if (parent) { parent.style.position = 'relative'; parent.style.minHeight = '200px'; }
    return canvas.getContext('2d');
  }

  function makeBarChart(id, labels, data, colors, yBeginZero) {
    var ctx = getCtx(id);
    if (!ctx) return;
    if (chartInstances[id]) chartInstances[id].destroy();
    chartInstances[id] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Count',
          data: data,
          backgroundColor: colors || data.map(function () { return C.peach; }),
          borderRadius: 8,
          borderSkipped: false,
          barPercentage: 0.7,
          categoryPercentage: 0.7,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: C.espresso, cornerRadius: 8, padding: 12, displayColors: false },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: C.textMuted, font: { size: 12, family: 'Inter' } }, border: { display: false } },
          y: { grid: { color: C.border, drawBorder: false }, ticks: { color: C.textMuted, font: { size: 12, family: 'Inter' }, padding: 8 }, border: { display: false }, beginAtZero: yBeginZero !== false },
        },
      },
    });
  }

  function makeLineChart(id, labels, data, color) {
    var ctx = getCtx(id);
    if (!ctx) return;
    var c = color || C.peach;
    if (chartInstances[id]) chartInstances[id].destroy();
    chartInstances[id] = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Value',
          data: data,
          borderColor: c,
          backgroundColor: function (ctx) {
            var g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
            g.addColorStop(0, c + '33');
            g.addColorStop(1, c + '00');
            return g;
          },
          fill: true,
          tension: 0.4,
          pointBackgroundColor: c,
          pointBorderColor: '#FFFFFF',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 6,
          borderWidth: 2.5,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: C.espresso, cornerRadius: 8, padding: 12, displayColors: false },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: C.textMuted, font: { size: 12, family: 'Inter' } }, border: { display: false } },
          y: { grid: { color: C.border, drawBorder: false }, ticks: { color: C.textMuted, font: { size: 12, family: 'Inter' }, padding: 8 }, border: { display: false }, beginAtZero: false },
        },
      },
    });
  }

  // Expose chart init functions globally
  window.initChartTaskVolume = function () {
    makeBarChart('chartTaskVolume', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], [1250, 1580, 980, 1840, 1420, 870, 620]);
  };

  window.initChartResponseTime = function () {
    makeLineChart('chartResponseTime', ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', 'Now'], [320, 280, 245, 210, 230, 260, 245]);
  };

  window.initChartTaskDistribution = function () {
    makeBarChart('chartTaskDistribution', ['Support', 'Code', 'Data', 'Writer', 'Security', 'Transl.'], [1700, 1400, 1100, 1900, 800, 2000], [C.peach, C.purple, C.blue, C.green, C.red, C.orange]);
  };

  window.initChartResponseTrend = function () {
    makeLineChart('chartResponseTrend', ['Jun 1', 'Jun 2', 'Jun 3', 'Jun 4', 'Jun 5', 'Jun 6', 'Jun 7', 'Jun 8', 'Jun 9'], [290, 305, 280, 260, 245, 230, 215, 200, 195]);
  };

  window.initChartSuccessRate = function () {
    var ctx = getCtx('chartSuccessRate');
    if (!ctx) return;
    if (chartInstances.successRate) chartInstances.successRate.destroy();
    // Use smaller parent height for doughnut
    var parent = document.getElementById('chartSuccessRate').parentElement;
    if (parent) { parent.style.minHeight = '150px'; }
    chartInstances.successRate = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Success (94.2%)', 'Errors (5.8%)'],
        datasets: [{ data: [94.2, 5.8], backgroundColor: [C.green, C.red], borderColor: '#FFFFFF', borderWidth: 2 }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '70%',
        plugins: {
          legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyleWidth: 8, pointStyleHeight: 8, font: { size: 12, family: 'Inter' }, color: C.espresso } },
          tooltip: { backgroundColor: C.espresso, cornerRadius: 8, padding: 10, callbacks: { label: function (ctx) { return ctx.label + ': ' + ctx.raw + '%'; } } },
        },
      },
    });
  };

  window.initChartDailyUsers = function () {
    makeLineChart('chartDailyUsers', ['Jun 1', 'Jun 2', 'Jun 3', 'Jun 4', 'Jun 5', 'Jun 6', 'Jun 7', 'Jun 8', 'Jun 9'], [850, 920, 1050, 980, 1120, 1080, 1150, 1200, 1247], C.purple);
  };

  // ----- Auto-init Charts Based on Current Page -----
  var page = detectCurrentPage();
  setTimeout(function () {
    if (page === 'dashboard') {
      window.initChartTaskVolume();
      window.initChartResponseTime();
    }
    if (page === 'analytics') {
      window.initChartTaskDistribution();
      window.initChartResponseTrend();
      window.initChartSuccessRate();
      window.initChartDailyUsers();
    }
  }, 150);

})();
