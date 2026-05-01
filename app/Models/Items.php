<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $item_uuid
 * @property string $part_uuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Items extends Model
{
    use HasFactory;
    use HasUuids;

    public const string ITEM_UUID = 'item_uuid';
    public const string PART_UUID = 'part_uuid';

    protected $primaryKey = self::ITEM_UUID;

    protected $fillable = [
        self::PART_UUID,
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Parts::class, self::PART_UUID, Parts::PART_UUID);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Blocks::class, self::ITEM_UUID, self::ITEM_UUID);
    }
}
