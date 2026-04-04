@extends('liff.layout')

@section('title', 'トラブル報告')

@section('content')
    <div id="liff-status" class="liff-alert liff-alert--amber hidden"></div>
    <div id="liff-error" class="liff-alert liff-alert--red hidden"></div>
    <div id="not-registered" class="liff-alert liff-alert--slate hidden">
        <p class="liff-lead" style="margin:0">先に入居者登録を行ってください。</p>
        <a href="#" id="lnk-register" class="liff-inline-link">入居者登録へ</a>
    </div>

    <h1 class="liff-h1">トラブル報告</h1>
    <p class="liff-lead" id="trouble-lead">まず困りごと種別を選んでください。</p>

    <form id="form-trouble" class="liff-form hidden" novalidate>
        <div class="liff-field">
            <label for="category_id">困りごと種別</label>
            <select id="category_id" name="category_id" required>
                <option value="">選択してください</option>
            </select>
        </div>

        <div id="panel-phone-trouble" class="hidden">
            <p class="liff-lead" style="margin-top:1rem">この種別はお電話でのご連絡となります。下記の担当業者へお電話ください。</p>
            <div id="phone-trouble-status" class="liff-hint" style="margin-top:0.5rem"></div>
            <ul id="phone-trouble-list" class="liff-phone-list" style="list-style:none;padding:0;margin:1rem 0"></ul>
            <p id="phone-trouble-emergency" class="liff-alert liff-alert--amber hidden" style="margin-top:0.75rem"></p>
        </div>

        <div id="panel-request-trouble" class="hidden">
            <p class="liff-lead" style="margin-top:1rem">依頼内容を入力して送信してください。</p>
            <div class="liff-field">
                <label for="vendor_id">担当業者（任意・空きがある場合）</label>
                <select id="vendor_id" name="vendor_id">
                    <option value="">指定なし</option>
                </select>
            </div>
            <div class="liff-field">
                <label for="preferred_date">希望日（任意）</label>
                <input type="date" id="preferred_date" name="preferred_date" />
            </div>
            <div class="liff-field">
                <label for="description">詳細</label>
                <textarea id="description" name="description" maxlength="2000" rows="5"></textarea>
            </div>
            <div class="liff-field">
                <label for="trouble_files">写真・動画（任意・最大10件）</label>
                <input type="file" id="trouble_files" accept="image/*,video/*" multiple />
                <p id="trouble_files_note" class="liff-hint"></p>
            </div>
            <p class="liff-hint">端末から Cloudinary に直接アップロードし、依頼に紐づけます（JPEG/PNG/GIF/WebP・MP4/MOV/WebM 等）。</p>
            <button type="submit" id="btn-submit" class="liff-btn">
                送信する
            </button>
        </div>
    </form>

    <p class="liff-foot">
        <a href="/liff">メニューへ</a>
    </p>
@endsection

