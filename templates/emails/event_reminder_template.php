<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Event</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .alert-box strong {
            color: #856404;
            font-size: 16px;
        }
        .event-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .event-details h2 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 22px;
        }
        .detail-row {
            display: flex;
            margin: 12px 0;
            align-items: flex-start;
        }
        .detail-icon {
            font-size: 20px;
            margin-right: 12px;
            min-width: 24px;
        }
        .detail-text {
            color: #555;
            line-height: 1.6;
        }
        .detail-text strong {
            color: #333;
            display: block;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%);
            color: white;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #666;
            font-size: 13px;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⏰ Reminder Event</h1>
        </div>
        
        <div class="content">
            <div class="alert-box">
                <strong>🔔 Event Anda akan segera dimulai!</strong>
            </div>
            
            <!-- Event Image (if exists) -->
            <?php if (!empty($event_image)): ?>
            <div style="margin: 20px 0; border-radius: 8px; overflow: hidden;">
                <img src="{{event_image}}" alt="{{event_title}}" style="width: 100%; max-height: 300px; object-fit: cover; display: block;" />
            </div>
            <?php endif; ?>
            
            <p style="color: #555; line-height: 1.8; margin-bottom: 20px;">
                Halo <strong>{{participant_name}}</strong>,
            </p>
            
            <p style="color: #555; line-height: 1.8;">
                Ini adalah pengingat bahwa event yang Anda daftarkan akan dimulai dalam <strong>24 jam</strong>. 
                Pastikan Anda sudah mempersiapkan diri dan tidak melewatkan event ini!
            </p>
            
            <div class="event-details">
                <h2>{{event_title}}</h2>
                
                <div class="detail-row">
                    <div class="detail-icon">📅</div>
                    <div class="detail-text">
                        <strong>Tanggal & Waktu</strong>
                        {{event_datetime}}
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-icon">📍</div>
                    <div class="detail-text">
                        <strong>Lokasi</strong>
                        {{event_location}}
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-icon">📝</div>
                    <div class="detail-text">
                        <strong>Deskripsi</strong>
                        {{event_description}}
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{event_detail_url}}" style="display: inline-block; background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;">Lihat Detail Event</a>
            </div>
            
            <!-- QR Code Section -->
            <div style="margin-top: 40px; padding: 25px; background: #ffffff; border: 2px dashed #c9384a; border-radius: 8px; text-align: center;">
                <h3 style="color: #c9384a; margin: 0 0 15px 0; font-size: 18px;">📱 QR Code Kehadiran</h3>
                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                    Tunjukkan QR code ini kepada panitia untuk konfirmasi kehadiran Anda
                </p>
                <div style="background: white; padding: 15px; display: inline-block; border-radius: 8px;">
                    {{qr_code_image}}
                </div>
                <p style="color: #999; font-size: 12px; margin-top: 15px;">
                    Simpan email ini atau screenshot QR code untuk memudahkan check-in
                </p>
            </div>
            
            <p style="color: #777; font-size: 14px; margin-top: 30px; line-height: 1.6;">
                Jika Anda memiliki pertanyaan atau tidak dapat hadir, silakan hubungi panitia event atau 
                batalkan pendaftaran Anda melalui sistem.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>EventSite</strong> - Platform Manajemen Event Mahasiswa</p>
            <p>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
