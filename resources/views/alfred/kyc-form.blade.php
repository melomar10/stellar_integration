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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--darker-gray) 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .page-container {
            max-width: 780px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.4rem;
        }

        .page-header p {
            color: rgba(255,255,255,0.7);
            font-size: 0.95rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-grid .col-full { grid-column: 1 / -1; }

        .form-group { display: flex; flex-direction: column; gap: 0.35rem; }

        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text);
        }

        .form-label .req { color: var(--error); margin-left: 2px; }

        .form-input, .form-select {
            padding: 0.65rem 0.9rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.925rem;
            color: var(--text);
            transition: border-color 0.2s, box-shadow 0.2s;
            background: white;
            width: 100%;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(57,183,127,0.12);
        }

        .form-input.is-invalid, .form-select.is-invalid {
            border-color: var(--error);
        }

        .form-input::placeholder { color: var(--text-light); }

        .field-error {
            font-size: 0.8rem;
            color: var(--error);
        }

        /* Move type radio */
        .radio-group {
            display: flex;
            gap: 1rem;
        }

        .radio-card {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 0.9rem 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .radio-card:hover { border-color: var(--primary); }

        .radio-card input[type="radio"] {
            accent-color: var(--primary);
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-card.selected {
            border-color: var(--primary);
            background: rgba(57,183,127,0.06);
        }

        .radio-card .radio-label { font-weight: 600; font-size: 0.9rem; }
        .radio-card .radio-desc  { font-size: 0.78rem; color: var(--text-muted); }

        /* Documents section */
        .doc-notice {
            background: var(--warning-bg);
            border: 1px solid #f6e05e;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            color: #744210;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .doc-card {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .doc-card:hover { border-color: var(--primary); background: rgba(57,183,127,0.03); }

        .doc-card.has-file {
            border-color: var(--primary);
            border-style: solid;
            background: rgba(57,183,127,0.05);
        }

        .doc-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .doc-title { font-weight: 600; font-size: 0.9rem; color: var(--text); margin-bottom: 0.2rem; }
        .doc-desc  { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem; }

        .doc-card input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .doc-btn {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            background: var(--border);
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text);
            pointer-events: none;
            transition: background 0.2s;
        }

        .doc-card.has-file .doc-btn {
            background: var(--primary);
            color: white;
        }

        .doc-filename {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .doc-check {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: var(--primary);
            border-radius: 50%;
            color: white;
            font-size: 0.7rem;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .doc-card.has-file .doc-check { display: flex; }

        /* Alerts */
        .alert {
            border-radius: 8px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }

        .alert-error   { background: var(--error-bg);   border: 1px solid #feb2b2; color: #742a2a; }
        .alert-success { background: var(--success-bg); border: 1px solid #9ae6b4; color: #1c4532; }

        .alert ul { margin: 0; padding-left: 1.25rem; }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover   { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(57,183,127,0.35); }
        .btn-submit:active  { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* KYC status badge (shown after customer creation) */
        .kyc-status-box {
            background: #f7fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .kyc-status-box h4 { font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; }

        .kyc-field-list { list-style: none; display: flex; flex-direction: column; gap: 0.3rem; }

        .kyc-field-list li {
            font-size: 0.8rem;
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
            background: white;
            border-radius: 5px;
            border: 1px solid var(--border);
        }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .col-full { grid-column: 1; }
            .doc-grid { grid-template-columns: 1fr; }
            .radio-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1>Verificación de Identidad</h1>
            <p>Completa tu información y sube al menos un documento de identidad para continuar</p>
        </div>

        {{-- Mensajes de error globales --}}
        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form id="kycForm"
              method="POST"
              action="{{ route('alfred.kyc.submit') }}"
              enctype="multipart/form-data"
              novalidate>
            @csrf

            {{-- ── Sección 1: Datos Personales ── --}}
            <div class="card">
                <div class="section-title">Datos Personales</div>
                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label" for="firstName">Nombre <span class="req">*</span></label>
                        <input class="form-input @error('firstName') is-invalid @enderror"
                               type="text" id="firstName" name="firstName"
                               value="{{ old('firstName') }}" placeholder="Juan" required>
                        @error('firstName') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="middleName">Segundo Nombre</label>
                        <input class="form-input @error('middleName') is-invalid @enderror"
                               type="text" id="middleName" name="middleName"
                               value="{{ old('middleName') }}" placeholder="Michael">
                        @error('middleName') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="lastName">Apellido <span class="req">*</span></label>
                        <input class="form-input @error('lastName') is-invalid @enderror"
                               type="text" id="lastName" name="lastName"
                               value="{{ old('lastName') }}" placeholder="Pérez" required>
                        @error('lastName') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Correo Electrónico <span class="req">*</span></label>
                        <input class="form-input @error('email') is-invalid @enderror"
                               type="email" id="email" name="email"
                               value="{{ old('email') }}" placeholder="juan@ejemplo.com" required>
                        @error('email') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phoneNumber">Teléfono <span class="req">*</span></label>
                        <input class="form-input @error('phoneNumber') is-invalid @enderror"
                               type="tel" id="phoneNumber" name="phoneNumber"
                               value="{{ old('phoneNumber') }}" placeholder="+1 (809) 555-0000" required>
                        @error('phoneNumber') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dateOfBirth">Fecha de Nacimiento</label>
                        <input class="form-input @error('dateOfBirth') is-invalid @enderror"
                               type="date" id="dateOfBirth" name="dateOfBirth"
                               value="{{ old('dateOfBirth') }}">
                        @error('dateOfBirth') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="placeOfBirth">Lugar de Nacimiento</label>
                        <input class="form-input @error('placeOfBirth') is-invalid @enderror"
                               type="text" id="placeOfBirth" name="placeOfBirth"
                               value="{{ old('placeOfBirth') }}" placeholder="Santo Domingo, RD">
                        @error('placeOfBirth') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gender">Género</label>
                        <select class="form-select @error('gender') is-invalid @enderror"
                                id="gender" name="gender">
                            <option value="">Seleccionar...</option>
                            <option value="Male"   {{ old('gender') === 'Male'   ? 'selected' : '' }}>Masculino</option>
                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Femenino</option>
                            <option value="Other"  {{ old('gender') === 'Other'  ? 'selected' : '' }}>Otro</option>
                        </select>
                        @error('gender') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="mainNationality">Nacionalidad Principal</label>
                        <input class="form-input @error('mainNationality') is-invalid @enderror"
                               type="text" id="mainNationality" name="mainNationality"
                               value="{{ old('mainNationality') }}" placeholder="DO">
                        @error('mainNationality') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="secondaryNationalities">Nacionalidades Secundarias</label>
                        <input class="form-input @error('secondaryNationalities') is-invalid @enderror"
                               type="text" id="secondaryNationalities" name="secondaryNationalities"
                               value="{{ old('secondaryNationalities') }}" placeholder="US, MX">
                        @error('secondaryNationalities') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="preferredLanguage">Idioma Preferido</label>
                        <select class="form-select @error('preferredLanguage') is-invalid @enderror"
                                id="preferredLanguage" name="preferredLanguage">
                            <option value="">Seleccionar...</option>
                            <option value="es" {{ old('preferredLanguage', 'es') === 'es' ? 'selected' : '' }}>Español</option>
                            <option value="en" {{ old('preferredLanguage') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="pt" {{ old('preferredLanguage') === 'pt' ? 'selected' : '' }}>Português</option>
                        </select>
                        @error('preferredLanguage') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="isPep">¿Es Persona Políticamente Expuesta (PEP)?</label>
                        <select class="form-select @error('isPep') is-invalid @enderror"
                                id="isPep" name="isPep">
                            <option value="false" {{ old('isPep', 'false') === 'false' ? 'selected' : '' }}>No</option>
                            <option value="true"  {{ old('isPep') === 'true'  ? 'selected' : '' }}>Sí</option>
                        </select>
                        @error('isPep') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            {{-- ── Sección 2: Dirección ── --}}
            <div class="card">
                <div class="section-title">Dirección</div>
                <div class="form-grid">

                    <div class="form-group col-full">
                        <label class="form-label" for="streetAddress">Dirección (Calle)</label>
                        <input class="form-input @error('streetAddress') is-invalid @enderror"
                               type="text" id="streetAddress" name="streetAddress"
                               value="{{ old('streetAddress') }}" placeholder="123 Calle Principal">
                        @error('streetAddress') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group col-full">
                        <label class="form-label" for="streetAddressLine2">Dirección Línea 2</label>
                        <input class="form-input @error('streetAddressLine2') is-invalid @enderror"
                               type="text" id="streetAddressLine2" name="streetAddressLine2"
                               value="{{ old('streetAddressLine2') }}" placeholder="Apto 4B">
                        @error('streetAddressLine2') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group col-full">
                        <label class="form-label" for="residentialAddress">Dirección Residencial</label>
                        <input class="form-input @error('residentialAddress') is-invalid @enderror"
                               type="text" id="residentialAddress" name="residentialAddress"
                               value="{{ old('residentialAddress') }}" placeholder="123 Calle Principal">
                        @error('residentialAddress') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="city">Ciudad</label>
                        <input class="form-input @error('city') is-invalid @enderror"
                               type="text" id="city" name="city"
                               value="{{ old('city') }}" placeholder="Santo Domingo">
                        @error('city') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="stateProvinceRegion">Estado / Provincia / Región</label>
                        <input class="form-input @error('stateProvinceRegion') is-invalid @enderror"
                               type="text" id="stateProvinceRegion" name="stateProvinceRegion"
                               value="{{ old('stateProvinceRegion') }}" placeholder="Distrito Nacional">
                        @error('stateProvinceRegion') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="country">País</label>
                        <select class="form-select @error('country') is-invalid @enderror"
                                id="country" name="country">
                            <option value="">Seleccionar...</option>
                            <option value="Dominican Republic" {{ old('country', 'Dominican Republic') === 'Dominican Republic' ? 'selected' : '' }}>República Dominicana</option>
                            <option value="United States" {{ old('country') === 'United States' ? 'selected' : '' }}>Estados Unidos</option>
                            <option value="Mexico" {{ old('country') === 'Mexico' ? 'selected' : '' }}>México</option>
                            <option value="Colombia" {{ old('country') === 'Colombia' ? 'selected' : '' }}>Colombia</option>
                            <option value="Brazil" {{ old('country') === 'Brazil' ? 'selected' : '' }}>Brasil</option>
                        </select>
                        @error('country') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="postalCode">Código Postal</label>
                        <input class="form-input @error('postalCode') is-invalid @enderror"
                               type="text" id="postalCode" name="postalCode"
                               value="{{ old('postalCode') }}" placeholder="10101">
                        @error('postalCode') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            {{-- ── Sección 3: Números de Documento ── --}}
            <div class="card">
                <div class="section-title">Números de Documento</div>
                <div class="form-grid">

                    <div class="form-group">
                        <label class="form-label" for="nationalId">Número de Cédula / ID Nacional</label>
                        <input class="form-input @error('nationalId') is-invalid @enderror"
                               type="text" id="nationalId" name="nationalId"
                               value="{{ old('nationalId') }}" placeholder="001-1234567-8">
                        @error('nationalId') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="licenseId">Número de Licencia de Conducir</label>
                        <input class="form-input @error('licenseId') is-invalid @enderror"
                               type="text" id="licenseId" name="licenseId"
                               value="{{ old('licenseId') }}" placeholder="DL123456">
                        @error('licenseId') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="dni">DNI</label>
                        <input class="form-input @error('dni') is-invalid @enderror"
                               type="text" id="dni" name="dni"
                               value="{{ old('dni') }}" placeholder="12345678">
                        @error('dni') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cpf">CPF</label>
                        <input class="form-input @error('cpf') is-invalid @enderror"
                               type="text" id="cpf" name="cpf"
                               value="{{ old('cpf') }}" placeholder="123.456.789-01">
                        @error('cpf') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            {{-- ── Sección 4: Tipo de Operación ── --}}
            <div class="card">
                <div class="section-title">Tipo de Operación</div>
                <div class="radio-group" id="moveTypeGroup">
                    <label class="radio-card {{ old('move_type', 'Enviar') === 'Enviar' ? 'selected' : '' }}"
                           id="labelEnviar">
                        <input type="radio" name="move_type" value="Enviar"
                               {{ old('move_type', 'Enviar') === 'Enviar' ? 'checked' : '' }}
                               onchange="updateRadioCards(this)">
                        <div>
                            <div class="radio-label">Enviar dinero</div>
                            <div class="radio-desc">USD → DOP (rol: sender)</div>
                        </div>
                    </label>
                    <label class="radio-card {{ old('move_type') === 'Recibir' ? 'selected' : '' }}"
                           id="labelRecibir">
                        <input type="radio" name="move_type" value="Recibir"
                               {{ old('move_type') === 'Recibir' ? 'checked' : '' }}
                               onchange="updateRadioCards(this)">
                        <div>
                            <div class="radio-label">Recibir dinero</div>
                            <div class="radio-desc">DOP → USD (rol: receiver)</div>
                        </div>
                    </label>
                </div>
                @error('move_type') <span class="field-error" style="margin-top:.5rem;display:block">{{ $message }}</span> @enderror
            </div>

            {{-- ── Sección 5: Documentos de Identidad (Fotos) ── --}}
            <div class="card">
                <div class="section-title">Fotos de Documentos</div>

                <div class="doc-notice">
                    ⚠️ Debes subir <strong>al menos uno</strong> de los siguientes documentos para continuar.
                    Se aceptan formatos JPG, PNG y PDF (máx. 10 MB).
                </div>

                @error('documents')
                    <div class="alert alert-error" style="margin-bottom:1rem">{{ $message }}</div>
                @enderror

                <div class="doc-grid" style="grid-template-columns: repeat(2, 1fr)">

                    {{-- Cédula / ID Front --}}
                    <div class="doc-card" id="cardCedula">
                        <div class="doc-check">✓</div>
                        <div class="doc-icon">🪪</div>
                        <div class="doc-title">Cédula / ID (Frontal)</div>
                        <div class="doc-desc">Foto frontal de tu cédula o ID nacional</div>
                        <span class="doc-btn" id="btnCedula">Seleccionar archivo</span>
                        <div class="doc-filename" id="nameCedula"></div>
                        <input type="file" name="idFront" id="idFront"
                               accept=".jpg,.jpeg,.png,.pdf"
                               onchange="handleFileChange(this, 'cardCedula', 'nameCedula', 'btnCedula')">
                    </div>

                    {{-- Pasaporte --}}
                    <div class="doc-card" id="cardPasaporte">
                        <div class="doc-check">✓</div>
                        <div class="doc-icon">📘</div>
                        <div class="doc-title">Pasaporte</div>
                        <div class="doc-desc">Foto de la página principal del pasaporte</div>
                        <span class="doc-btn" id="btnPasaporte">Seleccionar archivo</span>
                        <div class="doc-filename" id="namePasaporte"></div>
                        <input type="file" name="passport" id="passport"
                               accept=".jpg,.jpeg,.png,.pdf"
                               onchange="handleFileChange(this, 'cardPasaporte', 'namePasaporte', 'btnPasaporte')">
                    </div>

                </div>
            </div>

            {{-- ── Botón de envío ── --}}
            <div class="card" style="padding: 1.5rem">
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="spinner" id="submitSpinner"></span>
                    <span id="submitText">Enviar Verificación</span>
                </button>
            </div>

        </form>
    </div>

    <script>
        function handleFileChange(input, cardId, nameId, btnId) {
            const card = document.getElementById(cardId);
            const nameEl = document.getElementById(nameId);
            const btn = document.getElementById(btnId);

            if (input.files && input.files[0]) {
                card.classList.add('has-file');
                nameEl.textContent = input.files[0].name;
                btn.textContent = 'Archivo seleccionado';
            } else {
                card.classList.remove('has-file');
                nameEl.textContent = '';
                btn.textContent = 'Seleccionar archivo';
            }
        }

        function updateRadioCards(input) {
            document.getElementById('labelEnviar').classList.toggle('selected', input.value === 'Enviar');
            document.getElementById('labelRecibir').classList.toggle('selected', input.value === 'Recibir');
        }

        document.getElementById('kycForm').addEventListener('submit', function (e) {
            const idFront  = document.getElementById('idFront').files.length > 0;
            const passport = document.getElementById('passport').files.length > 0;

            if (!idFront && !passport) {
                e.preventDefault();
                alert('Debes subir al menos un documento de identidad (Cédula/ID o Pasaporte) para continuar.');
                return;
            }

            // Show loading state
            const btn     = document.getElementById('submitBtn');
            const spinner = document.getElementById('submitSpinner');
            const text    = document.getElementById('submitText');
            btn.disabled         = true;
            spinner.style.display = 'block';
            text.textContent      = 'Enviando...';
        });
    </script>
</body>
</html>
