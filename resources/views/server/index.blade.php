@extends('viewmanager::layouts.app')

@section('page-title', 'Server Monitor')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <h5 class="card-title">🚀 Server Monitor</h5>
    </div>
    <div>
      <span class="status-dot status-connecting" id="connectionStatus"></span>
      <span id="connectionStatusText">Connecting...</span>
    </div>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col">
        <div class="controls">
          <button class="btn btn-primary" onclick="optimizedMonitor.setUpdateInterval(3)">Fast (3s)</button>
          <button class="btn btn-success" onclick="optimizedMonitor.setUpdateInterval(5)">Normal (5s)</button>
          <button class="btn btn-warning" onclick="optimizedMonitor.setUpdateInterval(10)">Slow (10s)</button>
          <button class="btn btn-danger" onclick="optimizedMonitor.pause()">Pause</button>
          <button class="btn btn-primary" onclick="optimizedMonitor.resume()">Resume</button>
          <span style="margin-left: auto; font-size: 12px; color: #666;">
                Update: <span id="currentInterval">5</span>s | 
                Last: <span id="lastUpdate">--:--:--</span>
          </span>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col">
        <div class="metrics-grid">
            <!-- CPU -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">CPU Load</div>
                    <span class="status-dot status-connecting" id="cpuStatus"></span>
                </div>
                <div id="cpuLoad">Loading...</div>
                <div class="chart-container">
                    <canvas id="cpuChart"></canvas>
                </div>
            </div>
            
            <!-- Memory -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">Memory Usage</div>
                    <span class="status-dot status-connecting" id="memoryStatus"></span>
                </div>
                <div id="memoryUsage">Loading...</div>
                <div class="progress-bar">
                    <div class="progress-fill" id="memoryProgress" style="width: 0%"></div>
                </div>
                <div class="chart-container">
                    <canvas id="memoryChart"></canvas>
                </div>
            </div>
            
            <!-- Disk -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">Disk Usage</div>
                    <span class="status-dot status-connecting" id="diskStatus"></span>
                </div>
                <div id="diskUsage">Loading...</div>
                <div class="progress-bar">
                    <div class="progress-fill" id="diskProgress" style="width: 0%"></div>
                </div>
            </div>
            
            <!-- Database -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">Database</div>
                    <span class="status-dot status-connecting" id="dbStatus"></span>
                </div>
                <div id="databaseStatus">Loading...</div>
            </div>
            
            <!-- System Info -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">System Info</div>
                </div>
                <div id="systemInfo">Loading...</div>
            </div>
            
            <!-- Health Status -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">Health Status</div>
                    <span class="status-dot status-connecting" id="healthStatus"></span>
                </div>
                <div id="healthInfo">Checking...</div>
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
      this.chartsEventSource = null;
      this.healthEventSource = null;
      
      this.metrics = {};
      this.cpuHistory = [];
      this.memoryHistory = [];
      this.maxHistory = 15;
      
      this.updateInterval = 5;
      this.isPaused = false;
      this.isPageVisible = true;
      this.lastChartUpdate = 0;
      this.chartUpdateInterval = 5000; // Update charts every 5 seconds

      this.charts = {
        cpu: null,
        memory: null
      };

      this.initCharts();
      this.initPageVisibility();
      this.connect();
    }

    initCharts() {
      // CPU Chart - simplified
      const cpuCtx = document.getElementById('cpuChart').getContext('2d');
      this.charts.cpu = new Chart(cpuCtx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            data: [],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { display: false },
            y: {
              display: false,
              beginAtZero: true,
              max: 10
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          },
          elements: {
            point: { radius: 0 }
          },
          animation: {
            duration: 0 // Disable animation for performance
          }
        }
      });

      // Memory Chart - simplified
      const memoryEl = document.getElementById('memoryChart');
      if(!memoryEl) return;
      
      const memoryCtx = memoryEl.getContext('2d');
      this.charts.memory = new Chart(memoryCtx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            data: [],
            borderColor: '#2ecc71',
            backgroundColor: 'rgba(46, 204, 113, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { display: false },
            y: {
              display: false,
              beginAtZero: true,
              max: 100
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          },
          elements: {
            point: { radius: 0 }
          },
          animation: {
            duration: 0
          }
        }
      });
    }

    initPageVisibility() {
      document.addEventListener('visibilitychange', () => {
        this.isPageVisible = !document.hidden;

        if (this.isPageVisible) {
          this.resume();
        } else {
          this.pause();
        }
      });
    }
    
    connect() {
      this.disconnect();
      
      const url = '{{ secure_url("https://vickyserver.my.id/server/api/v1/cores/metrics") }}' + `?interval=${this.updateInterval}`;

      try {
        this.eventSource = new EventSource(url);

        this.eventSource.onopen = () => {
          this.updateConnectionStatus('connected', 'Connected');
        };

        this.eventSource.addEventListener('metrics', (event) => {
          if (this.isPaused || !this.isPageVisible) return;

          const data = JSON.parse(event.data);
          this.handleMetricsUpdate(data);
          this.updateLastUpdate();
        });

        this.eventSource.addEventListener('heartbeat', (event) => {
          this.updateConnectionStatus('connected', 'Connected');
        });

        this.eventSource.onerror = (error) => {
          console.error('SSE connection error:', error);
          this.updateConnectionStatus('disconnected', 'Connection Error');
          this.reconnect();
        };

      } catch (error) {
        console.error('Failed to connect SSE:', error);
        this.updateConnectionStatus('disconnected', 'Connection Failed');
      }
    }

    handleMetricsUpdate(data) {
      this.metrics = data;
      this.updateEssentialDisplays();

      // Throttle chart updates
      const now = Date.now();
      if (now - this.lastChartUpdate > this.chartUpdateInterval) {
        this.updateCharts();
        this.lastChartUpdate = now;
      }
    }

    updateEssentialDisplays() {
      // CPU
      if (this.metrics.resources?.cpu_usage) {
        const load = this.metrics.resources.cpu_usage.load_1min || 0;
        document.getElementById('cpuLoad').innerHTML = `
          <div class="metric-value">${load.toFixed(2)}</div>
          <div class="metric-subvalue">1min average</div>`;
        this.updateStatus('cpuStatus', 'connected');
      }

      // Memory
      if (this.metrics.resources?.memory_percentage !== undefined) {
        const percent = this.metrics.resources.memory_percentage;
        document.getElementById('memoryUsage').innerHTML = `
          <div class="metric-value">${percent.toFixed(1)}%</div>
          <div class="metric-subvalue">${this.metrics.resources.memory_usage || ''}</div>`;

        const progress = document.getElementById('memoryProgress');
        progress.style.width = `${Math.min(percent, 100)}%`;
        progress.style.background = percent > 90 ? '#e74c3c' : (percent > 70 ? '#f39c12' : '#3498db');

        this.updateStatus('memoryStatus', 'connected');
      }

      // Disk
      if (this.metrics.resources?.disk_usage?.percentage !== undefined) {
        const percent = this.metrics.resources.disk_usage.percentage;
        document.getElementById('diskUsage').innerHTML = `
          <div class="metric-value">${percent.toFixed(1)}%</div>
          <div class="metric-subvalue">Disk usage</div>`;

        const progress = document.getElementById('diskProgress');
        progress.style.width = `${Math.min(percent, 100)}%`;
        progress.style.background = percent > 90 ? '#e74c3c' : (percent > 70 ? '#f39c12' : '#3498db');

        this.updateStatus('diskStatus', 'connected');
      }

      // Database
      if (this.metrics.database) {
        const status = this.metrics.database.status;
        document.getElementById('databaseStatus').innerHTML = `
          <div class="metric-value" style="color: ${status === 'connected' ? '#2ecc71' : '#e74c3c'}">
            ${status.toUpperCase()}
          </div>`;
        this.updateStatus('dbStatus', status === 'connected' ? 'connected' : 'disconnected');
      }

      // System Info
      if (this.metrics.system) {
      document.getElementById('systemInfo').innerHTML = `
        <div class="metric-value">${this.metrics.system.hostname}</div>
        <div class="metric-subvalue">
          ${this.metrics.system.environment} • Uptime: ${this.metrics.system.uptime}
        </div>`;
      }
    }

    updateCharts() {
      // Update CPU chart with latest data
      if (this.metrics.resources?.cpu_usage) {
        const load = this.metrics.resources.cpu_usage.load_1min || 0;
        this.cpuHistory.push(load);
        if (this.cpuHistory.length > this.maxHistory) {
          this.cpuHistory.shift();
        }

        this.charts.cpu.data.datasets[0].data = this.cpuHistory;
        this.charts.cpu.update('none');
      }

      // Update memory chart with latest data
      if (this.metrics.resources?.memory_percentage !== undefined) {
        const percent = this.metrics.resources.memory_percentage;
        this.memoryHistory.push(percent);
        if (this.memoryHistory.length > this.maxHistory) {
          this.memoryHistory.shift();
        }

        this.charts.memory.data.datasets[0].data = this.memoryHistory;
        this.charts.memory.update('none');
      }
    }

    setUpdateInterval(seconds) {
      this.updateInterval = seconds;
      document.getElementById('currentInterval').textContent = seconds;
      this.connect();
    }

    pause() {
      this.isPaused = true;
      this.updateConnectionStatus('disconnected', 'Paused');
    }
    
    resume() {
      this.isPaused = false;
      this.connect();
    }

    updateConnectionStatus(status, text) {
      const statusDot = document.getElementById('connectionStatus');
      const statusText = document.getElementById('connectionStatusText');

      statusDot.className = `status-dot status-${status}`;
      statusText.textContent = text;
    }
    
    updateStatus(elementId, status) {
      const element = document.getElementById(elementId);
      if (element) {
        element.className = `status-dot status-${status}`;
      }
    }

    updateLastUpdate() {
      const now = new Date();
      document.getElementById('lastUpdate').textContent = now.toLocaleTimeString();
    }

    reconnect() {
      setTimeout(() => {
        if (!this.isPaused && this.isPageVisible) {
          this.connect();
        }
      }, 5000);
    }

    disconnect() {
      if (this.eventSource) {
        this.eventSource.close();
      }
      if (this.chartsEventSource) {
        this.chartsEventSource.close();
      }
      if (this.healthEventSource) {
        this.healthEventSource.close();
      }
    }
  }

  // Initialize monitor when page loads
  document.addEventListener('DOMContentLoaded', function() {
    window.optimizedMonitor = new LaravelEventStreamMonitor();

    window.addEventListener('beforeunload', function() {
      window.optimizedMonitor.disconnect();
    });
  });
</script>
@endsection

@section('styles')
<style>
  .monitor-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
  }
        
        .monitor-header {
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .controls {
            display: flex;
            gap: 10px;
            padding: 10px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            overflow-x: scroll;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary);
        }
        
        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .metric-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .metric-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .metric-subvalue {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.5s ease;
        }
        
        .chart-container {
            height: 80px;
            margin-top: 10px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-connected { background: var(--success); }
        .status-disconnected { background: var(--danger); }
        .status-connecting { background: var(--warning); }
</style>
@endsection