<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Logging;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Slim\Logging\StdLogger;
use Stringable;

class StdLoggerTest extends TestCase
{
    private $stdout;
    private $stderr;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        ob_start();

        // Create temporary in-memory streams for testing
        $this->stdout = fopen('php://temp', 'w+');
        $this->stderr = fopen('php://temp', 'w+');

        $this->logger = new StdLogger($this->stdout, $this->stderr);
    }

    protected function tearDown(): void
    {
        fclose($this->stdout);
        fclose($this->stderr);

        // Ensure no unexpected output buffer issues
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    #[DataProvider('logLevelProvider')]
    public function testLogWritesToCorrectStream(string $level, string $expectedStream): void
    {
        // Log a message
        $this->logger->log($level, 'Test message');

        $stream = $expectedStream === 'stderr' ? $this->stderr : $this->stdout;
        rewind($stream);
        $output = stream_get_contents($stream);

        $this->assertStringContainsString('Test message' . "\n", $output);
    }

    public static function logLevelProvider(): array
    {
        return [
            [LogLevel::ERROR, 'stderr'],
            [LogLevel::CRITICAL, 'stderr'],
            [LogLevel::ALERT, 'stderr'],
            [LogLevel::EMERGENCY, 'stderr'],
            [LogLevel::DEBUG, 'stdout'],
            [LogLevel::INFO, 'stdout'],
            [LogLevel::NOTICE, 'stdout'],
            [LogLevel::WARNING, 'stdout'],
        ];
    }

    public function testLogTruncatesMessage(): void
    {
        $longMessage = str_repeat('a', 2048);
        $this->logger->log(LogLevel::ERROR, $longMessage);

        rewind($this->stderr);
        $output = stream_get_contents($this->stderr);

        $this->assertSame(1024, strlen($output));
    }

    public function testLogRemovesNullCharacters(): void
    {
        $messageWithNulls = "Test message with null \0 character";
        $this->logger->log(LogLevel::ERROR, $messageWithNulls);

        rewind($this->stderr);
        $output = stream_get_contents($this->stderr);

        $this->assertSame('Test message with null  character' . "\n", $output);
    }

    public function testLogHandlesStringableObject(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };
        $this->logger->log(LogLevel::ERROR, $stringable);

        rewind($this->stderr);
        $output = stream_get_contents($this->stderr);

        $this->assertSame('Stringable message' . "\n", $output);
    }
}
