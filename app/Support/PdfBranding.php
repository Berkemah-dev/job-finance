<?php

namespace App\Support;

class PdfBranding
{
    public static function logoSource(): string
    {
        foreach (['rdx-logo-clean.png' => 'image/png', 'rdx-header-logo.jpg' => 'image/jpeg', 'logo.png' => 'image/png'] as $file => $mime) {
            $path = public_path('images/'.$file);
            if (is_file($path)) {
                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
            }
        }
        return '';
    }
}
