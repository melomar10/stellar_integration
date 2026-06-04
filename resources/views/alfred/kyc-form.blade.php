<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verificación de Identidad (KYC)</title>
    <style>
        :root {
            --primary:      #39B77F;
            --primary-dark: #274830;
            --primary-light:#e8f8f1;
            --dark-gray:    #2d3748;
            --darker-gray:  #1a202c;
            --border:       #e2e8f0;
            --text:         #1a202c;
            --text-muted:   #718096;
            --text-light:   #a0aec0;
            --error:        #e53e3e;
            --error-bg:     #fff5f5;
            --success:      #38a169;
            --success-bg:   #f0fff4;
            --warning:      #d69e2e;
            --warning-bg:   #fffff0;
            --radius:       12px;
            --shadow:       0 20px 60px rgba(0,0,0,0.25);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--darker-gray) 100%);
            min-height: 100vh;
            padding: 2rem 1rem 4rem;
        }

        .page-container { max-width: 760px; margin: 0 auto; }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: .4rem; }
        .page-header p  { color: rgba(255,255,255,.7); font-size: .95rem; }

        /* ── Progress ── */
        .progress-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 2rem;
        }
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .3rem;
            position: relative;
        }
        .progress-step + .progress-step::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 16px;
            width: 48px;
            height: 2px;
            background: rgba(255,255,255,.25);
            transition: background .3s;
        }
        .progress-step.done + .progress-step::before { background: var(--primary); }

        .progress-step .dot {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,.2);
            border: 2px solid rgba(255,255,255,.3);
            color: rgba(255,255,255,.5);
            font-size: .8rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: all .3s;
        }
        .progress-step.active .dot {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 4px rgba(57,183,127,.3);
        }
        .progress-step.done .dot {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .progress-step .label {
            font-size: .7rem;
            color: rgba(255,255,255,.5);
            font-weight: 500;
            white-space: nowrap;
            transition: color .3s;
        }
        .progress-step.active .label,
        .progress-step.done .label { color: rgba(255,255,255,.9); }

        .progress-connector { width: 48px; height: 2px; background: rgba(255,255,255,.25); flex-shrink: 0; margin-bottom: 20px; }
        .progress-connector.done { background: var(--primary); }

        /* ── Cards ── */
        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.75rem 2rem;
            margin-bottom: 1.25rem;
        }

        .step-section { display: none; }
        .step-section.active { display: block; }

        .section-title {
            font-size: .85rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 1.25rem;
            padding-bottom: .5rem;
            border-bottom: 2px solid var(--border);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            color: var(--text-muted);
            cursor: pointer;
            margin-bottom: 1.25rem;
            background: none;
            border: none;
            padding: 0;
            transition: color .2s;
        }
        .back-btn:hover { color: var(--primary); }

        /* ── Phone step ── */
        .phone-input-row {
            display: flex;
            gap: .75rem;
            align-items: stretch;
        }
        .phone-input-row .form-input { flex: 1; font-size: 1.05rem; padding: .75rem 1rem; }

        .step-intro {
            color: var(--text-muted);
            font-size: .9rem;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        /* ── Form ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid .col-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: .35rem; }
        .form-label { font-size: .875rem; font-weight: 500; color: var(--text); }
        .form-label .req { color: var(--error); margin-left: 2px; }

        .form-input, .form-select {
            padding: .65rem .9rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: .915rem;
            color: var(--text);
            background: white;
            width: 100%;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(57,183,127,.12);
        }
        .form-input::placeholder { color: var(--text-light); }

        /* ── Buttons ── */
        .btn-primary, .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .8rem 1.4rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 9px;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-submit { width: 100%; }
        .btn-primary:hover, .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(57,183,127,.35);
        }
        .btn-primary:active, .btn-submit:active { transform: translateY(0); }
        .btn-primary:disabled, .btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .btn-whatsapp {
            background: #25D366 !important;
            color: #fff !important;
        }
        .btn-whatsapp:hover {
            background: #1da851 !important;
            box-shadow: 0 4px 14px rgba(37, 211, 102, .35);
        }

        .btn-search {
            padding: .75rem 1.4rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 9px;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            transition: all .2s;
        }
        .btn-search:hover { background: var(--primary-dark); }
        .btn-search:disabled { opacity: .6; cursor: not-allowed; }

        /* ── Spinner ── */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Phone badge ── */
        .phone-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--primary-light);
            color: var(--primary-dark);
            border: 1px solid var(--primary);
            border-radius: 99px;
            padding: .4rem 1rem;
            font-size: .9rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }

        /* ── Client info card ── */
        .client-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem .75rem;
            background: #f8fafb;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 1rem 1.25rem;
        }
        .info-row { display: flex; flex-direction: column; gap: .1rem; }
        .info-row .info-label { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .04em; }
        .info-row .info-value { font-size: .9rem; font-weight: 600; color: var(--text); }
        .info-row .info-value code {
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: .78rem;
            background: var(--border);
            padding: .1rem .35rem;
            border-radius: 4px;
        }

        /* ── Radio cards ── */
        .radio-group { display: flex; gap: 1rem; }
        .radio-card {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: .9rem 1rem;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: .65rem;
        }
        .radio-card:hover { border-color: var(--primary); }
        .radio-card input[type="radio"] { accent-color: var(--primary); width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; }
        .radio-card.selected { border-color: var(--primary); background: var(--primary-light); }
        .radio-card .radio-label { font-weight: 600; font-size: .9rem; color: var(--text); }
        .radio-card .radio-desc  { font-size: .78rem; color: var(--text-muted); margin-top: .1rem; }

        /* ── KYC status badge ── */
        .kyc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .kyc-header .section-title { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }

        .status-badge {
            font-size: .75rem;
            font-weight: 700;
            padding: .3rem .75rem;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .status-needs_info { background: #fff3cd; color: #856404; border: 1px solid #ffd966; }
        .status-pending    { background: #cfe2ff; color: #084298; border: 1px solid #9ec5fe; }
        .status-approved   { background: var(--success-bg); color: var(--success); border: 1px solid #9ae6b4; }
        .status-rejected   { background: var(--error-bg); color: var(--error); border: 1px solid #feb2b2; }
        .status-unknown    { background: var(--border); color: var(--text-muted); border: 1px solid var(--border); }

        /* ── Docs ── */
        .doc-notice {
            background: var(--warning-bg);
            border: 1px solid #f6e05e;
            border-radius: 8px;
            padding: .7rem 1rem;
            margin-bottom: 1.25rem;
            font-size: .855rem;
            color: #744210;
        }

        .doc-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }

        .doc-card {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }
        .doc-card:hover { border-color: var(--primary); background: rgba(57,183,127,.03); }
        .doc-card.has-file { border-color: var(--primary); border-style: solid; background: var(--primary-light); }

        .doc-icon { font-size: 2rem; margin-bottom: .5rem; }
        .doc-title { font-weight: 600; font-size: .875rem; color: var(--text); margin-bottom: .2rem; }
        .doc-desc  { font-size: .75rem; color: var(--text-muted); margin-bottom: .75rem; }
        .doc-btn {
            display: inline-block;
            padding: .38rem .85rem;
            background: var(--border);
            border-radius: 6px;
            font-size: .78rem;
            font-weight: 500;
            color: var(--text);
            pointer-events: none;
            transition: background .2s;
        }
        .doc-card.has-file .doc-btn { background: var(--primary); color: white; }
        .doc-filename { font-size: .71rem; color: var(--text-muted); margin-top: .35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .doc-check {
            position: absolute; top: 8px; right: 8px;
            width: 20px; height: 20px;
            background: var(--primary); border-radius: 50%;
            color: white; font-size: .7rem;
            display: none; align-items: center; justify-content: center;
        }
        .doc-card.has-file .doc-check { display: flex; }
        .doc-card input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
            font-size: 1rem; /* evita zoom en iOS al tocar */
        }
        .doc-hint {
            font-size: .7rem; color: var(--text-muted); margin-top: .5rem; line-height: 1.35;
        }

        /* ── Errors / Alerts ── */
        .field-error { font-size: .82rem; color: var(--error); margin-top: .35rem; }
        .alert { border-radius: 8px; padding: .85rem 1rem; margin-bottom: 1rem; font-size: .9rem; }
        .alert-error   { background: var(--error-bg);   border: 1px solid #feb2b2; color: #742a2a; }
        .alert-success { background: var(--success-bg); border: 1px solid #9ae6b4; color: #1c4532; }

        /* ── Result ── */
        .result-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            font-size: 2rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
        }
        .result-icon.success { background: var(--success-bg); color: var(--success); border: 3px solid var(--success); }
        .result-icon.error   { background: var(--error-bg);   color: var(--error);   border: 3px solid var(--error); }
        .result-title { font-size: 1.4rem; font-weight: 700; margin-bottom: .5rem; }
        .result-subtitle { color: var(--text-muted); font-size: .925rem; margin-bottom: 1.5rem; }
        .result-detail {
            display: flex; align-items: center; justify-content: space-between;
            background: #f8fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: .65rem 1rem;
            margin-bottom: .5rem; font-size: .875rem;
        }
        .result-detail span { color: var(--text-muted); }
        .result-detail code {
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: .8rem;
            background: var(--border);
            padding: .15rem .4rem;
            border-radius: 4px;
            color: var(--text);
        }

        @media (max-width: 600px) {
            .card { padding: 1.25rem; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .col-full { grid-column: 1; }
            .doc-grid { grid-template-columns: 1fr; }
            .radio-group { flex-direction: column; }
            .client-info-grid { grid-template-columns: 1fr; }
            .phone-input-row { flex-direction: column; }
            .phone-input-row .btn-search { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="page-container">

    {{-- Header --}}
    <div class="page-header">
        <h1>Verificación de Identidad</h1>
        <p>Proceso de KYC guiado en 3 pasos</p>
    </div>

    {{-- Progress --}}
    <div class="progress-wrap">
        <div class="progress-step active" id="prog-1">
            <div class="dot">1</div>
            <div class="label">Teléfono</div>
        </div>
        <div class="progress-connector" id="conn-1-2"></div>
        <div class="progress-step" id="prog-2">
            <div class="dot">2</div>
            <div class="label">Cliente</div>
        </div>
        <div class="progress-connector" id="conn-2-3"></div>
        <div class="progress-step" id="prog-3">
            <div class="dot">3</div>
            <div class="label">KYC</div>
        </div>
        <div class="progress-connector" id="conn-3-4"></div>
        <div class="progress-step" id="prog-4">
            <div class="dot">4</div>
            <div class="label">Listo</div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         STEP 1 – Buscar por teléfono
    ══════════════════════════════════════════ --}}
    <div class="step-section active" id="step-phone">
        <div class="card">
            <div class="section-title">Buscar Cliente por Teléfono</div>
            <p class="step-intro">
                Ingresa el número de teléfono del cliente. Verificaremos si ya existe en el sistema
                para recuperar sus datos automáticamente.
            </p>
            <div class="phone-input-row">
                <input class="form-input" type="tel" id="phone-input"
                       placeholder="Ej: 8095550000 ó +18095550000"
                       autofocus>
                <button class="btn-search" id="btn-check-phone" onclick="checkPhone()">
                    <span class="spinner" id="sp-phone"></span>
                    <span class="btn-text" id="txt-phone">Buscar</span>
                </button>
            </div>
            <div class="field-error" id="phone-error" style="display:none"></div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         STEP 2a – Crear nuevo cliente
    ══════════════════════════════════════════ --}}
    <div class="step-section" id="step-create">
        <div class="card">
            <button class="back-btn" onclick="goBack('step-phone', 1)">← Cambiar teléfono</button>
            <div class="section-title">Nuevo Cliente</div>
            <p class="step-intro">No encontramos este número en el sistema. Completa los datos para registrar al cliente.</p>

            <div style="margin-bottom:1.25rem">
                <div class="phone-badge">📱 <span id="create-phone-display"></span></div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="create-firstName">Nombre <span class="req">*</span></label>
                    <input class="form-input" type="text" id="create-firstName" placeholder="Juan">
                </div>
                <div class="form-group">
                    <label class="form-label" for="create-lastName">Apellido <span class="req">*</span></label>
                    <input class="form-input" type="text" id="create-lastName" placeholder="Pérez">
                </div>
                <div class="form-group col-full">
                    <label class="form-label" for="create-email">Correo electrónico <span class="req">*</span></label>
                    <input class="form-input" type="email" id="create-email" placeholder="juan@ejemplo.com">
                </div>
            </div>

            <div class="field-error" id="create-error" style="display:none;margin-top:.75rem"></div>

            <button class="btn-submit" id="btn-create-client" style="margin-top:1.25rem" onclick="createClient()">
                <span class="spinner" id="sp-create"></span>
                <span class="btn-text" id="txt-create">Crear y continuar</span>
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         STEP 3 – KYC (tipo + campos dinámicos)
    ══════════════════════════════════════════ --}}
    <div class="step-section" id="step-kyc">

        {{-- Cliente identificado --}}
        <div class="card">
            <button class="back-btn" onclick="goBack('step-phone', 1)">← Cambiar teléfono</button>
            <div class="section-title">Cliente Identificado</div>
            <div class="client-info-grid" id="client-info-card">
                {{-- populated by JS --}}
            </div>
        </div>

        {{-- Tipo de operación --}}
        <div class="card" id="move-type-card">
            <div class="section-title">Tipo de Operación</div>
            <div class="radio-group" id="move-type-group">
                <label class="radio-card selected" id="labelEnviar">
                    <input type="radio" name="move_type" value="Enviar" checked
                           onchange="updateRadioCards(this)">
                    <div>
                        <div class="radio-label">Enviar dinero</div>
                        <div class="radio-desc">USD → DOP &nbsp;·&nbsp; rol: sender</div>
                    </div>
                </label>
                <label class="radio-card" id="labelRecibir">
                    <input type="radio" name="move_type" value="Recibir"
                           onchange="updateRadioCards(this)">
                    <div>
                        <div class="radio-label">Recibir dinero</div>
                        <div class="radio-desc">DOP → USD &nbsp;·&nbsp; rol: receiver</div>
                    </div>
                </label>
            </div>

            <div class="field-error" id="kyc-error" style="display:none;margin-top:.75rem"></div>

            <button class="btn-submit" id="btn-load-kyc" style="margin-top:1.25rem" onclick="loadKycFields()">
                <span class="spinner" id="sp-kyc"></span>
                <span class="btn-text" id="txt-kyc">Consultar requisitos KYC</span>
            </button>
        </div>

        {{-- Campos KYC dinámicos --}}
        <div id="kyc-fields-section" style="display:none">
            <div class="card">
                <div class="kyc-header">
                    <div class="section-title" style="margin-bottom:0;border-bottom:none;padding-bottom:0">
                        Campos Requeridos
                    </div>
                    <span id="kyc-status-badge" class="status-badge"></span>
                </div>
                <div style="height:2px;background:var(--border);margin-bottom:1.25rem;border-radius:2px"></div>

                <form id="kyc-form" enctype="multipart/form-data" onsubmit="submitKyc(event)">
                    {{-- Text / select fields --}}
                    <div class="form-grid" id="kyc-text-fields" style="margin-bottom:1.25rem"></div>

                    {{-- File fields --}}
                    <div id="kyc-files-section" style="display:none">
                        <div class="section-title">Documentos de Identidad</div>
                        <div class="doc-notice">
                            ⚠️ Sube los documentos indicados. Formatos aceptados: JPG, PNG, PDF (máx. 10 MB).
                        </div>
                        <div class="doc-grid" id="kyc-file-fields"></div>
                    </div>

                    <div class="field-error" id="submit-error" style="display:none;margin-top:1rem"></div>

                    <button type="submit" class="btn-submit" id="btn-submit-kyc" style="margin-top:1.5rem">
                        <span class="spinner" id="sp-submit"></span>
                        <span class="btn-text" id="txt-submit">Enviar verificación</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         STEP 4 – Resultado
    ══════════════════════════════════════════ --}}
    <div class="step-section" id="step-result">
        <div class="card" style="text-align:center;padding:2.5rem 2rem">
            <div id="result-content"></div>
        </div>
    </div>

