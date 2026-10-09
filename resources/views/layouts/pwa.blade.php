<link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v={{ filemtime(public_path('manifest.webmanifest')) }}">
<meta name="theme-color" content="#0F5342">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Toko MM">
@php($versiIcon = filemtime(public_path('apple-touch-icon.png')))
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('apple-touch-icon.png') }}?v={{ $versiIcon }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v={{ $versiIcon }}">
@if (app()->isProduction())
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
    });
}
</script>
@endif

{{-- Jembatan native untuk APK (Capacitor). Di browser biasa hanya menyiapkan
     window.TokoNative dengan aktif=false, jadi tidak mengubah perilaku web. --}}
<meta name="toko-native-auth" content="{{ auth()->check() ? '1' : '0' }}">
<script src="{{ asset('native-bridge.js') }}?v={{ filemtime(public_path('native-bridge.js')) }}" defer></script>
<script>
window.addEventListener('load', function () {
    if (!window.TokoNative || !window.TokoNative.aktif) return;
    var meta = document.querySelector('meta[name="toko-native-auth"]');
    if (!meta || meta.content !== '1') return;
    window.TokoNative.daftarPush().catch(function () {});
});
</script>
