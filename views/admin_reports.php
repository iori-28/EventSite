<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Data for Analytics

// 1. Total User Breakdown
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$user_roles = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Event Stats
$total_events = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$event_status = $db->query("SELECT status, COUNT(*) as count FROM events GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Top Active Events (Most Participants)
$top_events = $db->query("
    SELECT e.title, COUNT(p.id) as participant_count 
    FROM events e 
    LEFT JOIN participants p ON e.id = p.event_id 
    GROUP BY e.id 
    ORDER BY participant_count DESC 
    LIMIT 5
")->fetchAll();

// 4. Registration Trends (Last 7 Days)
$date_range = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $date_range[] = $date;
}

$daily_registrations = [];
foreach ($date_range as $date) {
    // Note: using created_at from participants as registered_at
    $count = $db->query("SELECT COUNT(*) FROM participants WHERE DATE(registered_at) = '$date'")->fetchColumn();
    $daily_registrations[$date] = $count;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Grafik - EventSite Admin</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-top: 20px;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            padding-top: 20px;
            border-bottom: 2px solid #ddd;
        }

        .bar {
            width: 40px;
            background: var(--primary-color);
            border-radius: 4px 4px 0 0;
            transition: height 0.3s;
            position: relative;
        }

        .bar:hover {
            opacity: 0.8;
        }

        .bar-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #666;
            white-space: nowrap;
        }

        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-title">
                    <button class="sidebar-toggle" onclick="toggleSidebar()" style="display:none; background:none; border:none; font-size:24px; cursor:pointer; margin-right:10px;">☰</button>
                    <h1>Laporan & Statistik</h1>
                    <div class="header-breadcrumb">Analisis performa platform</div>
                </div>
            </header>

            <div class="grid grid-2" style="gap: 30px;">
                <!-- User Distribution -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0;">Distribusi Pengguna</h3>
                    </div>
                    <div class="card-body" style="padding: 20px;">
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div style="flex: 1;">
                                <div style="font-size: 36px; font-weight: bold; margin-bottom: 5px;"><?= $total_users ?></div>
                                <div style="color: var(--text-muted); font-size: 14px;">Total Akun Terdaftar</div>
                            </div>
                            <div style="flex: 1;">
                                <?php foreach ($user_roles as $role => $count): ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="text-transform: capitalize;"><?= $role ?></span>
                                        <strong><?= $count ?></strong>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: #eee; border-radius: 3px; margin-bottom: 15px;">
                                        <div style="width: <?= ($count / $total_users) * 100 ?>%; height: 100%; background: <?= $role === 'admin' ? '#333' : ($role === 'panitia' ? '#8e44ad' : '#3498db') ?>; border-radius: 3px;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Status -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0;">Status Event</h3>
                    </div>
                    <div class="card-body" style="padding: 20px;">
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <div style="flex: 1;">
                                <div style="font-size: 36px; font-weight: bold; margin-bottom: 5px;"><?= $total_events ?></div>
                                <div style="color: var(--text-muted); font-size: 14px;">Total Event Dibuat</div>
                            </div>
                            <div style="flex: 1;">
                                <?php
                                $statuses = ['pending', 'approved', 'rejected', 'cancelled'];
                                foreach ($statuses as $stat):
                                    $cnt = $event_status[$stat] ?? 0;
                                    $color = match ($stat) {
                                        'pending' => '#ffc107',
                                        'approved' => '#28a745',
                                        'rejected' => '#dc3545',
                                        'cancelled' => '#6c757d',
                                        default => '#007bff'
                                    };
                                ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <span style="text-transform: capitalize;"><?= $stat ?></span>
                                        <strong><?= $cnt ?></strong>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: #eee; border-radius: 3px; margin-bottom: 15px;">
                                        <div style="width: <?= $total_events > 0 ? ($cnt / $total_events) * 100 : 0 ?>%; height: 100%; background: <?= $color ?>; border-radius: 3px;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registration Trends Chart -->
                <div class="card" style="grid-column: span 2;">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0;">Tren Pendaftaran Peserta (7 Hari Terakhir)</h3>
                    </div>
                    <div class="card-body" style="padding: 20px;">
                        <div class="bar-chart">
                            <?php
                            $max_val = max(array_values($daily_registrations)) ?: 1;
                            foreach ($daily_registrations as $date => $cnt):
                                $height = ($cnt / $max_val) * 100; // max height %
                                $day = date('D', strtotime($date));
                            ?>
                                <div class="bar" style="height: <?= $height > 0 ? $height : 1 ?>%;">
                                    <span class="bar-value"><?= $cnt ?></span>
                                    <span class="bar-label"><?= $day ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Events Table -->
                <div class="card" style="grid-column: span 2;">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0;">Top 5 Event (Paling Banyak Peserta)</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($top_events) > 0): ?>
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8f9fa; text-align: left;">
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Judul Event</th>
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Jumlah Peserta</th>
                                        <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_events as $evt): ?>
                                        <tr>
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <strong><?= htmlspecialchars($evt['title']) ?></strong>
                                            </td>
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <?= $evt['participant_count'] ?> orang
                                            </td>
                                            <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                                <div style="width: 100%; max-width: 200px; height: 6px; background: #eee; border-radius: 3px;">
                                                    <div style="width: <?= ($evt['participant_count'] / ($max_val ?: 100)) * 100 ?>%; height: 100%; background: var(--primary-color); border-radius: 3px;"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px;">
                                <p class="text-muted">Belum ada data event.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>