</div><!-- /page-container -->

@php
    $wasapiWaMeDigits = preg_replace('/\D/', '', (string) config('services.wasapi.whatsapp_number', ''));
@endphp

<script>
/* ─── State ─── */
const STATE = {
    phone:       null,
    customerId:  null,
    moveType:    null,
    client:      null,
    alfredAccount: null,
};

/* ─── Constants ─── */
// Campos que Alfred puede pedir como archivos (camelCase = nombre del campo en la respuesta KYC)
const FILE_FIELDS = ['idFront', 'idBack', 'driverLicenseFront', 'driverLicenseBack', 'selfie', 'proofOfAddress', 'bankStatement', 'incomeProof'];

const FIELD_LABELS = {
    firstName:               'Nombre',
    middleName:              'Segundo nombre',
    lastName:                'Apellido',
    email:                   'Correo electrónico',
    phoneNumber:             'Teléfono',
    dateOfBirth:             'Fecha de nacimiento',
    placeOfBirth:            'Lugar de nacimiento',
    gender:                  'Género',
    mainNationality:         'Nacionalidad principal',
    secondaryNationalities:  'Nacionalidades secundarias',
    preferredLanguage:       'Idioma preferido',
    isPep:                   '¿Es PEP?',
    streetAddress:           'Dirección (calle)',
    streetAddressLine2:      'Dirección línea 2',
    residentialAddress:      'Dirección residencial',
    city:                    'Ciudad',
    stateProvinceRegion:     'Estado / Provincia / Región',
    country:                 'País',
    postalCode:              'Código postal',
    nationalId:              'Cédula / ID Nacional',
    licenseId:               'Licencia de conducir',
    dni:                     'DNI',
    cpf:                     'CPF',
    idFront:              'Foto frontal del ID',
    idBack:               'Foto trasera del ID',
    driverLicenseFront:   'Licencia de conducir (frontal)',
    driverLicenseBack:    'Licencia de conducir (trasera)',
    selfie:               'Selfie con ID',
    proofOfAddress:       'Comprobante de domicilio',
    bankStatement:        'Estado de cuenta bancario',
    incomeProof:          'Comprobante de ingresos',
};

