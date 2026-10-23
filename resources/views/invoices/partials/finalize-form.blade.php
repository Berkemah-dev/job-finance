<form id="{{ $finalizeFormId }}" method="POST" action="{{ route('invoices.finalize', $invoice) }}" data-confirm="Finalisasi invoice ini sekarang? Jurnal piutang, pendapatan, HPP, dan PPN akan dibuat.">
    @csrf
    <input type="hidden" name="job_version" value="{{ $job->lock_version }}">
    <input type="hidden" name="lock_version" value="{{ $invoice->lock_version }}">
    <button type="submit" class="button button-primary"><x-icon name="check"/> Finalisasi Invoice</button>
</form>
