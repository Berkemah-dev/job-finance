@extends('layouts.app')
@section('title','Detail Dokumen Job')
@section('content')
@php
$job = $quotation->job;
$tab = request('tab', 'overview');
$events = collect([
    ['time' => $quotation->created_at, 'title' => 'Quotation dibuat', 'actor' => $quotation->creator?->name],
    ['time' => $quotation->submitted_at, 'title' => 'Quotation diajukan', 'actor' => null],
    ['time' => $quotation->approved_at, 'title' => 'Quotation disetujui', 'actor' => $quotation->approver?->name],
    ['time' => $quotation->rejected_at, 'title' => 'Quotation ditolak', 'actor' => null],
    ['time' => $quotation->converted_at, 'title' => $job ? 'Job Order dibuat dari '.$quotation->number : 'Quotation dikonversi', 'actor' => null],
    ['time' => $job?->opened_at, 'title' => 'Job Order aktif', 'actor' => null],
    ['time' => $job?->closed_at, 'title' => 'Job Order selesai', 'actor' => null],
])->filter(fn ($event) => $event['time'])->sortBy('time');
@endphp
<div class="page-heading">
    <div>
        <p class="eyebrow">DOKUMEN JOB</p>
        <h1>{{ $job?->number ?? $quotation->number }}</h1>
        <p>{{ $quotation->customer_snapshot['name'] }} · {{ $quotation->subject }}</p>
    </div>
    <div class="action-group">
        @if($job)@can('update',$job)<a class="button button-secondary" href="{{ route('jobs.edit',$job) }}">Edit</a>@endcan
        @else@can('update',$quotation)<a class="button button-secondary" href="{{ route('quotations.edit',$quotation) }}">Edit</a>@endcan @endif
        <a class="button button-secondary" href="{{ route('documents.quotation.preview',$quotation) }}">Preview PDF</a>
        <a class="button button-primary" href="{{ route('documents.quotation.pdf',['quotation'=>$quotation,'mode'=>'download']) }}">Download PDF</a>
    </div>
</div>

<section class="panel document-flow">
    <div><span>Quotation</span><strong>{{ $quotation->number }}</strong><em class="status-badge status-{{ $quotation->status->value }}">{{ $quotation->status->label() }}</em></div>
    <x-icon name="arrow"/>
    <div><span>Job Order</span><strong>{{ $job?->number ?? 'Belum dibuat' }}</strong><em class="status-badge status-{{ $job?->status ?? 'draft' }}">{{ $job ? config('operations.job_statuses.'.$job->status) : 'Menunggu' }}</em></div>
    <x-icon name="arrow"/>
    <div><span>Financial Process</span><strong>{{ $job?->invoice?->number ?? 'Belum invoice' }}</strong><em>{{ $job?->closingSnapshot ? 'Closing selesai' : 'Referensi keuangan' }}</em></div>
</section>

<nav class="document-tabs">
    @foreach(['overview'=>'Overview','quotation'=>'Quotation','job'=>'Job Order','history'=>'Riwayat & Approval'] as $value => $label)
    <a class="{{ $tab === $value ? 'active' : '' }}" href="{{ route('documents.show', ['quotation' => $quotation, 'tab' => $value]) }}">{{ $label }}</a>
    @endforeach
</nav>

@if($tab === 'overview')
<section class="document-detail-grid">
    <article class="panel"><div class="panel-heading"><h2>Informasi Umum</h2><span class="status-badge status-{{ $job?->status ?? $quotation->status->value }}">{{ $job ? config('operations.job_statuses.'.$job->status) : $quotation->status->label() }}</span></div>
        <dl class="detail-grid">
            <div><dt>Job Number</dt><dd>{{ $job?->number ?? 'Belum dibuat' }}</dd></div>
            <div><dt>Customer</dt><dd>{{ $quotation->customer_snapshot['name'] }}</dd></div>
            <div><dt>Project / Job Name</dt><dd>{{ $quotation->subject }}</dd></div>
            <div><dt>PIC Customer</dt><dd>{{ $quotation->customer_snapshot['contact_name'] ?? '-' }}</dd></div>
            <div><dt>PIC Internal</dt><dd>{{ $quotation->creator?->name ?? '-' }}</dd></div>
            <div><dt>Job Date</dt><dd>{{ $job?->job_date?->format('d/m/Y') ?? '-' }}</dd></div>
            <div><dt>Start Date</dt><dd>{{ $job?->job_date?->format('d/m/Y') ?? '-' }}</dd></div>
            <div><dt>End Date</dt><dd>{{ $job?->expected_completion_date?->format('d/m/Y') ?? '-' }}</dd></div>
            <div><dt>Currency</dt><dd>IDR</dd></div>
        </dl>
    </article>
    <article class="panel"><div class="panel-heading"><h2>Financial Summary</h2></div>
        <div class="summary-box document-summary">
            <div class="summary-row"><span>Quotation Value</span><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></div>
            <div class="summary-row"><span>Discount</span><strong>Rp 0,00</strong></div>
            <div class="summary-row"><span>Tax</span><strong>Rp 0,00</strong></div>
            <div class="summary-row summary-total"><span>Grand Total</span><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></div>
        </div>
    </article>
