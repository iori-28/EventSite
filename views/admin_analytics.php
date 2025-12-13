<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .chart-card h3 {
            margin: 0 0 20px 0;
            font-size: 18px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .summary-card h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
        }
        
        .summary-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .summary-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .summary-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php 
            $page_title = 'Analytics Dashboard';
            $breadcrumb = 'Statistik dan visualisasi data event';
            include 'components/dashboard_header.php'; 
            ?>

            <!-- Summary Stats -->
            <div class="summary-stats">
                <div class="summary-card">
                    <h4>Total Events</h4>
                    <p class="value" id="stat-total-events">-</p>
                </div>
                <div class="summary-card green">
                    <h4>Approved Events</h4>
                    <p class="value" id="stat-approved-events">-</p>
                </div>
                <div class="summary-card orange">
                    <h4>Total Participants</h4>
                    <p class="value" id="stat-total-participants">-</p>
                </div>
                <div class="summary-card blue">
                    <h4>Most Popular Category</h4>
                    <p class="value" id="stat-popular-category" style="font-size: 20px;">-</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="analytics-grid">
                <!-- Participants per Event Chart -->
                <div class="chart-card">
                    <h3>
                        <span>📊</span>
                        <span>Peserta per Event (Top 10)</span>
                    </h3>
                    <div class="chart-container">
                        <canvas id="participantsChart"></canvas>
                    </div>
                </div>

                <!-- Event Category Popularity Chart -->
                <div class="chart-card">
                    <h3>
                        <span>🎯</span>
                        <span>Popularitas Kategori Event</span>
                    </h3>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Registration Trend Chart -->
                <div class="chart-card">
                    <h3>
                        <span>📈</span>
                        <span>Trend Pendaftaran (6 Bulan Terakhir)</span>
                    </h3>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- Event Status Chart -->
                <div class="chart-card">
                    <h3>
                        <span>📋</span>
                        <span>Status Event</span>
                    </h3>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Chart instances
        let participantsChart, categoryChart, trendChart, statusChart;

        // Color schemes
        const colors = {
            primary: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'],
            gradient: {
                purple: ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)'],
                green: ['rgba(17, 153, 142, 0.8)', 'rgba(56, 239, 125, 0.8)'],
                orange: ['rgba(240, 147, 251, 0.8)', 'rgba(245, 87, 108, 0.8)'],
                blue: ['rgba(79, 172, 254, 0.8)', 'rgba(0, 242, 254, 0.8)']
            }
        };

        // Fetch and display summary stats
        async function loadSummary() {
            try {
                const response = await fetch('api/analytics.php?type=summary');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('stat-total-events').textContent = data.total_events;
                    document.getElementById('stat-approved-events').textContent = data.approved_events;
                    document.getElementById('stat-total-participants').textContent = data.total_participants;
                    document.getElementById('stat-popular-category').textContent = data.most_popular_category;
                }
            } catch (error) {
                console.error('Error loading summary:', error);
            }
        }

        // Load Participants per Event Chart
        async function loadParticipantsChart() {
            try {
                const response = await fetch('api/analytics.php?type=participants_per_event');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const data = result.data;
                    const labels = data.map(item => item.title.length > 20 ? item.title.substring(0, 20) + '...' : item.title);
                    const values = data.map(item => parseInt(item.participant_count));
                    
                    const ctx = document.getElementById('participantsChart').getContext('2d');
                    participantsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Jumlah Peserta',
                                data: values,
                                backgroundColor: colors.primary,
                                borderRadius: 8,
                                borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(context) {
                                            return data[context[0].dataIndex].title;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading participants chart:', error);
            }
        }

        // Load Category Popularity Chart
        async function loadCategoryChart() {
            try {
                const response = await fetch('api/analytics.php?type=event_category_popularity');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const data = result.data;
                    const labels = data.map(item => item.category);
                    const values = data.map(item => parseInt(item.event_count));
                    
                    const ctx = document.getElementById('categoryChart').getContext('2d');
                    categoryChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: colors.primary,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value} event (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading category chart:', error);
            }
        }

        // Load Registration Trend Chart
        async function loadTrendChart() {
            try {
                const response = await fetch('api/analytics.php?type=registration_trend');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    
                    // Format month labels
                    const labels = data.map(item => {
                        const [year, month] = item.month.split('-');
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        return `${monthNames[parseInt(month) - 1]} ${year}`;
                    });
                    const values = data.map(item => parseInt(item.registration_count));
                    
                    const ctx = document.getElementById('trendChart').getContext('2d');
                    trendChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Pendaftaran',
                                data: values,
                                borderColor: '#667eea',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 5,
                                pointBackgroundColor: '#667eea',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 7
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading trend chart:', error);
            }
        }

        // Load Event Status Chart
        async function loadStatusChart() {
            try {
                const response = await fetch('api/analytics.php?type=event_status');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const data = result.data;
                    const labels = data.map(item => {
                        const statusMap = {
                            'approved': 'Disetujui',
                            'pending': 'Menunggu',
                            'rejected': 'Ditolak',
                            'draft': 'Draft',
                            'cancelled': 'Dibatalkan'
                        };
                        return statusMap[item.status] || item.status;
                    });
                    const values = data.map(item => parseInt(item.count));
                    
                    const ctx = document.getElementById('statusChart').getContext('2d');
                    statusChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: values,
                                backgroundColor: colors.primary,
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading status chart:', error);
            }
        }

        // Initialize all charts
        async function initCharts() {
            await loadSummary();
            await loadParticipantsChart();
            await loadCategoryChart();
            await loadTrendChart();
            await loadStatusChart();
        }

        // Load charts when page is ready
        document.addEventListener('DOMContentLoaded', initCharts);
    </script>
</body>
</html>
