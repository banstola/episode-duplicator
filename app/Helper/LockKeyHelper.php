<?php

declare(strict_types=1);

namespace App\Helper;

final readonly class LockKeyHelper
{
    public const string DUPLICATE_EPISODE = 'duplicate_episode_lock';

    public const string DUPLICATION_STATUS = 'duplication_status_lock';

    public const string DUPLICATION_CANCEL = 'duplication_cancel_lock';

    private static function build(string $key, string ...$identifiers): string
    {
        return sprintf('%s_%s', $key, implode(':', $identifiers));
    }

    public static function getDuplicateEpisodeKey(string ...$identifiers): string
    {
        return self::build(self::DUPLICATE_EPISODE, ...$identifiers);

    }

    public static function getDuplicationStatusKey(string ...$identifiers): string
    {
        return self::build(self::DUPLICATION_STATUS, ...$identifiers);
    }

    public function getDuplicationCancelKey(string ...$identifiers): string
    {
        return self::build(self::DUPLICATION_CANCEL, ...$identifiers);
    }
}
