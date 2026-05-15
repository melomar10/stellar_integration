<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cuentas bancarias</title>
    <style>
        :root {
            --primary: #39B77F; --primary-dark: #274830; --primary-light: #e8f8f1;
            --border: #e2e8f0; --text: #1a202c; --text-muted: #718096;
            --error: #e53e3e; --error-bg: #fff5f5; --success: #38a169; --success-bg: #f0fff4;
            --warning-bg: #fffff0; --warning-border: #f6e05e; --radius: 12px;
            --shadow: 0 20px 60px rgba(0,0,0,0.25);
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
        .notice-default {
            background: var(--primary-light); border: 1px solid var(--primary);
            border-radius: 8px; padding: .85rem 1rem; margin-bottom: 1rem;
            font-size: .9rem; color: var(--primary-dark); line-height: 1.45;
        }
        .notice-info {
            background: var(--warning-bg); border: 1px solid var(--warning-border);
            border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem;
            font-size: .875rem; color: #744210;
        }
        .account-card {
            border: 2px solid var(--border); border-radius: 10px;
            padding: 1rem 1.1rem; margin-bottom: .75rem; cursor: pointer;
            transition: border-color .2s, background .2s;
            display: flex; align-items: flex-start; gap: .75rem;
        }
        .account-card:hover { border-color: var(--primary); }
        .account-card.selected { border-color: var(--primary); background: var(--primary-light); }
        .account-card.is-default-card { border-color: var(--primary); border-width: 2px; }
        .account-card input[type="radio"] { margin-top: .25rem; accent-color: var(--primary); flex-shrink: 0; }
        .account-body { flex: 1; }
        .account-title { font-weight: 700; font-size: .95rem; color: var(--text); margin-bottom: .2rem; }
        .account-meta { font-size: .82rem; color: var(--text-muted); }
        .badge-default {
            display: inline-block; background: var(--primary); color: white;
            font-size: .7rem; font-weight: 700; padding: .2rem .55rem;
            border-radius: 99px; margin-left: .35rem; vertical-align: middle;
        }
        .btn-submit {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .75rem 1.25rem; background: var(--primary); color: white; border: none;
            border-radius: 9px; font-size: .92rem; font-weight: 600; cursor: pointer; width: 100%;
        }
        .btn-submit:hover { background: var(--primary-dark); }
        .btn-submit:disabled { opacity: .55; cursor: not-allowed; }
        .btn-outline {
            display: inline-flex; align-items: center; justify-content: center;
            padding: .75rem 1.25rem; background: white; color: var(--primary-dark);
            border: 2px solid var(--primary); border-radius: 9px;
            font-size: .92rem; font-weight: 600; cursor: pointer; width: 100%;
            text-decoration: none; margin-top: 1rem;
        }
        .btn-outline:hover { background: var(--primary-light); }
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
    </style>
</head>
<body>
<div class="page-container">
    <div class="page-header">
        <h1>Cuentas bancarias</h1>
        <p>Selecciona la cuenta donde recibirás el pago</p>
    </div>

    <div id="step-loading" class="card">
        <div class="spinner-wrap">Validando teléfono y cargando cuentas…</div>
    </div>

    <div id="step-error" class="card hidden">
        <div class="alert alert-error" id="error-message"></div>
    </div>

    <div id="step-accounts" class="hidden">
        <div class="card">
            <div class="phone-badge">📱 +<span id="display-phone">{{ $phone }}</span></div>
            <div class="notice-default" id="default-payment-notice" class="hidden">
                El pago se acreditará en la cuenta marcada como <strong>predeterminada</strong> (arriba).
            </div>
            <div class="section-title">Tus cuentas registradas</div>
            <div id="accounts-list"></div>
            <div id="multi-hint" class="notice-info hidden">
                Tienes más de una cuenta. Selecciona cuál será la predeterminada para recibir pagos y pulsa «Guardar predeterminada».
            </div>
            <div style="margin-top:1rem">
                <button type="button" class="btn-submit" id="btn-save-default" onclick="saveDefault()" disabled>
                    Guardar cuenta predeterminada
                </button>
            </div>
            <div class="alert alert-success hidden" id="save-success" style="margin-top:1rem"></div>
            <a href="{{ route('alfred.bank-details.create-form', ['phone' => $phone]) }}" class="btn-outline">
                Registra nueva cuenta
            </a>
        </div>
    </div>

    <div id="step-empty" class="card hidden">
        <div class="alert alert-error">No tienes cuentas registradas. Registra tu primera cuenta para recibir pagos.</div>
        <a href="{{ route('alfred.bank-details.create-form', ['phone' => $phone]) }}" class="btn-outline">
            Registra nueva cuenta
        </a>
    </div>
</div>

<script>
const PHONE = @json($phone);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let STATE = { customerId: null, accounts: [], selectedId: null };

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
    ['step-loading','step-error','step-accounts','step-empty'].forEach(s => {
        document.getElementById(s).classList.toggle('hidden', s !== id);
    });
}

