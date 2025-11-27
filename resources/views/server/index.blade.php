@extends('viewmanager::layouts.app')

@section('page-title', 'Server Monitor')

@section('content')
<div class="monitor-container">
        <div class="monitor-header">
            <h1>🚀 Server Monitor</h1>
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
            
            <!-- Modules Status -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-title">Modules ({{ count($modules) }})</div>
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  class ServerMonitor {
    constructor() {
      this.eventSource = null;
      this.healthSource = null;
      this.cpuData = [];
      this.maxDataPoints = 20;
      this.cpuChart = null;
                
      this.initCharts();
      this.connect();
    }
            
    initCharts() {
      const ctx = document.getElementById('cpuChart').getContext('2d');
      this.cpuChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: [],
          datasets: [{
            label: 'CPU Load (1min)',
            data: [],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 10,
              ticks: {
                callback: function(value) {
                  return value.toFixed(1);
                }
              }
            }
          }
        }
      });
    }
            
    connect() {
      // Connect to metrics stream
      this.eventSource = new EventSource('{{ route("api.cores.metrics") }}');
                
      this.eventSource.onopen = (event) => {
        this.updateConnectionStatus('connected', 'Connected');
        console.log('SSE connection established');
      };
                
      this.eventSource.onmessage = (event) => {
        this.updateConnectionStatus('connected', 'Connected');
        this.updateLastUpdate();
      };
                
      this.eventSource.addEventListener('connected', (event) => {
        const data = JSON.parse(event.data);
        console.log('Server monitor connected:', data);
      });
                
      this.eventSource.addEventListener('metrics', (event) => {
        const data = JSON.parse(event.data);
        this.updateMetrics(data);
        this.updateLastUpdate();
      });
                
      this.eventSource.addEventListener('heartbeat', (event) => {
        const data = JSON.parse(event.data);
        console.log('Heartbeat:', data);
        this.updateConnectionStatus('connected', 'Connected');
      });
                
      this.eventSource.addEventListener('error', (event) => {
        const data = JSON.parse(event.data);
        console.error('SSE error:', data);
        this.updateConnectionStatus('disconnected', 'Error');
      });
                
      this.eventSource.onerror = (error) => {
        console.error('EventSource error:', error);
        this.updateConnectionStatus('disconnected', 'Connection Error');
        this.reconnect();
      };
                
      // Connect to health stream
      this.connectHealthStream();
    }
            
    connectHealthStream() {
      this.healthSource = new EventSource('/api/server-monitor/health');
                
      this.healthSource.addEventListener('health', (event) => {
        const data = JSON.parse(event.data);
        this.updateHealthStatus(data);
      });
    }
            
    updateMetrics(data) {
      this.updateSystemInfo(data.system);
      this.updateResourceUsage(data.resources);
      this.updateCpuLoad(data.resources.cpu_usage);
      this.updateMemoryUsage(data.resources);
      this.updateDiskUsage(data.resources.disk_usage);
      this.updateDatabaseStatus(data.database);
      this.updateApplicationStatus(data.application, data.queue);
      this.updateModulesStatus(data.modules);
    }
            
            updateSystemInfo(system) {
                document.getElementById('systemInfo').innerHTML = `
                    <div class="metric-value">${system.hostname}</div>
                    <div class="metric-subvalue">
                        PHP ${system.php_version} • Laravel ${system.laravel_version}<br>
                        ${system.os} • ${system.environment}
                    </div>
                `;
            }
            
            updateResourceUsage(resources) {
                document.getElementById('resourceUsage').innerHTML = `
                    <div class="metric-value">${resources.memory_usage}</div>
                    <div class="metric-subvalue">
                        Peak: ${resources.memory_peak}<br>
                        Limit: ${resources.memory_limit}
                    </div>
                `;
            }
            
            updateCpuLoad(cpuUsage) {
                const load = cpuUsage.load_1min || 0;
                
                // Update CPU chart
                this.cpuData.push(load);
                if (this.cpuData.length > this.maxDataPoints) {
                    this.cpuData.shift();
                }
                
                this.cpuChart.data.labels = Array.from({length: this.cpuData.length}, (_, i) => i + 1);
                this.cpuChart.data.datasets[0].data = this.cpuData;
                this.cpuChart.update('none');
                
                document.getElementById('cpuLoad').innerHTML = `
                    <div class="metric-value">${load.toFixed(2)}</div>
                    <div class="metric-subvalue">
                        5min: ${cpuUsage.load_5min} • 15min: ${cpuUsage.load_15min}
                    </div>
                `;
            }
            
            updateMemoryUsage(resources) {
                const memoryUsed = this.parseBytes(resources.memory_usage);
                const memoryLimit = this.parseBytes(resources.memory_limit);
                const percentage = (memoryUsed / memoryLimit) * 100;
                
                const progress = document.getElementById('memoryProgress');
                progress.style.width = `${Math.min(percentage, 100)}%`;
                
                if (percentage > 90) {
                    progress.className = 'progress-fill danger';
                } else if (percentage > 70) {
                    progress.className = 'progress-fill warning';
                } else {
                    progress.className = 'progress-fill';
                }
            }
            
            updateDiskUsage(diskUsage) {
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
                    </div>
                `;
            }
            
            updateDatabaseStatus(database) {
                const statusClass = database.status === 'connected' ? 'status-enabled' : 'status-disabled';
                document.getElementById('databaseStatus').innerHTML = `
                    <span class="module-status ${statusClass}">${database.status.toUpperCase()}</span>
                    <div class="metric-subvalue">
                        ${database.connection} • ${database.version}
                    </div>
                `;
            }
            
            updateApplicationStatus(application, queue) {
                document.getElementById('applicationHealth').innerHTML = `
                    <div class="metric-value">${application.uptime}</div>
                    <div class="metric-subvalue">
                        ${application.cache_driver} • ${application.queue_driver}<br>
                        ${application.maintenance_mode ? 'MAINTENANCE MODE' : 'RUNNING'}
                    </div>
                `;
                
                document.getElementById('activeConnections').textContent = application.active_connections;
                document.getElementById('queueSize').textContent = queue.size;
            }
            
            updateModulesStatus(modules) {
                const modulesList = document.getElementById('modulesList');
                modulesList.innerHTML = modules.map(module => `
                    <div class="module-item">
                        <span>${module.name} v${module.version}</span>
                        <span class="module-status ${module.enabled ? 'status-enabled' : 'status-disabled'}">
                            ${module.enabled ? 'Enabled' : 'Disabled'}
                        </span>
                    </div>
                `).join('');
            }
            
            updateHealthStatus(health) {
                const healthStatus = document.getElementById('healthStatus');
                const healthText = document.getElementById('healthText');
                
                if (health.healthy) {
                    healthStatus.className = 'status-dot status-connected';
                    healthText.textContent = 'Healthy';
                } else {
                    healthStatus.className = 'status-dot status-warning';
                    healthText.textContent = 'Issues Detected';
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
                document.getElementById('lastUpdate').textContent = 
                    now.toLocaleTimeString();
            }
            
            parseBytes(bytesString) {
                const units = {B: 1, KB: 1024, MB: 1048576, GB: 1073741824, TB: 1099511627776};
                const match = bytesString.match(/^([\d.]+)\s*([KMGTP]?B)$/);
                if (match) {
                    return parseFloat(match[1]) * units[match[2]];
                }
                return 0;
            }
            
            reconnect() {
                if (this.eventSource) {
                    this.eventSource.close();
                }
                if (this.healthSource) {
                    this.healthSource.close();
                }
                
                setTimeout(() => {
                    console.log('Attempting to reconnect...');
                    this.connect();
                }, 5000);
            }
            
            disconnect() {
                if (this.eventSource) {
                    this.eventSource.close();
                }
                if (this.healthSource) {
                    this.healthSource.close();
                }
                this.updateConnectionStatus('disconnected', 'Disconnected');
            }
        }
        
        // Initialize server monitor when page loads
        document.addEventListener('DOMContentLoaded', function() {
            window.serverMonitor = new ServerMonitor();
            
            // Handle page unload
            window.addEventListener('beforeunload', function() {
                window.serverMonitor.disconnect();
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