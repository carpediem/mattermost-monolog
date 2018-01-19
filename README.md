# Mattermost PHP Monolog

This package allows sending log to Mattermost webhook using a dedicated Handler and Formatter.

This package requires [carpediem/mattermost-webhook](https://github.com/carpediem/mattermost-webhook)

## System Requirements

You need **PHP >= 5.6.0** but the latest stable version of PHP is recommended.

## Installation

```bash
$ composer require carpediem/mattermost-monolog
```

## Basic usage

The code above will register a Mattermost handler to your `Monolog\Logger` object.

```php
<?php

require __DIR__ . 'vendor/autoload.php';

use Carpediem\Mattermost\Monolog\Formatter;
use Carpediem\Mattermost\Monolog\Handler;
use Carpediem\Mattermost\Webhook\Message;
use Carpediem\Mattermost\Webhook\Client;
use GuzzleHttp\Client as GuzzleClient;
use Monolog\Logger;

$mattermost_message_template = (new Message('This text will be overriden by the logger'))
    ->setChannel('alerts')
    ->setUsername('AlertBot')
    ->setIconUrl('https://cdn2.iconfinder.com/data/icons/security-2-1/512/bug-512.png')
;
$mattermost_monolog_formatter = new Formatter($mattermost_message_template);

$mattermost_client = new Client(new GuzzleClient(['http_errors' => false]));
$mattermost_monolog_handler = new Handler(
    'https://your_mattermost_webhook_url',
    $mattermost_client
    Logger::WARNING,
    true
);
$mattermost_monolog_handler->setFormatter($mattermost_monolog_formatter);

$logger = new Logger('MyAwesomeLogger');
$logger->pushHandler($mattermost_monolog_handler);
```

## Advanced usage

If you don't like our formatter don't worry you can create your own formatter as long as

`Formatter::format` and `Formatter::formatBatch` returns a valid `Carpediem\Mattermost\Webhook\MessageInterface` object.