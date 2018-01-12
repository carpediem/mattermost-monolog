<?php

namespace Carpediem\Mattermost\Monolog\Test;

use Carpediem\Mattermost\Monolog\Exception;
use Carpediem\Mattermost\Monolog\Formatter;
use Carpediem\Mattermost\Monolog\Handler;
use Carpediem\Mattermost\Webhook\Client;
use Carpediem\Mattermost\Webhook\Message;
use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Carpediem\Mattermost\Monolog\Handler;
 */
final class HandlerTest extends TestCase
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
            'extra' => [],
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

    public function testHandlerThrowsException()
    {
        $this->expectException(Exception::class);
        $handler = new Handler('http://localhost', new Client(new GuzzleClient()));
        $handler->handle($this->getRecord());
    }

    public function testHandlerSend()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([new Response(200, ['X-Foo' => 'Bar'])]);
        $handler = HandlerStack::create($mock);
        $httpClient = new GuzzleClient(['handler' => $handler]);
        $client = new Client($httpClient);
        $handler = new Handler('http://localhost', $client);
        $handler->setFormatter(new Formatter(new Message()));
        $this->assertFalse($handler->handle($this->getRecord()));
    }
}
