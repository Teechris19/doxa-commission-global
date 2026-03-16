<?php

namespace App\Livewire\Admin\Dashboard\Settings;

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component {
    
};
?>

<div class="dark:bg-zinc-800">
    <x-fancy-header title="System Settings" subtitle="Manage global and landing page settings" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Settings']
    ]">
    </x-fancy-header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-card class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <h3 class="text-xl font-bold mb-2">Global Settings</h3>
            <p class="mb-4">Configure church-wide settings that apply to all chapters.</p>
            <x-link :href="route('admin.dashboard.settings.global')" text="Manage Global Settings" 
                class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" 
                wire:navigate />
        </x-card>

        <x-card class="dark:bg-zinc-900 dark:text-gray-200 text-zinc-900">
            <h3 class="text-xl font-bold mb-2">Landing Page Settings</h3>
            <p class="mb-4">Manage homepage hero media, text content, and service blocks.</p>
            <x-link :href="route('admin.dashboard.settings.landing')" text="Manage Landing Page" 
                class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" 
                wire:navigate />
        </x-card>
    </div>
</div>
