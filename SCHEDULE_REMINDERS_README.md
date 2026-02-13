# Email Pengingat Jadwal Otomatis - Dokumentasi

## 📋 Ringkasan Perubahan

Sistem email pengingat jadwal telah diupdate dengan fitur **2-tahap pengiriman email**:

### ✅ Email Pertama
- **Waktu Pengiriman:** Langsung saat jadwal dibuat atau diupdate
- **Tujuan:** Notifikasi awal kepada semua user tentang jadwal baru
- **File:** `jadual/ajax.php` (case 'add' dan 'update')

### ✅ Email Kedua (Pengingat)
- **Waktu Pengiriman:** Otomatis pada tanggal dan jam yang ditentukan
- **Tujuan:** Pengingat saat kegiatan akan dimulai
- **Penyimpanan:** Tabel `jadwal_reminder`
- **Executor:** Script `send_schedule_reminders.php` via cronjob

---

## 🔧 File-File yang Berubah / Baru

### 1. **jadual/ajax.php** (DIMODIFIKASI)
- Menambahkan logika untuk menyimpan jadwal pengingat ke `jadwal_reminder` table
- Mengirim email pertama saat pembuatan/update jadwal
- Case 'add' dan 'update' diupdate dengan fitur reminder scheduling

### 2. **send_schedule_reminders.php** (BARU)
- Script yang menjalankan pengiriman email pengingat
- Mengecek jadwal yang sudah saatnya dikirim
- Auto-create tabel `jadwal_reminder` jika belum ada
- Diciptakan untuk dijalankan oleh cronjob

### 3. **SCHEDULE_REMINDERS_SETUP.html** (BARU)
- Panduan setup lengkap untuk mengaktifkan cronjob
- Opsi untuk Linux/Unix, Windows, dan manual testing
- Include security setup dan troubleshooting

---

## 🚀 Cara Kerja Sistem

```
User Membuat Jadwal
     ↓
[Email 1] Langsung dikirim ke semua user
     ↓
Jadwal disimpan ke database dengan:
- nama_kegiatan
- tanggal
- waktu
     ↓
Jadwal pengingat disimpan ke table jadwal_reminder
     ↓
Cronjob berjalan setiap 5 menit
     ↓
Cek: Ada pengingat yang sudah saatnya dikirim?
     ↓
Ya → [Email 2] Kirim email pengingat
     ↓
Update: sent_at = NOW()
     ↓
Log tercatat di database untuk monitoring
```

---

## ⚙️ Tabel `jadwal_reminder`

Struktur tabel yang digunakan untuk menyimpan jadwal pengingat:

```sql
CREATE TABLE jadwal_reminder (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    nama_kegiatan VARCHAR(255) NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    reminder_datetime DATETIME NOT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reminder (schedule_id, email),
    KEY idx_reminder_datetime (reminder_datetime),
    KEY idx_sent (sent_at)
);
```

**Penjelasan:**
- `reminder_datetime`: Gabungan tanggal + waktu untuk pengingat
- `sent_at`: Null jika belum dikirim, terisi dengan timestamp saat email terkirim
- `UNIQUE KEY unique_reminder`: Pastikan 1 user hanya dapat 1 pengingat per jadwal

---

## 🔐 Setup Cronjob

### Untuk Linux/Unix/XAMPP

1. Buka cPanel → Cron Jobs (atau SSH)
2. Tambah cronjob baru dengan command:

```bash
*/5 * * * * curl -s http://localhost/web_sunsal/send_schedule_reminders.php > /dev/null 2>&1
```

3. Dengan security key:
```bash
*/5 * * * * curl -s "http://localhost/web_sunsal/send_schedule_reminders.php?key=YOUR_SECRET_KEY" > /dev/null 2>&1
```

### Untuk Windows

1. Buka Task Scheduler
2. Buat task baru yang menjalankan:
```
C:\xampp\php\php.exe -f "C:\xampp\htdocs\web_sunsal\send_schedule_reminders.php"
```
3. Set trigger: Repeat every 5 minutes

---

## 🧪 Testing Pengingat Email

### Test Manual di Browser
```
http://localhost/web_sunsal/send_schedule_reminders.php
```

Akan menampilkan JSON response:
```json
{
  "success": true,
  "message": "Pengingat jadwal terkirim (5 sent, 0 failed)",
  "sent": 5,
  "failed": 0,
  "errors": [],
  "current_time": "2026-01-20 15:30:45"
}
```

### Test Membuat Jadwal
1. Login sebagai admin
2. Buat jadwal dengan waktu 2-3 menit ke depan
3. Tunggu 5 menit (waktu cronjob berjalan)
4. Cek inbox email - seharusnya dapat 2 email:
   - Email 1: Notification jadwal baru
   - Email 2: Pengingat pada waktu yang ditentukan

---

## 📊 Monitoring & Reporting

