<table class="grid">
    <thead><tr><th style="width:5%">NO</th><th style="width:22%">NO. REFERENSI</th><th style="width:31%">CHARGES</th><th class="money" style="width:21%">JUAL (IDR)</th><th class="money" style="width:21%">BELI (IDR)</th></tr></thead>
    <tbody>
        @forelse($items as $cost)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $cost->number }}<br><span class="muted">{{ match($cost->cost_category) { 'payment_request' => 'Payment Request', 'credit_note' => 'Credit Note', 'debit_note' => 'Debit Note', 'reimbursement' => 'Reimbursement', default => $cost->cost_category } }}</span></td><td>{{ $cost->description }}</td><td class="money">{{ \App\Support\Money::format($cost->total_price) }}</td><td class="money">{{ \App\Support\Money::format($cost->total_cost) }}</td></tr>
        @empty
            <tr><td colspan="5">Belum ada biaya final.</td></tr>
        @endforelse
        <tr class="total"><td colspan="3">TOTAL</td><td class="money">{{ \App\Support\Money::format($totalSell) }}</td><td class="money">{{ \App\Support\Money::format($totalBuy) }}</td></tr>
    </tbody>
</table>
