@php
use App\Models\Chapter;
$chapters = Auth::user()->hasRole('super-admin') ? Chapter::all() : collect();
$currentChapter = request()->query('chapter');
@endphp

<div>
    @if(Auth::user()->hasRole('super-admin') && $chapters->count() > 0)
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <form method="GET" action="{{ url()->current() }}">
                @foreach(request()->query() as $key => $value)
                    @if($key !== 'chapter')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label for="chapter-select" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Select Chapter</label>
                <div class="flex gap-2">
                    <select name="chapter" id="chapter-select" class="flex-1 rounded-md border border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        @foreach($chapters as $chapter)
                            <option value="{{ $chapter->name }}" @selected($currentChapter === $chapter->name)>
                                {{ $chapter->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 font-medium text-white transition hover:bg-indigo-700">
                        Switch
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
