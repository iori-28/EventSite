<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR Code Service
 * 
 * Service untuk generate QR codes menggunakan chillerlan/php-qrcode library.
 * Support multiple output formats: base64 image, HTML img tag, dan file.
 * 
 * QR codes digunakan untuk:
 * - Attendance tracking (participant check-in via QR scan)
 * - Email notifications (embed QR di email registrasi dan reminder)
 * - User dashboard (display QR code untuk di-screenshot)
 * 
 * Library: chillerlan/php-qrcode v5.0
 * Format: PNG image dengan ECC Level H (High error correction 30%)
 * Size: Configurable, default 250-300px
 * 
 * @package EventSite\Services
 * @author EventSite Team
 */
class QRCodeService
{
    /**
     * Generate QR code sebagai base64 encoded PNG image
     * 
     * Method ini untuk embed QR code di HTML (email, web page).
     * Output adalah base64 string (tanpa data URI prefix).
     * 
     * QR Code Settings:
     * - Version: 5 (37x37 modules, optimal untuk tokens)
     * - ECC Level: H (30% error correction, tetap readable walau rusak)
     * - Scale: 10 (pixel per module)
     * - Output: PNG image format
     * 
     * Error Handling:
     * - Return null jika generation gagal
     * - Log error ke error_log untuk debugging
     * 
     * @param string $data Data yang akan di-encode (biasanya qr_token dari participants table)
     * @param int $size Size hint (not used in current implementation)
     * @return string|null Base64 encoded PNG (without data URI prefix), atau null jika error
     */
    public static function generateQRBase64($data, $size = 300)
    {
        try {
            $options = new QROptions([
                'version'      => 5,
                'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel'     => QRCode::ECC_H, // High error correction
                'scale'        => 10,
                'imageBase64'  => true,
            ]);

            $qrcode = new QRCode($options);
            $base64 = $qrcode->render($data);

            // Remove data:image/png;base64, prefix if present
            if (strpos($base64, 'data:image/png;base64,') === 0) {
                $base64 = substr($base64, 22);
            }

            return $base64;
        } catch (Exception $e) {
            error_log("QR Code generation error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate QR code sebagai inline HTML img tag
     * 
     * Method ini untuk direct display QR code di web page.
     * Output adalah complete HTML img tag dengan base64 inline image.
     * 
     * Use cases:
     * - Display QR code di modal (user_my_events.php)
     * - Embed QR code di email template
     * - Preview QR code sebelum print
     * 
     * Image styling:
     * - Centered dengan margin auto
     * - Display block untuk proper alignment
     * - Size configurable (default 250px)
     * 
     * @param string $data Data yang akan di-encode (qr_token)
     * @param int $size Display size dalam pixels (default: 250)
     * @return string HTML img tag dengan inline base64 image, atau error message jika gagal
     */
    public static function generateQRImageTag($data, $size = 250)
    {
        // Generate base64 QR code
        $base64 = self::generateQRBase64($data, $size);

        // Handle error: return user-friendly error message
        if (!$base64) {
            return '<p style="color: red;">QR Code generation failed</p>';
        }

        // Build HTML img tag dengan data URI scheme
        // Format: data:image/png;base64,<base64_data>
        return sprintf(
            '<img src="data:image/png;base64,%s" alt="QR Code Kehadiran" style="width:%dpx; height:%dpx; display:block; margin:0 auto;" />',
            $base64,
            $size,
            $size
        );
    }

    /**
     * Generate QR code dan save sebagai file PNG
     * 
     * Method ini untuk generate QR code sebagai file di server.
     * File bisa digunakan untuk:
     * - Printable QR codes (poster event)
     * - Archive QR codes
     * - Attach ke email sebagai file (bukan inline)
     * 
     * Note: Currently not used in system (prefer inline base64).
     * Future use: Batch generate QR codes untuk semua participants.
     * 
     * @param string $data Data yang akan di-encode (qr_token)
     * @param string $filePath Path lengkap untuk save file (e.g., /path/to/qr_12345.png)
     * @return bool True jika file berhasil di-save dan exist, false jika error
     */
    public static function saveQRToFile($data, $filePath)
    {
        try {
            // Setup QR options tanpa imageBase64 (output langsung ke file)
            $options = new QROptions([
                'version'      => 5,       // QR version 5 (37x37 modules)
                'outputType'   => QRCode::OUTPUT_IMAGE_PNG,  // PNG format
                'eccLevel'     => QRCode::ECC_H,  // High error correction
                'scale'        => 10,      // Pixel per module
            ]);

            // Generate dan save QR code ke file
            $qrcode = new QRCode($options);
            $qrcode->render($data, $filePath);

            // Verify file berhasil dibuat
            return file_exists($filePath);
        } catch (Exception $e) {
            // Log error untuk debugging
            error_log("QR Code save error: " . $e->getMessage());
            return false;
        }
    }
}
