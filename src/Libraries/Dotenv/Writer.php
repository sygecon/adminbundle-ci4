<?php namespace Sygecon\AdminBundle\Libraries\Dotenv;

use Sygecon\AdminBundle\Libraries\Dotenv\Contracts\Formatter as DotenvFormatter;
use Sygecon\AdminBundle\Libraries\Dotenv\Contracts\Writer as DotenvWriter;
use Sygecon\AdminBundle\Libraries\Dotenv\Exceptions\UnableWriteToFileException;

/**
 * The DotenvWriter writer.
 */
class Writer implements DotenvWriter
{
    /**
     * The content buffer
     * @var string
     */
    protected $buffer;

    /**
     * The formatter instance
     */
    protected $formatter;

    /**
     * Create a new writer instance
     */
    public function __construct(DotenvFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Tests file for writability. If the file doesn't exist, check
     * the parent directory for writability so the file can be created.
     *
     * @return void
     */
    protected function ensureFileIsWritable($filePath)
    {
        if ((is_file($filePath) && !is_writable($filePath)) || (!is_file($filePath) && !is_writable(dirname($filePath)))) {
            throw new UnableWriteToFileException(sprintf('Unable to write to the file at %s.', $filePath));
        }
    }

    /**
     * Set buffer with content
     *
     * @param  string $content
     *
     * @return DotenvWriter
     */
    public function setBuffer($content)
    {
        if (!empty($content)) {
            $content = rtrim($content) . PHP_EOL;
        }

        $this->buffer = $content;

        return $this;
    }

    /**
     * Return content in buffer
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Append new line to buffer
     *
     * @param  string|null  $text
     *
     * @return DotenvWriter
     */
    protected function appendLine($text = null)
    {
        $this->buffer .= $text . PHP_EOL;
        return $this;
    }

    /**
     * Append empty line to buffer
     *
     * @return DotenvWriter
     */
    public function appendEmptyLine()
    {
        return $this->appendLine();
    }

    /**
     * Append comment line to buffer
     *
     * @param  string $comment
     *
     * @return DotenvWriter
     */
    public function appendCommentLine($comment)
    {
        return $this->appendLine('# '.$comment);
    }

    /**
     * Append one setter to buffer
     *
     * @param  string       $key
     * @param  string|null  $value
     * @param  string|null  $comment
     * @param  boolean      $export
     *
     * @return DotenvWriter
     */
    public function appendSetter($key, $value = null, $comment = null, $export = false)
    {
        $line = $this->formatter->formatSetterLine($key, $value, $comment, $export);

        return $this->appendLine($line);
    }

    /**
     * Update one setter in buffer
     *
     * @param  string       $key
     * @param  string|null  $value
     * @param  string|null  $comment
     * @param  boolean      $export
     *
     * @return DotenvWriter
     */
    public function updateSetter($key, $value = null, $comment = null, $export = false)
    {
        $pattern      = "/^(export\h)?\h*{$key}=.*/m";
        $line         = $this->formatter->formatSetterLine($key, $value, $comment, $export);
        $this->buffer = preg_replace_callback($pattern, function () use ($line) {
            return $line;
        }, $this->buffer);

        return $this;
    }

    /**
     * Delete one setter in buffer
     *
     * @param  string $key
     *
     * @return DotenvWriter
     */
    public function deleteSetter($key)
    {
        $pattern      = "/^(export\h)?\h*{$key}=.*\n/m";
        $this->buffer = preg_replace($pattern, null, $this->buffer);

        return $this;
    }

    /**
     * Save buffer to special file path
     *
     * @param  string $filePath
     *
     * @return DotenvWriter
     */
    public function save($filePath)
    {
        $this->ensureFileIsWritable($filePath);
        file_put_contents($filePath, $this->buffer);

        return $this;
    }
}
