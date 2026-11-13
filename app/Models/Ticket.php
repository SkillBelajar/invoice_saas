<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    public const PRIORITIES = [
        'Rendah',
        'Sedang',
        'Tinggi',
    ];

    public const STATUSES = [
        'Buka',
        'Diproses',
        'Selesai',
        'Tutup',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
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

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['Selesai', 'Tutup'])) {
            return false;
        }

        return $this->due_date ? $this->due_date->isPast() : false;
    }
}
