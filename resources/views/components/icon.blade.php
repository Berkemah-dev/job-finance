@props(['name' => 'grid'])
@php
$paths = [
'grid' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
'briefcase' => 'M8 6V4h8v2 M3 7h18v14H3z M3 12c6 3 12 3 18 0 M10 13h4',
'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-4',
'file' => 'M14 2H5v20h14V7z M14 2v6h5 M8 12h8 M8 16h6',
'wallet' => 'M3 5h16v4 M3 5v15h18V9H3 M16 13h5 M17 15h.01',
'chart' => 'M3 3v18h18 M7 15l5-5 4 3 5-8',
'check' => 'M20 6 9 17l-5-5',
'clock' => 'M12 8v4l3 2 M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
'arrow' => 'M5 12h14 M13 6l6 6-6 6',
'logout' => 'M9 5H3v14h6 M9 12h12 M17 8l4 4-4 4',
'menu' => 'M4 6h16 M4 12h16 M4 18h16',
'lock' => 'M5 10h14v11H5z M8 10V6a4 4 0 0 1 8 0v4',
'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12 M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0',
'calendar' => 'M3 5h18v16H3z M7 3v4 M17 3v4 M3 10h18',
];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['grid'] }}"/></svg>
