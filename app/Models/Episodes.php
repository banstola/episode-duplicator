<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $episode_uuid
 * @property string $title
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Episodes extends Model
{
    use HasFactory;
    use HasUuids;

    public const string EPISODE_UUID = 'episode_uuid';
    public const string TITLE = 'title';
    public const string STATUS = 'status';

    protected $primaryKey = self::EPISODE_UUID;

    protected $fillable = [
        self::TITLE,
        self::STATUS,
    ];

    public function parts(): HasMany
    {
        return $this->hasMany(Parts::class, self::EPISODE_UUID, self::EPISODE_UUID);
    }
}
