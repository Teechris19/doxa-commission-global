<?php

use App\Models\MissionReport;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $missions;
    public $selectedMission = null;

    public function mount()
    {
        $this->missions = MissionReport::where('status', 'submitted')
            ->latest('report_date')
            ->get();
    }

    public function openMissionModal($id)
    {
        $this->selectedMission = MissionReport::findOrFail($id);
    }

    public function closeMissionModal()
    {
        $this->selectedMission = null;
    }
};
?>

<div class="bg-white pb-12">
    <section class="border-b border-emerald-100 bg-gradient-to-b from-emerald-50 to-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-600">Missions & Outreach</p>
                <h1 class="mt-3 text-3xl font-semibold text-slate-900 sm:text-4xl">Bringing the Gospel to the Nations</h1>
                <p class="mt-4 text-sm leading-7 text-slate-600">See how God is using Doxa Commission Global to reach souls across different locations through our mission outreach programs.</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if($missions->isEmpty())
            <div class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-4">
                    <i class="bi bi-globe text-2xl text-emerald-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No missions recorded yet</h3>
                <p class="mt-2 text-gray-500">Check back soon for updates on our outreach activities.</p>
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($missions as $mission)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300 cursor-pointer"
                         wire:click="openMissionModal({{ $mission->id }})">
                        @if($mission->images && count($mission->images) > 0)
                            <div class="h-48 overflow-hidden relative">
                                <img src="{{ asset('storage/' . $mission->images[0]) }}" alt="{{ $mission->location }}" class="w-full h-full object-cover">
                                @if(count($mission->images) > 1)
                                    <div class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-2 py-1 rounded">
                                        +{{ count($mission->images) - 1 }} more
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="h-48 bg-emerald-100 flex items-center justify-center">
                                <i class="bi bi-globe text-4xl text-emerald-600"></i>
                            </div>
                        @endif
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Outreach</span>
                                <span class="text-xs text-gray-500">{{ $mission->report_date->format('M d, Y') }}</span>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-lg mb-2">{{ $mission->location }}</h3>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span class="flex items-center gap-1">
                                    <i class="bi bi-people"></i>
                                    {{ number_format($mission->number_reached) }} reached
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if($selectedMission)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click="closeMissionModal">
            <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6" wire:click.stop>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Mission Report</span>
                        <h3 class="font-semibold text-gray-900 text-xl mt-1">{{ $selectedMission->location }}</h3>
                        <p class="text-sm text-gray-500">{{ $selectedMission->report_date->format('F d, Y') }}</p>
                    </div>
                    <button wire:click="closeMissionModal" class="text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-lg text-xl"></i>
                    </button>
                </div>
                
                @if($selectedMission->images && count($selectedMission->images) > 0)
                    <div class="mb-6">
                        <div class="grid grid-cols-{{ min(count($selectedMission->images), 3) }} gap-2">
                            @foreach($selectedMission->images as $index => $image)
                                <img src="{{ asset('storage/' . $image) }}" alt="Mission image {{ $index + 1 }}" 
                                     class="w-full h-40 object-cover rounded-lg {{ count($selectedMission->images) == 1 ? 'col-span-3' : '' }}">
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <div class="grid gap-4 md:grid-cols-2 mb-6">
                    <div class="bg-emerald-50 rounded-lg p-4">
                        <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">People Reached</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($selectedMission->number_reached) }}</p>
                    </div>
                    @if($selectedMission->expenses)
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Total Expenses</p>
                            <p class="text-2xl font-bold text-gray-900">₦{{ number_format($selectedMission->expenses, 2) }}</p>
                        </div>
                    @endif
                </div>
                
                @if($selectedMission->testimonies)
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Testimonies</h4>
                        <p class="text-gray-700 whitespace-pre-line">{{ $selectedMission->testimonies }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
