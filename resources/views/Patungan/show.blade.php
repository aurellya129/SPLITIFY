@extends('layouts.app')
@section('title', $patungan->judul.' | Splitify')
@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp
@section('content')
    <h1>{{ $patungan->judul }}</h1>
    <p class="lead">Dibuat {{ $patungan->created_at->format('d/m/Y H:i') }} &middot; {{ $patungan->participants->count() }} orang &middot; {{ $patungan->receipts->count() }} struk</p>
    <div class="slip" aria-labelledby="slipTitle">
        <h2 id="slipTitle">Siapa transfer ke siapa</h2>
        @forelse ($patungan->transfers as $t)
            <div class="line">
                <span><strong>{{ $t->from->name }}</strong> transfer ke <strong>{{ $t->to->name }}</strong></span>
                <span class="amt">{{ $rp($t->amount) }}</span>
            </div>
        @empty
            <p class="settled">Semua sudah impas. Tidak ada yang perlu transfer.</p>
        @endforelse
        <div class="slip-foot">
            <button type="button" class="btn" id="copyLink" data-url="{{ route('patungan.show', $patungan->slug) }}">Salin tautan</button>
            <span id="copyMsg" class="msg" role="status"></span>
        </div>
    </div>
    <section class="block">
        <h2>Ringkasan per orang</h2>
        <div class="scroll">
            <table>
                <thead>
                    <tr><th>Nama</th><th class="num">Total belanja</th><th class="num">Sudah bayar</th><th class="num">Selisih</th></tr>
                </thead>
                <tbody>
                    @foreach ($patungan->participants as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td class="num">{{ $rp($p->total_owed) }}</td>
                            <td class="num">{{ $rp($p->total_paid) }}</td>
                            <td class="num {{ $p->net > 0 ? 'pos' : ($p->net < 0 ? 'neg' : '') }}">
                                {{ $p->net > 0 ? '+' : ($p->net < 0 ? '-' : '') }}{{ $rp(abs($p->net)) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="hint">Selisih positif berarti uangnya akan diterima. Selisih negatif berarti perlu membayar.</p>
    </section>
    <section class="block">
        <h2>Rincian struk</h2>
        @foreach ($patungan->receipts as $receipt)
            @php $sub = $receipt->items->sum(fn ($i) => $i->price * $i->qty); @endphp
            <article class="receipt">
                <header>
                    <h3>Struk {{ $loop->iteration }}</h3>
                    <span class="hint">Dibayar oleh {{ $receipt->payer->name }}</span>
                </header>
                <div class="scroll">
                    <table>
                        <thead>
                            <tr><th>Barang</th><th>Diambil oleh</th><th class="num">Jumlah</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($receipt->items as $item)
                                <tr>
                                    <td>{{ $item->name }}<br><span class="hint">{{ $item->qty }} &times; {{ $rp($item->price) }}</span></td>
                                    <td>
                                        @foreach ($item->assignments as $a)
                                            {{ $a->participant->name }} &times;{{ $qty($a->qty) }}@if (! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td class="num">{{ $rp($item->price * $item->qty) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sum">
                    <span>Subtotal</span><span class="num">{{ $rp($sub) }}</span>
                    <span>Pajak {{ rtrim(rtrim(number_format((float) $receipt->tax_percent, 2, ',', '.'), '0'), ',') }}%</span>
                    <span class="num">{{ $rp($receipt->total - $sub) }}</span>
                    <strong>Total</strong><strong class="num">{{ $rp($receipt->total) }}</strong>
                </div>
            </article>
        @endforeach
    </section>
    <a class="btn ghost" href="{{ route('patungan.create') }}">Buat patungan baru</a>
@endsection
@push('scripts')
    <script>
        (function () {
            var btn = document.getElementById('copyLink');
            var msg = document.getElementById('copyMsg');
            btn.addEventListener('click', function () {
                var url = btn.dataset.url;
                var done = function () { msg.textContent = 'Tautan disalin.'; };
                var fail = function () { msg.textContent = 'Salin manual: ' + url; };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(done, fail);
                } else {
                    fail();
                }
            });
        })();
    </script>
@endpush