@push('scripts')
<script>
(function () {
    const LIFF_ID = @json(config('services.line.liff_id_trouble'));
    const LIFF_ID_REGISTER = @json(config('services.line.liff_id_register'));
    const API_ORIGIN = window.location.origin;
    const BASE_HEADERS = {
        'Accept': 'application/json',
        'ngrok-skip-browser-warning': '69420'
    };

    const statusEl = document.getElementById('liff-status');
    const errEl = document.getElementById('liff-error');
    const notReg = document.getElementById('not-registered');
    const form = document.getElementById('form-trouble');
    const categorySelect = document.getElementById('category_id');
    const vendorSelect = document.getElementById('vendor_id');
    const btnSubmit = document.getElementById('btn-submit');
    const panelPhone = document.getElementById('panel-phone-trouble');
    const panelRequest = document.getElementById('panel-request-trouble');
    const phoneListEl = document.getElementById('phone-trouble-list');
    const phoneStatusEl = document.getElementById('phone-trouble-status');
    const phoneEmergencyEl = document.getElementById('phone-trouble-emergency');
    const troubleLeadEl = document.getElementById('trouble-lead');
    const descriptionEl = document.getElementById('description');

    let propertyId = null;
    /** @type {Array<{id:number,display_name:string,show_phone_number:boolean,emergency_phone:string|null}>} */
    var troubleCategoryRows = [];

    function showErr(msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
    }

    function showStatus(msg) {
        statusEl.textContent = msg;
        statusEl.classList.remove('hidden');
    }

    async function uploadTroubleMediaFiles(fileList) {
        const attachments = [];
        const max = 10;
        var files = Array.prototype.slice.call(fileList || [], 0, max);
        if (files.length === 0) {
            return attachments;
        }
        const token = typeof liff !== 'undefined' ? liff.getIDToken() : null;
        if (!token) throw new Error('LINE にログインできていません。');

        var sigRes = await fetch(API_ORIGIN + '/api/media/trouble-upload-signature', {
            method: 'GET',
            headers: Object.assign({}, BASE_HEADERS, { Authorization: 'Bearer ' + token }),
        });
        var sigText = await sigRes.text();
        var sig;
        try { sig = JSON.parse(sigText); } catch (_) { sig = { message: sigText.slice(0, 200) }; }
        if (!sigRes.ok) {
            var sm = sig.message || sigRes.statusText;
            throw new Error(typeof sm === 'string' ? sm : JSON.stringify(sm));
        }

        for (var i = 0; i < files.length; i++) {
            var fd = new FormData();
            fd.append('file', files[i]);
            fd.append('api_key', sig.api_key);
            fd.append('timestamp', String(sig.timestamp));
            fd.append('signature', sig.signature);
            if (sig.folder) {
                fd.append('folder', sig.folder);
            }
            var res = await fetch(sig.upload_url, { method: 'POST', body: fd });
            var text = await res.text();
            var body;
            try { body = JSON.parse(text); } catch (_) { body = { error: { message: text.slice(0, 200) } }; }
            if (!res.ok || body.error) {
                var errMsg = (body.error && (body.error.message || body.error)) || body.message || res.statusText;
                throw new Error(typeof errMsg === 'string' ? errMsg : JSON.stringify(errMsg));
            }
            if (body.resource_type !== 'image' && body.resource_type !== 'video') {
                throw new Error('画像・動画以外は送信できません。');
            }
            var ft = body.resource_type === 'video' ? 'video' : 'image';
            attachments.push({
                cloudinary_public_id: body.public_id,
                file_type: ft,
                url: body.secure_url,
            });
        }
        return attachments;
    }

    async function apiJson(url, options) {
        const opts = options || {};
        const token = typeof liff !== 'undefined' ? liff.getIDToken() : null;
        if (!token) throw new Error('LINE にログインできていません。');
        const res = await fetch(url, Object.assign({}, opts, {
            headers: Object.assign({}, BASE_HEADERS, {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            }, opts.headers || {})
        }));
        const text = await res.text();
        let body;
        try { body = JSON.parse(text); } catch (_) { body = { message: text.slice(0, 200) }; }
        if (!res.ok) {
            const m = body.message || body.error || res.statusText;
            throw new Error(typeof m === 'string' ? m : JSON.stringify(m));
        }
        return body;
    }

    function setRequestPanelEnabled(on) {
        if (!panelRequest) return;
        panelRequest.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            el.disabled = !on;
        });
        if (descriptionEl) {
            descriptionEl.required = !!on;
        }
    }

    function telHref(phone) {
        if (!phone) return '#';
        var digits = String(phone).replace(/[^\d+]/g, '');
        return 'tel:' + digits;
    }

    async function loadCategories() {
        const res = await fetch(API_ORIGIN + '/api/trouble-categories', { headers: Object.assign({}, BASE_HEADERS) });
        const text = await res.text();
        if (!res.ok) {
            throw new Error('HTTP ' + res.status);
        }
        let rows;
        try {
            rows = JSON.parse(text);
        } catch (_) {
            throw new Error('種別の応答が JSON ではありません（ngrok 警告の可能性）');
        }
        if (!Array.isArray(rows)) {
            throw new Error('種別データの形式が不正です');
        }
        troubleCategoryRows = rows;
        categorySelect.innerHTML = '<option value="">選択してください</option>';
        rows.forEach(function (c) {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.display_name;
            categorySelect.appendChild(o);
        });
    }

    async function loadVendorPhonesForPanel(categoryId, emergencyPhone) {
        phoneListEl.innerHTML = '';
        phoneStatusEl.textContent = '連絡先を読み込んでいます…';
        phoneEmergencyEl.classList.add('hidden');
        phoneEmergencyEl.textContent = '';

        const token = typeof liff !== 'undefined' ? liff.getIDToken() : null;
        if (!token) {
            phoneStatusEl.textContent = 'LINE にログインできていません。';
            return;
        }
        if (!propertyId) {
            phoneStatusEl.textContent = '物件情報が取得できません。';
            return;
        }

        const u = new URL('/api/vendors/contact-list', API_ORIGIN);
        u.searchParams.set('category_id', String(categoryId));
        u.searchParams.set('property_id', String(propertyId));

        try {
            const res = await fetch(u.toString(), {
                headers: Object.assign({}, BASE_HEADERS, { Authorization: 'Bearer ' + token }),
            });
            const txt = await res.text();
            let list;
            try {
                list = JSON.parse(txt);
            } catch (_) {
                phoneStatusEl.textContent = '応答の解析に失敗しました。';
                return;
            }
            if (!res.ok) {
                phoneStatusEl.textContent = (list && list.message) || res.statusText || '取得に失敗しました。';
                return;
            }
            if (!Array.isArray(list)) {
                phoneStatusEl.textContent = 'データ形式が不正です。';
                return;
            }

            phoneStatusEl.textContent = '';
            if (list.length === 0) {
                phoneStatusEl.textContent = 'この地域・種別に登録されている担当業者がいません。管理会社へお問い合わせください。';
            } else {
                list.forEach(function (row) {
                    const li = document.createElement('li');
                    li.style.marginBottom = '0.75rem';
                    li.style.padding = '0.75rem';
                    li.style.border = '1px solid #e2e8f0';
                    li.style.borderRadius = '0.5rem';
                    const name = document.createElement('div');
                    name.style.fontWeight = '600';
                    name.textContent = row.vendor_name || '担当業者';
                    const tel = document.createElement('a');
                    tel.href = telHref(row.phone);
                    tel.className = 'liff-inline-link';
                    tel.style.display = 'inline-block';
                    tel.style.marginTop = '0.25rem';
                    tel.textContent = row.phone || '（電話番号未登録）';
                    if (!row.phone) {
                        tel.removeAttribute('href');
                    }
                    li.appendChild(name);
                    li.appendChild(document.createElement('br'));
                    li.appendChild(tel);
                    phoneListEl.appendChild(li);
                });
            }

            if (emergencyPhone && String(emergencyPhone).trim() !== '') {
                phoneEmergencyEl.textContent = '';
                phoneEmergencyEl.classList.remove('hidden');
                phoneEmergencyEl.appendChild(document.createTextNode('緊急連絡先（管理会社）: '));
                const emA = document.createElement('a');
                emA.href = telHref(emergencyPhone);
                emA.className = 'liff-inline-link';
                emA.textContent = emergencyPhone;
                phoneEmergencyEl.appendChild(emA);
            }
        } catch (e) {
            phoneStatusEl.textContent = e.message || String(e);
        }
    }

    function syncCategoryPanels() {
        errEl.classList.add('hidden');
        panelPhone.classList.add('hidden');
        panelRequest.classList.add('hidden');
        setRequestPanelEnabled(false);

        const raw = categorySelect.value;
        const id = raw ? parseInt(raw, 10) : 0;
        const cat = id ? troubleCategoryRows.find(function (c) { return c.id === id; }) : null;

        if (!id || !cat) {
            troubleLeadEl.textContent = 'まず困りごと種別を選んでください。';
            vendorSelect.innerHTML = '<option value="">指定なし</option>';
            return;
        }

        if (cat.show_phone_number) {
            troubleLeadEl.textContent = 'お電話にてご連絡ください。';
            panelPhone.classList.remove('hidden');
            loadVendorPhonesForPanel(id, cat.emergency_phone).catch(function (e) {
                phoneStatusEl.textContent = e.message || String(e);
            });
        } else {
            troubleLeadEl.textContent = '依頼内容を入力して送信します。';
            panelRequest.classList.remove('hidden');
            setRequestPanelEnabled(true);
            loadVendors(id).catch(function () {});
        }
    }

    async function loadVendors(categoryId) {
        vendorSelect.innerHTML = '<option value="">指定なし</option>';
        if (!categoryId || !propertyId) return;
        const u = new URL('/api/vendors/availability', API_ORIGIN);
        u.searchParams.set('category_id', String(categoryId));
        u.searchParams.set('property_id', String(propertyId));
        const res = await fetch(u.toString(), { headers: Object.assign({}, BASE_HEADERS) });
        if (!res.ok) return;
        const text = await res.text();
        let list;
        try {
            list = JSON.parse(text);
        } catch (_) {
            return;
        }
        if (!Array.isArray(list)) return;
        list.forEach(function (row) {
            const o = document.createElement('option');
            o.value = row.vendor_id;
            o.textContent = row.vendor_name;
            vendorSelect.appendChild(o);
        });
    }

    const lnkReg = document.getElementById('lnk-register');
    if (lnkReg) {
        lnkReg.addEventListener('click', function (e) {
            e.preventDefault();
            if (LIFF_ID_REGISTER === LIFF_ID) {
                window.location.href = '/liff/resident-register';
            } else if (typeof liff.openWindow === 'function') {
                liff.openWindow({ url: 'https://liff.line.me/' + LIFF_ID_REGISTER });
            } else {
                window.location.href = '/liff/resident-register';
            }
        });
    }

    categorySelect.addEventListener('change', function () {
        syncCategoryPanels();
    });

    document.getElementById('trouble_files').addEventListener('change', function () {
        var el = document.getElementById('trouble_files');
        var n = el.files ? el.files.length : 0;
        var note = document.getElementById('trouble_files_note');
        if (n > 10) {
            note.textContent = '10件を超えた分は無視されます。';
        } else if (n > 0) {
            note.textContent = n + '件選択中';
        } else {
            note.textContent = '';
        }
    });

    form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        errEl.classList.add('hidden');
        const cid = categorySelect.value ? parseInt(categorySelect.value, 10) : 0;
        const selCat = cid ? troubleCategoryRows.find(function (c) { return c.id === cid; }) : null;
        if (selCat && selCat.show_phone_number) {
            return;
        }
        if (!descriptionEl || !descriptionEl.value.trim()) {
            showErr('詳細を入力してください。');
            return;
        }
        btnSubmit.disabled = true;
        try {
            const fileInput = document.getElementById('trouble_files');
            var attachments = [];
            if (fileInput.files && fileInput.files.length > 0) {
                showStatus('画像・動画をアップロードしています…');
                attachments = await uploadTroubleMediaFiles(fileInput.files);
                statusEl.classList.add('hidden');
            }
            const vid = vendorSelect.value ? parseInt(vendorSelect.value, 10) : null;
            const pref = document.getElementById('preferred_date').value || null;
            const payload = {
                category_id: parseInt(categorySelect.value, 10),
                description: document.getElementById('description').value.trim(),
                vendor_id: vid,
                preferred_date: pref,
                attachments: attachments,
            };
            const data = await apiJson(API_ORIGIN + '/api/trouble-requests', { method: 'POST', body: JSON.stringify(payload) });
            alert((data.message || '受付しました') + (data.request_id ? '（依頼番号: ' + data.request_id + '）' : ''));
            form.reset();
            vendorSelect.innerHTML = '<option value="">指定なし</option>';
            document.getElementById('trouble_files_note').textContent = '';
            syncCategoryPanels();
        } catch (e) {
            statusEl.classList.add('hidden');
            showErr(e.message || String(e));
        } finally {
            btnSubmit.disabled = false;
        }
    });

    async function init() {
        if (!LIFF_ID) {
            showErr('.env に LINE_LIFF_ID_TROUBLE（単一 LIFF なら LINE_LIFF_ID）を設定してください。');
            return;
        }
        try {
            await liff.init({ liffId: LIFF_ID });
        } catch (e) {
            showErr('LIFF の初期化に失敗しました: ' + (e.message || String(e)));
            return;
        }

        if (!liff.isLoggedIn()) {
            showStatus('LINE でログインします…');
            liff.login({ redirectUri: window.location.href.split('#')[0] });
            return;
        }

        const meRes = await fetch(API_ORIGIN + '/api/me', {
            headers: Object.assign({}, BASE_HEADERS, {
                Authorization: 'Bearer ' + liff.getIDToken(),
            }),
        });
        const meText = await meRes.text();
        let meBody = {};
        try {
            meBody = meText ? JSON.parse(meText) : {};
        } catch (_) {}
        if (meRes.status === 404) {
            notReg.classList.remove('hidden');
            return;
        }
        if (!meRes.ok) {
            showErr(meBody.message || '入居者情報の取得に失敗しました。');
            return;
        }
        propertyId = meBody.property_id;

        try {
            await loadCategories();
        } catch (e) {
            showErr('種別の取得に失敗しました。' + (e.message ? '（' + e.message + '）' : ''));
            return;
        }

        form.classList.remove('hidden');
        syncCategoryPanels();
    }

    init().catch(function (e) {
        showErr(e.message || String(e));
    });
})();
</script>
@endpush
