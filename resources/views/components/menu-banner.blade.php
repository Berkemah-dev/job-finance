@props([
    'tag' => 'WORKSPACE JOBFINANCE',
    'title' => '',
    'description' => '',
    'icon' => 'briefcase',
    'artTitle' => 'Setiap pekerjaan,',
    'artSubtitle' => 'lebih terukur.',
    'actionUrl' => null,
    'actionLabel' => null,
    'actionIcon' => 'arrow',
    'showDate' => false,
])

@php
    $cleanLabel = $actionLabel ? ltrim($actionLabel, '+ ') : '';
    $isBack = str_contains($actionLabel ?? '', 'Kembali') || str_starts_with(trim($actionLabel ?? ''), '←');
    if ($isBack && $actionIcon === 'arrow') {
        $actionIcon = null;
    }
    $isTrailingIcon = in_array($actionIcon, ['arrow', 'chevron-right', 'external-link'], true);
@endphp

<section class="welcome-banner" {{ $attributes->merge(['class' => '']) }}>
    <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap">
            <span class="banner-tag"><span class="status-dot"></span> {{ $tag }}</span>
            @if($showDate)
                <span class="banner-date-badge" style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.2);color:#fff;font-size:10px;font-weight:500;">
                    <x-icon name="calendar"/>{{ now()->locale('id')->translatedFormat('d F Y') }}
                </span>
            @endif
        </div>
        <h2>{{ $title }}</h2>
        @if($description)
            <p>{!! nl2br(e($description)) !!}</p>
        @endif
        @if($actionUrl && $cleanLabel)
            <a href="{{ $actionUrl }}" class="button button-white" style="display: inline-flex; align-items: center; gap: 8px;">
                @if($actionIcon && !$isTrailingIcon)<x-icon :name="$actionIcon"/>@endif
                <span>{{ $cleanLabel }}</span>
                @if($actionIcon && $isTrailingIcon)<x-icon :name="$actionIcon"/>@endif
            </a>
        @elseif($slot->isNotEmpty())
            <div class="banner-actions" style="margin-top: 18px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                {{ $slot }}
            </div>
        @endif
    </div>
    <div class="banner-art" aria-hidden="true">
        <div class="art-orbit"></div>
        <div class="art-card">
            <span class="art-icon"><x-icon :name="$icon"/></span>
            <span>{{ $artTitle }}<br><strong>{{ $artSubtitle }}</strong></span>
            <div class="art-bars"><i></i><i></i><i></i><i></i><i></i></div>
            <span class="art-check"><x-icon name="check"/></span>
        </div>
    </div>
</section>
