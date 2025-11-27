@extends('viewmanager::layouts.app')

@section('page-title', 'Server Monitor')

@section('content')
@endsection

@section('scripts')
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
      this.eventSource = new EventSource('{{ route("cores.metrics") }}');
                
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