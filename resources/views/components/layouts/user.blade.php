<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name', 'Doxa Commission Global') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e3a8a',
                        'primary-light': '#3b82f6',
                        'primary-dark': '#1e40af',
                        accent: '#f59e0b',
                        'accent-light': '#fbbf24',
                        text: '#1f2937',
                        'text-light': '#6b7280',
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem',
                    },
                    backdropBlur: {
                        'glass': '25px',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.8s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'scale-in': 'scaleIn 0.5s ease-out',
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.37)',
                        'soft': '0 4px 20px rgba(0, 0, 0, 0.08)',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes scaleIn {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            .animation-delay-200 {
                animation-delay: 0.2s;
            }
            .animation-delay-400 {
                animation-delay: 0.4s;
            }
            .animation-delay-600 {
                animation-delay: 0.6s;
            }
        }

        /* Glassmorphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Improved mobile spacing */
        @media (max-width: 768px) {
            .mobile-section {
                padding-top: 2.5rem;
                padding-bottom: 2.5rem;
            }
            .mobile-card {
                margin-bottom: 1.5rem;
                padding: 1.5rem;
            }
            .mobile-heading {
                margin-bottom: 2rem;
            }
            .mobile-grid-gap {
                gap: 1.5rem;
            }
        }

        /* Carousel styles */
        .carousel {
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
        }
        .carousel-item {
            scroll-snap-align: start;
            flex-shrink: 0;
        }

        /* Form styling */
        .form-input {
            @apply w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300;
        }
        /* Social media hover effects */
        .social-facebook:hover {
            background-color: white !important;
        }

        .social-facebook:hover i {
            color: #3b5998 !important;
        }

        .social-instagram:hover {
            background-color: white !important;
        }

        .social-instagram:hover i {
            color: #E1306C !important;
        }

        .social-youtube:hover {
            background-color: white !important;
        }

        .social-youtube:hover i {
            color: #FF0000 !important;
        }

        .social-tiktok:hover {
            background-color: white !important;
        }

        .social-tiktok:hover i {
            color: #000000 !important;
        }

        .social-spotify:hover {
            background-color: white !important;
        }

        .social-spotify:hover i {
            color: #1DB954 !important;
        }
    </style>
    @fluxAppearance
</head>

