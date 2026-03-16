<?php

use App\Models\Testimony;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $testimonies;
    public $selectedTestimony = null;

    public function mount()
    {
        $this->testimonies = Testimony::where('status', 'approved')
            ->latest()
            ->get();
    }

    public function openTestimonyModal($id)
    {
        $this->selectedTestimony = Testimony::findOrFail($id);
    }

    public function closeTestimonyModal()
    {
        $this->selectedTestimony = null;
    }
};
?>

<div class="bg-white pb-12">
    <section class="border-b border-blue-100 bg-gradient-to-b from-blue-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Testimonies</p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Stories of Faith & God's Grace</h1>
                <p class="mt-4 text-sm leading-7 text-slate-600">Read inspiring testimonies from our church family about how God is working in their lives.</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if($testimonies->isEmpty())
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
                    <i class="bi bi-book text-2xl text-blue-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No testimonies yet</h3>
                <p class="mt-2 text-gray-500">Be the first to share your testimony!</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($testimonies as $testimony)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                        @if($testimony->image)
                            <div class="h-48 overflow-hidden">
                                <img src="{{ asset('storage/' . $testimony->image) }}" alt="{{ $testimony->name }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold">{{ substr($testimony->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-3">
                                    <h3 class="font-semibold text-gray-900">{{ $testimony->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $testimony->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm line-clamp-4">{{ $testimony->testimony }}</p>
                            <button 
                                wire:click="openTestimonyModal({{ $testimony->id }})"
                                class="mt-4 text-blue-600 hover:text-blue-800 text-sm font-medium"
                            >
                                Read more →
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if($selectedTestimony)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click="closeTestimonyModal">
            <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6" wire:click.stop>
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-semibold text-lg">{{ substr($selectedTestimony->name, 0, 1) }}</span>
                        </div>
                        <div class="ml-3">
                            <h3 class="font-semibold text-gray-900 text-lg">{{ $selectedTestimony->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $selectedTestimony->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                    <button wire:click="closeTestimonyModal" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                
                @if($selectedTestimony->image)
                    <div class="mb-6 rounded-lg overflow-hidden">
                        <img src="{{ asset('storage/' . $selectedTestimony->image) }}" alt="{{ $selectedTestimony->name }}" class="w-full h-auto">
                    </div>
                @endif
                
                <div class="prose max-w-none">
                    <p class="text-gray-700 whitespace-pre-line">{{ $selectedTestimony->testimony }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
