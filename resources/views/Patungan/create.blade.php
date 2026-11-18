@extends('layouts.app')
@section('content')
    <h1>Hitung patungan tanpa ribet.</h1>
    <p class="lead">Catat struk, tandai siapa mengambil apa, lalu dapatkan nominal transfer yang pasti. Tanpa login dan tanpa unduh aplikasi.</p>
    <form id="splitForm" data-action="{{ route('patungan.store') }}" novalidate>
        <section class="block">
            <h2>Judul patungan</h2>
            <label for="judul" class="sr">Judul patungan</label>
            <input id="judul" type="text" maxlength="100" placeholder="Contoh: Makan malam Sabtu" autocomplete="off">
        </section>
        <section class="block">
            <h2>Teman yang ikut</h2>
            <p class="hint">Masukkan semua nama, termasuk kamu. Minimal 2 orang.</p>
            <div class="add-row">
                <label for="pName" class="sr">Nama teman</label>
                <input id="pName" type="text" maxlength="40" placeholder="Nama teman" autocomplete="off">
                <button type="button" id="addP" class="btn">Tambah teman</button>
            </div>
            <p id="pMsg" class="msg" role="status"></p>
            <ul id="pList" class="chips"></ul>
        </section>
        <section class="block">
            <h2>Struk belanja</h2>
            <p class="hint">Isi satu struk per pembayar. Pada tiap barang, isi jumlah yang diambil tiap orang. Barang yang dimakan bersama cukup diisi 1 oleh masing-masing orang, biayanya dibagi rata.</p>
            <div id="receipts"></div>
            <button type="button" id="addR" class="btn ghost">Tambah struk</button>
        </section>
        <div id="errors" class="errors" role="alert" hidden></div>
        <button type="submit" id="submitBtn" class="btn primary big">Hitung patungan</button>
    </form>
@endsection
@push('scripts')
    <script src="{{ asset('js/create.js') }}"></script>
@endpush