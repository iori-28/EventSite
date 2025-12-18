<?php

/**
 * Analytics Service
 * 
 * Service untuk mengolah data analytics dan generate insights.
 * Menyediakan metrik, rekomendasi, dan export functionality.
 * 
 * Features:
 * - Calculate metrics (total events, participants, popular category)
 * - Generate recommendations berdasarkan data
 * - Export data ke CSV format
 * - Aggregation dan statistical analysis
 * 
 * @package EventSite\Services
 * @author EventSite Team
 */
class AnalyticsService
{
    /**
     * Get participants per event
     * 
     * Menghitung jumlah peserta untuk setiap event.
     * Useful untuk grafik bar chart.
     * 
     * @param PDO $db Database connection
     * @param int $limit Limit jumlah event (default: top 10)
     * @return array Array of events dengan participant count
     */
    public static function getParticipantsPerEvent($db, $limit = 10)
    {
        $stmt = $db->prepare("
            SELECT 
                e.id,
                e.title,
                COUNT(p.id) as participant_count
            FROM events e
            LEFT JOIN participants p ON e.id = p.event_id
            WHERE e.status = 'approved'
            GROUP BY e.id, e.title
            ORDER BY participant_count DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get event category popularity
     * 
     * Menghitung jumlah peserta per kategori event.
     * Untuk analitik: "Jenis event paling diminati mahasiswa"
     * 
     * @param PDO $db Database connection
     * @return array Array of categories dengan participant count
     */
    public static function getCategoryPopularity($db)
    {
        $stmt = $db->query("
            SELECT 
                e.category,
                COUNT(DISTINCT e.id) as event_count,
                COUNT(p.id) as participant_count,
                ROUND(AVG(participant_per_event.count), 2) as avg_participants
            FROM events e
            LEFT JOIN participants p ON e.id = p.event_id
            LEFT JOIN (
                SELECT event_id, COUNT(*) as count 
                FROM participants 
                GROUP BY event_id
            ) participant_per_event ON e.id = participant_per_event.event_id
            WHERE e.status = 'approved'
            GROUP BY e.category
            ORDER BY participant_count DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get registration trend (time-series)
     * 
     * Menghitung trend pendaftaran event dalam periode waktu.
     * Default: 6 bulan terakhir, per bulan.
     * 
     * @param PDO $db Database connection
     * @param int $months Jumlah bulan ke belakang (default: 6)
     * @return array Array of months dengan registration count
     */
    public static function getRegistrationTrend($db, $months = 6)
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime("-$i months"));

            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM participants 
                WHERE DATE_FORMAT(registered_at, '%Y-%m') = :month
            ");
            $stmt->execute([':month' => $month]);
            $count = $stmt->fetchColumn();

            $data[] = [
                'month' => $monthName,
                'count' => (int)$count
            ];
        }

        return $data;
    }

    /**
     * Get event status distribution
     * 
     * Menghitung distribusi status event (approved, pending, rejected, dll).
     * 
     * @param PDO $db Database connection
     * @return array Array of status dengan count
     */
    public static function getEventStatusDistribution($db)
    {
        $stmt = $db->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM events
            GROUP BY status
            ORDER BY count DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate summary metrics
     * 
     * Menghitung metrics ringkasan untuk dashboard.
     * 
     * @param PDO $db Database connection
     * @return array Associative array dengan metrics
     */
    public static function calculateMetrics($db)
    {
        // Total events
        $total_events = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();

        // Approved events
        $approved_events = $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn();

        // Total participants
        $total_participants = $db->query("SELECT COUNT(*) FROM participants")->fetchColumn();

        // Most popular category
        $popular = $db->query("
            SELECT e.category, COUNT(p.id) as count
            FROM events e
            LEFT JOIN participants p ON e.id = p.event_id
            WHERE e.status = 'approved'
            GROUP BY e.category
            ORDER BY count DESC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        // Average participants per event
        $avg_participants = $total_events > 0 ? round($total_participants / $total_events, 2) : 0;

        // Attendance rate (checked_in vs registered)
        $checked_in = $db->query("SELECT COUNT(*) FROM participants WHERE status = 'checked_in'")->fetchColumn();
        $attendance_rate = $total_participants > 0 ? round(($checked_in / $total_participants) * 100, 2) : 0;

        return [
            'total_events' => (int)$total_events,
            'approved_events' => (int)$approved_events,
            'total_participants' => (int)$total_participants,
            'popular_category' => $popular['category'] ?? 'N/A',
            'avg_participants_per_event' => $avg_participants,
            'attendance_rate' => $attendance_rate
        ];
    }

    /**
     * Generate recommendations based on analytics data
     * 
     * Generate insights dan rekomendasi berdasarkan data analytics.
     * Untuk membantu admin membuat keputusan.
     * 
     * @param PDO $db Database connection
     * @return array Array of recommendations
     */
    public static function generateRecommendations($db)
    {
        $recommendations = [];

        // 1. Check category popularity
        $categories = self::getCategoryPopularity($db);
        if (!empty($categories)) {
            $top_category = $categories[0];
            $recommendations[] = [
                'type' => 'category',
                'title' => 'Kategori Paling Diminati',
                'message' => "Event kategori '{$top_category['category']}' paling diminati dengan {$top_category['participant_count']} peserta. Pertimbangkan untuk menambah event kategori ini.",
                'priority' => 'high'
            ];

            // Check low-performing categories
            if (count($categories) > 1) {
                $low_category = end($categories);
                if ($low_category['participant_count'] < $top_category['participant_count'] * 0.3) {
                    $recommendations[] = [
                        'type' => 'category',
                        'title' => 'Kategori Perlu Promosi',
                        'message' => "Event kategori '{$low_category['category']}' memiliki peserta rendah ({$low_category['participant_count']}). Pertimbangkan strategi promosi lebih baik.",
                        'priority' => 'medium'
                    ];
                }
            }
        }

        // 2. Check attendance rate
        $metrics = self::calculateMetrics($db);
        if ($metrics['attendance_rate'] < 70) {
            $recommendations[] = [
                'type' => 'attendance',
                'title' => 'Tingkat Kehadiran Rendah',
                'message' => "Attendance rate hanya {$metrics['attendance_rate']}%. Pertimbangkan reminder lebih aktif atau incentive untuk meningkatkan kehadiran.",
                'priority' => 'high'
            ];
        }

        // 3. Check event approval rate
        $pending_events = $db->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn();
        if ($pending_events > 5) {
            $recommendations[] = [
                'type' => 'approval',
                'title' => 'Event Menunggu Approval',
                'message' => "Ada {$pending_events} event menunggu approval. Segera review untuk tidak menghambat panitia.",
                'priority' => 'high'
            ];
        }

        // 4. Check registration trend
        $trend = self::getRegistrationTrend($db, 3);
        if (count($trend) >= 3) {
            $recent_avg = ($trend[1]['count'] + $trend[2]['count']) / 2;
            $oldest = $trend[0]['count'];

            if ($recent_avg < $oldest * 0.7) {
                $recommendations[] = [
                    'type' => 'trend',
                    'title' => 'Trend Pendaftaran Menurun',
                    'message' => "Pendaftaran event mengalami penurunan {$recent_avg} dari {$oldest}. Evaluasi kualitas event atau strategi promosi.",
                    'priority' => 'medium'
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Export data to CSV format
     * 
     * Generate CSV file dari data array dan trigger download.
     * 
     * @param array $data Data array untuk export
     * @param string $filename Nama file CSV (default: export.csv)
     * @param array $headers Custom headers (optional, auto-detect from data)
     * @return void (triggers download)
     */
    public static function exportToCSV($data, $filename = 'export.csv', $headers = null)
    {
        // Set headers untuk CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add BOM untuk Excel compatibility (UTF-8)
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if (empty($data)) {
            fputcsv($output, ['No data available']);
            fclose($output);
            exit;
        }

        // Auto-detect headers dari first row kalau tidak disediakan
        if ($headers === null) {
            $headers = array_keys($data[0]);
        }

        // Write headers
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Export participants per event to CSV
     * 
     * Shortcut method untuk export participants data.
     * 
     * @param PDO $db Database connection
     * @return void (triggers download)
     */
    public static function exportParticipantsCSV($db)
    {
        $data = self::getParticipantsPerEvent($db, 100); // Get all, not just top 10

        // Format data
        $formatted = array_map(function ($row) {
            return [
                'Event Title' => $row['title'],
                'Participant Count' => $row['participant_count']
            ];
        }, $data);

        self::exportToCSV($formatted, 'participants_per_event_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export category popularity to CSV
     * 
     * Shortcut method untuk export category data.
     * 
     * @param PDO $db Database connection
     * @return void (triggers download)
     */
    public static function exportCategoryCSV($db)
    {
        $data = self::getCategoryPopularity($db);

        // Format data
        $formatted = array_map(function ($row) {
            return [
                'Category' => $row['category'],
                'Event Count' => $row['event_count'],
                'Total Participants' => $row['participant_count'],
                'Avg Participants' => $row['avg_participants']
            ];
        }, $data);

        self::exportToCSV($formatted, 'category_popularity_' . date('Y-m-d') . '.csv');
    }

    /**
     * Export full analytics report to CSV
     * 
     * Export comprehensive analytics report dengan semua metrics.
     * 
     * @param PDO $db Database connection
     * @return void (triggers download)
     */
    public static function exportFullReport($db)
    {
        $metrics = self::calculateMetrics($db);
        $categories = self::getCategoryPopularity($db);
        $trend = self::getRegistrationTrend($db, 12);

        // Combine all data
        $report = [
            ['SUMMARY METRICS', ''],
            ['Total Events', $metrics['total_events']],
            ['Approved Events', $metrics['approved_events']],
            ['Total Participants', $metrics['total_participants']],
            ['Popular Category', $metrics['popular_category']],
            ['Avg Participants/Event', $metrics['avg_participants_per_event']],
            ['Attendance Rate', $metrics['attendance_rate'] . '%'],
            ['', ''],
            ['CATEGORY POPULARITY', ''],
            ['Category', 'Participants'],
        ];

        foreach ($categories as $cat) {
            $report[] = [$cat['category'], $cat['participant_count']];
        }

        $report[] = ['', ''];
        $report[] = ['REGISTRATION TREND (12 Months)', ''];
        $report[] = ['Month', 'Registrations'];

        foreach ($trend as $t) {
            $report[] = [$t['month'], $t['count']];
        }

        self::exportToCSV($report, 'analytics_full_report_' . date('Y-m-d') . '.csv');
    }
}
