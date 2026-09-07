<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk · JobFinance</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
<section class="login-story"><a class="brand" href="{{ route('login') }}"><span class="brand-symbol"><x-icon name="chart"/></span><span>JobFinance<small>JOB COSTING & ACCOUNTING</small></span></a><div class="login-story-content"><span class="banner-tag">WORKSPACE KEUANGAN ANDA</span><h1>Pekerjaan terkelola.<br>Keuangan tertata.</h1><p>Dari penawaran hingga pembayaran, kelola setiap pekerjaan dengan lebih jelas dan terarah.</p><div class="login-feature"><x-icon name="check"/>Biaya dan profit per pekerjaan</div><div class="login-feature"><x-icon name="check"/>Tagihan dan pembayaran terintegrasi</div><div class="login-feature"><x-icon name="check"/>Akses sesuai peran tim Anda</div></div><small>JobFinance · Setiap job, lebih terkontrol.</small></section>
<main class="login-main"><div class="login-form"><span class="login-badge"><x-icon name="lock"/></span><p class="eyebrow">SELAMAT DATANG KEMBALI</p><h2>Masuk ke workspace</h2><p class="login-description">Gunakan akun Anda untuk melanjutkan.</p>
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
