<div class="grid gap-6 lg:grid-cols-[240px_1fr]">
    <x-card class="h-fit">
        <flux:navlist>
            <flux:navlist.item icon="user" :href="route('settings.profile')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item icon="key" :href="route('settings.password')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
        </flux:navlist>
    </x-card>

    <x-card>
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-2xl">
            {{ $slot }}
        </div>
    </x-card>
</div>
