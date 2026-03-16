<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellMember extends Model
{
    protected $fillable = [
        'cell_group_id',
        'account_id',
        'name',
        'phone',
        'email',
        'joined_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'joined_at' => 'date',
    ];

    public function cellGroup(): BelongsTo
    {
        return $this->belongsTo(CellGroup::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Accounts::class);
    }
}
