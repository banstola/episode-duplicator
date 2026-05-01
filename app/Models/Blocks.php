<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $block_uuid
 * @property string $item_uuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Blocks extends Model
{
    use HasFactory;
    use HasUuids;

    public const string BLOCK_UUID = 'block_uuid';
    public const string ITEM_UUID = 'item_uuid';

    protected $primaryKey = self::BLOCK_UUID;

    protected $fillable = [
        self::ITEM_UUID,
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, self::ITEM_UUID, Items::ITEM_UUID);
    }

    public function blockFields(): HasMany
    {
        return $this->hasMany(BlockFields::class, self::BLOCK_UUID, self::BLOCK_UUID);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, self::BLOCK_UUID, self::BLOCK_UUID);
    }
}
