<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}"><link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}"><title>Masuk · JobFinance</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
<section class="login-story" style="display: flex; flex-direction: column; justify-content: space-between; align-items: center; position: relative; overflow: hidden; background: radial-gradient(circle at 20% 25%, rgba(37, 99, 235, 0.22), transparent 45%), radial-gradient(circle at 80% 75%, rgba(185, 28, 28, 0.18), transparent 45%), linear-gradient(150deg, #0f1f3d 0%, #081020 100%); padding: 48px 36px;">
    {{-- Ambient Decorative Background Rings --}}
    <div style="position: absolute; width: 450px; height: 450px; border: 1px solid rgba(255,255,255,0.06); border-radius: 50%; top: -100px; left: -100px; pointer-events: none;"></div>
    <div style="position: absolute; width: 600px; height: 600px; border: 1px solid rgba(255,255,255,0.04); border-radius: 50%; top: -175px; left: -175px; pointer-events: none;"></div>
    <div style="position: absolute; width: 500px; height: 500px; border: 1px solid rgba(255,255,255,0.05); border-radius: 50%; bottom: -150px; right: -150px; pointer-events: none;"></div>

    {{-- Top Status Pill --}}
    <div style="z-index: 2; align-self: flex-start;">
        <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 600; letter-spacing: 0.8px; color: #cbd5e1; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); padding: 6px 14px; border-radius: 30px; backdrop-filter: blur(8px);">
            <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
            WORKSPACE ENTERPRISE
        </span>
    </div>

    {{-- Center Glass Card with Logo & Feature Highlights --}}
    <div style="z-index: 2; width: 100%; max-width: 440px; margin: auto; display: flex; flex-direction: column; align-items: center; text-align: center;">
        <div style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 20px; padding: 40px 32px; box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4); width: 100%; display: flex; flex-direction: column; align-items: center;">
            {{-- Logo with subtle glow --}}
            <div style="position: relative; padding: 12px; margin-bottom: 24px;">
                <div style="position: absolute; inset: 0; background: radial-gradient(circle, rgba(59,130,246,0.3) 0%, transparent 70%); filter: blur(12px); border-radius: 50%;"></div>
                <img src="{{ asset('images/logo.png') }}" alt="JobFinance Logo" class="login-hero-logo" style="position: relative; width: auto; max-width: 240px; max-height: 75px; filter: brightness(0) invert(1); object-fit: contain; display: block;">
            </div>

            {{-- Divider --}}
            <div style="width: 60px; height: 2px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); margin-bottom: 24px;"></div>

            {{-- Feature Badges --}}
            <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); padding: 10px 16px; border-radius: 10px; font-size: 11.5px; color: #f1f5f9; text-align: left; transition: all 0.2s ease;">
                    <div style="width: 26px; height: 26px; border-radius: 6px; background: rgba(59,130,246,0.2); display: grid; place-items: center; color: #93c5fd; flex-shrink: 0;">
                        <x-icon name="briefcase" style="width: 14px; height: 14px;"/>
                    </div>
                    <span>Manajemen Job Order & Operasional</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); padding: 10px 16px; border-radius: 10px; font-size: 11.5px; color: #f1f5f9; text-align: left; transition: all 0.2s ease;">
                    <div style="width: 26px; height: 26px; border-radius: 6px; background: rgba(16,185,129,0.2); display: grid; place-items: center; color: #6ee7b7; flex-shrink: 0;">
                        <x-icon name="chart" style="width: 14px; height: 14px;"/>
                    </div>
                    <span>Kontrol Biaya Aktual & Laba Rugi</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); padding: 10px 16px; border-radius: 10px; font-size: 11.5px; color: #f1f5f9; text-align: left; transition: all 0.2s ease;">
                    <div style="width: 26px; height: 26px; border-radius: 6px; background: rgba(245,158,11,0.2); display: grid; place-items: center; color: #fcd34d; flex-shrink: 0;">
                        <x-icon name="wallet" style="width: 14px; height: 14px;"/>
                    </div>
                    <span>Penagihan Invoice & Rekonsiliasi Bank</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Brand Footer --}}
    <div style="z-index: 2; display: flex; justify-content: space-between; align-items: center; width: 100%; font-size: 10px; color: #94a3b8;">
        <span>© {{ date('Y') }} JobFinance</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;">
            <x-icon name="lock" style="width: 12px; height: 12px;"/> Terenkripsi & Aman
        </span>
    </div>
</section>
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
