@php
    $code = $code ?? '500';
    $title = $title ?? 'Terjadi kendala';
    $message = $message ?? 'Halaman belum bisa ditampilkan. Silakan kembali ke dashboard atau coba lagi nanti.';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f1f3d">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ file_exists(public_path('images/favicon.png')) ? filemtime(public_path('images/favicon.png')) : time() }}">
    <title>{{ $code }} · JobFinance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body{min-height:100vh;margin:0;display:grid;place-items:center;background:#f4f7fb;color:#0f1f3d;font-family:Poppins,system-ui,sans-serif;padding:24px}.error-shell{width:min(680px,100%);background:#fff;border:1px solid #e1e7f1;border-radius:24px;box-shadow:0 24px 70px rgba(15,31,61,.12);overflow:hidden}.error-hero{position:relative;isolation:isolate;padding:42px;background:linear-gradient(112deg,#101f3d 0%,#30203e 48%,#c51f25 100%);color:#fff}.error-hero:after{content:"";position:absolute;z-index:-1;width:320px;height:320px;right:-90px;top:-120px;border:1px solid rgba(255,255,255,.14);border-radius:50%;box-shadow:0 0 0 34px rgba(255,255,255,.035),0 0 0 70px rgba(255,255,255,.025)}.error-code{display:inline-flex;align-items:center;gap:10px;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.1);border-radius:999px;padding:8px 13px;font-size:12px;font-weight:700;letter-spacing:1px}.error-hero h1{font-size:30px;line-height:1.3;margin:22px 0 10px}.error-hero p{font-size:13px;line-height:1.9;color:#fee2e2;max-width:520px}.error-body{padding:26px 42px 34px;display:flex;justify-content:space-between;gap:16px;align-items:center}.error-body small{display:block;color:#7f8da3;font-size:11px;line-height:1.7}.button{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border-radius:11px;border:1px solid #0f1f3d;background:#0f1f3d;color:#fff;font-size:12px;font-weight:700;padding:12px 18px}.button-secondary{background:#fff;color:#0f1f3d;border-color:#dbe3ef}@media(max-width:640px){.error-body{display:grid}.error-hero,.error-body{padding:28px}.button{width:100%}}
    </style>
</head>
<body>
    <main class="error-shell">
        <section class="error-hero">
            <span class="error-code">ERROR {{ $code }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
        </section>
        <section class="error-body">
            <small>Jika halaman ini muncul setelah submit data, data belum tentu tersimpan. Silakan cek ulang dari daftar.</small>
            <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
                <a class="button button-secondary" href="javascript:history.back()">Kembali</a>
                <a class="button" href="{{ route('dashboard.finance') }}">Dashboard</a>
            </div>
        </section>
    </main>
</body>
</html>
