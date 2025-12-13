<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventSite - Event Disetujui</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">
                                ✅ Event Disetujui!
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 24px; font-weight: 600;">
                                Selamat, {{organizer_name}}! 🎉
                            </h2>
                            
                            <p style="margin: 0 0 30px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Event Anda telah disetujui oleh admin dan sekarang sudah <strong style="color: #11998e;">LIVE</strong> di platform EventSite!
                            </p>
                            
                            <!-- Event Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 8px; padding: 25px; margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px 0; color: #11998e; font-size: 20px; font-weight: 600;">
                                            📅 {{event_title}}
                                        </h3>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #666666; font-size: 14px;">📍 Lokasi:</span>
                                                    <br>
                                                    <strong style="color: #333333; font-size: 15px;">{{event_location}}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #666666; font-size: 14px;">🕒 Waktu:</span>
                                                    <br>
                                                    <strong style="color: #333333; font-size: 15px;">{{event_datetime}}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #666666; font-size: 14px;">👥 Kapasitas:</span>
                                                    <br>
                                                    <strong style="color: #333333; font-size: 15px;">{{event_capacity}} peserta</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Next Steps -->
                            <div style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin-bottom: 30px; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0; color: #1976d2; font-size: 16px; font-weight: 600;">
                                    📋 Langkah Selanjutnya:
                                </p>
                                <ol style="margin: 0; padding-left: 20px; color: #666666; font-size: 14px; line-height: 1.8;">
                                    <li>Event Anda sekarang dapat dilihat oleh semua user</li>
                                    <li>User dapat mendaftar ke event Anda</li>
                                    <li>Anda akan menerima notifikasi untuk setiap pendaftaran baru</li>
                                    <li>Pantau peserta melalui dashboard panitia</li>
                                </ol>
                            </div>
                            
                            <!-- CTA Buttons -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{event_manage_url}}" style="display: inline-block; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: #ffffff; text-decoration: none; padding: 16px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; margin: 0 5px; box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);">
                                            Kelola Event
                                        </a>
                                        <a href="{{event_detail_url}}" style="display: inline-block; background-color: #ffffff; color: #11998e; text-decoration: none; padding: 16px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; margin: 0 5px; border: 2px solid #11998e;">
                                            Lihat Event
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                Semoga event Anda sukses dan mendapat banyak peserta!
                            </p>
                            
                            <p style="margin: 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                Salam,<br>
                                <strong style="color: #11998e;">Tim EventSite</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0 0 10px 0; color: #999999; font-size: 13px;">
                                Email ini dikirim otomatis oleh sistem EventSite
                            </p>
                            <p style="margin: 0; color: #cccccc; font-size: 12px;">
                                © 2025 EventSite. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
