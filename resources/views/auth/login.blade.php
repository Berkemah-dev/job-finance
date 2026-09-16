<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}?v={{ filemtime(public_path('images/favicon.png')) }}"><link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}?v={{ filemtime(public_path('images/favicon.png')) }}"><title>Masuk · JobFinance</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
<section class="login-story" style="display: flex; flex-direction: column; justify-content: space-between; align-items: center; position: relative; overflow: hidden; background: linear-gradient(150deg, rgba(15,31,61,.82), rgba(8,16,32,.72)), url('{{ asset('images/login-containers.png') }}') center/cover no-repeat; padding: 48px 36px;">
    {{-- Ambient Decorative Background Rings --}}
    <div style="position: absolute; width: 450px; height: 450px; border: 1px solid rgba(255,255,255,0.06); border-radius: 50%; top: -100px; left: -100px; pointer-events: none;"></div>
    <div style="position: absolute; width: 600px; height: 600px; border: 1px solid rgba(255,255,255,0.04); border-radius: 50%; top: -175px; left: -175px; pointer-events: none;"></div>
    <div style="position: absolute; width: 500px; height: 500px; border: 1px solid rgba(255,255,255,0.05); border-radius: 50%; bottom: -150px; right: -150px; pointer-events: none;"></div>

    {{-- Top Logo --}}
    <div style="z-index: 2; align-self: flex-start; display:flex; align-items:center; gap:10px;">
        <img src="{{ asset('images/logo.png') }}" alt="RDX" style="height:42px; width:auto; max-width:150px; object-fit:contain; filter: brightness(0) invert(1);">
    </div>
</section>
<main class="login-main"><div class="login-form"><div style="margin-bottom:28px;"><img src="{{ asset('images/logo.png') }}" alt="RDX" style="height:58px;width:auto;max-width:180px;object-fit:contain;"></div><p class="eyebrow" style="color:#b91c1c !important;font-weight:700;">SELAMAT DATANG KEMBALI</p>
<h1 style="font-size:24px;font-weight:700;color:#0f1f3d;margin:4px 0 16px">Masuk ke workspace</h1>
<form method="POST" action="{{ route('login.store') }}">@csrf
<label for="email">Alamat email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@perusahaan.com" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
@error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
<label for="password">Kata sandi</label><div class="password-field"><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror><button type="button" class="icon-button" data-password-toggle aria-label="Tampilkan kata sandi" aria-pressed="false"><x-icon name="eye"/></button></div>
@error('password')<p class="field-error" id="password-error" role="alert">{{ $message }}</p>@enderror
<label class="checkbox-label"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
<button class="button button-primary login-submit" type="submit">Masuk <x-icon name="arrow"/></button>
</form><p class="login-help">Belum memiliki akun? Hubungi administrator Anda.</p><div class="login-security"><x-icon name="lock"/>Akses workspace sesuai hak pengguna</div>
</div></main>
</body></html>
