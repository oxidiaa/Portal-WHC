<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject ?? 'Notifikasi Komentar Form SATURNUS' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #0b1120;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
        }
        .main-wrapper {
            background-color: #0b1120;
            padding: 30px 15px;
        }
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }
        .email-header {
            background: linear-gradient(135deg, #091e42 0%, #1a3fa8 50%, #00adef 100%);
            padding: 26px 32px;
            color: #ffffff;
        }
        .brand-title {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }
        .portal-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .email-body {
            padding: 30px 32px;
        }
        .greeting-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
        }
        .intro-text {
            font-size: 14px;
            line-height: 22px;
            color: #334155;
            margin: 0 0 18px 0;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .meta-row {
            margin: 4px 0;
        }
        .comment-box {
            background-color: #f0f9ff;
            border-left: 4px solid #0284c7;
            border-radius: 0 10px 10px 0;
            padding: 16px 18px;
            margin-bottom: 24px;
        }
        .comment-author {
            font-size: 12px;
            font-weight: 700;
            color: #0369a1;
            margin-bottom: 6px;
        }
        .comment-text {
            font-size: 14px;
            line-height: 21px;
            color: #0f172a;
            white-space: pre-line;
            margin: 0;
        }
        .btn-action {
            display: inline-block;
            background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 26px;
            border-radius: 10px;
        }
        .email-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 18px 32px;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="main-wrapper">
    <div class="email-container">
        <div class="email-header">
            <div class="brand-title">SATURNUS — NOTIFIKASI KOMENTAR</div>
            <div class="portal-subtitle">{{ $appName }} · PT Metalart Astra Indonesia</div>
        </div>
        <div class="email-body">
            <p class="greeting-title">Halo, {{ $recipientName }}</p>
            <p class="intro-text">
                Terdapat komentar baru pada formulir <strong>{{ $moduleLabel }}</strong> di sistem SATURNUS yang melibatkan Anda atau departemen terkait.
            </p>

            <div class="meta-box">
                <div class="meta-row"><strong>Nomor Form:</strong> {{ $formNumber }}</div>
                <div class="meta-row"><strong>Modul:</strong> {{ $moduleLabel }}</div>
                <div class="meta-row"><strong>Departemen Form:</strong> {{ $requestorDept }}</div>
                <div class="meta-row"><strong>Waktu:</strong> {{ $currentDate }}</div>
            </div>

            <div class="comment-box">
                <div class="comment-author">
                    {{ $commenterName }} ({{ $commenterRole }} · {{ $commenterDept }})
                </div>
                <p class="comment-text">{{ $commentText }}</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ $actionUrl }}" class="btn-action">Buka Formulir &amp; Balas Komentar</a>
            </div>
        </div>
        <div class="email-footer">
            Email ini dikirim otomatis oleh sistem SATURNUS MAI Warehouse Portal. Mohon tidak membalas langsung email ini.
        </div>
    </div>
</div>
</body>
</html>
