<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #7c3aed, #a855f7); padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .header p { color: #e9d5ff; margin: 5px 0 0; }
        .body { padding: 30px; }
        .body h2 { color: #1f2937; }
        .body p { color: #4b5563; line-height: 1.6; }
        .info-box { background: #f5f3ff; border-left: 4px solid #7c3aed; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .info-box p { margin: 4px 0; color: #374151; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🌳 Tarombo Batak</h1>
        <p>Silsilah & Asal Muasal Orang Batak</p>
    </div>
    <div class="body">
        <h2>Horas, {{ $nodeRequest->requester_name }}!</h2>
        <p>Terima kasih telah mengajukan permintaan untuk bergabung dalam silsilah Batak. Permintaan Anda telah kami terima dan sedang dalam proses peninjauan oleh admin kami.</p>
        <div class="info-box">
            <p><strong>Nama:</strong> {{ $nodeRequest->name }}</p>
            <p><strong>Marga:</strong> {{ $nodeRequest->marga ?? '-' }}</p>
            <p><strong>Bergabung di bawah:</strong> {{ $nodeRequest->parentNode->name }}</p>
        </div>
        <p>Kami akan menghubungi Anda kembali melalui email ini setelah proses peninjauan selesai. Proses ini biasanya memakan waktu 1-3 hari kerja.</p>
        <p>Salam hangat,<br><strong>Tim Tarombo Batak</strong></p>
    </div>
    <div class="footer">
        <p>Email ini dikirim otomatis, mohon jangan membalas email ini.</p>
        <p>© {{ date('Y') }} Tarombo - Silsilah Batak</p>
    </div>
</div>
</body>
</html>
