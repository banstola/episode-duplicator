<?php

declare(strict_types=1);

namespace App\Helper;

final class DuplicationStatus
{
    public const string PROCESSING = 'processing';

    public const string COMPLETED = 'completed';

    public const string FAILED = 'failed';

    public const string CANCELLED = 'cancelled';

    public const string UNKNOWN = 'unknown';
}
