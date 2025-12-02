@extends('viewmanager::layouts.app')

@section('page-title', 'Server Monitor')

@section('content')
<div class="card">
  <div class="card-header text-end">
    <div class="float-start me-auto">
      <h5 class="card-title">🚀 Server Monitor</h5>
      <span class="small ms-2" id="lastUpdate"></span>
    </div>
    <div>
      <span class="status-dot status-connecting" id="connectionStatus"></span>
      <span id="connectionStatusText">Connecting...</span>
    </div>
  </div>
  <div class="card-body">
    <div class="card-group mt-2">
      
      <div class="card">
        <div class="card-body">
          <div class="text-body-secondary small text-uppercase fw-semibold">{{ $dataServer["distro"]["name"] }} <span class="text-muted">{{ $dataServer["distro"]["version"] }}</span>
          </div>
          <div class="fs-6 fw-semibold py-3">{{ $dataServer["model"] }} <span class="text-muted">{{ $dataServer["kernel"] }}</span></div>
          <div class="font-weight-bold text-muted">{{ $dataServer["cpu"][0]["Vendor"] }}</div>
          <span class="text-muted small">{{ $dataServer["cpu"][0]["Model"] }}</span>
        </div>
      </div>
      
      <div class="card">
        <div class="card-body text-body-secondary small text-uppercase fw-semibold">Uptime: <span class="text-muted" id="uptime-text"></span></div>
      </div>
      
      <!-- Disk -->
      <div class="card">
        <div class="card-body">
          <div class="text-body-secondary text-end">
            <svg class="icon icon-xxl">
              <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-storage') }}"></use>
            </svg>
          </div>
          <div class="text-body-secondary small text-uppercase fw-semibold">
            <span class="status-dot status-connecting me-2" id="diskStatus"></span>
            Disk Usage
          </div>
          <div class="progress-group">
            <div class="progress-group-header">
              <div class="fs-6 fw-semibold py-3" id="diskUsage">Loading...</div>
              <div class="ms-auto font-weight-bold" id="diskUsagePercentage">0%</div>
            </div>
            <div class="progress-group-bars">
              <div class="progress progress-thin">
                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="diskProgress"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- /. Disk -->
      
      <!-- Database -->
      <div class="card">
        <div class="card-body">
          <div class="text-body-secondary text-end">
            <svg class="icon icon-xxl">
              <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-layers') }}"></use>
            </svg>
          </div>
          <div class="text-body-secondary small text-uppercase fw-semibold">
            <span class="status-dot status-connecting" id="dbStatus"></span>
            Database
          </div>
          <div id="databaseStatus">Loading...</div>
        </div>
      </div>
      <!-- /. Database -->
      
      <!-- System Info -->
      <div class="card">
        <div class="card-body">
          <div class="text-body-secondary text-end">
            <svg class="icon icon-xxl">
              <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-lan') }}"></use>
            </svg>
          </div>
          <div class="text-body-secondary small text-uppercase fw-semibold">System Info</div>
          <div id="systemInfo">Loading...</div>
        </div>
      </div>
      <!-- /. System Info -->
      
      <!-- Health Status -->
      <div class="card">
        <div class="card-body">
          <div class="text-body-secondary text-end">
            <span class="status-dot status-connecting" id="healthStatus"></span>
          </div>
          <div class="text-body-secondary small text-uppercase fw-semibold">Health Status</div>
          <div id="healthInfo">Coming soon...</div>
        </div>
      </div>
      <!-- /. Health Status -->
    </div>
    
    <div class="card-group mt-2">
            <!-- CPU -->
      <div class="card">
        <div class="card-header">
          <strong>CPU</strong>
        </div>
        <div class="card-body">
          <div class="c-chart-wrapper">
            <canvas id="chart-cpu" height="200px"></canvas>
          </div>
        </div>
      </div>
      <!-- /. CPU -->
      
      <!-- CPU Temps -->
      <div class="card">
        <div class="card-header">
          <strong>CPU Temps</strong>
        </div>
        <div class="card-body">
          <div class="c-chart-wrapper">
            <canvas id="chart-cpu-temps" height="300px"></canvas>
          </div>
        </div>
      </div>
      <!-- /. CPU Temp -->
    </div>
    
    
    <div class="card-group mt-2">
      <!-- Memory -->
      <div class="card">
        <div class="card-header">
          <strong>Memory</strong>
          <span class="small ms-2" id="memory-percentage"></span>
        </div>
        <div class="card-body">
          <div class="c-chart-wrapper">
            <canvas id="chart-memory"></canvas>
          </div>
        </div>
        <div class="card-footer">
          <div class="font-weight-bold fs-6">Total <span id="memory-total"></span></div>
        </div>
      </div>
      <!-- /. Memory -->
    </card-group>
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
      this.memoryHistory = [];
      this.maxHistory = 30;
      
      this.updateInterval = 5;
      this.isPaused = false;
      this.isPageVisible = true;
      this.lastChartUpdate = 0;
      this.chartUpdateInterval = 5000; // Update charts every 5 seconds

      this.charts = {
        cpu: null,
        cpuTemps: null,
        memory: null
      };

      this.initCharts();
      this.initPageVisibility();
      this.connect();
    }

    initCharts() {
      // CPU Chart - simplified
      this.charts.cpu = new Chart(document.getElementById('chart-cpu'), {
        type: 'bar',
        data: {
          labels: [],
          datasets: [{
            data: [],
            label: "CPU",
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgb(54, 162, 235)',
            highlightFill: 'rgba(151, 187, 205, 0.75)',
            highlightStroke: 'rgba(151, 187, 205, 1)',
          }]
        },
        options: {
          responsive: true,
          beginAtZero: true
        }
      });
      
      this.charts.cpuTemps = new Chart(document.getElementById('chart-cpu-temps'), {
        type: 'bar',
        data: {
          labels: [],
          datasets: [{
            data: [],
            label: 'CPU Temp',
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgb(54, 162, 235)',
            highlightFill: 'rgba(151, 187, 205, 0.75)',
            highlightStroke: 'rgba(151, 187, 205, 1)',
          }]
        },
        options: {
          responsive: true,
          beginAtZero: true,
          scales: {
            y: {
              beginAtZero: true,
              suggestedMax: 100,
              max: 100
            }
          }
        }
      });

      // Memory Chart - simplified
      this.charts.memory = new Chart(document.getElementById('chart-memory'), {
        type: 'doughnut',
        data: {
          labels: ['Used', 'Free'],
          datasets: [{
            data: [],
            backgroundColor: ['#FF6384', '#36A2EB'],
            hoverBackgroundColor: ['#FF6384', '#36A2EB']
          }]
        },
        options: {
          responsive: true
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
      // Uptime
      if(this.metrics.uptime){
        document.getElementById('uptime-text').textContent = this.metrics.uptime.text
      }
      
      // Memory

      // Disk
      if (this.metrics.resources?.disk_usage?.percentage !== undefined) {
        const disk = this.metrics.resources.disk_usage;
        document.getElementById('diskUsage').innerText = `${disk.used}/${disk.total}`;
          document.getElementById('diskUsagePercentage').innerHtml = `${disk.percentage.toFixed(1)}%`;

        const progress = document.getElementById('diskProgress');
        progress.style.width = `${Math.min(disk.percentage, 100)}%`;
        progress.style.background = disk.percentage > 90 ? '#e74c3c' : (disk.percentage > 70 ? '#f39c12' : '#3498db');

        this.updateStatus('diskStatus', 'connected');
      }

      // Database
      if (this.metrics.database) {
        const database = this.metrics.database;
        const tablesInfo = database.tables ? ` ● ${database.tables} tables` : "";
        document.getElementById('databaseStatus').innerHTML = `
          <div class="metric-value" style="color: ${database.status === 'connected' ? '#2ecc71' : '#e74c3c'}">
            ${database.status.toUpperCase()}
          </div>
          <div class="small text-muted mt-2">${database.connection} ● ${database.version}${tablesInfo}</div>`;
        this.updateStatus('dbStatus', database.status === 'connected' ? 'connected' : 'disconnected');
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
      if (this.metrics.cpu) {
        const cpus = this.metrics.cpu;
        
        this.charts.cpu.data.datasets[0].data = cpus.map(cpu => cpu.usage_percentage);
        this.charts.cpu.data.labels = Object.keys(cpus).map(cpu => `Core ${cpu}`);
        
        this.charts.cpu.update('none');
      }
      
      // Update CPU Temps chart
      if(this.metrics.temps){
        const temps = this.metrics.temps;
        
        this.charts.cpuTemps.data.datasets[0].data = temps.map(cpu => cpu.temp);
        this.charts.cpuTemps.data.labels = temps.map(cpu => cpu.name);
        
        this.charts.cpuTemps.update('none');
      }

      // Update memory chart with latest data
      if (this.metrics.ram !== undefined) {
        const percent = this.metrics.ram.percentage;
        const total = this.metrics.ram.total;
        const free = this.metrics.ram.free;
        const used = this.metrics.ram.used;
        
        document.getElementById('memory-percentage').textContent = `${percent.toFixed(2)}%`;
        document.getElementById('memory-total').textContent = this.humanFileSize(total, false, 2);

        this.charts.memory.data.datasets[0].data = [used, free];
        this.charts.memory.update('none');
      }
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
    
    /**
     * Format bytes as human-readable text.
     * 
     * @param bytes Number of bytes.
     * @param si True to use metric (SI) units, aka powers of 1000. False to use 
     * binary (IEC), aka powers of 1024.
     * @param dp Number of decimal places to display.
     * 
     * @return Formatted string.
     */
    humanFileSize(bytes, si=false, dp=1) {
      const thresh = si ? 1000 : 1024;
      
      if (Math.abs(bytes) < thresh) {
        return bytes + ' B';
      }
      
      const units = si ? ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] : ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
      let u = -1;
      const r = 10**dp;
      
      do {
        bytes /= thresh;
        ++u;
      } while (Math.round(Math.abs(bytes) * r) / r >= thresh && u < units.length - 1);
      
      return bytes.toFixed(dp) + ' ' + units[u];
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
            border-bottom: 1px solid #dee2e6;
            overflow-x: scroll;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .metric-card {
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--cui-primary);
        }
        
        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .metric-title {
            font-weight: 600;
            font-size: 14px;
        }
        
        .metric-value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .metric-subvalue {
            font-size: 12px;
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
        
        .status-connected { background: var(--cui-success); }
        .status-disconnected { background: var(--cui-danger); }
        .status-connecting { background: var(--cui-warning); }
</style>
@endsection