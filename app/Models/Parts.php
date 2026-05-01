<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $part_uuid
 * @property string $title
 * @property string $episode_uuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Parts extends Model
{
    use HasFactory;
    use HasUuids;

    public const string PART_UUID = 'part_uuid';

    public const string TITLE = 'title';

    public const string EPISODE_UUID = 'episode_uuid';

    protected $primaryKey = self::PART_UUID;

    protected $fillable = [
        self::TITLE,
        self::EPISODE_UUID,
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episodes::class, self::EPISODE_UUID, Episodes::EPISODE_UUID);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Items::class, self::PART_UUID, self::PART_UUID);
    }
}
