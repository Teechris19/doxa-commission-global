<?php

namespace App\Livewire\Admin\Dashboard\Settings;

use App\Models\GlobalSetting;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('components.layouts.admin')] class extends Component {
    use Interactions;

    public string $whatsappNumber = '';

    public function mount()
    {
        $settings = GlobalSetting::first();
        if ($settings) {
            $socialLinks = json_decode($settings->social_links, true) ?? [];
            $this->whatsappNumber = $socialLinks['whatsapp'] ?? '';
        }
    }

    public function save()
    {
        $settings = GlobalSetting::firstOrCreate([]);
        $socialLinks = json_decode($settings->social_links, true) ?? [];
        $socialLinks['whatsapp'] = $this->whatsappNumber;
        
        $settings->social_links = json_encode($socialLinks);
        $settings->save();

        $this->toast()->success('Updated', 'WhatsApp number for message requests updated successfully.')->send();
    }
}; ?>

<div>
    <x-fancy-header title="Request Message Settings" subtitle="Set the WhatsApp number for message requests" :breadcrumbs="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Settings'],
        ['label' => 'Request Message']
    ]">
    </x-fancy-header>

    <div class="max-w-2xl">
        <x-card class="dark:bg-zinc-800">
            <div class="space-y-6">
                <div>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                        Enter the WhatsApp number (including country code, e.g., 2348012345678) that users will use to request messages via the floating button on the sermons page.
                    </p>
                    <x-input label="WhatsApp Number" 
                             wire:model="whatsappNumber" 
                             placeholder="e.g. 2348012345678" 
                             hint="Include country code without '+' or spaces" />
                </div>

                <div class="flex justify-end">
                    <x-button wire:click="save" 
                              wire:loading.attr="disabled"
                              class="bg-blue-600 hover:bg-blue-700 text-white flex items-center gap-2">
                        <span wire:loading wire:target="save" class="animate-spin text-lg">↻</span>
                        <span>Save Changes</span>
                    </x-button>
                </div>
            </div>
        </x-card>
    </div>
</div>
