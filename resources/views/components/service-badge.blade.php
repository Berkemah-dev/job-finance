@props(['service'])

@php
    $config = \App\Models\ServiceType::badgeConfig($service);
@endphp

<span {{ $attributes->merge(['class' => 'status-badge service-badge service-badge-' . $config['code']]) }} style="display: inline-flex; align-items: center; gap: 5px; background: {{ $config['bg'] }}; color: {{ $config['color'] }}; border: 1px solid {{ $config['border'] }}; font-weight: 600; padding: 2px 8px; border-radius: 6px; font-size: 11px; letter-spacing: 0.02em; white-space: nowrap;">
    @if(!empty($config['icon']))
        <x-icon :name="$config['icon']" style="width: 12px; height: 12px; flex-shrink: 0; stroke-width: 2.2;" />
    @endif
    <span>{{ $config['label'] }}</span>
</span>
