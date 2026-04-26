@php
    $globalSettings = \App\Models\GlobalSetting::first();
    $footerChurchName = $globalSettings?->church_name ?? config('app.name', 'Doxa Commission Global');
    $footerDescription = $globalSettings?->footer_description ?? "Bringing nations into God's glory worldwide.";
    $footerSocial = json_decode($globalSettings?->social_links ?? '{}', true) ?? [];
@endphp

<footer class="bg-dark text-white mt-5 py-5 flex-shrink-0">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <h5 class="fw-bold mb-3">{{ $footerChurchName }}</h5>
                <p class="text-muted">{{ $footerDescription }}</p>
            </div>
            <div class="col-md-6">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="{{ route('home') }}" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="{{ route('about') }}" class="text-muted text-decoration-none">About Us</a></li>
                    <li><a href="{{ route('sermons.index') }}" class="text-muted text-decoration-none">Messages</a></li>
                    <li><a href="{{ route('events.index') }}" class="text-muted text-decoration-none">Events</a></li>
                    <li><a href="{{ route('believers.academy') }}" class="text-muted text-decoration-none">Believers Academy</a></li>
                </ul>
            </div>
        </div>
        <hr class="bg-secondary my-4">
        <div class="row">
            <div class="col-md-6">
                <p class="text-muted mb-0">&copy; {{ date('Y') }} Doxa Commission Global. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-flex align-items-center justify-content-md-end gap-2">
                    @if (!empty($footerSocial['facebook']))
                        <a href="{{ $footerSocial['facebook'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#1877F2';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['twitter']))
                        <a href="{{ $footerSocial['twitter'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#1DA1F2';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-twitter"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['instagram']))
                        <a href="{{ $footerSocial['instagram'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#E4405F';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-instagram"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['youtube']))
                        <a href="{{ $footerSocial['youtube'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#FF0000';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-youtube"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['tiktok']))
                        <a href="{{ $footerSocial['tiktok'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#000000';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['telegram']))
                        <a href="{{ $footerSocial['telegram'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#0088CC';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-telegram-plane"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['spotify']))
                        <a href="{{ $footerSocial['spotify'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white me-2" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#1DB954';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-spotify"></i>
                        </a>
                    @endif
                    @if (!empty($footerSocial['whatsapp']))
                        <a href="{{ $footerSocial['whatsapp'] }}" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary text-white" style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.1) !important; transition: all 0.3s;" target="_blank" rel="noopener noreferrer" onmouseover="this.style.backgroundColor='white'; this.style.color='#25D366';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.1)'; this.style.color='white';">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>
