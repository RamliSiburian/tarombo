<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #b45309, #d97706); padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; }
        .header p { color: #fef3c7; margin: 5px 0 0; font-size: 13px; }
        .body { padding: 30px; }
        .body h2 { color: #1f2937; }
        .body p { color: #4b5563; line-height: 1.6; }
        .info-box { background: #fffbeb; border-left: 4px solid #d97706; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .info-box p { margin: 4px 0; color: #374151; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🌳 Punguan Pomparan Raja Sigodang Ulu Sihotang</h1>
        <p>Dohot Boruna Se-Indonesia</p>
    </div>
    <div class="body">
        <h2>Horas, {{ $nodeRequest->requester_name }}!</h2>
        <p>Terima kasih telah mengajukan data untuk silsilah Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia. Permintaan Anda telah kami terima dan sedang dalam proses peninjauan oleh admin/pengurus.</p>
        <div class="info-box">
            <p><strong>Nama:</strong> {{ $nodeRequest->name }}</p>
            <p><strong>Marga:</strong> {{ $nodeRequest->marga ?? '-' }}</p>
            <p><strong>Bergabung di bawah:</strong> {{ $nodeRequest->parentNode->name }}</p>
        </div>
        <p>Kami akan menghubungi Anda kembali melalui email ini setelah proses peninjauan selesai. Proses ini biasanya memakan waktu 1-3 hari kerja.</p>
        <p>Salam hangat,<br><strong>Pengurus Punguan Sihotang</strong></p>
    </div>
    <div class="footer">
        <p>Email ini dikirim otomatis, mohon jangan membalas email ini.</p>
        <p>© {{ date('Y') }} Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia</p>
    </div>
</div>
</body>
</html>
