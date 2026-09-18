<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject ?? 'Notifikasi Approval SATURNUS MAI' }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #0b1120; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; color: #334155; }
        
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

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #091e42 0%, #1a3fa8 50%, #00adef 100%);
            padding: 28px 32px;
            color: #ffffff;
        }

        .brand-title {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #ffffff;
            margin: 0 0 4px 0;
        }

        .portal-subtitle {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Stage Ribbon */
        .stage-ribbon {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 32px;
        }

        .stepper-container {
            display: table;
            width: 100%;
            margin-top: 8px;
        }

        .stepper-step {
            display: table-cell;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: #94a3b8;
            padding: 0 4px;
        }

        .stepper-step.active {
            color: #0284c7;
        }

        .stepper-step.passed {
            color: #10b981;
        }

        .step-bubble {
            width: 24px;
            height: 24px;
            line-height: 24px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #64748b;
            display: inline-block;
            margin-bottom: 4px;
            font-size: 11px;
        }

        .stepper-step.active .step-bubble {
            background: linear-gradient(135deg, #0284c7 0%, #00adef 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 173, 239, 0.4);
        }

        .stepper-step.passed .step-bubble {
            background-color: #10b981;
            color: #ffffff;
        }

        /* Body Content */
        .email-body {
            padding: 32px;
        }

        .greeting-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 12px 0;
        }

        .intro-text {
            font-size: 14px;
            line-height: 22px;
            color: #334155;
            margin: 0 0 20px 0;
        }

        /* Info Card */
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .info-grid {
            width: 100%;
        }

        .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding-bottom: 4px;
        }

        .info-value {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            padding-bottom: 12px;
        }

        /* Note Box */
        .note-box {
            background-color: #eff6ff;
            border-left: 4px solid #00adef;
            border-radius: 0 10px 10px 0;
            padding: 14px 18px;
            margin-bottom: 24px;
        }

        .note-title {
            font-size: 11px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .note-content {
            font-size: 13px;
            line-height: 20px;
            color: #1e3a8a;
            font-style: italic;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 26px;
            font-size: 12px;
        }

        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Button CTA */
        .btn-cta-wrapper {
            text-align: center;
            padding: 10px 0 20px 0;
        }

        .btn-cta {
            display: inline-block;
            background: linear-gradient(135deg, #1a3fa8 0%, #00adef 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 34px;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 173, 239, 0.4);
            letter-spacing: 0.02em;
        }

        /* Footer */
        .email-footer {
            background-color: #0f172a;
            color: #94a3b8;
            padding: 24px 32px;
            text-align: center;
            font-size: 11px;
            line-height: 18px;
        }

        .footer-brand {
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
        }

        @media only screen and (max-width: 600px) {
            .email-header, .stage-ribbon, .email-body, .email-footer {
                padding: 20px 16px !important;
            }
            .btn-cta {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="email-container">
            
            <!-- 1. Header -->
            <div class="email-header">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td>
                            <div class="brand-title">PT METALART ASTRA INDONESIA</div>
                            <div class="portal-subtitle">SATURNUS — Sistem Registrasi &amp; Approval Consumable</div>
                        </td>
                        <td align="right" valign="middle">
                            <span style="background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; padding: 5px 12px; font-size: 11px; color: #ffffff; font-weight: 700; display: inline-block;">
                                {{ strtoupper($moduleName) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 2. Stage Ribbon & Progress Stepper -->
            <div class="stage-ribbon">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-size: 13px; font-weight: 800; color: #0f172a;">
                            📢 {{ $stageTitle }}
                        </td>
                        <td align="right" style="font-size: 11px; color: #64748b; font-weight: 600;">
                            {{ $currentDate }}
                        </td>
                    </tr>
                </table>

                @if($moduleName === 'Registrasi Consumable')
                <!-- Stepper for Registrasi: Staff -> Accounting -> Warehouse -->
                <div class="stepper-container">
                    <div class="stepper-step {{ in_array($stageKey, ['staff_needed']) ? 'active' : 'passed' }}">
                        <div class="step-bubble">1</div>
                        <div>Staff Dept</div>
                    </div>
                    <div class="stepper-step {{ $stageKey === 'accounting_needed' ? 'active' : (in_array($stageKey, ['warehouse_needed', 'completed']) ? 'passed' : '') }}">
                        <div class="step-bubble">2</div>
                        <div>Accounting</div>
                    </div>
                    <div class="stepper-step {{ $stageKey === 'warehouse_needed' ? 'active' : ($stageKey === 'completed' ? 'passed' : '') }}">
                        <div class="step-bubble">3</div>
                        <div>Warehouse</div>
                    </div>
                    <div class="stepper-step {{ $stageKey === 'completed' ? 'passed' : '' }}">
                        <div class="step-bubble">✓</div>
                        <div>Selesai</div>
                    </div>
                </div>
                @else
                <!-- Stepper for Unregistrasi: Staff -> Warehouse -->
                <div class="stepper-container">
                    <div class="stepper-step {{ in_array($stageKey, ['staff_needed']) ? 'active' : 'passed' }}">
                        <div class="step-bubble">1</div>
                        <div>Staff Dept</div>
                    </div>
                    <div class="stepper-step {{ $stageKey === 'warehouse_needed' ? 'active' : ($stageKey === 'completed' ? 'passed' : '') }}">
                        <div class="step-bubble">2</div>
                        <div>Warehouse Final</div>
                    </div>
                    <div class="stepper-step {{ $stageKey === 'completed' ? 'passed' : '' }}">
                        <div class="step-bubble">✓</div>
                        <div>Selesai</div>
                    </div>
                </div>
                @endif
            </div>

            <!-- 3. Email Body -->
            <div class="email-body">
                <div class="greeting-title">
                    Yth. Bapak/Ibu <strong>{{ $recipientName }}</strong>,
                </div>

                <div class="intro-text">
                    @if($stageKey === 'staff_needed')
                        Pemberitahuan otomatis dari sistem <strong>SATURNUS</strong>: Pemohon <strong>{{ $requestorName }}</strong> dari Departemen <strong>{{ $requestorDept }}</strong> telah membuat formulir <strong>{{ $moduleName }}</strong> baru dengan nomor <strong>{{ $formNumber }}</strong>. Mohon kesediaan Anda untuk meninjau dan melakukan tindakan persetujuan (approval) tahap pertama.
                    @elseif($stageKey === 'accounting_needed')
                        Pemberitahuan otomatis: Formulir <strong>{{ $formNumber }}</strong> telah <strong>disetujui oleh Staff Section Head</strong> ({{ $previousApprover ?? 'Staff Dept' }}). Saat ini formulir berada dalam antrean Anda untuk dilakukan verifikasi dan persetujuan (approval) Tahap 2 Accounting.
                    @elseif($stageKey === 'warehouse_needed')
                        Pemberitahuan otomatis: Formulir <strong>{{ $formNumber }}</strong> telah disetujui pada tahapan sebelumnya ({{ $previousApprover ?? 'Approver Terkait' }}). Saat ini formulir siap untuk diverifikasi dan didaftarkan/diselesaikan oleh tim <strong>Warehouse Consumable</strong>.
                    @elseif($stageKey === 'completed')
                        Kabar baik! Formulir pengajuan <strong>{{ $formNumber }}</strong> yang Anda ajukan telah <strong>disetujui sepenuhnya</strong> oleh Warehouse Consumable ({{ $previousApprover ?? 'Warehouse' }}). Barang consumable kini telah resmi tercatat pada sistem portal MAI.
                    @else
                        Formulir <strong>{{ $formNumber }}</strong> memerlukan perhatian dan tindakan Anda pada sistem SATURNUS.
                    @endif
                </div>

                <!-- Previous Approver Note (if any) -->
                @if(!empty($previousComment))
                <div class="note-box">
                    <div class="note-title">💬 Catatan dari {{ $previousApprover ?? 'Approver Sebelumnya' }}:</div>
                    <div class="note-content">"{{ $previousComment }}"</div>
                </div>
                @endif

                <!-- Detail Information Card -->
                <div class="info-card">
                    <table class="info-grid" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="50%" valign="top">
                                <div class="info-label">Nomor Formulir</div>
                                <div class="info-value" style="color: #0284c7;">{{ $formNumber }}</div>
                            </td>
                            <td width="50%" valign="top">
                                <div class="info-label">Jenis Pengajuan</div>
                                <div class="info-value">{{ $moduleName }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <div class="info-label">Departemen Pemohon</div>
                                <div class="info-value">{{ $requestorDept }}</div>
                            </td>
                            <td valign="top">
                                <div class="info-label">Tanggal Pengajuan</div>
                                <div class="info-value">{{ $formDate }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding-bottom: 0;">
                                <div class="info-label">Diajukan Oleh</div>
                                <div class="info-value" style="padding-bottom: 0;">{{ $requestorName }}</div>
                            </td>
                            <td valign="top" style="padding-bottom: 0;">
                                <div class="info-label">Jumlah Barang</div>
                                <div class="info-value" style="padding-bottom: 0;">{{ $totalItems }} Item</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Items Table -->
                @if(!empty($items) && count($items) > 0)
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                    📋 Rincian Item Barang yang Diajukan:
                </div>

                <table class="items-table" border="0" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="8%">No</th>
                            <th width="24%">Kode Barang</th>
                            <th width="40%">Nama Barang</th>
                            <th width="28%">Detail / Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $idx => $item)
                        <tr>
                            <td align="center"><strong>{{ $idx + 1 }}</strong></td>
                            <td><strong style="color: #0284c7;">{{ $item['kode_barang'] ?? '-' }}</strong></td>
                            <td>
                                <strong>{{ $item['nama_barang'] ?? '-' }}</strong>
                                @if(!empty($item['spesifikasi']))
                                    <br><small style="color: #64748b;">Spec: {{ $item['spesifikasi'] }}</small>
                                @endif
                            </td>
                            <td>
                                <span style="font-size: 11px;">
                                    {{ $item['kategori_penggunaan'] ?? $item['kategori'] ?? 'Consumable' }}
                                </span>
                                @if(isset($item['min']) && isset($item['max']))
                                    <br><small style="color: #64748b;">Min: {{ $item['min'] }} | Max: {{ $item['max'] }}</small>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                <!-- CTA Action Button -->
                <div class="btn-cta-wrapper">
                    <a href="{{ $actionUrl }}" target="_blank" class="btn-cta">
                        @if($stageKey === 'completed')
                            Lihat Detail Formulir Selesai &rarr;
                        @else
                            Buka Formulir untuk Verifikasi &amp; Approval &rarr;
                        @endif
                    </a>
                </div>

                <div style="text-align: center; font-size: 11px; color: #94a3b8; margin-top: 6px;">
                    Atau salin tautan berikut ke browser Anda:<br>
                    <a href="{{ $actionUrl }}" style="color: #0284c7; word-break: break-all;">{{ $actionUrl }}</a>
                </div>

            </div>

            <!-- 4. Footer -->
            <div class="email-footer">
                <div class="footer-brand">PT METALART ASTRA INDONESIA</div>
                <div>Kawasan Industri KIIC, Karawang Barat, Jawa Barat — Indonesia</div>
                <div style="margin-top: 8px; color: #64748b; font-size: 10px;">
                    Email ini dikirimkan secara otomatis oleh Sistem Portal SATURNUS. Mohon tidak membalas email ini secara langsung.
                </div>
            </div>

        </div>
    </div>
</body>
</html>
