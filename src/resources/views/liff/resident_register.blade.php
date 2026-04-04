@extends('liff.layout')

@section('title', '入居者登録')

@section('content')
    <div id="liff-status" class="liff-alert liff-alert--amber hidden"></div>
    <div id="liff-error" class="liff-alert liff-alert--red hidden"></div>

    <h1 class="liff-h1" id="page-title">入居者登録</h1>
    <p class="liff-lead" id="page-lead">物件・お名前・お部屋番号などを登録します。</p>

    <form id="form-register" class="liff-form">
        <div class="liff-field">
            <label for="property_id">物件</label>
            <select id="property_id" name="property_id" required>
                <option value="">読み込み中…</option>
            </select>
        </div>
        <div class="liff-field">
            <label for="name">お名前</label>
            <input type="text" id="name" name="name" required maxlength="100" autocomplete="name" />
        </div>
        <div class="liff-field">
            <label for="age">年齢（任意）</label>
            <input type="number" id="age" name="age" min="0" max="120" />
        </div>
        <div class="liff-field">
            <label for="room_number">部屋番号</label>
            <input type="text" id="room_number" name="room_number" required maxlength="20" />
        </div>
        <div class="liff-field">
            <label for="phone">電話番号（ハイフン可）</label>
            <input type="tel" id="phone" name="phone" required maxlength="20" placeholder="090-1234-5678" />
        </div>
        <button type="submit" id="btn-submit" class="liff-btn">
            <span id="btn-submit-label">登録する</span>
        </button>
    </form>

    <p class="liff-foot">
        <a href="/liff">メニューへ</a>
    </p>
@endsection

@push('scripts')
<script>
(function () {
    const LIFF_ID = @json(config('services.line.liff_id_register'));
    const LIFF_ID_TROUBLE = @json(config('services.line.liff_id_trouble'));
    const API_ORIGIN = window.location.origin;
    const BASE_HEADERS = {
        'Accept': 'application/json',
        'ngrok-skip-browser-warning': '69420'
    };

    const statusEl = document.getElementById('liff-status');
    const errEl = document.getElementById('liff-error');
    const form = document.getElementById('form-register');
    const propertySelect = document.getElementById('property_id');
    const btnSubmit = document.getElementById('btn-submit');
    const pageTitleEl = document.getElementById('page-title');
    const pageLeadEl = document.getElementById('page-lead');
    const btnSubmitLabel = document.getElementById('btn-submit-label');

    let isEditMode = false;

    function showErr(msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
    }

    function showStatus(msg) {
        statusEl.textContent = msg;
        statusEl.classList.remove('hidden');
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

    async function tryLoadExistingProfile() {
        const token = typeof liff !== 'undefined' ? liff.getIDToken() : null;
        if (!token) return;
        const res = await fetch(API_ORIGIN + '/api/me', {
            headers: Object.assign({}, BASE_HEADERS, { Authorization: 'Bearer ' + token }),
        });
        if (res.status === 404) {
            return;
        }
        const text = await res.text();
        let me;
        try {
            me = JSON.parse(text);
        } catch (_) {
            return;
        }
        if (!res.ok) {
            return;
        }
        isEditMode = true;
        pageTitleEl.textContent = '入居者情報の変更';
        pageLeadEl.textContent = '登録内容を更新できます。';
        btnSubmitLabel.textContent = '更新する';
        document.getElementById('name').value = me.name || '';
        if (me.age != null && me.age !== '') {
            document.getElementById('age').value = String(me.age);
        }
        document.getElementById('room_number').value = me.room_number || '';
        document.getElementById('phone').value = me.phone || '';
        if (me.property_id) {
            propertySelect.value = String(me.property_id);
        }
    }

    async function loadProperties() {
        const res = await fetch(API_ORIGIN + '/api/properties', { headers: Object.assign({}, BASE_HEADERS) });
        const text = await res.text();
        if (!res.ok) {
            throw new Error('HTTP ' + res.status + ' ' + text.slice(0, 120));
        }
        let items;
        try {
            items = JSON.parse(text);
        } catch (_) {
            throw new Error('JSON でない応答（ngrok 警告ページの可能性）');
        }
        if (!Array.isArray(items)) {
            throw new Error('物件データの形式が不正です');
        }
        propertySelect.innerHTML = '<option value="">選択してください</option>';
        items.forEach(function (p) {
            const o = document.createElement('option');
            o.value = p.id;
            o.textContent = p.name;
            propertySelect.appendChild(o);
        });
    }

    async function init() {
        if (!LIFF_ID) {
            showErr('.env に LINE_LIFF_ID を設定してください。');
            btnSubmit.disabled = true;
            return;
        }
        try {
            await liff.init({ liffId: LIFF_ID });
        } catch (e) {
            showErr('LIFF の初期化に失敗しました: ' + (e.message || String(e)));
            btnSubmit.disabled = true;
            return;
        }

        if (!liff.isLoggedIn()) {
            showStatus('LINE でログインします…');
            liff.login({ redirectUri: window.location.href.split('#')[0] });
            return;
        }

        try {
            await loadProperties();
            await tryLoadExistingProfile();
        } catch (e) {
            showErr('物件一覧の取得に失敗しました。' + (e.message ? '（' + e.message + '）' : ''));
        }
    }

    form.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        errEl.classList.add('hidden');
        btnSubmit.disabled = true;
        try {
            const payload = {
                property_id: parseInt(propertySelect.value, 10),
                name: document.getElementById('name').value.trim(),
                age: document.getElementById('age').value ? parseInt(document.getElementById('age').value, 10) : null,
                room_number: document.getElementById('room_number').value.trim(),
                phone: document.getElementById('phone').value.trim(),
            };
            const method = isEditMode ? 'PUT' : 'POST';
            const data = await apiJson(API_ORIGIN + '/api/residents', { method: method, body: JSON.stringify(payload) });
            alert(data.message || (isEditMode ? '更新が完了しました' : '登録が完了しました'));
            // トラブル用 LIFF が別アプリのときは liff.line.me で切り替え（同一 ID なら画面遷移のみ）
            if (LIFF_ID === LIFF_ID_TROUBLE) {
                window.location.href = '/liff/trouble-report';
            } else if (typeof liff.openWindow === 'function') {
                liff.openWindow({ url: 'https://liff.line.me/' + LIFF_ID_TROUBLE });
            } else {
                window.location.href = '/liff/trouble-report';
            }
        } catch (e) {
            showErr(e.message || String(e));
        } finally {
            btnSubmit.disabled = false;
        }
    });

    init().catch(function (e) {
        showErr(e.message || String(e));
    });
})();
</script>
@endpush
