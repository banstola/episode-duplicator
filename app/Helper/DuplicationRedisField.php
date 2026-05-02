<?php

declare(strict_types=1);

namespace App\Helper;

final class DuplicationRedisField
{
    public const string STATUS = 'status';

    public const string SOURCE_EPISODE_UUID = 'source_episode_uuid';

    public const string NEW_EPISODE_UUID = 'new_episode_uuid';

    public const string BATCH_ID = 'batch_id';

    public const string STARTED_AT = 'started_at';

    public const string COMPLETED_AT = 'completed_at';

    public const string FAILED_AT = 'failed_at';

    public const string CANCELLED_AT = 'cancelled_at';
}
