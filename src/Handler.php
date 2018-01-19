<?php
/**
* This file is part of the Carpediem.Errors library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/carpediem/mattermost-monolog/
* @version 1.1.0
* @package carpediem.mattermost-php
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Carpediem\Mattermost\Monolog;

use Carpediem\Mattermost\Webhook\ClientInterface;
use Carpediem\Mattermost\Webhook\MessageInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Psr\Http\Message\UriInterface;

class Handler extends AbstractProcessingHandler
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * The mattermost incoming webhook Url
     *
     * @var string|UriInterface
     */
    protected $uri;

    /**
     * New instance.
     *
     * @param string|UriInterface $uri
     * @param ClientInterface     $client
     * @param int                 $level
     * @param bool                $bubble
     */
    public function __construct($uri, ClientInterface $client, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
        if (!$uri instanceof UriInterface && !is_string($uri)) {
            throw new Exception(sprintf('% expects the uri to be an string or a %s object %s given', __METHOD__, UriInterface::class, gettype($uri)));
        }

        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (!$record['formatted'] instanceof MessageInterface) {
            throw new Exception(sprintf('%s expects the `formatted` index to contain an object implementing %s', get_class($this), MessageInterface::class));
        }

        $this->client->notify($this->uri, $record['formatted']);
    }
}
