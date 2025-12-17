<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventSite - Event Ditolak</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7fa; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">
                                ❌ Event Ditolak
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 24px; font-weight: 600;">
                                Halo, {{organizer_name}}
                            </h2>
                            
                            <p style="margin: 0 0 30px 0; color: #666666; font-size: 16px; line-height: 1.6;">
                                Mohon maaf, event Anda <strong>"{{event_title}}"</strong> tidak dapat disetujui oleh admin.
                            </p>
                            
                            <!-- Reason Box -->
                            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin-bottom: 30px; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0; color: #856404; font-size: 16px; font-weight: 600;">
                                    📝 Alasan Penolakan:
                                </p>
                                <p style="margin: 0; color: #856404; font-size: 15px; line-height: 1.6;">
                                    {{rejection_reason}}
                                </p>
                            </div>
                            
                            <!-- Next Steps -->
                            <div style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin-bottom: 30px; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0; color: #1976d2; font-size: 16px; font-weight: 600;">
                                    💡 Apa yang bisa dilakukan:
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #666666; font-size: 14px; line-height: 1.8;">
                                    <li>Perbaiki event sesuai dengan alasan penolakan</li>
                                    <li>Submit ulang event untuk review</li>
                                    <li>Hubungi admin jika ada pertanyaan</li>
                                </ul>
                            </div>
                            
                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{edit_event_url}}" style="display: inline-block; background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(201, 56, 74, 0.3);">
                                            Edit Event
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0 0 10px 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                Jangan berkecil hati! Silakan perbaiki dan submit ulang event Anda.
                            </p>
                            
                            <p style="margin: 0; color: #666666; font-size: 15px; line-height: 1.6;">
                                Salam,<br>
                                <strong style="color: #c9384a;">Tim EventSite</strong>
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
