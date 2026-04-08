<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait MemoryStreamTrait
{
    /**
     * @return resource
     */
    private function memoryStream(string $mode): mixed
    {
        $r = fopen('php://memory', $mode);
        self::assertNotFalse($r);

        return $r;
    }
}
