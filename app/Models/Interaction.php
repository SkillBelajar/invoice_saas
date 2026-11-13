<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    use HasFactory;

    public const TYPES = [
        'Telepon',
        'Email',
        'Meeting',
        'Lainnya',
    ];

    public const STATUSES = [
        'Pending',
        'Selesai',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'type',
        'notes',
        'scheduled_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
