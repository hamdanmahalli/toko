<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#0F5342">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Toko MM">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('apple-touch-icon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@if (app()->isProduction())
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
    });
}
</script>
@endif
