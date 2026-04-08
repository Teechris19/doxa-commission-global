<?php

namespace App\Livewire\Admin\Dashboard\Members;

use App\Models\Chapter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class MembersTable extends PowerGridComponent
{
    public string $tableName = 'members-table';

    #[Url]
    public ?string $chapter = null;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
                

            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return User::with(['teams', 'chapter'])
            ->when($this->chapter, function (Builder $builder) {
                $chapter = Chapter::where('name', $this->chapter)->first();
                if ($chapter) {
                    $builder->where('chapter_id', $chapter->id);
                }
            })
            ->when(
                $this->search,
                fn(Builder $query) =>
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
            );
    }

    public function relationSearch(): array
    {
        return [
            'chapter' => ['name'],
            'teams'   => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('email')
            ->add('chapter_name', fn(User $user) => $user->chapter?->name)
            ->add('teams_list', fn(User $user) => $user->teams->pluck('name')->join(', '))
            ->add('created_at_formatted', fn(User $user) => $user->created_at?->format('Y-m-d H:i'));
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make('Name', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Chapter', 'chapter_name', 'chapter.name')
                ->sortable()
                ->searchable(),

            Column::make('Teams', 'teams_list')
                ->bodyAttribute('whitespace-normal')
                ->searchable(),

            Column::make('Created', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('name')->operators(['contains']),
            Filter::inputText('email')->operators(['contains']),
            Filter::select('chapter_id', 'chapter.name')
                ->dataSource(Chapter::all())
                ->optionLabel('name')
                ->optionValue('id'),
            Filter::datetimepicker('created_at'),
        ];
    }

    #[\Livewire\Attributes\On('bulkDelete')]
    public function bulkDelete(): void
    {
        $ids = $this->getSelected(); // PowerGrid helper
        if (!empty($ids)) {
            User::whereIn('id', $ids)->delete();
            $this->clearSelected(); // clear checkboxes
            $this->js("alert('Deleted ".count($ids)." users successfully')");
        }
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js("alert('Edit user: {$rowId}')");
    }

    #[\Livewire\Attributes\On('delete')]
    public function delete($rowId): void
    {
        $this->js("alert('Delete user: {$rowId}')");
    }

    #[\Livewire\Attributes\On('teams')]
    public function editTeams($rowId): void
    {
        $this->dispatch('openModal', id: 'teams_modal', rowId: $rowId);
    }

    public function actions(User $row): array
    {
        return [
            Button::add('edit')
                ->slot('Edit')
                ->class('pg-btn pg-btn-blue')
                ->dispatch('edit', ['rowId' => $row->id]),

            Button::add('teams')
                ->slot('Teams')
                ->class('pg-btn pg-btn-amber')
                ->dispatch('teams', ['rowId' => $row->id]),

            Button::add('delete')
                ->slot('Delete')
                ->class('pg-btn pg-btn-red')
                ->dispatch('delete', ['rowId' => $row->id]),
        ];
    }
}
