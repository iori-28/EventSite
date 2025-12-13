<?php

/**
 * Analytics API Endpoint
 * Provides data for charts and statistics
 */

header('Content-Type: application/json');
session_start();

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

$db = Database::connect();
$type = $_GET['type'] ?? 'summary';

try {
    switch ($type) {
        case 'participants_per_event':
            // Get top 10 events with most participants
            $query = "
                SELECT 
                    e.id,
                    e.title,
                    COALESCE(e.category, 'Lainnya') as category,
                    COUNT(p.id) as participant_count
                FROM events e
                LEFT JOIN participants p ON e.id = p.event_id AND p.status = 'registered'
                WHERE e.status = 'approved'
                GROUP BY e.id, e.title
                ORDER BY participant_count DESC
                LIMIT 10
            ";

            $stmt = $db->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'event_category_popularity':
            // Get event count by category
            // Check if category column exists
            try {
                $query = "
                    SELECT 
                        COALESCE(category, 'Lainnya') as category,
                        COUNT(*) as event_count,
                        SUM(
                            (SELECT COUNT(*) FROM participants 
                             WHERE event_id = e.id AND status = 'registered')
                        ) as total_participants
                    FROM events e
                    WHERE status = 'approved'
                    GROUP BY category
                    ORDER BY event_count DESC
                ";

                $stmt = $db->query($query);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // If category column doesn't exist, return default data
                $data = [
                    [
                        'category' => 'Lainnya',
                        'event_count' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn(),
                        'total_participants' => 0
                    ]
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'registration_trend':
            // Get registration trend for last 6 months
            $query = "
                SELECT 
                    DATE_FORMAT(registered_at, '%Y-%m') as month,
                    COUNT(*) as registration_count
                FROM participants
                WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                AND status = 'registered'
                GROUP BY DATE_FORMAT(registered_at, '%Y-%m')
                ORDER BY month ASC
            ";

            $stmt = $db->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'event_status':
            // Get event count by status
            $query = "
                SELECT 
                    status,
                    COUNT(*) as count
                FROM events
                GROUP BY status
            ";

            $stmt = $db->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'summary':
            // Get summary statistics
            $stats = [
                'total_events' => $db->query("SELECT COUNT(*) FROM events")->fetchColumn(),
                'approved_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn(),
                'total_participants' => $db->query("SELECT COUNT(*) FROM participants WHERE status = 'registered'")->fetchColumn(),
                'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
                'total_panitia' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'panitia'")->fetchColumn(),
                'pending_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn(),
            ];

            // Get most popular category
            try {
                $categoryQuery = "
                    SELECT COALESCE(category, 'Lainnya') as category, COUNT(*) as count
                    FROM events
                    WHERE status = 'approved'
                    GROUP BY category
                    ORDER BY count DESC
                    LIMIT 1
                ";
                $categoryStmt = $db->query($categoryQuery);
                $popularCategory = $categoryStmt->fetch(PDO::FETCH_ASSOC);
                $stats['most_popular_category'] = $popularCategory['category'] ?? 'N/A';
            } catch (PDOException $e) {
                $stats['most_popular_category'] = 'N/A';
            }

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type parameter']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
