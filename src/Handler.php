<?php
/**
* This file is part of the Carpediem.Errors library
*
* @license http://opensource.org/licenses/MIT
* @link https://github.com/carpediem/mattermost-monolog/
* @version 0.1.0
* @package carpediem.mattermost-php
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Carpediem\Mattermost\Monolog;

use Carpediem\Mattermost\Webhook\Client;
use Carpediem\Mattermost\Webhook\Message;
use GuzzleHttp\Psr7;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class Handler extends AbstractProcessingHandler
{
    protected $mattermost_client;

    protected $mattermost_uri;

    public function __construct($uri, Client $client, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->mattermost_client = $client;
        $this->mattermost_uri = Psr7\uri_for($uri);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (!$record['formatted'] instanceof Message) {
            throw new Exception(sprintf('%s expects the `formatted` index to contain a %s object', get_class($this), Message::class));
        }

        $this->mattermost_client->send($this->mattermost_uri, $record['formatted'], ['http_errors' => false]);
    }
}
