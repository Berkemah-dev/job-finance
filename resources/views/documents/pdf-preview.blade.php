@extends('layouts.app')
@section('title',$title)
@section('content')
<div class="pdf-toolbar">
    <a class="button button-secondary" href="{{ $backUrl }}">← Back</a>
    <strong>{{ $title }}</strong>
    <div class="action-group">
        <button class="button button-secondary" type="button" data-pdf-zoom="-0.1">Zoom -</button>
        <span data-pdf-scale>100%</span>
        <button class="button button-secondary" type="button" data-pdf-zoom="0.1">Zoom +</button>
        <button class="button button-secondary" type="button" data-pdf-fit>Fit Width</button>
        <button class="button button-secondary" type="button" data-pdf-print>Print</button>
        <a class="button button-primary" href="{{ $downloadUrl }}">Download PDF</a>
    </div>
</div>
<section class="panel pdf-viewer-shell">
    <iframe id="pdfFrame" src="{{ $pdfUrl }}" title="{{ $title }}"></iframe>
</section>
<script>
(() => {
    let scale = 1;
    const frame = document.getElementById('pdfFrame');
    const label = document.querySelector('[data-pdf-scale]');
    const render = () => {
        frame.style.width = `${100 * scale}%`;
        frame.style.height = `${82 / scale}vh`;
        label.textContent = `${Math.round(scale * 100)}%`;
    };
    document.querySelectorAll('[data-pdf-zoom]').forEach((button) => button.addEventListener('click', () => {
        scale = Math.min(1.8, Math.max(0.7, scale + Number(button.dataset.pdfZoom)));
        render();
    }));
    document.querySelector('[data-pdf-fit]').addEventListener('click', () => {
        scale = 1;
        render();
    });
    document.querySelector('[data-pdf-print]').addEventListener('click', () => {
        frame.contentWindow.focus();
        frame.contentWindow.print();
    });
    render();
})();
</script>
@endsection
