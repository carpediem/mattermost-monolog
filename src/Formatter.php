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

use Carpediem\Mattermost\Webhook\Attachment;
use Carpediem\Mattermost\Webhook\MessageInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

/**
 * Formats incoming records into MessageInterface instance
 */
class Formatter implements FormatterInterface
{
    const COLOR_DANGER = 'danger';
    const COLOR_WARNING = 'warning';
    const COLOR_GOOD = 'good';
    const COLOR_DEFAULT = 'default';

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @var NormalizerFormatter
     */
    private $formatter;

    /**
     * New instance
     *
     * @param MessageInterface $message Template message with default value
     */
    public function __construct(MessageInterface $message)
    {
        $this->message = clone $message;
        $this->formatter = new NormalizerFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        $message = clone $this->message;

        return $message
            ->setText('### Batch alerts from '.date_create()->format('Y-m-d H:i:s'))
            ->setAttachments(array_map([$this, 'setAttachment'], $records));
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $message = clone $this->message;

        return $message
            ->setText('### Alert from '.date_create()->format('Y-m-d H:i:s'))
            ->setAttachments([$this->setAttachment($record)]);
    }

    /**
     * Generate the Attachment to the message
     *
     * @param array $record
     *
     * @return Attachment
     */
    private function setAttachment(array $record)
    {
        return (new Attachment($record['message']))
            ->setColor($this->setAttachmentColor($record['level']))
            ->setTitle('Details')
            ->setText($this->setAttachmentText($record))
        ;
    }

    /**
     * set the attachment color
     *
     * @param int $level
     *
     * @return string
     */
    private function setAttachmentColor($level)
    {
        static $levels_colors = [
            Logger::DEBUG     => self::COLOR_DEFAULT,
            Logger::INFO      => self::COLOR_GOOD,
            Logger::NOTICE    => self::COLOR_GOOD,
            Logger::WARNING   => self::COLOR_WARNING,
            Logger::ERROR     => self::COLOR_DANGER,
            Logger::CRITICAL  => self::COLOR_DANGER,
            Logger::ALERT     => self::COLOR_DANGER,
            Logger::EMERGENCY => self::COLOR_DANGER,
        ];

        static $color_lists = [
            self::COLOR_DANGER => '#d50200',
            self::COLOR_WARNING => '#de9e31',
            self::COLOR_GOOD => '#2fa44f',
            self::COLOR_DEFAULT => '#e3e4e6',
        ];

        $color_index = self::COLOR_DEFAULT;
        if (isset($levels_colors[$level])) {
            $color_index = $levels_colors[$level];
        }

        return $color_lists[$color_index];
    }

    /**
     * Generate Attachment text
     *
     * @param array $record
     *
     * @return string
     */
    private function setAttachmentText(array $record)
    {
        $message = $record['message'];
        if (isset($record['datetime'])) {
            $message .= ' '.$record['datetime']->format('Y-m-d H:i:s');
        }

        $content = [$message, ''];
        if (!empty($record['context'])) {
            $content[] = $this->addMarkdownTable('context', $record['context']);
        }

        if (!empty($record['extra'])) {
            $content[] = $this->addMarkdownTable('extra', $record['extra']);
        }

        return implode(PHP_EOL, $content);
    }

    /**
     * Generate a Markdown table from an array
     *
     * @param string $table_name
     * @param array  $data
     *
     * @return string
     */
    private function addMarkdownTable($table_name, array $data)
    {
        $content = [
            "#### $table_name",
            '',
            '| Name | Value |',
            '|:---------|:---------|',
        ];

        foreach ($data as $name => $value) {
            $content[] = "| $name | ".$this->formatValue($value).' |';
        }

        $content[] = '';

        return implode(PHP_EOL, $content);
    }

    /**
     * Format value to be included in markdown table cell.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function formatValue($value)
    {
        if (is_array($value)) {
            return sprintf('```%s```', $this->stringify($value));
        }

        if (null === $value) {
            return '`null`';
        }

        if (is_bool($value)) {
            return $value ? '`true`' : '`false`';
        }

        return (string) $value;
    }

    /**
     * Stringifies an array of key/value pairs to be used in attachment fields
     *
     * @param array $fields
     *
     * @return string
     */
    public function stringify($fields)
    {
        $normalized = $this->formatter->format($fields);
        $prettyPrintFlag = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 128;

        $hasSecondDimension = count(array_filter($normalized, 'is_array'));
        $hasNonNumericKeys = !count(array_filter(array_keys($normalized), 'is_numeric'));

        return $hasSecondDimension || $hasNonNumericKeys ? json_encode($normalized, $prettyPrintFlag) : json_encode($normalized);
    }
}
