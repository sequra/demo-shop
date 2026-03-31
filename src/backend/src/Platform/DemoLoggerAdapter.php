<?php

declare(strict_types=1);

namespace SeQura\Demo\Platform;

use SeQura\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use SeQura\Core\Infrastructure\Logger\LogData;

/**
 * Minimal logger adapter that delegates every message to error_log().
 */
final class DemoLoggerAdapter implements ShopLoggerAdapter
{
    /**
     * @inheritDoc
     */
    public function logMessage(LogData $data): void
    {
        error_log(
            sprintf(
                '[SeQura][%s][%s] %s',
                $this->levelLabel($data->getLogLevel()),
                $data->getComponent(),
                $data->getMessage()
            )
        );
    }

    /**
     * Map numeric log level to a human-readable label.
     *
     * @param int $level Log level constant.
     *
     * @return string
     */
    private function levelLabel(int $level): string
    {
        $map = [
            0 => 'ERROR',
            1 => 'WARNING',
            2 => 'INFO',
            3 => 'DEBUG',
        ];

        return $map[$level] ?? 'UNKNOWN';
    }
}
