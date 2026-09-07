<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    use HasFactory;

    #[Fillable(['user_id', 'name', 'email', 'phone', 'cpf', 'resume_path', 'status'])]
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
