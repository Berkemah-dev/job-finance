@extends('layouts.app')
@section('title','Notifikasi')
@section('content')
<div class="page-heading"><div><p class="eyebrow">WORKSPACE ALERTS</p><h1>Notifikasi</h1><p>Pengingat pekerjaan yang membutuhkan perhatian Anda.</p></div><span class="count-badge">{{ $notifications->count() }} notifikasi</span></div>
<section class="panel notification-list">@forelse($notifications as $notification)<a class="notification-row" href="{{ $notification['url'] }}"><span class="notification-icon {{ $notification['type'] }}"><x-icon name="bell"/></span><span><strong>{{ $notification['title'] }}</strong><small>{{ $notification['message'] }}</small></span><x-icon name="chevron-right"/></a>@empty<div class="empty-state"><x-icon name="check"/><h3>Tidak ada notifikasi baru</h3><p>Semua pekerjaan Anda dalam kondisi terkendali.</p></div>@endforelse</section>
@endsection
