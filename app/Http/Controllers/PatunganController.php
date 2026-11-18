<?php
namespace App\Http\Controllers;
use App\Models\Patungan;
use App\Services\SplitCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class PatunganController extends Controller{
    public function create(){
        return view('patungan.create');
    }
    public function store(Request $request, SplitCalculator $calculator): JsonResponse{
        $validator = Validator::make($request->all(), [
            'judul' => ['required', 'string', 'max:100'],
            'participants' => ['required', 'array', 'min:2', 'max:30'],
            'participants.*' => ['required', 'string', 'max:40', 'distinct:ignore_case'],
            'receipts' => ['required', 'array', 'min:1', 'max:20'],
            'receipts.*.payer' => ['required', 'integer', 'min:0'],
            'receipts.*.tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'receipts.*.items' => ['required', 'array', 'min:1', 'max:100'],
            'receipts.*.items.*.name' => ['required', 'string', 'max:80'],
            'receipts.*.items.*.price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'receipts.*.items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'receipts.*.items.*.buyers' => ['nullable', 'array'],
            'receipts.*.items.*.buyers.*' => ['numeric', 'min:0'],
        ], [
            'required' => 'Ada kolom wajib yang belum diisi.',
            'string' => 'Ada isian yang formatnya tidak valid.',
            'integer' => 'Harga, jumlah, dan pembayar harus berupa angka bulat.',
            'numeric' => 'Pajak dan jumlah yang diambil harus berupa angka.',
            'min' => 'Ada nilai yang terlalu kecil (minimal 2 teman, harga dan jumlah minimal 1).',
            'max' => 'Ada isian yang melebihi batas yang diizinkan.',
            'distinct' => 'Nama teman tidak boleh kembar.',
            'array' => 'Format data tidak valid.',
        ]);
        $validator->after(function ($v) use ($request) {
            $names = $request->input('participants');
            $n = is_array($names) ? count($names) : 0;
            $receipts = $request->input('receipts');
            if (! is_array($receipts)) {
                return;
            }
            foreach (array_values($receipts) as $r => $receipt) {
                $label = 'Struk '.($r + 1);
                $payer = $receipt['payer'] ?? null;
                if (is_numeric($payer) && ((int) $payer < 0 || (int) $payer >= $n)) {
                    $v->errors()->add("receipts.$r.payer", "Pembayar pada $label tidak ada dalam daftar teman.");
                }
                $items = $receipt['items'] ?? [];
                if (! is_array($items)) {
                    continue;
                }
                foreach (array_values($items) as $i => $item) {
                    $buyers = $item['buyers'] ?? [];
                    $sum = 0;
                    $invalidKey = false;
                    if (is_array($buyers)) {
                        foreach ($buyers as $pi => $weight) {
                            if (! is_numeric($pi) || (int) $pi < 0 || (int) $pi >= $n) {
                                $invalidKey = true;
                            } elseif (is_numeric($weight)) {
                                $sum += (float) $weight;
                            }
                        }
                    }
                    $name = $item['name'] ?? 'tanpa nama';
                    if ($invalidKey) {
                        $v->errors()->add("receipts.$r.items.$i.buyers", "Pembeli barang \"$name\" ($label) tidak valid.");
                    } elseif ($sum <= 0) {
                        $v->errors()->add("receipts.$r.items.$i.buyers", "Barang \"$name\" ($label) belum memiliki pembeli.");
                    }
                }
            }
        });
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data belum valid.',
                'errors' => array_values(array_unique($validator->errors()->all())),
            ], 422);
        }
        $names = array_values($request->input('participants'));
        $receipts = [];
        foreach (array_values($request->input('receipts')) as $receipt) {
            $items = [];
            foreach (array_values($receipt['items']) as $item) {
                $buyers = [];
                foreach (($item['buyers'] ?? []) as $pi => $weight) {
                    if ((float) $weight > 0) {
                        $buyers[(int) $pi] = (float) $weight;
                    }
                }
                $items[] = [
                    'name' => trim($item['name']),
                    'price' => (int) $item['price'],
                    'qty' => (int) $item['qty'],
                    'buyers' => $buyers,
                ];
            }
            $receipts[] = [
                'payer' => (int) $receipt['payer'],
                'tax' => (float) ($receipt['tax'] ?? 0),
                'items' => $items,
            ];
        }
        $result = $calculator->calculate(count($names), $receipts);
        $patungan = DB::transaction(function () use ($request, $names, $receipts, $result) {
            $patungan = Patungan::create([
                'slug' => $this->uniqueSlug(),
                'judul' => trim($request->input('judul')),
            ]);
            $rows = [];
            foreach ($names as $i => $name) {
                $rows[$i] = $patungan->participants()->create([
                    'name' => trim($name),
                    'total_owed' => $result['totals'][$i]['owed'],
                    'total_paid' => $result['totals'][$i]['paid'],
                    'net' => $result['totals'][$i]['net'],
                ]);
            }
            foreach ($receipts as $r => $receipt) {
                $receiptRow = $patungan->receipts()->create([
                    'payer_id' => $rows[$receipt['payer']]->id,
                    'tax_percent' => $receipt['tax'],
                    'total' => $result['receipt_totals'][$r],
                ]);
                foreach ($receipt['items'] as $item) {
                    $itemRow = $receiptRow->items()->create([
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                    ]);
                    foreach ($item['buyers'] as $pi => $weight) {
                        $itemRow->assignments()->create([
                            'participant_id' => $rows[$pi]->id,
                            'qty' => $weight,
                        ]);
                    }
                }
            }
            foreach ($result['transfers'] as $t) {
                $patungan->transfers()->create([
                    'from_id' => $rows[$t['from']]->id,
                    'to_id' => $rows[$t['to']]->id,
                    'amount' => $t['amount'],
                ]);
            }
            return $patungan;
        });
        return response()->json(['url' => route('patungan.show', $patungan->slug)]);
    }
    public function show(string $slug){
        $patungan = Patungan::where('slug', $slug)
            ->with([
                'participants',
                'receipts.payer',
                'receipts.items.assignments.participant',
                'transfers.from',
                'transfers.to',
            ])
            ->firstOrFail();
        return view('patungan.show', compact('patungan'));
    }
    private function uniqueSlug(): string{
        do {
            $slug = Str::lower(Str::random(10));
        } while (Patungan::where('slug', $slug)->exists());
        return $slug;
    }
}