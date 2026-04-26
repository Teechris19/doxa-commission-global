<?php
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\{BeliversAcademy, StudentClasses, AcademyClases, AcademyBatch};
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.tailwind-layout')] class extends Component {
    public $classes;
    public $student_classes;
    public $classDone;
    public $classNotDone;
    public $completionDate;
    public $academy;
    public $student;
    public $progressPercentage = 0;
    public $batch;

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->route('home.login');
        }

        $this->student_classes = StudentClasses::where('user_id', auth()->user()->id)->get();
        $this->student = $this->student_classes->first();

        if (!$this->student) {
            $this->classes = collect();
            $this->classDone = [];
            $this->classNotDone = [];
            return;
        }

        $this->academy = BeliversAcademy::find($this->student->academy_id);
        
        if ($this->student->batch_id) {
            $this->batch = AcademyBatch::with('classes')->find($this->student->batch_id);
            $this->classes = $this->batch ? $this->batch->classes : collect();
        } else {
            $this->classes = $this->academy ? AcademyClases::where('academy_id', $this->academy->id)->get() : collect();
        }

        $this->classDone = json_decode($this->student->class_completed ?? '[]', true) ?? [];
        $this->classNotDone = array_diff($this->classes->pluck('id')->toArray(), $this->classDone);
        
        // Calculate progress percentage
        $totalClasses = $this->classes->count();
        if ($totalClasses > 0) {
            $this->progressPercentage = round((count($this->classDone) / $totalClasses) * 100);
        }
        
        // Find completion date (when last class was completed)
        if (!empty($this->classDone)) {
            $this->completionDate = now()->format('Y-m-d');
        }
    }
}; ?>

<div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
    {{-- Header Section --}}
    <section class="rounded-3xl border border-blue-100 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(37,99,235,0.45)] sm:p-8 mb-6">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-blue-600">Believers Academy</p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">My Progress</h1>
                <p class="mt-2 text-sm text-slate-600">Track your learning milestones and completion status.</p>
            </div>

            @if($student && count($classNotDone) == 0 && $academy && $academy->certificate_template)
                <a href="{{ route('certificate.generate', ['name' => auth()->user()->name, 'date' => $completionDate ?? now()->toDateString()]) }}" 
                   class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700" 
                   target="_blank">
                    <i class="fas fa-certificate mr-2"></i>Download Certificate
                </a>
            @endif
        </div>

        @if(!$student)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700">
                <i class="fas fa-info-circle mr-2"></i>You are not enrolled in Believers Academy yet. 
                <a href="{{ route('believers_academy.register') }}" class="underline font-semibold">Register now</a> to start your discipleship journey.
            </div>
        @elseif($classes->isEmpty())
            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-4 text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>Classes are not available for your academy yet. Please check back later.
            </div>
        @else
            {{-- Progress Overview --}}
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Overall Progress</h2>
                        <p class="text-sm text-gray-600">{{ count($classDone) }} of {{ $classes->count() }} classes completed</p>
                    </div>
                    <div class="text-right">
                        <span class="text-3xl font-bold text-blue-600">{{ $progressPercentage }}%</span>
                    </div>
                </div>
                
                {{-- Progress Bar --}}
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all duration-500 ease-out" 
                         style="width: {{ $progressPercentage }}%"></div>
                </div>

                @if($batch)
                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-600">
                        <span class="inline-flex items-center gap-2">
                            <i class="fas fa-users text-blue-500"></i>
                            <span class="font-medium">{{ $batch->name }}</span>
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <i class="fas fa-calendar text-green-500"></i>
                            <span>Starts: {{ \Carbon\Carbon::parse($batch->start_date)->format('M d, Y') }}</span>
                        </span>
                    </div>
                @endif
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-5 border border-green-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-700">{{ count($classDone) }}</p>
                            <p class="text-sm text-green-600">Completed</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-5 border border-amber-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-700">{{ count($classNotDone) }}</p>
                            <p class="text-sm text-amber-600">Pending</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-5 border border-blue-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-book text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-700">{{ $classes->count() }}</p>
                            <p class="text-sm text-blue-600">Total Classes</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Classes List --}}
            <div class="overflow-x-auto rounded-2xl border border-blue-100">
                <div class="bg-blue-50 px-4 py-3 border-b border-blue-100">
                    <h3 class="font-semibold text-gray-900">Your Classes</h3>
                </div>
                <table class="min-w-full divide-y divide-blue-100 text-sm">
                    <thead class="bg-blue-50 text-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">#</th>
                            <th class="px-4 py-3 text-left font-semibold">Course</th>
                            <th class="px-4 py-3 text-left font-semibold">Description</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-50 bg-white">
                        @foreach ($classes as $key => $class)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-4 py-3 text-slate-600">{{ $key + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $class->name }}</td>
                                <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $class->description }}">
                                    {{ Str::limit($class->description ?? 'No description', 50) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if (in_array($class->id, $classDone))
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                            <i class="fas fa-check-circle"></i>
                                            Completed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                            <i class="fas fa-clock"></i>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Completion Message --}}
            @if(count($classNotDone) == 0)
                <div class="mt-6 rounded-2xl border border-green-200 bg-green-50 p-6 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-500 rounded-full mb-4">
                        <i class="fas fa-trophy text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-green-800 mb-2">Congratulations!</h3>
                    <p class="text-green-700 mb-4">You have completed all the classes in your batch.</p>
                    @if($academy && $academy->certificate_template)
                        <a href="{{ route('certificate.generate', ['name' => auth()->user()->name, 'date' => $completionDate ?? now()->toDateString()]) }}" 
                           class="inline-flex items-center justify-center rounded-xl bg-green-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-700" 
                           target="_blank">
                            <i class="fas fa-download mr-2"></i>Download Your Certificate
                        </a>
                    @endif
                </div>
            @endif
        @endif
    </section>
</div>
