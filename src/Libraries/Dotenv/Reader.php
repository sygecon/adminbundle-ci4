<?php namespace Sygecon\AdminBundle\Libraries\Dotenv;

use Sygecon\AdminBundle\Libraries\Dotenv\Contracts\Formatter as DotenvFormatter;
use Sygecon\AdminBundle\Libraries\Dotenv\Contracts\Reader as DotenvReader;
use Sygecon\AdminBundle\Libraries\Dotenv\Exceptions\UnableReadFileException;

/**
 * The DotenvReader class.
 */
class Reader implements DotenvReader
{
    /**
     * The file path
     *
     * @var string
     */
    protected $filePath;

    /**
     *
     * @var object
     */
    protected $formatter;

    /**
     * Create a new reader instance
     */
    public function __construct(DotenvFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * Load file
     *
     * @param  string $filePath
     *
     * @return DotenvReader
     */
    public function load($filePath)
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * Ensures the given file is readable.
     *
     * @return void
     */
    protected function ensureFileIsReadable()
    {
        if (!is_readable($this->filePath) || !is_file($this->filePath)) { 
            throw new UnableReadFileException(sprintf('Unable to read the file at %s.', $this->filePath));
        }
    }

    /**
     * Get content of file
     *
     * @return string
     */
    public function content()
    {
        $this->ensureFileIsReadable();
        return file_get_contents($this->filePath);
    }

    /**
     * Get informations of all lines from file content
     *
     * @return array
     */
    public function lines()
    {
        $content = [];
        $lines   = $this->readLinesFromFile();

        foreach ($lines as $row => $line) {
            $data = [
                'line'        => $row+1,
                'raw_data'    => $line,
                'parsed_data' => $this->formatter->parseLine($line)
            ];

            $content[] = $data;
        }

        return $content;
    }

    /**
     * Get informations of all keys from file content
     *
     * @return array
     */
    public function keys()
    {
        $content = [];
        $lines   = $this->readLinesFromFile();

        foreach ($lines as $row => $line) {
            $data = $this->formatter->parseLine($line);

            if ($data['type'] == 'setter') {
                $content[$data['key']] = [
                    'line'    => $row+1,
                    'export'  => $data['export'],
                    'value'   => $data['value'],
                    'comment' => $data['comment']
                ];
            }
        }

        return $content;
    }

    /**
     * Read content into an array of lines with auto-detected line endings
     *
     * @return array
     */
    protected function readLinesFromFile()
    {
        $this->ensureFileIsReadable();

        $autodetect = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES);
        ini_set('auto_detect_line_endings', $autodetect);

        return $lines;
    }
}