function normalizeSingleDefault(accounts) {
    let chosen = null;
    for (const acc of accounts) {
        if (acc.isDefault && chosen === null) {
            chosen = acc.id;
        }
    }
    return accounts.map(acc => ({
        ...acc,
        isDefault: chosen !== null && acc.id === chosen,
    }));
}

function renderAccounts(accounts) {
    accounts = normalizeSingleDefault(accounts);
    const list = document.getElementById('accounts-list');
    const hasDefault = accounts.some(a => a.isDefault);
    document.getElementById('default-payment-notice').classList.toggle('hidden', !hasDefault);
    document.getElementById('multi-hint').classList.toggle('hidden', accounts.length <= 1);

    list.innerHTML = accounts.map(acc => {
        const id = acc.id;
        const isDef = !!acc.isDefault;
        const checked = isDef ? 'checked' : '';
        const sel = STATE.selectedId === id || (isDef && !STATE.selectedId) ? 'selected' : '';
        const defClass = isDef ? 'is-default-card' : '';
        const badge = isDef ? '<span class="badge-default">Predeterminada</span>' : '';
        return `<label class="account-card ${defClass} ${sel}" data-id="${id}">
            <input type="radio" name="default_account" value="${id}" ${checked}
                   onchange="selectAccount('${id}')">
            <div class="account-body">
                <div class="account-title">${esc(acc.accountName || 'Cuenta')}${badge}</div>
                <div class="account-meta">Tipo: ${esc(acc.type || '—')} · Cuenta: ${esc(acc.accountType || '—')}</div>
                <div class="account-meta">Nº ${esc(acc.accountNumber || '—')} · ${esc(acc.countryCode || '')}</div>
            </div>
        </label>`;
    }).join('');

    const defaultAcc = accounts.find(a => a.isDefault);
    STATE.selectedId = STATE.selectedId || (defaultAcc && defaultAcc.id) || (accounts[0] && accounts[0].id);
    document.getElementById('btn-save-default').disabled = accounts.length < 2;
}

function selectAccount(id) {
    STATE.selectedId = id;
    document.querySelectorAll('.account-card').forEach(el => {
        el.classList.toggle('selected', el.dataset.id === id);
    });
}

async function loadAccounts() {
    show('step-loading');
    try {
        const res = await api('{{ route("alfred.bank-details.load") }}', { phone: PHONE });
        if (!res.success) throw res;
        STATE.customerId = res.customer_id;
        STATE.accounts = res.accounts || [];

        if (STATE.accounts.length === 0) {
            show('step-empty');
            return;
        }

        renderAccounts(STATE.accounts);
        show('step-accounts');
    } catch (e) {
        document.getElementById('error-message').textContent =
            e.message || (e.errors ? Object.values(e.errors).flat().join(' ') : 'No se pudo validar el teléfono.');
        show('step-error');
    }
}

async function saveDefault() {
    if (!STATE.selectedId) return;
    const btn = document.getElementById('btn-save-default');
    btn.disabled = true;
    try {
        const res = await api('{{ route("alfred.bank-details.set-default") }}', {
            phone: PHONE,
            bank_detail_id: STATE.selectedId,
        });
        STATE.accounts = res.accounts || [];
        renderAccounts(STATE.accounts);
        const ok = document.getElementById('save-success');
        ok.textContent = res.message || 'Cuenta predeterminada guardada.';
        ok.classList.remove('hidden');
        setTimeout(() => ok.classList.add('hidden'), 4000);
    } catch (e) {
        alert(e.message || 'Error al guardar');
    } finally {
        btn.disabled = STATE.accounts.length < 2;
    }
}

function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('DOMContentLoaded', loadAccounts);
</script>
</body>
</html>
