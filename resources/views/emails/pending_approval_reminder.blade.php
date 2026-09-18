<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject ?? 'Pemberitahuan Formulir Menunggu Persetujuan Anda' }}</title>
    <style>
        /* Base styles for HTML email client compatibility */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }

        /* Responsive layout */
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: auto !important; }
            .fluid { max-width: 100% !important; height: auto !important; margin-left: auto !important; margin-right: auto !important; }
            .stack-column, .stack-column-center { display: block !important; width: 100% !important; max-width: 100% !important; direction: ltr !important; }
            .stack-column-center { text-align: center !important; }
            .mobile-p-20 { padding: 20px !important; }
            .table-responsive { display: block; width: 100%; overflow-x: auto; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0b1329; color: #334155; -webkit-font-smoothing: antialiased;">

    <!-- Wrapper Table -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: radial-gradient(circle at 50% 0%, #1e293b 0%, #020617 100%); background-color: #020617; padding: 30px 10px;">
        <tr>
            <td align="center" valign="top">

                <!-- Main Container (640px) -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 650px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.35); border: 1px solid #e2e8f0;" class="email-container">
                    
                    <!-- 1. Header Banner -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0a1b4d 0%, #1a3fa8 50%, #00adef 100%); padding: 28px 32px; text-align: left;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 800; color: #7dd3fc; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 4px;">
                                            PT METALART ASTRA INDONESIA
                                        </div>
                                        <div style="font-size: 22px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.5px;">
                                            SATURNUS <span style="font-size: 14px; font-weight: 400; color: #e0f2fe; margin-left: 6px;">PORTAL NOTIFIKASI</span>
                                        </div>
                                        <div style="font-size: 12px; color: #bae6fd; margin-top: 4px;">
                                            Smart Asset Tracking, Registration & Unregistration Network Utility System
                                        </div>
                                    </td>
                                    <td align="right" valign="middle" style="padding-left: 15px;">
                                        <div style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; padding: 6px 12px; font-size: 11px; color: #ffffff; font-weight: 700; display: inline-block;">
                                            {{ strtoupper($priority) }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 2. Priority / Alert Ribbon -->
                    @php
                        $alertBg = match(strtolower($priority)) {
                            'urgent' => '#fffbeb',
                            'final', 'final notice' => '#fef2f2',
                            default => '#f0f9ff'
                        };
                        $alertBorder = match(strtolower($priority)) {
                            'urgent' => '#fde68a',
                            'final', 'final notice' => '#fecdd3',
                            default => '#bae6fd'
                        };
                        $alertText = match(strtolower($priority)) {
                            'urgent' => '#b45309',
                            'final', 'final notice' => '#be123c',
                            default => '#0369a1'
                        };
                        $alertIcon = match(strtolower($priority)) {
                            'urgent' => '⚠️',
                            'final', 'final notice' => '🚨',
                            default => '🔔'
                        };
                    @endphp
                    <tr>
                        <td style="background-color: {{ $alertBg }}; border-bottom: 1px solid {{ $alertBorder }}; padding: 12px 32px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="font-size: 13px; font-weight: 700; color: {{ $alertText }};">
                                        <span style="margin-right: 6px; font-size: 15px;">{{ $alertIcon }}</span>
                                        Terdapat <span style="text-decoration: underline;">{{ $totalForms }} Formulir</span> yang membutuhkan persetujuan (approval) Anda.
                                    </td>
                                    <td align="right" style="font-size: 11px; color: {{ $alertText }}; opacity: 0.85;">
                                        {{ $currentDate }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 3. Main Body Content -->
                    <tr>
                        <td style="padding: 32px;" class="mobile-p-20">
                            
                            <!-- Greeting -->
                            <p style="font-size: 15px; line-height: 24px; color: #1e293b; margin: 0 0 16px 0;">
                                Yth. Bapak/Ibu <strong>{{ $recipientName }}</strong>,
                            </p>
                            
                            <p style="font-size: 14px; line-height: 22px; color: #475569; margin: 0 0 20px 0;">
                                Melalui pemberitahuan ini, kami informasikan bahwa terdapat pengajuan formulir pada sistem <strong>SATURNUS</strong> yang saat ini berada dalam status antrean verifikasi dan menunggu tindakan persetujuan (approval) dari Anda.
                            </p>

                            <!-- Custom Message Box from Sender (if any) -->
                            @if(!empty($customMessage))
                            <div style="background-color: #f8fafc; border-left: 4px solid #1a3fa8; border-radius: 0 8px 8px 0; padding: 14px 18px; margin-bottom: 24px;">
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                                    💬 Catatan Khusus dari Pengirim ({{ $senderName }} · {{ $senderDept }}):
                                </div>
                                <div style="font-size: 13px; line-height: 20px; color: #1e293b; font-style: italic;">
                                    "{!! nl2br(e($customMessage)) !!}"
                                </div>
                            </div>
                            @endif

                            <!-- Section Title: Table of Pending Forms -->
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center;">
                                📋 DAFTAR FORMULIR MENUNGGU APPROVAL:
                            </div>

                            <!-- Pending Forms Table -->
                            <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 24px;">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 12px; text-align: left;">
                                    <thead>
                                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                            <th style="padding: 10px 12px; font-weight: 700; color: #475569; width: 30px;">#</th>
                                            <th style="padding: 10px 12px; font-weight: 700; color: #475569;">No. Formulir</th>
                                            <th style="padding: 10px 12px; font-weight: 700; color: #475569;">Modul / Kategori</th>
                                            <th style="padding: 10px 12px; font-weight: 700; color: #475569;">Departemen</th>
                                            <th style="padding: 10px 12px; font-weight: 700; color: #475569;">Tahap Menunggu</th>
                                            <th style="padding: 10px 12px; font-weight: 700; color: #475569; text-align: right;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pendingForms as $index => $form)
                                        @php
                                            $rowBg = ($index % 2 == 0) ? '#ffffff' : '#f8fafc';
                                            $isUnreg = str_contains(strtoupper($form['module'] ?? ''), 'UNREG');
                                            $moduleBadge = $isUnreg ? '#f43f5e' : '#0284c7';
                                            $moduleBg = $isUnreg ? '#fff1f2' : '#f0f9ff';
                                        @endphp
                                        <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid #f1f5f9;">
                                            <td style="padding: 10px 12px; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                                            <td style="padding: 10px 12px; font-weight: 700; color: #0f172a;">
                                                {{ $form['form_number'] ?? '-' }}
                                                @if(!empty($form['item_count']))
                                                    <span style="font-size: 10px; font-weight: 400; color: #64748b; display: block;">({{ $form['item_count'] }} item)</span>
                                                @endif
                                            </td>
                                            <td style="padding: 10px 12px;">
                                                <span style="background-color: {{ $moduleBg }}; color: {{ $moduleBadge }}; border: 1px solid {{ $moduleBadge }}33; border-radius: 4px; padding: 2px 6px; font-size: 10px; font-weight: 700;">
                                                    {{ $form['module'] ?? 'Registrasi' }}
                                                </span>
                                            </td>
                                            <td style="padding: 10px 12px; color: #334155;">
                                                {{ $form['department'] ?? '-' }}
                                                @if(!empty($form['date']))
                                                    <span style="font-size: 10px; color: #94a3b8; display: block;">{{ $form['date'] }}</span>
                                                @endif
                                            </td>
                                            <td style="padding: 10px 12px;">
                                                <span style="background-color: #fef3c7; color: #92400e; border-radius: 4px; padding: 2px 6px; font-size: 10px; font-weight: 700; display: inline-block;">
                                                    {{ $form['status_label'] ?? 'Menunggu Approval' }}
                                                </span>
                                            </td>
                                            <td style="padding: 10px 12px; text-align: right;">
                                                <a href="{{ $form['action_url'] ?? url('/saturnus/proses-approval') }}" target="_blank" style="background-color: #1a3fa8; color: #ffffff; text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; display: inline-block;">
                                                    Review &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" style="padding: 16px; text-align: center; color: #94a3b8; font-style: italic;">
                                                Tidak ada rincian formulir spesifik yang dilampirkan.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Primary Action Button (CTA) -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/saturnus/proses-approval') }}" target="_blank" style="background: linear-gradient(135deg, #1a3fa8 0%, #00adef 100%); background-color: #1a3fa8; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 30px; font-size: 14px; font-weight: 800; display: inline-block; letter-spacing: 0.3px; box-shadow: 0 4px 15px rgba(0, 173, 239, 0.4);">
                                            ⚡ Buka Sistem &amp; Lakukan Persetujuan Sekarang
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Instructions & Tips Box -->
                            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 14px 16px; font-size: 12px; line-height: 18px; color: #64748b;">
                                <strong>💡 Panduan Singkat Approval:</strong>
                                <ul style="margin: 6px 0 0 0; padding-left: 18px;">
                                    <li>Klik tombol <strong>Review</strong> atau <strong>Buka Sistem</strong> untuk masuk ke akun Anda.</li>
                                    <li>Periksa spesifikasi item, kewajaran kebutuhan teknis, serta dokumen lampiran.</li>
                                    <li>Jika terdapat ketidaksesuaian, Anda dapat menambahkan <strong>catatan revisi</strong> di panel komentar formulir sebelum menolak atau menyetujui.</li>
                                </ul>
                            </div>

                            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #475569;">
                                Hormat kami,<br>
                                <strong>{{ $senderName }}</strong><br>
                                <span style="font-size: 12px; color: #64748b;">{{ $senderDept }} · PT Metalart Astra Indonesia</span>
                            </div>

                        </td>
                    </tr>

                    <!-- 4. Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 32px; text-align: center;">
                            <p style="font-size: 11px; line-height: 16px; color: #94a3b8; margin: 0 0 8px 0;">
                                Email ini dikirimkan secara otomatis oleh sistem <strong>SATURNUS PORTAL</strong> PT Metalart Astra Indonesia.<br>
                                Mohon untuk tidak membalas langsung ke alamat email ini (<em>No-Reply</em>).
                            </p>
                            <p style="font-size: 10px; color: #cbd5e1; margin: 0;">
                                &copy; {{ date('Y') }} PT Metalart Astra Indonesia — All Rights Reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Main Container -->

            </td>
        </tr>
    </table>
    <!-- End Wrapper Table -->

</body>
</html>
