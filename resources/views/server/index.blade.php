@extends('viewmanager::layouts.app')

@section('page-title', 'Server Monitor')

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="card-title">🚀 Server Monitor</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col">
        <div class="status-indicators">
          <div class="status-indicator">
            <div class="status-dot status-connected" id="connectionStatus"></div>
            <span id="connectionText">Connecting...</span>
          </div>
          <div class="status-indicator">
            <div class="status-dot" id="healthStatus"></div>
            <span id="healthText">Checking health...</span>
          </div>
          <div class="status-indicator">
            <span>Last update: </span>
            <span id="lastUpdate">--:--:--</span>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="metrics-grid">
          <!-- System Information -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">System Information</div>
            </div>
            <div id="systemInfo">
              <div>Loading system information...</div>
            </div>
          </div>
            
          <!-- Resource Usage -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">Resource Usage</div>
            </div>
            <div id="resourceUsage">
              <div>Loading resource usage...</div>
            </div>
          </div>
            
          <!-- CPU Load -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">CPU Load</div>
            </div>
            <div id="cpuLoad">
              <div>Loading CPU information...</div>
            </div>
            <canvas id="cpuChart" height="100"></canvas>
          </div>
            
          <!-- Memory Usage -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">Memory Usage</div>
            </div>
            <div id="memoryUsage">
              <div>Loading memory information...</div>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" id="memoryProgress" style="width: 0%"></div>
            </div>
          </div>
            
          <!-- Disk Usage -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">Disk Usage</div>
            </div>
            <div id="diskUsage">
              <div>Loading disk information...</div>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" id="diskProgress" style="width: 0%"></div>
            </div>
          </div>
            
          <!-- Database Status -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">Database</div>
            </div>
            <div id="databaseStatus">
              <div>Loading database information...</div>
            </div>
          </div>
            
          <!-- Application Health -->
          <div class="metric-card">
            <div class="metric-header">
              <div class="metric-title">Application Health</div>
            </div>
            <div id="applicationHealth">
              <div>Loading health information...</div>
            </div>
            <div class="connection-stats">
              <div class="stat-item">
                <div class="stat-value" id="activeConnections">0</div>
                <div class="stat-label">Active Connections</div>
              </div>
              <div class="stat-item">
                <div class="stat-value" id="queueSize">0</div>
                <div class="stat-label">Queue Size</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  class LaravelEventStreamMonitor {
    constructor() {
      this.eventSource = null;
      this.metrics = {};
      this.cpuHistory = [];
      this.memoryHistory = [];
      this.maxHistory = 20;
      this.connectionType = null;

      this.charts = {
        cpu: null,
        memory: null
      };

      this.initCharts();
      this.connectSSE(); // Default connection
    }

    initCharts() {
      // CPU Chart
      const cpuCtx = document.getElementById('cpuChart').getContext('2d');
      this.charts.cpu = new Chart(cpuCtx, {
        type: 'line',
        data: {
          labels: Array.from({length: this.maxHistory}, (_, i) => ''),
          datasets: [{
            label: 'CPU Load (1min)',
            data: Array(this.maxHistory).fill(0),
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { display: false },
            y: {
              beginAtZero: true,
              max: 10,
              ticks: {
                callback: function(value) {
                  return value.toFixed(1);
                }
              }
            }
          },
          plugins: {
            legend: { display: false }
          }
        }
      });
                
      // Memory Chart
      const memoryEl = document.getElementById('memoryChart');
      if(!memoryEl) return;
      
      const memoryCtx = memoryEl.getContext('2d');
      this.charts.memory = new Chart(memoryCtx, {
        type: 'line',
        data: {
          labels: Array.from({length: this.maxHistory}, (_, i) => ''),
          datasets: [{
            label: 'Memory Usage %',
            data: Array(this.maxHistory).fill(0),
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { display: false },
            y: {
              beginAtZero: true,
              max: 100,
              ticks: {
                callback: function(value) {
                  return value + '%';
                }
              }
            }
          },
          plugins: {
            legend: { display: false }
          }
        }
      });
    }
            
    connectSSE() {
      this.disconnect();
      this.connectionType = 'sse';

      try {
        this.eventSource = new EventSource('{{ route("api.v1.cores.metrics") }}');

        this.eventSource.onopen = () => {
          this.updateConnectionStatus('connected', 'SSE Event Stream Connected');
          console.log('Laravel SSE connected');
        };

        this.eventSource.onmessage = (event) => {
          console.log('SSE raw message:', event);
        };

        this.eventSource.addEventListener('metrics', (event) => {
        console.log(event)
          const data = JSON.parse(event.data);
          this.handleMetricsUpdate(data);
          this.updateLastUpdate();
        });

        this.eventSource.addEventListener('health', (event) => {
          const data = JSON.parse(event.data);
          this.handleHealthUpdate(data);
        });

        this.eventSource.addEventListener('modules', (event) => {
          const data = JSON.parse(event.data);
          this.handleModulesUpdate(data);
        });

        this.eventSource.addEventListener('heartbeat', (event) => {
          const data = JSON.parse(event.data);
          console.log('SSE heartbeat:', data);
          this.updateConnectionStatus('connected', 'SSE Connected');
        });

        this.eventSource.addEventListener('error', (event) => {
          const data = JSON.parse(event.data);
          console.error('SSE server error:', data);
          this.updateConnectionStatus('disconnected', 'SSE Error');
        });

        this.eventSource.onerror = (error) => {
          console.error('SSE connection error:', error.message);
          this.updateConnectionStatus('disconnected', 'SSE Connection Error');
          this.reconnect();
        };
      } catch (error) {
        console.error('Failed to connect SSE:', error);
        this.updateConnectionStatus('disconnected', 'SSE Failed');
      }
    }

    handleMetricsUpdate(metrics) {
      this.metrics = metrics;
      this.updateAllDisplays();
    }

    handleHealthUpdate(health) {
      this.updateHealthStatus(health);
    }

    handleModulesUpdate(data) {
      this.updateModulesStatus(data.modules || []);
    }

    updateAllDisplays() {
      this.updateSystemInfo(this.metrics.system);
      this.updateResourceUsage(this.metrics.resources);
      this.updateCpuLoad(this.metrics.resources.cpu_usage);
      this.updateMemoryUsage(this.metrics.resources);
      this.updateDiskUsage(this.metrics.resources.disk_usage);
      this.updateDatabaseStatus(this.metrics.database);
      this.updateApplicationStatus(this.metrics.application, this.metrics.queue);

      // Update charts with history
      if (this.metrics.history) {
        this.updateCharts(this.metrics.history);
      }
    }

    updateSystemInfo(system) {
      if (!system) return;

      document.getElementById('systemInfo').innerHTML = `
        <div class="metric-value">${system.hostname}</div>
        <div class="metric-subvalue">
          PHP ${system.php_version} • Laravel ${system.laravel_version}<br>
          ${system.os} • ${system.environment}<br>
          Uptime: ${system.uptime}
        </div>`;
    }

    updateResourceUsage(resources) {
      if (!resources) return;

      document.getElementById('resourceUsage').innerHTML = `
        <div class="metric-value">${resources.memory_usage}</div>
        <div class="metric-subvalue">
          ${resources.memory_percentage}% used • Limit: ${resources.memory_limit}
        </div>`;
    }

    updateCpuLoad(cpuUsage) {
      if (!cpuUsage) return;

      const load = cpuUsage.load_1min || 0;

      document.getElementById('cpuLoad').innerHTML = `
        <div class="metric-value">${load.toFixed(2)}</div>
        <div class="metric-subvalue">
          5min: ${cpuUsage.load_5min} • 15min: ${cpuUsage.load_15min}
        </div>`;
        
        // Update CPU history for chart
        this.cpuHistory.push(load);
        if (this.cpuHistory.length > this.maxHistory) {
          this.cpuHistory.shift();
        }

        this.updateChart(this.charts.cpu, this.cpuHistory);
    }

    updateMemoryUsage(resources) {
      if (!resources) return;

      const percentage = resources.memory_percentage || 0;

      const progress = document.getElementById('memoryProgress');
      progress.style.width = `${Math.min(percentage, 100)}%`;

      if (percentage > 90) {
        progress.className = 'progress-fill danger';
      } else if (percentage > 70) {
        progress.className = 'progress-fill warning';
      } else {
        progress.className = 'progress-fill';
      }

      document.getElementById('memoryUsage').innerHTML = `
        <div class="metric-value">${resources.memory_usage}</div>
        <div class="metric-subvalue">
          ${percentage}% used • Peak: ${resources.memory_peak}
        </div>`;

      // Update memory history for chart
      this.memoryHistory.push(percentage);
      if (this.memoryHistory.length > this.maxHistory) {
        this.memoryHistory.shift();
      }

      this.updateChart(this.charts.memory, this.memoryHistory);
    }

    updateDiskUsage(diskUsage) {
      if (!diskUsage) return;

      const progress = document.getElementById('diskProgress');
      progress.style.width = `${diskUsage.percentage}%`;

      if (diskUsage.percentage > 90) {
        progress.className = 'progress-fill danger';
      } else if (diskUsage.percentage > 70) {
        progress.className = 'progress-fill warning';
      } else {
        progress.className = 'progress-fill';
      }

      document.getElementById('diskUsage').innerHTML = `
        <div class="metric-value">${diskUsage.used} / ${diskUsage.total}</div>
        <div class="metric-subvalue">
          ${diskUsage.percentage}% used • ${diskUsage.free} free
        </div>`;
    }
    
    updateDatabaseStatus(database) {
      if (!database) return;

      const statusClass = database.status === 'connected' ? 'status-enabled' : 'status-disabled';
      const tablesInfo = database.tables ? ` • ${database.tables} tables` : '';

      document.getElementById('databaseStatus').innerHTML = `
        <span class="module-status ${statusClass}">${database.status.toUpperCase()}</span>
        <div class="metric-subvalue">
          ${database.connection} • ${database.version}${tablesInfo}
        </div>`;
    }

    updateApplicationStatus(application, queue) {
      if (!application) return;

      document.getElementById('applicationHealth').innerHTML = `
        <div class="metric-value">${application.uptime}</div>
        <div class="metric-subvalue">
          ${application.cache_driver} • ${application.queue_driver}<br>
          ${application.maintenance_mode ? '🛑 MAINTENANCE MODE' : '✅ RUNNING'}
        </div>`;

      document.getElementById('activeConnections').textContent = application.active_connections || 0;
      document.getElementById('queueSize').textContent = queue?.size || 0;
    }

    updateModulesStatus(modules) {
      const modulesList = document.getElementById('modulesList');
      
      if (!modules || modules.length === 0) {
        modulesList.innerHTML = '<div>No modules found</div>';
        return;
      }

      modulesList.innerHTML = modules.map(module => `
        <div class="module-item">
          <span>${module.name} v${module.version}</span>
          <span class="module-status ${module.enabled ? 'status-enabled' : 'status-disabled'}">
            ${module.enabled ? 'Enabled' : 'Disabled'}
          </span>
        </div>`).join('');
    }

    updateHealthStatus(health) {
      const healthStatus = document.getElementById('healthStatus');
      const healthText = document.getElementById('healthStatusText');

      if (!health) return;

      if (health.healthy) {
        healthStatus.className = 'status-dot status-connected';
        healthText.textContent = 'Healthy';
      } else {
        healthStatus.className = 'status-dot status-warning';
        healthText.textContent = `Issues: ${health.failed_checks?.join(', ') || 'Unknown'}`;
      }
    }

    updateChart(chart, data) {
      if (chart && data) {
        chart.data.datasets[0].data = data;
        chart.update('none');
      }
    }

    updateCharts(history) {
      if (history.cpu) {
        this.updateChart(this.charts.cpu, history.cpu.slice(-this.maxHistory));
      }
      if (history.memory) {
        this.updateChart(this.charts.memory, history.memory.slice(-this.maxHistory));
      }
    }

    updateConnectionStatus(status, text) {
      const statusDot = document.getElementById('connectionStatus');
      const statusText = document.getElementById('connectionText');

      statusDot.className = `status-dot status-${status}`;
      statusText.textContent = text;
    }

    updateLastUpdate() {
      const now = new Date();
      document.getElementById('lastUpdate').textContent = now.toLocaleTimeString();
    }

    reconnect() {
      setTimeout(() => {
        console.log('Attempting to reconnect...');
        if (this.connectionType === 'sse') {
          this.connectSSE();
        } else if (this.connectionType === 'json') {
          this.connectJSON();
        }
      }, 5000);
    }

    disconnect() {
      if (this.eventSource) {
        this.eventSource.close();
        this.eventSource = null;
      }
      this.updateConnectionStatus('disconnected', 'Disconnected');
    }
  }

  // Initialize monitor when page loads
  document.addEventListener('DOMContentLoaded', function() {
    window.laravelMonitor = new LaravelEventStreamMonitor();

    // Handle page visibility change
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        console.log('Page hidden, disconnecting streams');
        window.laravelMonitor.disconnect();
      } else {
        console.log('Page visible, reconnecting streams');
        if (window.laravelMonitor.connectionType === 'sse') {
          window.laravelMonitor.connectSSE();
        } else if (window.laravelMonitor.connectionType === 'json') {
          window.laravelMonitor.connectJSON();
        }
      }
    });

    // Handle page unload
    window.addEventListener('beforeunload', function() {
      window.laravelMonitor.disconnect();
    });
  });
</script>
@endsection

@section('styles')
<style>
    .monitor-container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .monitor-header {
            background: var(--dark);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-indicators {
            display: flex;
            gap: 20px;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-connected { background: var(--success); }
        .status-disconnected { background: var(--danger); }
        .status-warning { background: var(--warning); }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary);
        }
        
        .metric-card.critical {
            border-left-color: var(--danger);
            background: #ffeaea;
        }
        
        .metric-card.warning {
            border-left-color: var(--warning);
            background: #fff8e6;
        }
        
        .metric-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .metric-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .metric-subvalue {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .progress-fill.warning { background: var(--warning); }
        .progress-fill.danger { background: var(--danger); }
        
        .modules-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .module-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .module-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-enabled { background: #d4edda; color: #155724; }
        .status-disabled { background: #f8d7da; color: #721c24; }
        
        .connection-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
</style>
@endsection