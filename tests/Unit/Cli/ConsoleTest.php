<?php

declare(strict_types=1);

namespace NowoTech\ClaudePhpSetup\Tests\Unit\Cli;

use NowoTech\ClaudePhpSetup\Cli\Console;
use NowoTech\ClaudePhpSetup\Tests\Support\MemoryStreamTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Represents the ConsoleTest class.
 */
final class ConsoleTest extends TestCase
{
    use MemoryStreamTrait;

    #[Test]
    /**
     * Handles the itWritesLinesAndSuccess operation.
     */
    public function itWritesLinesAndSuccess(): void
    {
        $out     = $this->memoryStream('w+');
        $console = new Console($this->memoryStream('r'), $out);
        $console->writeln('hello');
        $console->success('ok');
        rewind($out);
        self::assertStringContainsString('hello', (string) stream_get_contents($out));
    }

    #[Test]
    /**
     * Handles the itWritesSectionInfoWarningAndUsesWrite operation.
     */
    public function itWritesSectionInfoWarningAndUsesWrite(): void
    {
        $out     = $this->memoryStream('w+');
        $console = new Console($this->memoryStream('r'), $out);
        $console->section('Title');
        $console->info('i');
        $console->warning('w');
        $console->error('e');
        $console->write('x');
        rewind($out);
        $text = (string) stream_get_contents($out);
        self::assertStringContainsString('Title', $text);
        self::assertStringContainsString('x', $text);
    }

    #[Test]
    /**
     * Handles the itChoiceReturnsDefaultWhenEmpty operation.
     */
    public function itChoiceReturnsDefaultWhenEmpty(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('b', $console->choice('q', ['a', 'b'], 'b'));
    }

    #[Test]
    /**
     * Handles the itChoiceReturnsFirstWhenDefaultEmptyAndAnswerEmpty operation.
     */
    public function itChoiceReturnsFirstWhenDefaultEmptyAndAnswerEmpty(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('a', $console->choice('q', ['a', 'b'], ''));
    }

    #[Test]
    /**
     * Handles the itChoiceReturnsByNumericIndex operation.
     */
    public function itChoiceReturnsByNumericIndex(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "2\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('b', $console->choice('q', ['a', 'b'], 'a'));
    }

    #[Test]
    /**
     * Handles the itChoiceReturnsTypedValue operation.
     */
    public function itChoiceReturnsTypedValue(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "laravel\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('laravel', $console->choice('q', ['none', 'laravel'], 'none'));
    }

    #[Test]
    /**
     * Handles the itChoiceFallsBackWhenInvalid operation.
     */
    public function itChoiceFallsBackWhenInvalid(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "999\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('def', $console->choice('q', ['a', 'b'], 'def'));
    }

    #[Test]
    /**
     * Handles the itConfirmReturnsDefaultWhenEmpty operation.
     */
    public function itConfirmReturnsDefaultWhenEmpty(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertTrue($console->confirm('ok?', true));
        rewind($in);
        fwrite($in, "\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertFalse($console->confirm('ok?', false));
    }

    #[Test]
    /**
     * Handles the itConfirmParsesYes operation.
     */
    public function itConfirmParsesYes(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "yes\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertTrue($console->confirm('ok?', false));
    }

    #[Test]
    /**
     * Handles the itMultiselectReturnsDefaultsOrAllKeysWhenEmptyOrAll operation.
     */
    public function itMultiselectReturnsDefaultsOrAllKeysWhenEmptyOrAll(): void
    {
        $choices = ['a' => 'A', 'b' => 'B'];
        $in      = $this->memoryStream('r+');
        fwrite($in, "\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame(['a', 'b'], $console->multiselect('q', $choices, ['a', 'b']));

        $in = $this->memoryStream('r+');
        fwrite($in, "ALL\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame(['a', 'b'], $console->multiselect('q', $choices, []));
    }

    #[Test]
    /**
     * Handles the itMultiselectParsesCommaSeparated operation.
     */
    public function itMultiselectParsesCommaSeparated(): void
    {
        $choices = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $in      = $this->memoryStream('r+');
        fwrite($in, "1,3\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame(['a', 'c'], $console->multiselect('q', $choices, ['b']));
    }

    #[Test]
    /**
     * Handles the itMultiselectFallsBackToDefaultsWhenNothingSelected operation.
     */
    public function itMultiselectFallsBackToDefaultsWhenNothingSelected(): void
    {
        $choices = ['a' => 'A'];
        $in      = $this->memoryStream('r+');
        fwrite($in, "9\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame(['a'], $console->multiselect('q', $choices, ['a']));
    }

    #[Test]
    /**
     * Handles the itAskReturnsDefaultWhenEmpty operation.
     */
    public function itAskReturnsDefaultWhenEmpty(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "\n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('def', $console->ask('name', 'def'));
    }

    #[Test]
    /**
     * Handles the itAskReturnsTrimmedAnswer operation.
     */
    public function itAskReturnsTrimmedAnswer(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "  val  \n");
        rewind($in);
        $console = new Console($in, $this->memoryStream('w'));
        self::assertSame('val', $console->ask('name', ''));
    }

    #[Test]
    /**
     * Handles the itReadLineReturnsEmptyWhenFgetsHitsEof operation.
     */
    public function itReadLineReturnsEmptyWhenFgetsHitsEof(): void
    {
        $in = $this->memoryStream('r+');
        fwrite($in, "x\n");
        rewind($in);
        $console  = new Console($in, $this->memoryStream('w'));
        $readLine = new ReflectionMethod(Console::class, 'readLine');
        self::assertSame('x', $readLine->invoke($console));
        self::assertSame('', $readLine->invoke($console));
    }
}
