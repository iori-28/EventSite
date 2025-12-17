# Cara Menggunakan QR Code untuk Kehadiran

## Untuk User (Peserta)

### 1. Daftar Event
- Login sebagai user
- Browse event yang tersedia
- Klik "Daftar" pada event yang diinginkan

### 2. Lihat QR Code
- Masuk ke menu **"Event Saya"**
- Pada event yang akan datang, klik tombol **"📱 QR Code"**
- QR code akan muncul di modal
- Simpan screenshot atau tampilkan dari HP saat event

### 3. Di Hari Event
- Tunjukkan QR code kepada panitia
- Panitia akan scan dengan kamera
- Status kehadiran otomatis terupdate menjadi "Hadir"

---

## Untuk Panitia

### 1. Lihat Daftar Peserta
- Login sebagai panitia
- Buka menu **"Daftar Peserta"**
- Pilih event yang sedang berlangsung

### 2. Scan QR Code Peserta
- Klik tombol **"📷 Scan QR Code"**
- Scanner akan membuka kamera device
- Arahkan kamera ke QR code peserta
- Scanner otomatis mendeteksi dan konfirmasi kehadiran

### 3. Konfirmasi Berhasil
- Muncul notifikasi: **"✓ Kehadiran [Nama Peserta] berhasil dikonfirmasi!"**
- Status peserta berubah menjadi **"✓ Hadir"**
- Halaman otomatis reload

---

## Alternatif Manual (Tetap Tersedia)

Jika QR code tidak bisa digunakan, panitia masih bisa:
- Klik tombol "✓ Hadir" pada setiap peserta (individual)
- Centang beberapa peserta → "Tandai Hadir (Selected)"
- Klik "Tandai Semua Hadir" untuk bulk confirmation

---

## Troubleshooting

### QR Code tidak muncul (User)
- Pastikan sudah terdaftar di event
- Refresh halaman "Event Saya"
- Cek koneksi internet (CDN library)

### Scanner tidak jalan (Panitia)
- Berikan permission akses kamera pada browser
- Gunakan HTTPS atau localhost
- Pastikan device memiliki kamera
- Cek koneksi internet (CDN library)

### QR Code tidak valid
- Pastikan QR code dari event yang sama
- QR code hanya bisa digunakan 1x (tidak bisa duplicate scan)
- Pastikan panitia adalah pemilik event

---

## Kelebihan QR Code vs Manual

✅ **Lebih Cepat** - 1-2 detik per peserta  
✅ **Contactless** - Tidak perlu input manual  
✅ **Akurat** - Tidak ada kesalahan nama  
✅ **Real-time** - Langsung terupdate di database  
✅ **Audit Trail** - Tercatat otomatis dengan timestamp  

---

## Keamanan

🔒 **Token Unik** - Setiap peserta dapat QR berbeda  
🔒 **Event Specific** - QR hanya valid untuk event terdaftar  
🔒 **One-time Use** - Setelah scan, tidak bisa scan ulang  
🔒 **Panitia Only** - Hanya pemilik event bisa scan  
🔒 **Encrypted** - SHA256 hash untuk token generation
