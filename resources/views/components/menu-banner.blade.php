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
])

<section class="welcome-banner" {{ $attributes->merge(['class' => '']) }}>
    <div>
        <span class="banner-tag"><span class="status-dot"></span> {{ $tag }}</span>
        <h2>{{ $title }}</h2>
        @if($description)
            <p>{!! nl2br(e($description)) !!}</p>
        @endif
        @if($actionUrl && $actionLabel)
            <a href="{{ $actionUrl }}" class="button button-white">
                {{ $actionLabel }} @if($actionIcon)<x-icon :name="$actionIcon"/>@endif
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
