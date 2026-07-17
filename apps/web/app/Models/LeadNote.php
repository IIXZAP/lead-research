<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadNote extends Model
{
    /** @use HasFactory<\Database\Factories\LeadNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'user_id',
        'note',
    ];

    /** @return BelongsTo<Lead, LeadNote> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return BelongsTo<User, LeadNote> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
