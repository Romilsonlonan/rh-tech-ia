<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacancy extends Model
{
    use HasFactory;

    #[Fillable(['user_id', 'title', 'description', 'requirements', 'salary', 'status', 'deadline'])]
    
    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
