<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $block_field_uuid
 * @property string $block_uuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BlockFields extends Model
{
    use HasFactory;
    use HasUuids;

    public const string BLOCK_FIELD_UUID = 'block_field_uuid';
    public const string BLOCK_UUID = 'block_uuid';

    protected $primaryKey = self::BLOCK_FIELD_UUID;

    protected $fillable = [
        self::BLOCK_UUID,
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Blocks::class, self::BLOCK_UUID, Blocks::BLOCK_UUID);
    }
}
