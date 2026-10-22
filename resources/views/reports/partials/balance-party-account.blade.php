<a class="balance-row balance-row-link {{ $levelClass }}" href="{{ $ledgerUrl($account) }}" title="Lihat rincian {{ $account->acc_no }} — {{ $account->name }}">
    <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
    <strong>{{ $money($account->report_balance) }} <x-icon name="arrow"/></strong>
</a>
