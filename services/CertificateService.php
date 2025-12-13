<?php

/**
 * Certificate Service
 * Handles certificate generation using TCPDF or FPDF
 * For now using simple HTML-to-image approach
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class CertificateService
{
    /**
     * Generate certificate for a participant
     * @param int $participant_id
     * @return array ['success' => bool, 'file_path' => string, 'certificate_id' => int]
     */
    public static function generate($participant_id)
    {
        // Validate input
        if (!is_numeric($participant_id) || $participant_id <= 0) {
            return ['success' => false, 'error' => 'Invalid participant ID'];
        }

        $db = Database::connect();

        // Get participant details
        $stmt = $db->prepare("
            SELECT 
                p.id as participant_id,
                u.name as participant_name,
                u.email as participant_email,
                e.title as event_title,
                e.start_at,
                e.end_at,
                p.registered_at,
                p.status
            FROM participants p
            JOIN users u ON p.user_id = u.id
            JOIN events e ON p.event_id = e.id
            WHERE p.id = ? AND p.status = 'checked_in'
        ");
        $stmt->execute([$participant_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return ['success' => false, 'error' => 'Participant not found or not checked in'];
        }

        // Check if certificate already exists
        $check = $db->prepare("SELECT * FROM certificates WHERE participant_id = ?");
        $check->execute([$participant_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            return [
                'success' => true,
                'file_path' => $existing['file_path'],
                'certificate_id' => $existing['id'],
                'message' => 'Certificate already exists'
            ];
        }

        // Generate certificate HTML
        $html = self::generateHTML($data);

        // Save as HTML file (can be converted to PDF later with library)
        $filename = 'certificate_' . $participant_id . '_' . time() . '.html';
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/certificates/';

        // Create directory if not exists with secure permissions
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                return ['success' => false, 'error' => 'Failed to create certificate directory'];
            }
        }

        $file_path = $upload_dir . $filename;
        if (file_put_contents($file_path, $html) === false) {
            return ['success' => false, 'error' => 'Failed to write certificate file'];
        }

        // Save to database
        $stmt = $db->prepare("
            INSERT INTO certificates (participant_id, file_path, issued_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$participant_id, 'certificates/' . $filename]);
        $certificate_id = $db->lastInsertId();

        return [
            'success' => true,
            'file_path' => 'certificates/' . $filename,
            'certificate_id' => $certificate_id,
            'message' => 'Certificate generated successfully'
        ];
    }

    /**
     * Generate certificate HTML template
     */
    private static function generateHTML($data)
    {
        $participant_name = htmlspecialchars($data['participant_name']);
        $event_title = htmlspecialchars($data['event_title']);
        $date = date('d F Y', strtotime($data['start_at']));
        $issue_date = date('d F Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate - {$participant_name}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .certificate {
            background: white;
            width: 1000px;
            padding: 60px 80px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            border: 20px solid #f8f9fa;
            outline: 2px solid #667eea;
            outline-offset: -30px;
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            bottom: 40px;
            border: 1px solid #ddd;
            pointer-events: none;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
        }
        
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 18px;
            font-weight: 300;
        }
        
        .divider {
            width: 100px;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 30px auto;
        }
        
        .content {
            text-align: center;
            margin: 40px 0;
        }
        
        .content p {
            font-size: 18px;
            color: #555;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .recipient-name {
            font-family: 'Great Vibes', cursive;
            font-size: 56px;
            color: #667eea;
            margin: 30px 0;
            font-weight: 400;
        }
        
        .event-title {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #2c3e50;
            font-weight: 700;
            margin: 20px 0;
        }
        
        .footer {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature {
            text-align: center;
            flex: 1;
        }
        
        .signature-line {
            width: 200px;
            border-top: 2px solid #333;
            margin: 50px auto 10px;
        }
        
        .signature p {
            font-size: 14px;
            color: #555;
        }
        
        .signature .name {
            font-weight: bold;
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .seal {
            position: absolute;
            bottom: 80px;
            right: 100px;
            width: 120px;
            height: 120px;
            border: 4px solid #667eea;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .seal .seal-text {
            font-size: 12px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
        }
        
        .issue-date {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .certificate {
                box-shadow: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="logo">ES</div>
            <h1>CERTIFICATE</h1>
            <p>of Participation</p>
        </div>
        
        <div class="divider"></div>
        
        <div class="content">
            <p>This certificate is proudly presented to</p>
            
            <div class="recipient-name">{$participant_name}</div>
            
            <p>For successfully participating in</p>
            
            <div class="event-title">{$event_title}</div>
            
            <p>Held on {$date}</p>
            
            <p style="margin-top: 30px; font-size: 16px; font-style: italic; color: #7f8c8d;">
                We appreciate your active participation and contribution to making this event a success.
            </p>
        </div>
        
        <div class="footer">
            <div class="signature">
                <div class="signature-line"></div>
                <p class="name">Event Organizer</p>
                <p>EventSite Committee</p>
            </div>
            
            <div class="signature">
                <div class="signature-line"></div>
                <p class="name">Administrator</p>
                <p>EventSite Platform</p>
            </div>
        </div>
        
        <div class="seal">
            <div class="seal-text">OFFICIAL<br>CERTIFICATE</div>
        </div>
        
        <div class="issue-date">
            Issued on: {$issue_date}
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get certificate by participant ID
     */
    public static function getByParticipant($participant_id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM certificates WHERE participant_id = ?");
        $stmt->execute([$participant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all certificates for a user
     */
    public static function getByUser($user_id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT 
                c.*,
                e.title as event_title,
                e.start_at,
                p.registered_at
            FROM certificates c
            JOIN participants p ON c.participant_id = p.id
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
