# Email Setup Instructions

## Setup Gmail untuk Mengirim Email

1. **Aktifkan 2-Factor Authentication (2FA)** di akun Gmail Anda:
   - Pergi ke [Google Account](https://myaccount.google.com/)
   - Security > 2-Step Verification > Turn on

2. **Buat App Password**:
   - Di Google Account > Security > 2-Step Verification
   - Scroll ke bawah, klik "App passwords"
   - Pilih "Mail" dan "Other (custom name)"
   - Masukkan nama seperti "Web Sunsal"
   - Copy 16-character password yang dihasilkan

3. **Update email_config.php**:
   - Ganti `'your-email@gmail.com'` dengan email Gmail Anda
   - Ganti `'your-app-password'` dengan App Password yang didapat

4. **Test**:
   - Coba register atau OAuth dengan email baru
   - Cek apakah email verifikasi terkirim

## Catatan:
- Jangan gunakan password utama Gmail, gunakan App Password.
- Jika masih tidak terkirim, cek error log di XAMPP atau file error_log.
- Untuk production, gunakan SMTP provider seperti SendGrid atau Mailgun.