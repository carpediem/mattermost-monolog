<?php

namespace Carpediem\Mattermost\Monolog\Test;

use Carpediem\Mattermost\Monolog\Formatter;
use Carpediem\Mattermost\Webhook\Message;
use DateTimeImmutable;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Carpediem\Mattermost\Monolog\Formatter;
 */
final class FormatterTest extends TestCase
{
    /**
     * @param  mixed $level
     * @param  mixed $message
     * @param  mixed $context
     * @return array Record
     */
    protected function getRecord($level = Logger::WARNING, $message = 'test', $context = [])
    {
        return [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => $context,
        ];
    }

    /**
     * @return array
     */
    protected function getMultipleRecords()
    {
        return [
            $this->getRecord(Logger::DEBUG, 'debug message 1'),
            $this->getRecord(Logger::DEBUG, 'debug message 2'),
            $this->getRecord(Logger::INFO, 'information'),
            $this->getRecord(Logger::WARNING, 'warning'),
            $this->getRecord(Logger::ERROR, 'error', [
                'foo' => 'bar',
                'baz' => 'qux',
                'bool' => false,
                'null' => null,
                'arr' => [null, 'toto', 'yes' => 'we can'],
            ]),
        ];
    }

    public function testFormat()
    {
        $formatter = new Formatter(new Message('basic message'));
        $record = $this->getRecord();

        $message = $formatter->format($record);
        $this->assertInstanceof(Message::class, $message);
    }

    public function testFormatBatch()
    {
        $formatter = new Formatter(new Message('basic message'));
        $records = $this->getMultipleRecords();

        $message = $formatter->formatBatch($records);
        $this->assertInstanceof(Message::class, $message);
    }
}
