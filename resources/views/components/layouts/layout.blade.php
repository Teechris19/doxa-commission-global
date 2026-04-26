<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doxa commission Global</title>
    <link rel="stylesheet" href="/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/vendor/aos/aos.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <script src="/assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="/assets/vendor/aos/aos.js"></script>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @livewireStyles
    @vite(['resources/js/app.js'])
    <style>
        .main {
            width: 80%;
            margin: 0 auto;
        }

        @media (max-width:700px) {
            .main {
                width: 90%;
            }
        }

        .countdown-section {
            padding: 120px 30px;
        }
    </style>
</head>

<body style="background-color: rgba(238, 238, 238, 0.761);">
    <nav class="navbar navbar-expand-md custom-navbar fixed-top">
        <div class="container-fluid">
            <img src="/Img/doxa.PNG" alt="logo" class="logo">

            <!-- Large screen nav links -->
            <div class="collapse navbar-collapse d-none d-md-flex justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}" wire:navigate>Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('sermons.index') }}" wire:navigate>Message</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('about') }}" wire:navigate>About</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('testimonies.index') }}" wire:navigate>Testimonies</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('missions.index') }}" wire:navigate>Missions</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('giving.index') }}" wire:navigate>Giving</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('attendance.index') }}" wire:navigate>Attendance</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('cells.index') }}" wire:navigate>Cell</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('events.index') }}" wire:navigate>Event</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('location.index') }}" wire:navigate>Location</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('believers.academy') }}" wire:navigate>Believers academy</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('transport') }}" wire:navigate>Need a Ride</a></li>
                    <li class="nav-item">
                        @auth
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link bg-transparent border-0 text-white" style="cursor: pointer;">Logout</button>
                            </form>
                        @else
                            <a class="nav-link" href="{{ route('home.login') }}" wire:navigate>Login</a>
                        @endauth
                    </li>
                </ul>
            </div>

            <!-- Mobile toggle -->
            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
                <i class="bi bi-list text-white" style="font-size: 1.8rem;"></i>
            </button>
        </div>
    </nav>
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.custom-navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
    <x-flash></x-flash>
    {{ $slot }}

    @php
        $globalSettings = \App\Models\GlobalSetting::first();
        $footerChurchName = $globalSettings?->church_name ?? config('app.name', 'Doxa Commission Global');
        $footerDescription = $globalSettings?->footer_description ?? "Bringing nations into God's glory worldwide.";
        $footerSocial = json_decode($globalSettings?->social_links ?? '{}', true) ?? [];
    @endphp

    <footer class="main-footer">

        <div class="row mx-2">

            <div class="footer-content-con row mx-2">
                <div class="footer-content col-lg-4 col-md-6">
                    <!-- <img src="Img/doxa.PNG"> -->
                    <h2>{{ $footerChurchName }}</h2>
                    <h4>{{ $footerDescription }}</h3>
                </div>

                <div class="footer-content col-lg-4 col-md-6">
                    <h2>Quick Links</h2>
                    <ul>
                        <li><a href="{{ route('home') }}" wire:navigate>Home</a></li>
                        <li><a href="{{ route('sermons.index') }}" wire:navigate>Messages</a></li>
                        <li><a href="{{ route('about') }}" wire:navigate>About</a></li>
                        <li><a href="{{ route('testimonies.index') }}" wire:navigate>Testimonies</a></li>
                        <li><a href="{{ route('missions.index') }}" wire:navigate>Missions</a></li>
                        <li><a href="{{ route('home') }}" wire:navigate>Location</a></li>
                        <li><a href="{{ route('believers.academy') }}" wire:navigate>Believers Class</a></li>
                    </ul>
                </div>

                <div class="footer-content newsletter col-lg-4 col-md-6">
                    <h3 class=>Stay Connected</h3>
                    <p></p>Subscribe to our newsletter for updates and spiritual insights.</p>
                    <form id="newsletter-form">
                        <input type="email" id="newsletter-email" placeholder="Your email address" required>
                        <button type="submit">
                            Subscribe
                        </button>
                    </form>
                    <div class="footer-icons-wrapper">
                        @if (!empty($footerSocial['facebook']))
                            <a href="{{ $footerSocial['facebook'] }}" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
                        @endif
                        @if (!empty($footerSocial['instagram']))
                            <a href="{{ $footerSocial['instagram'] }}" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
                        @endif
                        @if (!empty($footerSocial['tiktok']))
                            <a href="{{ $footerSocial['tiktok'] }}" aria-label="TikTok" target="_blank" rel="noopener noreferrer"><i class="fab fa-tiktok"></i></a>
                        @endif
                        @if (!empty($footerSocial['youtube']))
                            <a href="{{ $footerSocial['youtube'] }}" aria-label="YouTube" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube"></i></a>
                        @endif
                        @if (!empty($footerSocial['spotify']))
                            <a href="{{ $footerSocial['spotify'] }}" aria-label="Spotify" target="_blank" rel="noopener noreferrer"><i class="fab fa-spotify"></i></a>
                        @endif
                    </div>
                    <p style="color: rgb(204, 198, 198); text-align: center; margin-top: 8px;">Follow us for update
                        and
                        inspiration!</p>
                </div>
            </div>

            <div class="break"></div>

            <div class="footer-2">
                <p>&copy; Copyright Doxa Commission Global <span id="current-year"></span> All Right Reserved. Desinged
                    and Developed by Doxa
                    database
                </p>
            </div>

        </div>
        <script>
            let year = new Date().getFullYear()
            document.getElementById('current-year').innerHTML = year
        </script>
        @livewireScripts
            @fluxScripts


    </footer>
