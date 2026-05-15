<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrar nueva cuenta</title>
    <style>
        :root {
            --primary: #39B77F; --primary-dark: #274830; --primary-light: #e8f8f1;
            --border: #e2e8f0; --text: #1a202c; --text-muted: #718096;
            --error: #e53e3e; --error-bg: #fff5f5; --success: #38a169; --success-bg: #f0fff4;
            --radius: 12px; --shadow: 0 20px 60px rgba(0,0,0,0.25);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, #1a202c 100%);
            min-height: 100vh; padding: 2rem 1rem 4rem;
        }
        .page-container { max-width: 720px; margin: 0 auto; }
        .page-header { text-align: center; color: white; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: .35rem; }
        .page-header p { color: rgba(255,255,255,.75); font-size: .9rem; }
        .card {
            background: white; border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 1.5rem 1.75rem; margin-bottom: 1rem;
        }
        .section-title {
            font-size: .85rem; font-weight: 700; color: var(--primary-dark);
            text-transform: uppercase; letter-spacing: .05em; margin-bottom: 1rem;
            padding-bottom: .5rem; border-bottom: 2px solid var(--border);
        }
        .btn-submit, .btn-back {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .75rem 1.25rem; border-radius: 9px; font-size: .92rem; font-weight: 600;
            cursor: pointer; text-decoration: none; border: none; width: 100%;
        }
        .btn-submit { background: var(--primary); color: white; }
        .btn-submit:hover { background: var(--primary-dark); }
        .btn-submit:disabled { opacity: .55; cursor: not-allowed; }
        .btn-back {
            background: transparent; color: white; border: 2px solid rgba(255,255,255,.45);
            margin-bottom: 1rem;
        }
        .btn-back:hover { background: rgba(255,255,255,.1); border-color: white; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        .form-grid .col-full { grid-column: 1 / -1; }
        .form-group { display: flex; flex-direction: column; gap: .3rem; }
        .form-label { font-size: .85rem; font-weight: 500; }
        .form-input, .form-select {
            padding: .6rem .85rem; border: 2px solid var(--border); border-radius: 8px;
            font-size: .9rem; width: 100%;
        }
        .alert { border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .9rem; }
        .alert-error { background: var(--error-bg); border: 1px solid #feb2b2; color: #742a2a; }
        .alert-success { background: var(--success-bg); border: 1px solid #9ae6b4; color: #1c4532; }
        .spinner-wrap { text-align: center; padding: 2rem; color: var(--text-muted); }
        .hidden { display: none !important; }
        .phone-badge {
            display: inline-flex; align-items: center; gap: .4rem;
            background: var(--primary-light); color: var(--primary-dark);
            border: 1px solid var(--primary); border-radius: 99px;
            padding: .35rem .9rem; font-size: .88rem; font-weight: 600; margin-bottom: 1rem;
        }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="page-container">
    <a href="{{ route('alfred.bank-details.form', ['phone' => $phone]) }}" class="btn-back">
        ← Volver a mis cuentas
    </a>

    <div class="page-header">
        <h1>Registrar nueva cuenta</h1>
        <p>Completa los datos de tu cuenta bancaria</p>
    </div>

    <div id="step-loading" class="card">
        <div class="spinner-wrap">Validando teléfono…</div>
    </div>

    <div id="step-error" class="card hidden">
        <div class="alert alert-error" id="error-message"></div>
    </div>

    <div id="step-form" class="card hidden">
        <div class="phone-badge">📱 +<span>{{ $phone }}</span></div>
        <div class="section-title">Datos de la cuenta</div>
        <form id="create-form" onsubmit="createAccount(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Tipo de banco</label>
                    <select class="form-select" name="type" id="bank-type" required>
                        <option value="ACH_DOM">Banco RD</option>
                        <option value="BANK_USA">Banco en USA</option>
                        <option value="SPEI">Banco en España</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de cuenta</label>
                    <input class="form-input" name="accountType" required placeholder="CORRIENTE / CHECKING">
                </div>
                <div class="form-group col-full">
                    <label class="form-label">Nombre del banco / titular</label>
                    <input class="form-input" name="accountName" required placeholder="Banco Popular Dominicano">
                </div>
                <div class="form-group">
                    <label class="form-label">Número de cuenta</label>
                    <input class="form-input" name="accountNumber" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Código país</label>
                    <input class="form-input" name="countryCode" id="country-code" value="DO" maxlength="8" readonly>
                </div>
                <div class="form-group col-full">
                    <label class="form-label">
                        <input type="checkbox" name="isDefault" value="1"> Marcar como predeterminada
                    </label>
                </div>
            </div>
            <button type="submit" class="btn-submit" id="btn-submit" style="margin-top:1rem">
                Registrar cuenta
            </button>
        </form>
        <div class="alert alert-success hidden" id="save-success" style="margin-top:1rem"></div>
    </div>
</div>

<script>
const PHONE = @json($phone);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const LIST_URL = @json(route('alfred.bank-details.form', ['phone' => $phone]));

const COUNTRY_BY_BANK_TYPE = {
    ACH_DOM: 'DO',
    BANK_USA: 'US',
    SPEI: 'ES',
};

function syncCountryCodeFromBankType() {
    const typeEl = document.getElementById('bank-type');
    const countryEl = document.getElementById('country-code');
    if (!typeEl || !countryEl) return;
    countryEl.value = COUNTRY_BY_BANK_TYPE[typeEl.value] || 'DO';
}

async function api(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw json;
    return json;
}

function show(id) {
    ['step-loading', 'step-error', 'step-form'].forEach(s => {
        document.getElementById(s).classList.toggle('hidden', s !== id);
    });
}

async function validatePhone() {
    show('step-loading');
    try {
        const res = await api('{{ route("alfred.bank-details.load") }}', { phone: PHONE });
        if (!res.success) throw res;
        show('step-form');
        syncCountryCodeFromBankType();
        document.getElementById('bank-type')?.addEventListener('change', syncCountryCodeFromBankType);
    } catch (e) {
        document.getElementById('error-message').textContent =
            e.message || (e.errors ? Object.values(e.errors).flat().join(' ') : 'No se pudo validar el teléfono.');
        show('step-error');
    }
}

async function createAccount(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit');
    btn.disabled = true;

    const fd = new FormData(e.target);
    const body = { phone: PHONE };
    fd.forEach((v, k) => { body[k] = k === 'isDefault' ? true : v; });
    if (!fd.get('isDefault')) body.isDefault = false;

    try {
        await api('{{ route("alfred.bank-details.create") }}', body);
        window.location.href = LIST_URL;
    } catch (err) {
        alert(err.message || 'Error al crear cuenta');
        btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', validatePhone);
</script>
</body>
</html>
