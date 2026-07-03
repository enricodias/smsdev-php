<?php

namespace enricodias\SmsDev\Tests;

use Psr\Log\AbstractLogger;

/**
 * PSR-3 logger spy used to assert log calls in tests.
 */
final class TestLogger extends AbstractLogger
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * Checks if a log entry with the given level and message was recorded.
     */
    public function hasRecord(string $level, string $message): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level && $record['message'] === $message) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a log entry with the given level, message and context subset was recorded.
     *
     * @param mixed[] $context
     */
    public function hasRecordWithContext(string $level, string $message, array $context): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] !== $level || $record['message'] !== $message) {
                continue;
            }

            if ($this->contextMatches($record['context'], $context) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @param mixed[] $actual
     * @param mixed[] $expected
     */
    private function contextMatches(array $actual, array $expected): bool
    {
        foreach ($expected as $key => $value) {
            if (\array_key_exists($key, $actual) === false) {
                return false;
            }

            if ($actual[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
