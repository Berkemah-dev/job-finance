@php
  $parties = $partyBreakdowns->get($account->name, collect());
  $toggleId = 'balance-account-'.$loop->parent?->index.'-'.$loop->index.'-'.\Illuminate\Support\Str::slug($account->name);
@endphp
@if($parties->isNotEmpty())
  <div class="balance-party-account">
    <button type="button" class="balance-row balance-row-toggle {{ $levelClass }}" data-balance-toggle data-balance-target="{{ $toggleId }}" aria-expanded="false">
      <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
      <strong>{{ $money($account->report_balance) }} <span data-balance-icon>🔽</span></strong>
    </button>
    <div id="{{ $toggleId }}" class="balance-party-list" hidden>
      @foreach($parties as $party)
        @php($partyId = $toggleId.'-party-'.$loop->index)
        <button type="button" class="balance-party-row" data-balance-toggle data-balance-target="{{ $partyId }}" aria-expanded="false">
          <span><small>Kode {{ $party->party_label }}: {{ $party->party_code }}</small><strong>{{ $party->party_name }}</strong></span>
          <strong>Belum dibayar {{ $money($party->outstanding) }} <span data-balance-icon>🔽</span></strong>
        </button>
        <div id="{{ $partyId }}" class="balance-party-transactions" hidden>
          <table>
            <thead><tr><th>No. Job</th><th>No. Invoice / CN / PR</th><th>Tanggal Invoice / CN / PR</th><th class="money">Jumlah Invoice / CN / PR</th></tr></thead>
            <tbody>
              @forelse($party->transactions as $transaction)
                <tr><td>{{ $transaction->job_number }}</td><td>{{ $transaction->document_number }}</td><td>{{ $transaction->document_date?->format('d/m/Y') ?? '—' }}</td><td class="money">{{ $money($transaction->document_amount) }}</td></tr>
              @empty
                <tr><td colspan="4" class="subtle">Belum ada mutasi transaksi pada periode ini.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      @endforeach
      <a class="balance-ledger-link" href="{{ $ledgerUrl($account) }}">Lihat mutasi lengkap di Buku Besar →</a>
    </div>
  </div>
@else
  <a class="balance-row balance-row-link {{ $levelClass }}" href="{{ $ledgerUrl($account) }}" title="Lihat rincian {{ $account->acc_no }} — {{ $account->name }}">
    <span><strong>{{ $account->acc_no }}</strong> &nbsp; {{ $account->name }}</span>
    <strong>{{ $money($account->report_balance) }} <x-icon name="arrow"/></strong>
  </a>
@endif
