<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasFactory;

    public const STAGES = [
        'Prospek',
        'Proposal',
        'Negosiasi',
        'Deal Menang',
        'Deal Batal',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'amount',
        'stage',
        'expected_close_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expected_close_date' => 'date',
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
