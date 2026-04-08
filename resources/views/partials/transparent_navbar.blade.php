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
            </ul>
        </div>

        <!-- Mobile toggle -->
        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasNav" aria-controls="offcanvasNav">
            <i class="bi bi-list text-white" style="font-size: 1.8rem;"></i>
        </button>
    </div>
</nav>