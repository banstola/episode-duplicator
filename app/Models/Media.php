<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $media_uuid
 * @property string $block_uuid
 * @property string $location
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Media extends Model
{
    use HasFactory;
    use HasUuids;

    public const string MEDIA_UUID = 'media_uuid';

    public const string BLOCK_UUID = 'block_uuid';

    public const string LOCATION = 'location';

    protected $primaryKey = self::MEDIA_UUID;

    protected $fillable = [
        self::BLOCK_UUID,
        self::LOCATION,
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(Blocks::class, self::BLOCK_UUID, Blocks::BLOCK_UUID);
    }
}
