<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventSite - Konfirmasi Pendaftaran</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header with Gradient -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">
                                🎉 EventSite
                            </h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                Platform Manajemen Event Mahasiswa
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 24px; font-weight: 600;">
                                Pendaftaran Event Berhasil! ✅
                            </h2>
                            
                            <p style="margin: 0 0 20px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Halo <strong>{{participant_name}}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 30px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Selamat! Anda telah berhasil terdaftar untuk event berikut:
                            </p>
                            
                            <!-- Event Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 25px; margin-bottom: 30px;">
                                <tr>
                                    <td>
                                        <h3 style="margin: 0 0 15px 0; color: #667eea; font-size: 20px; font-weight: 600;">
                                            📅 {{event_title}}
                                        </h3>
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #999999; font-size: 14px;">📍 Lokasi:</span>
                                                    <br>
                                                    <strong style="color: #333333; font-size: 15px;">{{event_location}}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <span style="color: #999999; font-size: 14px;">🕒 Waktu:</span>
                                                    <br>
                                                    <strong style="color: #333333; font-size: 15px;">{{event_datetime}}</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Description -->
                            <div style="background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 30px; border-radius: 4px;">
                                <p style="margin: 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                    {{event_description}}
                                </p>
                            </div>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{event_detail_url}}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                                            Lihat Detail Event
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Important Info -->
                            <div style="background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                                <p style="margin: 0 0 10px 0; color: #856404; font-size: 15px; font-weight: 600;">
                                    ⚠️ Informasi Penting:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 14px; line-height: 1.6;">
                                    <li>Harap datang 15 menit sebelum acara dimulai</li>
                                    <li>Bawa kartu identitas mahasiswa</li>
                                    <li>Simpan email ini sebagai bukti pendaftaran</li>
                                </ul>
                            </div>
                            
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                Terima kasih telah mendaftar! Kami tunggu kehadiran Anda.
                            </p>
                            
                            <p style="margin: 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                Salam,<br>
                                <strong style="color: #667eea;">Tim EventSite</strong>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0 0 10px 0; color: #999999; font-size: 13px;">
                                Email ini dikirim otomatis oleh sistem EventSite
                            </p>
                            <p style="margin: 0 0 15px 0; color: #999999; font-size: 13px;">
                                Jika ada pertanyaan, hubungi kami di 
                                <a href="mailto:support@eventsite.com" style="color: #667eea; text-decoration: none;">support@eventsite.com</a>
                            </p>
                            
                            <!-- Social Links -->
                            <div style="margin-top: 20px;">
                                <a href="#" style="display: inline-block; margin: 0 8px; color: #667eea; text-decoration: none; font-size: 20px;">📘</a>
                                <a href="#" style="display: inline-block; margin: 0 8px; color: #667eea; text-decoration: none; font-size: 20px;">📷</a>
                                <a href="#" style="display: inline-block; margin: 0 8px; color: #667eea; text-decoration: none; font-size: 20px;">🐦</a>
                            </div>
                            
                            <p style="margin: 20px 0 0 0; color: #cccccc; font-size: 12px;">
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
