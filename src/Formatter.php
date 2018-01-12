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

use Carpediem\Mattermost\Webhook\Attachment;
use Carpediem\Mattermost\Webhook\Message;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

/**
 * Formats incoming records into Message instance
 */
class Formatter implements FormatterInterface
{
    const COLOR_DANGER = 'danger';
    const COLOR_WARNING = 'warning';
    const COLOR_GOOD = 'good';
    const COLOR_DEFAULT = '#e3e4e6';

    /**
     * New instance
     *
     * @param Message $message Template message with default value
     */
    public function __construct(Message $message)
    {
        $this->message = clone $message;
        $this->formatter = new NormalizerFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        $attachments = array_map([$this, 'setAttachment'], $records);

        $message = clone $this->message;
        $message
            ->setText('### Alert Batches from '.date_create()->format('Y-m-d H:i:s'))
            ->setAttachments($attachments);

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $attachment = $this->setAttachment($record);
        $message = clone $this->message;

        return $message
            ->setText('### Alert from '.date_create()->format('Y-m-d H:i:s'))
            ->setAttachments([$attachment]);
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
        $color = $this->setAttachmentColor($record['level']);
        $text = $this->setAttachmentText($record);

        return (new Attachment())
            ->setColor($color)
            ->setTitle('Details')
            ->setFallback($record['message'])
            ->setText($text)
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
        switch (true) {
            case $level >= Logger::ERROR:
                return self::COLOR_DANGER;
            case $level >= Logger::WARNING:
                return self::COLOR_WARNING;
            case $level >= Logger::INFO:
                return self::COLOR_GOOD;
            default:
                return self::COLOR_DEFAULT;
        }
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
        foreach (['extra', 'context'] as $key) {
            if (empty($record[$key])) {
                continue;
            }

            $content[] = $this->addMarkdownTable($key, $record[$key]);
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
            $value = $this->formatValue($value);
            $content[] = "| $name | $value |";
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
