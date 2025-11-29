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
                const memoryCtx = document.getElementById('memoryChart').getContext('2d');
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
                
                const url = `/api/server-monitor/sse/metrics-optimized?interval=${this.updateInterval}`;
                
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
                        <div class="metric-subvalue">1min average</div>
                    `;
                    this.updateStatus('cpuStatus', 'connected');
                }
                
                // Memory
                if (this.metrics.resources?.memory_percentage !== undefined) {
                    const percent = this.metrics.resources.memory_percentage;
                    document.getElementById('memoryUsage').innerHTML = `
                        <div class="metric-value">${percent.toFixed(1)}%</div>
                        <div class="metric-subvalue">${this.metrics.resources.memory_usage || ''}</div>
                    `;
                    
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
                        <div class="metric-subvalue">Disk usage</div>
                    `;
                    
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
                        </div>
                    `;
                    this.updateStatus('dbStatus', status === 'connected' ? 'connected' : 'disconnected');
                }
                
                // System Info
                if (this.metrics.system) {
                    document.getElementById('systemInfo').innerHTML = `
                        <div class="metric-value">${this.metrics.system.hostname}</div>
                        <div class="metric-subvalue">
                            ${this.metrics.system.environment} • Uptime: ${this.metrics.system.uptime}
                        </div>
                    `;
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
    window.optimizedMonitor = new OptimizedServerMonitor();
            
            window.addEventListener('beforeunload', function() {
                window.optimizedMonitor.disconnect();
            });
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