const FILE_ICONS = {
    idFront: '🪪', idBack: '🪪',
    driverLicenseFront: '🚗', driverLicenseBack: '🚗',
    selfie: '🤳', proofOfAddress: '🏠',
    bankStatement: '🏦', incomeProof: '💼',
};

/** Atributos del input file: cámara + galería en móvil (accept + capture). */
function kycFileInputAttributes(fieldKey) {
    const allowsPdf = ['bankStatement', 'incomeProof'].includes(fieldKey);
    const accept = allowsPdf
        ? 'image/*,.pdf,application/pdf'
        : 'image/*';
    // selfie: cámara frontal; documentos: cámara trasera (mejor para fotos de ID)
    const capture = fieldKey === 'selfie' ? 'user' : 'environment';
    return `accept="${accept}" capture="${capture}"`;
}

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/** Dígitos del número de WhatsApp registrado en Wasapi (wa.me), sin "+". */
const WASAPI_WA_ME_DIGITS = @json($wasapiWaMeDigits);

/* ─── HTTP helper ─── */
async function api(url, body) {
    const isFormData = body instanceof FormData;
    const headers = { 'X-CSRF-TOKEN': CSRF };
    if (!isFormData) headers['Content-Type'] = 'application/json';
    const res = await fetch(url, {
        method: 'POST',
        headers,
        body: isFormData ? body : JSON.stringify(body),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw json;
    return json;
}

/* ─── Progress ─── */
const STEP_MAP = { 'step-phone': 1, 'step-create': 2, 'step-kyc': 3, 'step-result': 4 };

function showStep(id) {
    document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });

    const cur = STEP_MAP[id] || 1;
    [1,2,3,4].forEach(n => {
        const el = document.getElementById('prog-' + n);
        el.classList.toggle('active', n === cur);
        el.classList.toggle('done',   n < cur);
    });
    [1,2,3].forEach(n => {
        const conn = document.getElementById('conn-' + n + '-' + (n+1));
        conn.classList.toggle('done', n < cur);
    });
}