### Query: Email Yang Sudah Dikirim
```sql
SELECT * FROM jadwal_reminder 
WHERE sent_at IS NOT NULL 
ORDER BY sent_at DESC 
LIMIT 20;
```

### Query: Email Pending (Belum Dikirim)
```sql
SELECT * FROM jadwal_reminder 
WHERE sent_at IS NULL 
AND reminder_datetime <= NOW();
```

### Query: Email Terjadwal (Belum Saatnya)
```sql
SELECT * FROM jadwal_reminder 
WHERE sent_at IS NULL 
AND reminder_datetime > NOW();
```

### Query: Statistik Per Jadwal
```sql
SELECT 
    schedule_id,
    COUNT(*) as total_reminders,
    SUM(CASE WHEN sent_at IS NOT NULL THEN 1 ELSE 0 END) as sent_count,
    SUM(CASE WHEN sent_at IS NULL THEN 1 ELSE 0 END) as pending_count
FROM jadwal_reminder
GROUP BY schedule_id;
```

---

## 🐛 Troubleshooting

### Problem: Email Pengingat Tidak Dikirim

**Penyebab 1: Cronjob Tidak Running**
- Cek apakah cronjob sudah setup di cPanel/Server
- Lihat error log: `apache/logs/error.log` atau `php/logs/php_error.log`

**Penyebab 2: Email Config Tidak Benar**
- Pastikan `includes/email_config.php` sudah dikonfigurasi
- Test kirim email dengan fitur lain (reset password, dll)

**Penyebab 3: Timezone/Waktu Server Tidak Sesuai**
- Cek timezone server: `date` (Linux) atau System Settings (Windows)
- Update `reminder_datetime` jika ada perbedaan

**Solution:**
```php
// Di send_schedule_reminders.php, ganti:
$currentDateTime = date('Y-m-d H:i:s');

// Dengan timezone yang benar:
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan timezone
$currentDateTime = date('Y-m-d H:i:s');
```

### Problem: Email Terkirim Tapi Dimarkir Spam

- Pastikan SMTP credentials benar di `email_config.php`
- Gunakan domain email yang valid
- Jika menggunakan Gmail, aktifkan "Less secure app access" atau buat App Password

---

## 📧 Isi Email Pengingat

### Email 1 (Notification - Langsung Saat Jadwal Dibuat)
```
Subjek: ⏰ Pengingat Jadwal Kegiatan - Web Sunsal

Isi:
- Notifikasi awal tentang jadwal baru
- Detail: Nama, Tanggal, Waktu, Deskripsi
- Link untuk membuka Web Sunsal
```

### Email 2 (Reminder - Saat Waktu Ditentukan)
```
Subjek: 🔔 PENGINGAT JADWAL - [Nama Kegiatan]

Isi:
- ⚠️ Pengingat URGENCY: "Waktu kegiatan sudah tiba!"
- Detail: Nama, Tanggal, Waktu
- Informasi: "Kegiatan dimulai sekarang!"
- Link untuk membuka Web Sunsal
```

---

## 🔒 Keamanan

### Melindungi Script dari Akses Tidak Sah

1. Edit `send_schedule_reminders.php`
2. Ganti secret key:
```php
$validKey = 'ganti-dengan-key-yang-aman-dan-unik';
```

3. Gunakan key saat memanggil script:
```
http://localhost/web_sunsal/send_schedule_reminders.php?key=ganti-dengan-key-yang-aman-dan-unik
```

4. Atau, batasi akses via `.htaccess` (Apache):
```apache
<Files "send_schedule_reminders.php">
    Order allow,deny
    Allow from 127.0.0.1
    Deny from all
</Files>
```

---

## 📝 Catatan Penting

1. **Timezone:** Pastikan timezone server sesuai dengan lokasi (Asia/Jakarta untuk Indonesia)
2. **Cronjob Frequency:** Semakin sering cronjob berjalan, semakin akurat pengiriman (minimum 1 menit)
3. **Database:** Pastikan database `cihuy` sudah ada dan terkoneksi
4. **Email Config:** SMTP setting harus benar di `includes/email_config.php`
5. **Performance:** Script ringan dan tidak akan impact performa, bisa dijalankan setiap menit

---

## 📞 Support & FAQ

**Q: Berapa lama email sampai?**
A: Dengan cronjob setiap 5 menit, email akan dikirim dalam 5 menit setelah waktu yang ditentukan.

**Q: Bisa lebih cepat dari 5 menit?**
A: Ya, ubah cronjob menjadi `* * * * *` untuk menjalankan setiap menit (tapi lebih heavy di server).

**Q: Apa kalau cronjob down?**
A: Email pengingat akan terdelay. Pastikan cron job selalu berjalan atau gunakan monitoring service.

**Q: Bisa test tanpa cronjob?**
A: Ya, jalankan manual: `http://localhost/web_sunsal/send_schedule_reminders.php`

---

**Versi:** 1.0
**Tanggal:** 20 Januari 2026
**Status:** ✅ Production Ready
