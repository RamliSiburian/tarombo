<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #059669, #10b981); padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .header p { color: #d1fae5; margin: 5px 0 0; font-size: 13px; }
        .body { padding: 30px; }
        .body h2 { color: #1f2937; }
        .body p { color: #4b5563; line-height: 1.6; }
        .info-box { background: #ecfdf5; border-left: 4px solid #059669; padding: 15px 20px; border-radius: 4px; margin: 20px 0; }
        .info-box p { margin: 4px 0; color: #374151; }
        .cta { text-align: center; margin: 30px 0; }
        .cta a { background: #059669; color: #fff; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .note { background: #fffbeb; border: 1px solid #fcd34d; padding: 12px 16px; border-radius: 4px; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✅ Permohonan Disetujui!</h1>
        <p>Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia</p>
    </div>
    <div class="body">
        <h2>Horas, {{ $nodeRequest->requester_name }}!</h2>
        <p>Kami memberitahukan bahwa permohonan Anda untuk bergabung dalam silsilah Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna telah <strong>disetujui</strong>! Data Anda kini telah tercantum dalam pohon silsilah keluarga besar.</p>
        <div class="info-box">
            <p><strong>Nama:</strong> {{ $nodeRequest->name }}</p>
            <p><strong>Marga:</strong> {{ $nodeRequest->marga ?? '-' }}</p>
            <p><strong>Di bawah:</strong> {{ $nodeRequest->parentNode->name }}</p>
        </div>
        @if($nodeRequest->admin_note)
        <div class="note">
            <p><strong>Catatan dari pengurus:</strong> {{ $nodeRequest->admin_note }}</p>
        </div>
        @endif
        <div class="cta">
            <a href="{{ config('app.url') }}">Lihat Silsilah Sekarang</a>
        </div>
        <p>Salam hangat,<br><strong>Pengurus Punguan Sihotang</strong></p>
    </div>
    <div class="footer">
        <p>Email ini dikirim otomatis, mohon jangan membalas email ini.</p>
        <p>© {{ date('Y') }} Punguan Pomparan Raja Sigodang Ulu Sihotang Dohot Boruna Se-Indonesia</p>
    </div>
</div>
</body>
</html>
