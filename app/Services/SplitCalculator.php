<?php
namespace App\Services;
class SplitCalculator
{
    /**
     * @param  int  $count  jumlah peserta (indeks 0..$count-1)
     * @param  array  $receipts  [
     *     ['payer' => int, 'tax' => float, 'items' => [
     *         ['price' => int, 'qty' => int, 'buyers' => [indeksPeserta => bobot]],
     *     ]],
     * ]
     * @return array{totals: array, receipt_totals: array, transfers: array}
     */
    public function calculate(int $count, array $receipts): array
    {
        $owed = array_fill(0, $count, 0);
        $paid = array_fill(0, $count, 0);
        $receiptTotals = [];
        foreach ($receipts as $r => $receipt) {
            $raw = array_fill(0, $count, 0.0);
            $subtotal = 0;
            foreach ($receipt['items'] as $item) {
                $line = $item['price'] * $item['qty'];
                $subtotal += $line;
                $weightSum = array_sum($item['buyers']);
                foreach ($item['buyers'] as $p => $weight) {
                    $raw[$p] += $line * ($weight / $weightSum);
                }
            }
            $factor = 1 + (($receipt['tax'] ?? 0) / 100);
            foreach ($raw as $p => $value) {
                $raw[$p] = $value * $factor;
            }
            $total = (int) round($subtotal * $factor);
            $share = $this->allocate($raw, $total);
            foreach ($share as $p => $amount) {
                $owed[$p] += $amount;
            }
            $paid[$receipt['payer']] += $total;
            $receiptTotals[$r] = $total;
        }
        $totals = [];
        $net = [];
        for ($p = 0; $p < $count; $p++) {
            $net[$p] = $paid[$p] - $owed[$p];
            $totals[$p] = ['owed' => $owed[$p], 'paid' => $paid[$p], 'net' => $net[$p]];
        }
        return [
            'totals' => $totals,
            'receipt_totals' => $receiptTotals,
            'transfers' => $this->simplifyDebts($net),
        ];
    }
    private function allocate(array $raw, int $total): array
    {
        $floors = [];
        $fractions = [];
        foreach ($raw as $p => $value) {
            $value = round($value, 6);
            $floors[$p] = (int) floor($value);
            if ($value > 0) {
                $fractions[$p] = $value - floor($value);
            }
        }
        $remainder = $total - array_sum($floors);
        arsort($fractions);
        $order = array_keys($fractions);
        for ($i = 0; $remainder > 0 && count($order) > 0; $i++, $remainder--) {
            $floors[$order[$i % count($order)]]++;
        }
        return $floors;
    }
    /**
     * @param  array<int,int>  $net  paid - owed per peserta
     * @return array<int,array{from:int,to:int,amount:int}>
     */
    private function simplifyDebts(array $net): array
    {
        $creditors = [];
        $debtors = [];
        foreach ($net as $p => $value) {
            if ($value > 0) {
                $creditors[$p] = $value;
            } elseif ($value < 0) {
                $debtors[$p] = -$value;
            }
        }
        $transfers = [];
        while ($creditors && $debtors) {
            arsort($creditors);
            arsort($debtors);
            $c = array_key_first($creditors);
            $d = array_key_first($debtors);
            $amount = min($creditors[$c], $debtors[$d]);
            $transfers[] = ['from' => $d, 'to' => $c, 'amount' => $amount];
            $creditors[$c] -= $amount;
            $debtors[$d] -= $amount;
            if ($creditors[$c] === 0) {
                unset($creditors[$c]);
            }
            if ($debtors[$d] === 0) {
                unset($debtors[$d]);
            }
        }
        return $transfers;
    }
}