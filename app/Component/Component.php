<?php 

namespace App\Component;

use Illuminate\Database\Eloquent\Model;
use Livewire\Volt\Component as VoltComponent;

class Component extends VoltComponent{
    public $bulkSelectedId;

    public function bulkDelete(Model $model)
    {
       $model->whereIn('id', $this->bulkSelectedId)->delete();
    }

    public function exportBulk()
    {
        
    }
}