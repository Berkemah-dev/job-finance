@if ($paginator->total() > 0)
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $total = $paginator->total();
        $perPage = $paginator->perPage();

        $window = [];
        if ($lastPage <= 5) {
            $window = range(1, $lastPage);
        } else {
            if ($currentPage <= 2) {
                $window = [1, 2, 3, '...', $lastPage];
            } elseif ($currentPage >= $lastPage - 1) {
                $window = [1, '...', $lastPage - 2, $lastPage - 1, $lastPage];
            } else {
                $window = [1, '...', $currentPage, '...', $lastPage];
            }
        }
    @endphp

    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-nav">
        <div class="pagination-info">
            <p>
                Menampilkan
                <strong>{{ $paginator->firstItem() ?? 1 }}</strong>
                &ndash;
                <strong>{{ $paginator->lastItem() ?? $total }}</strong>
                dari
                <strong>{{ $total }}</strong>
                data
            </p>

            <div class="pagination-per-page">
                <select class="pagination-select" onchange="window.location.href=this.value" aria-label="Jumlah data per halaman">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" @selected($perPage == $size)>
                            {{ $size }} / hal
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pagination-links">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" title="Halaman Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" title="Halaman Sebelumnya" aria-label="Halaman Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Compact Page Numbers --}}
            <div class="pagination-pages">
                @foreach ($window as $item)
                    @if ($item === '...')
                        <span class="pagination-dots" aria-disabled="true">&hellip;</span>
                    @elseif ($item == $currentPage)
                        <span class="pagination-btn pagination-btn-active" aria-current="page">{{ $item }}</span>
                    @else
                        <a href="{{ $paginator->url($item) }}" class="pagination-btn">{{ $item }}</a>
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" title="Halaman Selanjutnya" aria-label="Halaman Selanjutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" title="Halaman Selanjutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
