(function () {
    'use strict';
    var form = document.getElementById('splitForm');
    if (!form) { return; }
    var elJudul = document.getElementById('judul');
    var elName = document.getElementById('pName');
    var elMsg = document.getElementById('pMsg');
    var elList = document.getElementById('pList');
    var elReceipts = document.getElementById('receipts');
    var elErrors = document.getElementById('errors');
    var btnSubmit = document.getElementById('submitBtn');
    var uid = 0;
    var state = { participants: [], receipts: [] };
    function nextId() { uid += 1; return uid; }
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function rupiah(n) { return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID'); }
    function newItem() { return { id: nextId(), name: '', price: '', qty: 1, buyers: {} }; }
    function newReceipt() { return { id: nextId(), payer: null, tax: '', items: [newItem()] }; }
    function findReceipt(id) {
        return state.receipts.filter(function (r) { return r.id === id; })[0];
    }
    function findItem(r, id) {
        return r.items.filter(function (i) { return i.id === id; })[0];
    }
    function hasBuyer(item) {
        return Object.keys(item.buyers).some(function (k) { return Number(item.buyers[k]) > 0; });
    }
    function renderParticipants() {
        elList.innerHTML = state.participants.map(function (p) {
            return '<li>' + esc(p.name) +
                '<button type="button" data-del-p="' + p.id + '" aria-label="Hapus ' + esc(p.name) + '">&times;</button></li>';
        }).join('');
    }
    function addParticipant() {
        var name = elName.value.trim();
        elMsg.textContent = '';
        if (!name) { elMsg.textContent = 'Tulis nama teman dulu.'; return; }
        var dup = state.participants.some(function (p) { return p.name.toLowerCase() === name.toLowerCase(); });
        if (dup) { elMsg.textContent = 'Nama "' + name + '" sudah ada. Beri tambahan agar berbeda, misalnya inisial.'; return; }
        state.participants.push({ id: nextId(), name: name });
        elName.value = '';
        elName.focus();
        renderParticipants();
        renderReceipts();
    }
    function removeParticipant(id) {
        state.participants = state.participants.filter(function (p) { return p.id !== id; });
        state.receipts.forEach(function (r) {
            if (r.payer === id) { r.payer = null; }
            r.items.forEach(function (it) { delete it.buyers[id]; });
        });
        renderParticipants();
        renderReceipts();
    }
    function itemHtml(it) {
        var buyers = state.participants.length
            ? state.participants.map(function (p) {
                var v = it.buyers[p.id] === undefined ? '' : it.buyers[p.id];
                return '<label class="buyer">' + esc(p.name) +
                    '<input type="number" inputmode="numeric" min="0" step="1" placeholder="0" value="' + esc(v) +
                    '" data-f="buyer" data-p="' + p.id + '" aria-label="Jumlah ' + esc(it.name || 'barang') + ' yang diambil ' + esc(p.name) + '"></label>';
            }).join('')
            : '<span>Tambah teman dulu untuk memilih pembeli.</span>';
        return '<div class="item" data-i="' + it.id + '">' +
            '<div class="item-main">' +
            '<div class="f-name"><label>Nama barang<input type="text" maxlength="80" data-f="name" value="' + esc(it.name) + '" placeholder="Contoh: Nasi goreng"></label></div>' +
            '<div><label>Harga satuan<input type="number" inputmode="numeric" min="1" step="1" data-f="price" value="' + esc(it.price) + '" placeholder="25000"></label></div>' +
            '<div><label>Jumlah<input type="number" inputmode="numeric" min="1" step="1" data-f="qty" value="' + esc(it.qty) + '"></label></div>' +
            '<button type="button" class="del" data-act="del-item" aria-label="Hapus barang">&times;</button>' +
            '</div>' +
            '<p class="line-total" data-line="' + it.id + '"></p>' +
            '<div class="buyers"><span>Yang ambil:</span>' + buyers + '</div>' +
            '</div>';
    }
    function receiptHtml(r, index) {
        var payerOpts = '<option value="">Pilih pembayar</option>' + state.participants.map(function (p) {
            return '<option value="' + p.id + '"' + (r.payer === p.id ? ' selected' : '') + '>' + esc(p.name) + '</option>';
        }).join('');
        return '<article class="receipt" data-r="' + r.id + '">' +
            '<header><h3>Struk ' + (index + 1) + '</h3>' +
            (state.receipts.length > 1 ? '<button type="button" class="link-btn" data-act="del-receipt">Hapus struk</button>' : '') +
            '</header>' +
            '<div class="row2">' +
            '<div><label>Yang membayar<select data-f="payer">' + payerOpts + '</select></label></div>' +
            '<div><label>Pajak (%)<input type="number" inputmode="decimal" min="0" max="100" step="0.1" data-f="tax" value="' + esc(r.tax) + '" placeholder="0"></label></div>' +
            '</div>' +
            r.items.map(itemHtml).join('') +
            '<div class="receipt-foot">' +
            '<button type="button" class="btn" data-act="add-item">Tambah barang</button>' +
            '<span class="sum-text" data-sum="' + r.id + '"></span>' +
            '</div></article>';
    }
    function renderReceipts() {
        elReceipts.innerHTML = state.receipts.map(receiptHtml).join('');
        updateTotals();
    }
    function updateTotals() {
        state.receipts.forEach(function (r) {
            var sub = 0;
            r.items.forEach(function (it) {
                var line = (parseInt(it.price, 10) || 0) * (parseInt(it.qty, 10) || 0);
                sub += line;
                var el = elReceipts.querySelector('[data-line="' + it.id + '"]');
                if (el) { el.textContent = line ? 'Total barang: ' + rupiah(line) : ''; }
            });
            var tax = Number(r.tax) || 0;
            var total = Math.round(sub * (1 + tax / 100));
            var sum = elReceipts.querySelector('[data-sum="' + r.id + '"]');
            if (sum) { sum.textContent = 'Total struk: ' + rupiah(total); }
        });
    }
    elReceipts.addEventListener('input', onField);
    elReceipts.addEventListener('change', onField);
    function onField(e) {
        var f = e.target.getAttribute('data-f');
        if (!f) { return; }
        var rEl = e.target.closest('[data-r]');
        var r = findReceipt(Number(rEl.getAttribute('data-r')));
        if (!r) { return; }
        if (f === 'payer') { r.payer = e.target.value ? Number(e.target.value) : null; return; }
        if (f === 'tax') { r.tax = e.target.value; updateTotals(); return; }
        var iEl = e.target.closest('[data-i]');
        var it = iEl && findItem(r, Number(iEl.getAttribute('data-i')));
        if (!it) { return; }
        if (f === 'buyer') {
            it.buyers[Number(e.target.getAttribute('data-p'))] = e.target.value;
            if (hasBuyer(it)) { iEl.classList.remove('invalid'); }
        } else {
            it[f] = e.target.value;
        }
        updateTotals();
    }
    elReceipts.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) { return; }
        var rEl = btn.closest('[data-r]');
        var r = findReceipt(Number(rEl.getAttribute('data-r')));
        var act = btn.getAttribute('data-act');
        if (act === 'add-item') {
            r.items.push(newItem());
        } else if (act === 'del-item') {
            var id = Number(btn.closest('[data-i]').getAttribute('data-i'));
            if (r.items.length === 1) { r.items = [newItem()]; }
            else { r.items = r.items.filter(function (i) { return i.id !== id; }); }
        } else if (act === 'del-receipt') {
            state.receipts = state.receipts.filter(function (x) { return x.id !== r.id; });
        }
        renderReceipts();
    });
    elList.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-del-p]');
        if (btn) { removeParticipant(Number(btn.getAttribute('data-del-p'))); }
    });
    document.getElementById('addP').addEventListener('click', addParticipant);
    elName.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addParticipant(); }
    });
    document.getElementById('addR').addEventListener('click', function () {
        state.receipts.push(newReceipt());
        renderReceipts();
    });
    function validate() {
        var errors = [];
        var bad = [];
        if (!elJudul.value.trim()) { errors.push('Judul patungan belum diisi.'); }
        if (state.participants.length < 2) { errors.push('Tambahkan minimal 2 teman.'); }
        state.receipts.forEach(function (r, ri) {
            var label = 'Struk ' + (ri + 1);
            if (r.payer === null) { errors.push('Pilih siapa yang membayar ' + label + '.'); }
            var tax = Number(r.tax) || 0;
            if (tax < 0 || tax > 100) { errors.push('Pajak ' + label + ' harus 0 sampai 100 persen.'); }
            r.items.forEach(function (it, ii) {
                var name = it.name.trim();
                var tag = label + ', barang ' + (ii + 1);
                if (!name) { errors.push(tag + ': nama belum diisi.'); }
                if (!(parseInt(it.price, 10) > 0)) { errors.push(tag + ': harga harus lebih dari 0.'); }
                if (!(parseInt(it.qty, 10) > 0)) { errors.push(tag + ': jumlah minimal 1.'); }
                if (!hasBuyer(it)) {
                    errors.push('Barang "' + (name || 'tanpa nama') + '" (' + label + ') belum memiliki pembeli.');
                    bad.push(it.id);
                }
            });
        });
        return { errors: errors, bad: bad };
    }
    function showErrors(list) {
        elErrors.innerHTML = '<strong>Periksa lagi sebelum menghitung:</strong><ul>' +
            list.map(function (m) { return '<li>' + esc(m) + '</li>'; }).join('') + '</ul>';
        elErrors.hidden = false;
        elErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function buildPayload() {
        var index = {};
        state.participants.forEach(function (p, i) { index[p.id] = i; });
        return {
            judul: elJudul.value.trim(),
            participants: state.participants.map(function (p) { return p.name; }),
            receipts: state.receipts.map(function (r) {
                return {
                    payer: index[r.payer],
                    tax: Number(r.tax) || 0,
                    items: r.items.map(function (it) {
                        var buyers = {};
                        Object.keys(it.buyers).forEach(function (pid) {
                            var w = Number(it.buyers[pid]);
                            if (w > 0 && index[pid] !== undefined) { buyers[index[pid]] = w; }
                        });
                        return {
                            name: it.name.trim(),
                            price: parseInt(it.price, 10),
                            qty: parseInt(it.qty, 10),
                            buyers: buyers
                        };
                    })
                };
            })
        };
    }
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        elErrors.hidden = true;
        var result = validate();
        elReceipts.querySelectorAll('.item.invalid').forEach(function (n) { n.classList.remove('invalid'); });
        result.bad.forEach(function (id) {
            var n = elReceipts.querySelector('[data-i="' + id + '"]');
            if (n) { n.classList.add('invalid'); }
        });
        if (result.errors.length) { showErrors(result.errors); return; }
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Menghitung...';
        fetch(form.getAttribute('data-action'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(buildPayload())
        }).then(function (res) {
            return res.json().then(function (body) { return { ok: res.ok, body: body }; });
        }).then(function (out) {
            if (out.ok && out.body.url) { window.location.href = out.body.url; return; }
            throw out.body.errors || [out.body.message || 'Terjadi kesalahan di server.'];
        }).catch(function (err) {
            showErrors(Array.isArray(err) ? err : ['Tidak bisa terhubung ke server. Coba lagi.']);
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Hitung patungan';
        });
    });
    state.receipts.push(newReceipt());
    renderParticipants();
    renderReceipts();
})();