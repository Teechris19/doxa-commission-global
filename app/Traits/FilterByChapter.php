<?php

namespace App\Traits;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
// use Symfony\Component\HttpFoundation\Request;

trait FilterByChapter
{
    public static function booted(): void
    {
        if (request()->query('chapter')) {
            $user = Auth::user();
            
            
            $chapter = Chapter::where('name', '=', e(request()->query('chapter')))->firstOrFail();
            static::creating(function (Model $model) use ($chapter, $user){
                $model->where('chapter_id', '=', $chapter->id);
            });

            static::addGlobalScope(function(Builder $builder) use ($chapter){
                $builder->where('chapter_id', $chapter->id);
            });
        }
    }
}