<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const STATUS_PLANNING = 'Perencanaan';
    public const STATUS_IN_PROGRESS = 'Sedang Berjalan';
    public const STATUS_REVIEW = 'Menunggu Review';
    public const STATUS_COMPLETED = 'Selesai';
    public const STATUS_CANCELLED = 'Dibatalkan';

    public const STATUSES = [
        self::STATUS_PLANNING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_REVIEW,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'description',
        'status',
        'progress_percentage',
        'budget',
        'start_date',
        'deadline',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'integer',
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'deadline' => 'date',
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

    public function logs(): HasMany
    {
        return $this->hasMany(ProjectLog::class)->orderByDesc('logged_at')->orderByDesc('id');
    }
}