<body class="font-poppins bg-gray-50 text-gray-800">
    <!-- Top Navigation (Desktop) -->
    <nav class="hidden lg:flex items-center justify-between px-8 py-5 bg-white shadow-md sticky top-0 z-50">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center">
                <img src="/img/doxa.PNG" alt="logo" class="w-full h-full object-cover">
            </div>
            <span class="text-xl font-bold text-primary">{{ config('app.name', 'Doxa Commission Global') }}</span>
        </div>

        <div class="flex space-x-10">
            <a href="{{ route('home') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">Home</a>
            <a href="{{ route('sermons.index') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">Message</a>
            <a href="{{ route('home') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">About</a>
            <a href="{{ route('home') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">Cell</a>
            <a href="{{ route('events.index') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">Event</a>
            <a href="{{ route('home') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">Location</a>
            <a href="{{ route('believers.academy') }}" class="font-medium text-primary border-b-2 border-primary pb-1">Believers Academy</a>
            <a href="{{ route('transport') }}" class="font-medium text-gray-600 hover:text-primary transition-colors duration-300">Need a Ride</a>
        </div>

        <div>
            @auth
                <a href="{{ route('home.dashboard') }}" class="bg-primary text-white px-7 py-3 rounded-full font-medium hover:bg-primary-dark transition-colors duration-300">Dashboard</a>
            @else
                <a href="{{ route('home.login') }}" class="bg-primary text-white px-7 py-3 rounded-full font-medium hover:bg-primary-dark transition-colors duration-300">Connect With Us</a>
            @endauth
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white shadow-2xl rounded-t-3xl p-4 flex justify-between items-center lg:hidden z-50">
        <a href="{{ route('home') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-home text-xl mb-1"></i>
            <span class="text-xs">Home</span>
        </a>
        <a href="{{ route('sermons.index') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-play-circle text-xl mb-1"></i>
            <span class="text-xs">Message</span>
        </a>
        <a href="{{ route('home') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-info-circle text-xl mb-1"></i>
            <span class="text-xs">About</span>
        </a>
        <a href="{{ route('home') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-users text-xl mb-1"></i>
            <span class="text-xs">Cell</span>
        </a>
        <a href="{{ route('events.index') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-calendar-alt text-xl mb-1"></i>
            <span class="text-xs">Event</span>
        </a>
        <a href="{{ route('home') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-map-marker-alt text-xl mb-1"></i>
            <span class="text-xs">Location</span>
        </a>
        <a href="{{ route('believers.academy') }}" class="flex flex-col items-center text-primary py-2">
            <i class="fas fa-graduation-cap text-xl mb-1"></i>
            <span class="text-xs">Academy</span>
        </a>
        <a href="{{ route('transport') }}" class="flex flex-col items-center text-gray-500 py-2">
            <i class="fas fa-car text-xl mb-1"></i>
            <span class="text-xs">Ride</span>
        </a>
    </nav>

    <!-- Main Content -->
    <main class="pb-20 lg:pb-0">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-blue-900 text-white py-12 px-4">
        <div class="container mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Column 1 -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Doxa Commission Global</h2>
                    <h4 class="text-gray-300 mb-4">Bringing nations into God's glory worldwide</h4>
                    <div class="space-y-3">
                        <p class="flex items-start">
                            <i class="fas fa-location-dot mt-1 mr-2 text-blue-400"></i>
                            <a href="https://maps.app.goo.gl/4ZqPqRu1CMYQnppW7" class="hover:text-blue-300 transition">129 Goldie, Adjacent Amika Utuk, Calabar, Cross River State, Nigeria.</a>
                        </p>
                        <p class="flex items-center cursor-pointer" id="call-us">
                            <i class="fas fa-phone mr-2 text-blue-400"></i>
                            <a href="tel:+2341234567890" class="hover:text-blue-300 transition">+234 1234567890</a>
                        </p>
                        <p class="flex items-center">
                            <i class="far fa-envelope mr-2 text-blue-400"></i>
                            <a href="mailto:info@doxachurch.org" class="hover:text-blue-300 transition">info@doxachurch.org</a>
                        </p>
                    </div>
                </div>

                <!-- Column 2 -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Service Times</h2>
                    <p class="mb-4">Sunday Glory Life Service:<br>7am, 8:30am, 10am and 4pm</p>
                    <h4 class="font-bold mb-2">Thursday Glory Experience</h4>
                    <p>5:30pm</p>
                </div>

                <!-- Column 3 -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Quick Links</h2>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="hover:text-blue-300 transition">Home</a></li>
                        <li><a href="{{ route('sermons.index') }}" class="hover:text-blue-300 transition">Messages</a></li>
                        <li><a href="{{ route('home') }}" class="hover:text-blue-300 transition">About</a></li>
                        <li><a href="{{ route('home') }}" class="hover:text-blue-300 transition">Cell</a></li>
                        <li><a href="{{ route('home') }}" class="hover:text-blue-300 transition">Location</a></li>
                        <li><a href="{{ route('believers.academy') }}" class="hover:text-blue-300 transition">Believers Academy</a></li>
                    </ul>
                </div>

                <!-- Column 4 -->
                <div>
                    <h3 class="text-xl font-bold mb-4">Stay Connected</h3>
                    <p class="mb-4">Subscribe to our newsletter for updates and spiritual insights.</p>
                    <form class="mb-6">
                        <div class="flex">
                            <input type="email" placeholder="Your email address" class="flex-grow px-4 py-2 rounded-l-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-r-lg transition">Subscribe</button>
                        </div>
                    </form>
                    <div class="flex space-x-4 mb-2">
                        <a href="https://www.facebook.com/DoxaCommissionGlobal/" aria-label="Facebook" class="social-facebook bg-blue-800 w-10 h-10 rounded-full flex items-center justify-center transition">
                            <i class="fab fa-facebook-f text-white"></i>
                        </a>
                        <a href="https://www.instagram.com/doxa_commission/?hl=en" aria-label="Instagram" class="social-instagram bg-blue-800 w-10 h-10 rounded-full flex items-center justify-center transition">
                            <i class="fab fa-instagram text-white"></i>
                        </a>
                        <a href="https://www.youtube.com/channel/UCZtReUAxK3S6qBKnV5G6PTw" aria-label="YouTube" class="social-youtube bg-blue-800 w-10 h-10 rounded-full flex items-center justify-center transition">
                            <i class="fab fa-youtube text-white"></i>
                        </a>
                        <a href="https://www.tiktok.com/discover/doxa-commission-global" aria-label="TikTok" class="social-tiktok bg-blue-800 w-10 h-10 rounded-full flex items-center justify-center transition">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="https://open.spotify.com/show/05Jdb4jyuqHoUdQ89VLvnF?si=1310344dc60e4d01" aria-label="Spotify" class="social-spotify bg-blue-800 w-10 h-10 rounded-full flex items-center justify-center transition">
                            <i class="fab fa-spotify text-white"></i>
                        </a>
                    </div>
                    <p class="text-blue-300 text-sm">Follow us for updates and inspiration!</p>
                </div>
            </div>

            <div class="border-t border-blue-700 mt-8 pt-6 text-center">
                <p>&copy; Copyright Doxa Commission Global <span id="current-year"></span> All Rights Reserved. Designed and Developed by Doxa Database</p>
            </div>
        </div>
    </footer>

    <script>
        // Phone call functionality
        document.getElementById('call-us')?.addEventListener('click', function (e) {
            e.preventDefault();
            window.open('tel:+2341234567890');
        });

        // Set current year in footer
        const yearSpan = document.getElementById('current-year');
        if (yearSpan) {
            yearSpan.textContent = new Date().getFullYear();
        }
    </script>
    @fluxScripts
</body>

</html>