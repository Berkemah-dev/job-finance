<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page{margin:28px 34px}body{font-family:DejaVu Sans,sans-serif;color:#26364f;font-size:11px;line-height:1.55}.header{border-bottom:2px solid #2563eb;padding-bottom:14px;margin-bottom:24px}.company{font-size:19px;font-weight:700;color:#163f8f}.muted{color:#6f7f96}.title{font-size:24px;letter-spacing:2px;text-align:right;color:#1f3e72;margin-top:-44px}.grid{display:table;width:100%;margin-bottom:18px}.col{display:table-cell;width:50%;vertical-align:top}.box{border:1px solid #dce5f2;padding:12px}table{width:100%;border-collapse:collapse;margin-top:14px}th{background:#f1f5fb;color:#50647f;text-align:left;font-size:9px;padding:8px;border:1px solid #dce5f2}td{padding:8px;border:1px solid #e6edf6}.money{text-align:right}.section{margin-top:20px}.sign{display:table;width:100%;margin-top:42px}.sign div{display:table-cell;width:33%;text-align:center}.line{border-top:1px solid #aeb9c8;margin:54px 20px 0;padding-top:8px}.footer{position:fixed;bottom:-8px;left:0;right:0;text-align:center;font-size:9px;color:#8a97aa}
    </style>
</head>
<body>
<div class="header"><div class="company">JobFinance Demo Company</div><div class="muted">Jakarta, Indonesia<br>operations@jobfinance.test · +62 21 0000 0000 · jobfinance.test</div><div class="title">JOB ORDER</div></div>
<div class="grid"><div class="col"><div class="box"><strong>Customer</strong><br>{{ $quotation->customer_snapshot['name'] }}<br><strong>Project</strong><br>{{ $job->subject }}<br><strong>PIC</strong><br>{{ $quotation->customer_snapshot['contact_name'] ?? '-' }}</div></div><div class="col"><div class="box"><strong>Job Order No</strong>: {{ $job->number }}<br><strong>Quotation Ref</strong>: {{ $quotation->number }}<br><strong>Date</strong>: {{ $job->job_date->format('d/m/Y') }}<br><strong>Status</strong>: {{ config('operations.job_statuses.'.$job->status) }}</div></div></div>
<div class="section"><strong>Scope of Work</strong><p>{{ $job->cargo_description ?: $job->subject }}</p></div>
<table><thead><tr><th>No</th><th>Service / Item</th><th>Qty</th><th>Unit</th><th class="money">Amount</th></tr></thead><tbody>@foreach($job->quotation_snapshot['items'] as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item['description'] }}</td><td>{{ \App\Support\Money::format($item['quantity']) }}</td><td>{{ $item['unit'] }}</td><td class="money">Rp {{ \App\Support\Money::format($item['total_price']) }}</td></tr>@endforeach</tbody></table>
<div class="grid section"><div class="col"><div class="box"><strong>Schedule</strong><br>Start Date: {{ $job->job_date->format('d/m/Y') }}<br>End Date: {{ $job->expected_completion_date?->format('d/m/Y') ?? '-' }}</div></div><div class="col"><div class="box"><strong>Operational Notes</strong><br>{{ $job->operational_notes ?: '-' }}</div></div></div>
<p><strong>Terms / Special Instruction</strong><br>{{ $quotation->notes ?: 'Pelaksanaan pekerjaan mengikuti quotation dan instruksi operasional yang telah disetujui.' }}</p>
<div class="sign"><div><div class="line">Prepared By</div></div><div><div class="line">Approved By</div></div><div><div class="line">Customer / PIC</div></div></div>
<div class="footer">Page <script type="text/php">if(isset($pdf)){echo $PAGE_NUM.' of '.$PAGE_COUNT;}</script></div>
</body>
</html>