function goBack(stepId, prog) { showStep(stepId); }

/* ─── Error helpers ─── */
function showErr(id, msg) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.style.display = 'block';
}
function hideErr(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

/* ─── Loading helpers ─── */
function setLoading(spId, txtId, btnId, loading, label) {
    const sp  = document.getElementById(spId);
    const txt = document.getElementById(txtId);
    const btn = document.getElementById(btnId);
    if (sp)  sp.style.display  = loading ? 'inline-block' : 'none';
    if (txt && label) txt.textContent = loading ? label : txt.dataset.orig || txt.textContent;
    if (btn) btn.disabled = loading;
}

/* ═══════════════════════════════════════
   STEP 1 – Check phone
═══════════════════════════════════════ */
async function checkPhone() {
    const phone = document.getElementById('phone-input').value.trim();
    if (!phone) return showErr('phone-error', 'Ingresa un número de teléfono.');
    hideErr('phone-error');
    setLoading('sp-phone', 'txt-phone', 'btn-check-phone', true, 'Buscando...');

    try {
        const res = await api('{{ route("alfred.kyc.check-phone") }}', { phone });
        STATE.phone = res.phone;

        if (res.found && res.customer_id) {
            STATE.client       = res.client;
            STATE.alfredAccount = res.alfred_account;
            STATE.customerId   = res.customer_id;
            populateClientCard(res.alfred_account, res.client);
            showStep('step-kyc');
        } else {
            document.getElementById('create-phone-display').textContent = '+' + res.phone;
            showStep('step-create');
        }
    } catch (e) {
        showErr('phone-error', e.message || 'Error al buscar. Intenta nuevamente.');
    } finally {
        setLoading('sp-phone', 'txt-phone', 'btn-check-phone', false, 'Buscar');
    }
}

/* ═══════════════════════════════════════
   STEP 2a – Create client
═══════════════════════════════════════ */
async function createClient() {
    const firstName = document.getElementById('create-firstName').value.trim();
    const lastName  = document.getElementById('create-lastName').value.trim();
    const email     = document.getElementById('create-email').value.trim();

    if (!firstName || !lastName || !email) {
        return showErr('create-error', 'Todos los campos marcados con * son obligatorios.');
    }
    hideErr('create-error');
    setLoading('sp-create', 'txt-create', 'btn-create-client', true, 'Creando...');

    try {
        const res = await api('{{ route("alfred.kyc.create-client") }}', {
            firstName, lastName, email, phone: STATE.phone,
        });

        if (res.success) {
            STATE.client        = res.client;
            STATE.alfredAccount = res.alfred_account;
            STATE.customerId    = res.customer_id;
            populateClientCard(res.alfred_account, res.client);
            showStep('step-kyc');
        } else {
            showErr('create-error', res.message || 'No se pudo crear el cliente.');
        }
    } catch (e) {
        const msg = e.errors
            ? Object.values(e.errors).flat().join(' · ')
            : (e.message || 'Error inesperado.');
        showErr('create-error', msg);
    } finally {
        setLoading('sp-create', 'txt-create', 'btn-create-client', false, 'Crear y continuar');
    }
}

/* ═══════════════════════════════════════
   STEP 3 – Load KYC status & render fields
═══════════════════════════════════════ */
async function loadKycFields() {
    const moveType = document.querySelector('input[name="move_type"]:checked')?.value;
    if (!moveType)          return showErr('kyc-error', 'Selecciona el tipo de operación.');
    if (!STATE.customerId)  return showErr('kyc-error', 'Sin ID de cliente. Regresa e intenta nuevamente.');

    STATE.moveType = moveType;
    hideErr('kyc-error');
    setLoading('sp-kyc', 'txt-kyc', 'btn-load-kyc', true, 'Consultando...');

    try {
        const res = await api('{{ route("alfred.kyc.status") }}', {
            customer_id: STATE.customerId,
            move_type:   moveType,
        });

        if (res.success) {
            const raw = res.kyc_status || {};
            const st = (raw.status != null ? String(raw.status) : '').toLowerCase();
            if (isKycCompleteStatus(st)) {
                document.getElementById('kyc-fields-section').style.display = 'none';
                document.getElementById('move-type-card').style.display = 'none';
                renderKycAlreadyApproved(raw);
                showStep('step-result');
            } else {
                const rendered = renderKycFields(res.kyc_status);
                if (rendered) {
                    document.getElementById('kyc-fields-section').style.display = 'block';
                    document.getElementById('move-type-card').style.display = 'none';
                } else {
                    document.getElementById('kyc-fields-section').style.display = 'none';
                    document.getElementById('move-type-card').style.display = 'block';
                }
            }
        } else {
            showErr('kyc-error', res.message || 'Error al consultar el estado KYC.');
        }
    } catch (e) {
        showErr('kyc-error', e.message || 'Error al consultar Alfred.');
    } finally {
        setLoading('sp-kyc', 'txt-kyc', 'btn-load-kyc', false, 'Consultar requisitos KYC');
    }
}

/** KYC ya aprobado en Alfred: no mostrar formulario de requisitos. */
function renderKycAlreadyApproved(kyc) {
    const el = document.getElementById('result-content');
    const fullName = kyc.fullName ? esc(kyc.fullName) : '';
    const extra = fullName ? `<div class="result-detail"><span>Nombre en KYC</span><span style="font-weight:600;color:var(--text)">${fullName}</span></div>` : '';
    const waBtn = WASAPI_WA_ME_DIGITS
        ? `<button type="button" class="btn-primary btn-whatsapp" onclick="openWasapiWhatsApp()">Volver a WhatsApp</button>`
        : '';
    el.innerHTML = `
        <div class="result-icon success">✓</div>
        <div class="result-title">Proceso completado</div>
        <div class="result-subtitle">Tu verificación de identidad (KYC) ya está aprobada. No es necesario enviar documentación de nuevo. Tu proceso fue completado de manera satisfactoria.</div>
        ${extra}
        <div class="result-detail"><span>Customer ID</span><code>${esc(STATE.customerId || '')}</code></div>
        <div style="margin-top:1.5rem;display:flex;flex-direction:column;align-items:center;gap:0.75rem;width:100%;max-width:340px;margin-left:auto;margin-right:auto;">
            ${bankAccountsButtonHtml()}
            ${waBtn}
            <button type="button" class="btn-primary" onclick="location.reload()">Nueva consulta</button>
        </div>`;
}

function openWasapiWhatsApp() {
    const d = String(WASAPI_WA_ME_DIGITS || '').replace(/\D/g, '');
    if (!d) return;
    window.location.href = 'https://wa.me/' + d;
}

function getBankDetailsUrl() {
    const phone = String(STATE.phone || '').replace(/\D/g, '');
    if (!phone) return null;
    return @json(url('/alfred/cuentas')) + '/' + phone;
}

function bankAccountsButtonHtml() {
    const url = getBankDetailsUrl();
    if (!url) return '';
    return `<a href="${esc(url)}" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;width:100%;text-align:center">Agregar cuentas bancarias</a>`;
}

function isKycCompleteStatus(status) {
    const st = (status != null ? String(status) : '').toLowerCase();
    return st === 'approved' || st === 'accepted';
}

function renderKycFields(kycStatus) {
    const st = (kycStatus && kycStatus.status != null ? String(kycStatus.status) : '').toLowerCase();
    const submitBtn = document.getElementById('btn-submit-kyc');
    hideErr('kyc-error');

    if (isKycCompleteStatus(st)) {
        document.getElementById('kyc-fields-section').style.display = 'none';
        document.getElementById('move-type-card').style.display = 'none';
        renderKycAlreadyApproved(kycStatus);
        showStep('step-result');
        return false;
    }

    const fields = kycStatus.fields || {};
    const fieldKeys = Object.keys(fields);

    if (fieldKeys.length === 0) {
        showErr('kyc-error', 'Alfred indica que el KYC está incompleto pero no devolvió los campos requeridos. Vuelve a consultar o contacta soporte.');
        document.getElementById('kyc-fields-section').style.display = 'none';
        if (submitBtn) submitBtn.disabled = true;
        return false;
    }

    if (submitBtn) submitBtn.disabled = false;

    const acc = STATE.alfredAccount || {};

    /* Existing data map – alfredAccount keys → Alfred field names */
    const existing = {
        firstName:               acc.first_name              || '',
        middleName:              acc.middle_name             || '',
        lastName:                acc.last_name               || '',
        email:                   acc.email                   || '',
        phoneNumber:             acc.phone ? '+' + acc.phone : '',
        dateOfBirth:             acc.date_of_birth           || '',
        placeOfBirth:            acc.place_of_birth          || '',
        gender:                  acc.gender                  || '',
        mainNationality:         acc.main_nationality        || '',
        secondaryNationalities:  acc.secondary_nationalities || '',
        preferredLanguage:       acc.preferred_language      || '',
        isPep:                   acc.is_pep != null ? (acc.is_pep ? 'true' : 'false') : '',
        streetAddress:           acc.street_address          || '',
        streetAddressLine2:      acc.street_address_line2    || '',
        residentialAddress:      acc.residential_address     || '',
        city:                    acc.city                    || '',
        stateProvinceRegion:     acc.state_province_region   || '',
        country:                 acc.country                 || '',
        postalCode:              acc.postal_code             || '',
        nationalId:              acc.national_id             || '',
        licenseId:               acc.license_id              || '',
        dni:                     acc.dni                     || '',
        cpf:                     acc.cpf                     || '',
    };

    /* Badge */
    const statusMap = {
        'NEEDS_INFO': 'Requiere información',
        'PENDING':    'Pendiente',
        'APPROVED':   'Aprobado',
        'ACCEPTED':   'Aprobado',
        'REJECTED':   'Rechazado',
    };
    const badge = document.getElementById('kyc-status-badge');
    badge.textContent = statusMap[kycStatus.status] || kycStatus.status || 'Desconocido';
    badge.className   = 'status-badge status-' + (kycStatus.status || 'unknown').toLowerCase();

    const textFields = [];
    const filesList  = [];

    for (const [key, def] of Object.entries(fields)) {
        if (FILE_FIELDS.includes(key)) filesList.push({ key, def });
        else                           textFields.push({ key, def });
    }

    /* ── Text / select fields ── */
    document.getElementById('kyc-text-fields').innerHTML = textFields.map(({ key, def }) => {
        const label    = FIELD_LABELS[key] || key;
        /* Campos que Alfred pide de nuevo no se prellenan (requieren re-verificación) */
        const val      = fields.hasOwnProperty(key) ? '' : (existing[key] || '');
        const required = !def.optional;
        const req      = required ? '<span class="req">*</span>' : '';
        const reqAttr  = required ? 'required' : '';
        const desc     = esc(def.description || '');

        if (key === 'gender') {
            return `<div class="form-group">
                <label class="form-label">${label} ${req}</label>
                <select class="form-select" name="gender" ${reqAttr}>
                    <option value="">Seleccionar...</option>
                    <option value="Male"   ${val==='Male'?'selected':''}>Masculino</option>
                    <option value="Female" ${val==='Female'?'selected':''}>Femenino</option>
                    <option value="Other"  ${val==='Other'?'selected':''}>Otro</option>
                </select>
            </div>`;
        }
        if (key === 'isPep') {
            return `<div class="form-group">
                <label class="form-label">${label} ${req}</label>
                <select class="form-select" name="isPep" ${reqAttr}>
                    <option value="false" ${val!=='true'?'selected':''}>No</option>
                    <option value="true"  ${val==='true'?'selected':''}>Sí</option>
                </select>
            </div>`;
        }
        if (key === 'preferredLanguage') {
            return `<div class="form-group">
                <label class="form-label">${label} ${req}</label>
                <select class="form-select" name="preferredLanguage" ${reqAttr}>
                    <option value="">Seleccionar...</option>
                    <option value="es" ${val==='es'?'selected':''}>Español</option>
                    <option value="en" ${val==='en'?'selected':''}>English</option>
                    <option value="pt" ${val==='pt'?'selected':''}>Português</option>
                </select>
            </div>`;
        }
        if (key === 'country') {
            const countries = [
                ['Dominican Republic','República Dominicana'],
                ['United States','Estados Unidos'],
                ['Mexico','México'],
                ['Colombia','Colombia'],
                ['Brazil','Brasil'],
                ['Argentina','Argentina'],
                ['Chile','Chile'],
                ['Peru','Perú'],
            ];
            const opts = countries.map(([v,t]) =>
                `<option value="${v}" ${val===v?'selected':''}>${t}</option>`
            ).join('');
            return `<div class="form-group">
                <label class="form-label">${label} ${req}</label>
                <select class="form-select" name="country" ${reqAttr}>
                    <option value="">Seleccionar...</option>${opts}
                </select>
            </div>`;
        }

        const type = def.type === 'date' ? 'date' : 'text';
        return `<div class="form-group">
            <label class="form-label">${label} ${req}</label>
            <input class="form-input" type="${type}" name="${key}"
                   value="${esc(val)}" placeholder="${desc}" ${reqAttr}>
        </div>`;
    }).join('');

    /* ── File fields ── */
    if (filesList.length > 0) {
        document.getElementById('kyc-files-section').style.display = 'block';
        document.getElementById('kyc-file-fields').innerHTML = filesList.map(({ key, def }) => {
            const label    = FIELD_LABELS[key] || key;
            const required = !def.optional;
            const req      = required ? '<span class="req">*</span>' : '';
            const icon     = FILE_ICONS[key] || '📄';
            return `<div class="doc-card" id="card-${key}">
                <div class="doc-check">✓</div>
                <div class="doc-icon">${icon}</div>
                <div class="doc-title">${label} ${req}</div>
                <div class="doc-desc">${esc(def.description || '')}</div>
                <span class="doc-btn" id="btn-${key}">Tomar foto o elegir archivo</span>
                <div class="doc-filename" id="name-${key}"></div>
                <p class="doc-hint">En el celular puedes abrir la cámara o elegir una imagen guardada.</p>
                <input type="file" name="${key}" id="file-${key}"
                       ${kycFileInputAttributes(key)}
                       onchange="handleFile(this,'card-${key}','name-${key}','btn-${key}')">
            </div>`;
        }).join('');
    } else {
        document.getElementById('kyc-files-section').style.display = 'none';
    }

    return true;
}

function handleFile(input, cardId, nameId, btnId) {
    const card   = document.getElementById(cardId);
    const nameEl = document.getElementById(nameId);
    const btn    = document.getElementById(btnId);
    if (input.files && input.files[0]) {
        card.classList.add('has-file');
        nameEl.textContent = input.files[0].name;
        btn.textContent    = 'Archivo seleccionado';
    } else {
        card.classList.remove('has-file');
        nameEl.textContent = '';
        btn.textContent    = 'Tomar foto o elegir archivo';
    }
}

/* ═══════════════════════════════════════
   STEP 4 – Submit KYC
═══════════════════════════════════════ */
async function submitKyc(e) {
    e.preventDefault();
    hideErr('submit-error');

    const textCount = document.getElementById('kyc-text-fields')?.children.length || 0;
    const fileCount = document.getElementById('kyc-file-fields')?.children.length || 0;
    if (textCount + fileCount === 0) {
        return showErr('submit-error', 'No hay campos para enviar. Consulta los requisitos KYC primero.');
    }

    setLoading('sp-submit', 'txt-submit', 'btn-submit-kyc', true, 'Enviando...');

    try {
        const form = document.getElementById('kyc-form');
        const fd   = new FormData(form);
        fd.append('customer_id', STATE.customerId);
        fd.append('move_type',   STATE.moveType);
        fd.append('_token',      CSRF);

        const res = await api('{{ route("alfred.kyc.submit") }}', fd);

        if (res.success) {
            renderResult(true, res);
            showStep('step-result');
        } else {
            showErr('submit-error', res.message || 'Error al enviar el KYC.');
        }
    } catch (e) {
        const msg = e.errors
            ? Object.values(e.errors).flat().join(' · ')
            : (e.message || 'Error inesperado al enviar.');
        showErr('submit-error', msg);
    } finally {
        setLoading('sp-submit', 'txt-submit', 'btn-submit-kyc', false, 'Enviar verificación');
    }
}

function renderResult(success, data) {
    const el = document.getElementById('result-content');
    if (success) {
        el.innerHTML = `
            <div class="result-icon success">✓</div>
            <div class="result-title">¡Verificación enviada!</div>
            <div class="result-subtitle">El KYC se procesó correctamente en Alfred.</div>
            ${data.whatsapp_sent ? `<div class="alert alert-success" style="text-align:left;margin-top:1rem">Te enviamos un mensaje por WhatsApp confirmando que tu proceso de registro se completó de manera correcta.</div>` : ''}
            ${data.kyc_id
                ? `<div class="result-detail"><span>KYC ID</span><code>${esc(data.kyc_id)}</code></div>`
                : ''}
            <div class="result-detail"><span>Customer ID</span><code>${esc(data.customer_id)}</code></div>
            <div style="margin-top:1.5rem;display:flex;flex-direction:column;align-items:center;gap:0.75rem;width:100%;max-width:340px;margin-left:auto;margin-right:auto;">
                ${bankAccountsButtonHtml()}
                <button type="button" class="btn-primary" onclick="location.reload()">Nueva verificación</button>
            </div>`;
    } else {
        el.innerHTML = `
            <div class="result-icon error">✗</div>
            <div class="result-title">Error</div>
            <div class="result-subtitle">${esc(data.message || 'Ocurrió un error inesperado.')}</div>
            <div style="margin-top:1.5rem">
                <button class="btn-primary" onclick="resetKycSection()">Reintentar</button>
            </div>`;
    }
}

function resetKycSection() {
    document.getElementById('move-type-card').style.display     = 'block';
    document.getElementById('kyc-fields-section').style.display = 'none';
    showStep('step-kyc');
}

/* ─── Helpers ─── */
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function populateClientCard(acc, client) {
    const el = document.getElementById('client-info-card');
    const name = acc
        ? ((acc.first_name || '') + ' ' + (acc.last_name || '')).trim()
        : ((client?.name || '') + ' ' + (client?.last_name || '')).trim();
    const email   = acc?.email   || client?.email  || '-';
    const phone   = acc?.phone   || client?.phone  || STATE.phone || '-';
    const custId  = STATE.customerId || '-';

    el.innerHTML = `
        <div class="info-row">
            <span class="info-label">Nombre completo</span>
            <span class="info-value">${esc(name) || '-'}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email</span>
            <span class="info-value">${esc(email)}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Teléfono</span>
            <span class="info-value">+${esc(String(phone))}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Alfred Customer ID</span>
            <span class="info-value"><code>${esc(custId)}</code></span>
        </div>`;
}

function updateRadioCards(input) {
    document.getElementById('labelEnviar').classList.toggle('selected', input.value === 'Enviar');
    document.getElementById('labelRecibir').classList.toggle('selected', input.value === 'Recibir');
}

/* ─── Enter key on phone ─── */
document.getElementById('phone-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); checkPhone(); }
});
</script>
</body>
</html>
