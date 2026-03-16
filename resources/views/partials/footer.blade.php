@php
    $globalSettings = \App\Models\GlobalSetting::first();
    $footerChurchName = $globalSettings?->church_name ?? config('app.name', 'Doxa Commission Global');
    $footerDescription = $globalSettings?->footer_description ?? "Bringing nations into God's glory worldwide.";
    $footerAddress = $globalSettings?->footer_address ?? '129 Goldie, Adjacent Amika Utuk, Calabar, Cross River State, Nigeria.';
    $footerPhone = $globalSettings?->footer_phone ?? '+234 1234567890';
    $footerEmail = $globalSettings?->footer_email ?? 'info@doxachurch.org';
    $footerSocial = json_decode($globalSettings?->social_links ?? '{}', true) ?? [];
@endphp

<footer class="bg-dark text-white mt-5 py-5 flex-shrink-0">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="fw-bold mb-3">{{ $footerChurchName }}</h5>
                <p class="text-muted">{{ $footerDescription }}</p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="{{ route('home') }}" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="{{ route('sermons.index') }}" class="text-muted text-decoration-none">Messages</a></li>
                    <li><a href="{{ route('events.index') }}" class="text-muted text-decoration-none">Events</a></li>
                    <li><a href="{{ route('believers.academy') }}" class="text-muted text-decoration-none">Believers Academy</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="fw-bold mb-3">Contact Us</h5>
                <p class="text-muted mb-2"><i class="fas fa-map-marker-alt me-2"></i>{{ $footerAddress }}</p>
                <p class="text-muted mb-2"><i class="fas fa-phone me-2"></i><a class="text-muted text-decoration-none" href="tel:{{ preg_replace('/\\s+/', '', $footerPhone) }}">{{ $footerPhone }}</a></p>
                <p class="text-muted"><i class="fas fa-envelope me-2"></i><a class="text-muted text-decoration-none" href="mailto:{{ $footerEmail }}">{{ $footerEmail }}</a></p>
            </div>
        </div>
        <hr class="bg-secondary my-4">
        <div class="row">
            <div class="col-md-6">
                <p class="text-muted mb-0">&copy; {{ date('Y') }} Doxa Commission Global. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                @if (!empty($footerSocial['facebook']))
                    <a href="{{ $footerSocial['facebook'] }}" class="text-muted text-decoration-none me-3" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook"></i></a>
                @endif
                @if (!empty($footerSocial['twitter']))
                    <a href="{{ $footerSocial['twitter'] }}" class="text-muted text-decoration-none me-3" target="_blank" rel="noopener noreferrer"><i class="fab fa-twitter"></i></a>
                @endif
                @if (!empty($footerSocial['instagram']))
                    <a href="{{ $footerSocial['instagram'] }}" class="text-muted text-decoration-none" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                @endif
            </div>
        </div>
    </div>
</footer>
