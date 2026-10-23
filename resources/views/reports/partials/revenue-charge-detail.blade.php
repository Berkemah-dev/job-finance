@if(count($charges) > 0)
    <tr id="{{ $detailPrefix }}-detail" style="display:none;background:#f8fafc;">
        <td colspan="4" style="padding:14px 18px;">
            <strong style="font-size:12px;color:#334155;">{{ $detailTitle }}</strong>
            <p class="subtle" style="font-size:11px;margin:6px 0;">Nilai jual berdasarkan jurnal periode laporan, termasuk revisi dan pembalik. Bukan nilai modal / HPP.</p>
            <div style="margin-top:8px;border:1px solid #dbe3ee;border-radius:8px;background:#fff;overflow:hidden;">
                @foreach($charges as $index => $charge)
                    @php
                        $chargeDetailId = $detailPrefix.'-charge-'.$index;
                    @endphp
                    <div style="border-bottom:1px solid #eef2f7;">
                        <button type="button" onclick="toggleIncomeDetail('{{ $chargeDetailId }}')" style="width:100%;display:flex;justify-content:space-between;align-items:center;gap:12px;border:0;background:#fff;padding:10px 12px;cursor:pointer;text-align:left;">
                            <span style="font-weight:700;color:#0f172a;">{{ strtoupper($charge->description) }} <small style="font-weight:500;color:#64748b;">({{ $charge->transaction_count }} mutasi)</small></span>
                            <span style="font-family:monospace;font-weight:700;color:#0f172a;">{{ $money($charge->total_price) }} <span id="{{ $chargeDetailId }}-icon" style="color:#94a3b8;">▶</span></span>
                        </button>
                        <div id="{{ $chargeDetailId }}" style="display:none;padding:0 12px 12px;background:#f8fafc;">
                            <div class="table-scroll">
                                <table style="width:100%;font-size:11px;border-collapse:collapse;">
                                    <thead><tr><th>Tanggal</th><th>Job Order</th><th>Invoice</th><th>Jurnal</th><th class="money">Nilai Jual / Pendapatan</th></tr></thead>
                                    <tbody>
                                        @foreach($charge->items as $item)
                                            <tr style="border-top:1px solid #e2e8f0;">
                                                <td>{{ $item['date'] }}</td>
                                                <td>
                                                    @if($item['job_id'])
                                                        <a href="{{ route('jobs.show', $item['job_id']) }}">{{ $item['job_number'] }}</a>
                                                    @else
                                                        {{ $item['job_number'] }}
                                                    @endif
                                                </td>
                                                <td>{{ $item['invoice_number'] }}</td>
                                                <td>{{ $item['journal_number'] }}</td>
                                                <td class="money">{{ $money($item['total_price']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </td>
    </tr>
@endif