</section>
@elseif($tab === 'quotation')
<section class="panel"><div class="panel-heading"><h2>Quotation {{ $quotation->number }}</h2><div class="action-group"><a class="text-link" href="{{ route('documents.quotation.preview',$quotation) }}">Preview PDF</a><a class="text-link" href="{{ route('documents.quotation.pdf',['quotation'=>$quotation,'mode'=>'download']) }}">Download PDF</a></div></div>
    <dl class="detail-grid"><div><dt>Date</dt><dd>{{ $quotation->quotation_date->format('d/m/Y') }}</dd></div><div><dt>Valid Until</dt><dd>{{ $quotation->valid_until->format('d/m/Y') }}</dd></div><div><dt>Customer</dt><dd>{{ $quotation->customer_snapshot['name'] }}</dd></div></dl>
    <div class="table-scroll"><table class="quote-detail-table"><thead><tr><th>No</th><th>Description</th><th>Qty</th><th>Unit</th><th class="money">Unit Price</th><th class="money">Amount</th></tr></thead><tbody>@foreach($quotation->items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ $item->description }}</td><td>{{ \App\Support\Money::format($item->quantity) }}</td><td>{{ $item->unit }}</td><td class="money">Rp {{ \App\Support\Money::format($item->unit_price) }}</td><td class="money">Rp {{ \App\Support\Money::format($item->total_price) }}</td></tr>@endforeach</tbody></table></div>
    <div class="summary-box"><div class="summary-row"><span>Subtotal</span><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></div><div class="summary-row"><span>Discount</span><strong>Rp 0,00</strong></div><div class="summary-row"><span>Tax</span><strong>Rp 0,00</strong></div><div class="summary-row summary-total"><span>Grand Total</span><strong>Rp {{ \App\Support\Money::format($quotation->subtotal) }}</strong></div></div>
    <div class="quote-actions">@can('submit',$quotation)<form method="POST" action="{{ route('quotations.submit',$quotation) }}">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><button class="button button-primary">Submit Approval</button></form>@endcan @can('update',$quotation)<a class="button button-secondary" href="{{ route('quotations.edit',$quotation) }}">Edit</a>@endcan @can('convert',$quotation)<form method="POST" action="{{ route('quotations.convert',$quotation) }}">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><button class="button button-primary">Create Job Order</button></form>@endcan</div>
</section>
@elseif($tab === 'job')
<section class="panel"><div class="panel-heading"><h2>Job Order</h2>@if($job)<div class="action-group"><a class="text-link" href="{{ route('documents.job.preview',$quotation) }}">Preview PDF</a><a class="text-link" href="{{ route('documents.job.pdf',['quotation'=>$quotation,'mode'=>'download']) }}">Download PDF</a></div>@endif</div>
@if($job)
    <dl class="detail-grid"><div><dt>Job Order Number</dt><dd>{{ $job->number }}</dd></div><div><dt>Reference Quotation</dt><dd>{{ $quotation->number }}</dd></div><div><dt>Customer</dt><dd>{{ $quotation->customer_snapshot['name'] }}</dd></div><div><dt>Project</dt><dd>{{ $job->subject }}</dd></div><div><dt>Scope of Work</dt><dd>{{ $job->cargo_description ?? '-' }}</dd></div><div><dt>Start Date</dt><dd>{{ $job->job_date->format('d/m/Y') }}</dd></div><div><dt>End Date</dt><dd>{{ $job->expected_completion_date?->format('d/m/Y') ?? '-' }}</dd></div><div><dt>PIC</dt><dd>{{ $quotation->creator?->name ?? '-' }}</dd></div><div><dt>Status</dt><dd>{{ config('operations.job_statuses.'.$job->status) }}</dd></div></dl>
    @if($job->operational_notes)<div class="detail-notes"><strong>Operational Notes</strong><br>{{ $job->operational_notes }}</div>@endif
    <div class="table-scroll"><table><thead><tr><th>Service / Item</th><th>Qty</th><th class="money">Amount</th></tr></thead><tbody>@foreach($job->quotation_snapshot['items'] as $item)<tr><td>{{ $item['description'] }}</td><td>{{ \App\Support\Money::format($item['quantity']) }} {{ $item['unit'] }}</td><td class="money">Rp {{ \App\Support\Money::format($item['total_price']) }}</td></tr>@endforeach</tbody></table></div>
@else
    <div class="empty-state"><h3>Job Order belum dibuat.</h3><p>Data customer, quotation reference, item, dan value akan otomatis disalin dari quotation.</p>@can('convert',$quotation)<form method="POST" action="{{ route('quotations.convert',$quotation) }}">@csrf<input type="hidden" name="lock_version" value="{{ $quotation->lock_version }}"><button class="button button-primary">Create Job Order dari Quotation</button></form>@endcan</div>
@endif
</section>
@else
<section class="panel"><div class="panel-heading"><h2>Riwayat & Approval</h2></div><ol class="approval-timeline">@foreach($events as $event)<li><span></span><div><strong>{{ $event['title'] }}</strong><p>{{ $event['actor'] ?? 'System' }} · {{ $event['time']->format('d M Y H:i') }}</p></div></li>@endforeach</ol></section>
@endif
@endsection
