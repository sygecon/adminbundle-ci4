<?php namespace Sygecon\AdminBundle\Libraries\Dotenv;

use CodeIgniter\Validation\FormatRules;
use Sygecon\AdminBundle\Libraries\Dotenv\Formatter;
use Sygecon\AdminBundle\Libraries\Dotenv\Reader;
use Sygecon\AdminBundle\Libraries\Dotenv\Writer;
use Sygecon\AdminBundle\Libraries\Dotenv\Exceptions\FileNotFoundException;
use Sygecon\AdminBundle\Libraries\Dotenv\Exceptions\KeyNotFoundException;
use Sygecon\AdminBundle\Libraries\Dotenv\Exceptions\NoBackupAvailableException;

/**
 * The DotenvEditor class.
 */
class Editor
{

    /**
     * The formatter instance
     */
    protected $formatter;

    /**
     * The reader instance
     */
    protected $reader;

    /**
     * The writer instance
     */
    protected $writer;

    /**
     * The file path
     *
     * @var string
     */
    protected $filePath;

    /**
     * The auto backup status
     *
     * @var bool
     */
    protected $autoBackup;

    /**
     * The backup path
     *
     * @var string
     */
    protected $backupPath;

    /**
     * The backup filename prefix
     */
    const BACKUP_FILENAME_PREFIX = '.env.backup_';

    /**
     * The backup filename suffix
     */
    const BACKUP_FILENAME_SUFFIX = '';

    /**
     * Create a new DotenvEditor instance
     *
     * @return void
     */
    public function __construct()
    {
        $this->formatter  = new Formatter;
        $this->reader     = new Reader($this->formatter);
        $this->writer     = new Writer($this->formatter);
        $this->autoBackup = true;
        $this->backupPath = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'dotenv'  . DIRECTORY_SEPARATOR;

        $this->createBackupFolder();
        $this->load();
    }

    /**
     * Load file for working
     *
     * @param  string|null  $filePath           The file path
     * @param  boolean      $restoreIfNotFound  Restore this file from other file if it's not found
     * @param  string|null  $restorePath        The file path you want to restore from
     *
     * @return DotenvEditor
     */
    public function load($filePath = null, $restoreIfNotFound = false, $restorePath = null)
    {
        $this->resetContent();
        if (is_null($filePath)) {
            $this->filePath = APPPATH .  'me.env.php';
        }
        $this->reader->load($this->filePath);
        if (file_exists($this->filePath)) {
            $this->writer->setBuffer($this->getContent());
            return $this;
        }
        if ($restoreIfNotFound) {
            return $this->restore($restorePath);
        }
        return $this;
    }

    /**
     * Reset content for editor
     *
     * @return void
     */
    protected function resetContent()
    {
        $this->filePath = null;

        $this->reader->load(null);
        $this->writer->setBuffer(null);
    }

    /**
     * Create backup folder if not exists
     *
     * @return void
     */
    protected function createBackupFolder()
    {
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0777, true);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Working with reading
    |--------------------------------------------------------------------------
    |
    | getContent($content)
    | getLines()
    | getKeys()
    | keyExists($key)
    | getValue($key)
    |
    */

    /**
     * Get raw content of file
     *
     * @return string
     */
    public function getContent()
    {
        return $this->reader->content();
    }

    /**
     * Get all lines from file
     *
     * @return array
     */
    public function getLines()
    {
        return $this->reader->lines();
    }

    /**
     * Get all or exists given keys in file content
     *
     * @param  array  $keys
     *
     * @return array
     */
    public function getKeys($keys = [])
    {
        $allKeys = $this->reader->keys();

        return array_filter($allKeys, function ($key) use ($keys) {
            if (!empty($keys)) {
                return in_array($key, $keys);
            }

            return true;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Check, if a given key is exists in the file content
     *
     * @param  string  $keys
     *
     * @return bool
     */
    public function keyExists($key)
    {
        $allKeys = $this->getKeys();

        return array_key_exists($key, $allKeys);
    }

    /**
     * Return the value matching to a given key in the file content
     *
     * @param  $key
     *
     * @return string
     */
    public function getValue($key)
    {
        $allKeys = $this->getKeys([$key]);

        if (array_key_exists($key, $allKeys)) {
            return $allKeys[$key]['value'];
        }
        return '';
        //throw new KeyNotFoundException('Requested key not found in your file.');
    }

    /*
    |--------------------------------------------------------------------------
    | Working with writing
    |--------------------------------------------------------------------------
    |
    | getBuffer()
    | addEmpty()
    | addComment($comment)
    | setKeys($data)
    | setKey($key, $value = null, $comment = null, $export = false)
    | deleteKeys($keys = [])
    | deleteKey($key)
    | save()
    |
    */

    /**
     * Return content in buffer
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->writer->getBuffer();
    }

    /**
     * Add empty line to buffer
     *
     * @return DotenvEditor
     */
    public function addEmpty()
    {
        $this->writer->appendEmptyLine();

        return $this;
    }

    /**
     * Add comment line to buffer
     *
     * @param object
     *
     * @return DotenvEditor
     */
    public function addComment($comment)
    {
        $this->writer->appendCommentLine($comment);

        return $this;
    }

    /**
     * Set many keys to buffer
     *
     * @param  array  $data
     *
     * @return DotenvEditor
     */
    public function setKeys($data)
    {
        foreach ($data as $i => $setter) {
            if (!is_array($setter)) {
                if (!is_string($i)) {
                    continue;
                }

                $setter = [
                    'key' => $i,
                    'value' => $setter,
                ];
            }

            if (array_key_exists('key', $setter)) {
                $key     = $this->formatter->formatKey($setter['key']);
                $value   = array_key_exists('value', $setter) ? $setter['value'] : null;
                $comment = array_key_exists('comment', $setter) ? $setter['comment'] : null;
                $export  = array_key_exists('export', $setter) ? $setter['export'] : false;

                if (!is_file($this->filePath) || !$this->keyExists($key)) {
                    $this->writer->appendSetter($key, $value, $comment, $export);
                } else {
                    $oldInfo = $this->getKeys([$key]);
                    $comment = is_null($comment) ? $oldInfo[$key]['comment'] : $comment;

                    $this->writer->updateSetter($key, $value, $comment, $export);
                }
            }
        }

        return $this;
    }

    /**
     * Set one key to buffer
     *
     * @param string       $key      Key name of setter
     * @param string|null  $value    Value of setter
     * @param string|null  $comment  Comment of setter
     * @param boolean      $export   Leading key name by "export "
     *
     * @return DotenvEditor
     */
    public function setKey($key, $value = null, $comment = null, $export = false)
    {
        $data = [compact('key', 'value', 'comment', 'export')];

        return $this->setKeys($data);
    }

    /**
     * Delete many keys in buffer
     *
     * @param  array $keys
     *
     * @return DotenvEditor
     */
    public function deleteKeys($keys = [])
    {
        foreach ($keys as $key) {
            $this->writer->deleteSetter($key);
        }

        return $this;
    }

    /**
     * Delete on key in buffer
     *
     * @param  string  $key
     *
     * @return DotenvEditor
     */
    public function deleteKey($key)
    {
        $keys = [$key];

        return $this->deleteKeys($keys);
    }

    /**
     * Save buffer to file
     *
     * @return DotenvEditor
     */
    public function save()
    {
        if (is_file($this->filePath) && $this->autoBackup) {
            $this->backup();
        }
        $this->writer->save($this->filePath);
        $this->writerAsConst();
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Working with backups
    |--------------------------------------------------------------------------
    |
    | autoBackup($on)
    | backup()
    | getBackups()
    | getLatestBackup()
    | restore($filePath = null)
    | deleteBackups($filePaths = [])
    | deleteBackup($filePath)
    |
    */

    /**
     * Switching of the auto backup mode
     *
     * @param  boolean  $on
     *
     * @return DotenvEditor
     */
    public function autoBackup($on = true)
    {
        $this->autoBackup = $on;

        return $this;
    }

    /**
     * Create one backup of loaded file
     *
     * @return DotenvEditor
     */
    public function backup()
    {
        if (!is_file($this->filePath)) {
            throw new FileNotFoundException("File does not exist at path {$this->filePath}");

            return false;
        }

        // Make sure the backup directory exists
        $this->createBackupFolder();

        copy(
            $this->filePath,
            $this->backupPath . self::BACKUP_FILENAME_PREFIX . date('Y_m_d_His') . self::BACKUP_FILENAME_SUFFIX
        );

        return $this;
    }

    /**
     * Return an array with all available backups
     *
     * @return array
     */
    public function getBackups()
    {
        $output = [];

        if (! is_dir($this->backupPath)) {
            return $output;
        }

        $filenameRegex = '/^' .preg_quote(self::BACKUP_FILENAME_PREFIX, '/'). '(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})' .preg_quote(self::BACKUP_FILENAME_SUFFIX, '/'). '$/';
        $backups       = array_filter(array_diff(scandir($this->backupPath), array('..', '.')), function($backup) use ($filenameRegex) {
            return preg_match($filenameRegex, $backup);
        });

        foreach ($backups as $backup) {
            $output[] = [
                'filename'   => $backup,
                'filepath'   => $this->backupPath . $backup,
                'created_at' => preg_replace($filenameRegex, '$1-$2-$3 $4:$5:$6', $backup)
            ];
        }

        return $output;
    }

    /**
     * Return the information of the latest backup file
     *
     * @return array
     */
    public function getLatestBackup()
    {
        $backups = $this->getBackups();

        if (empty($backups)) {
            return null;
        }

        $latestBackup = 0;

        foreach ($backups as $backup) {
            $timestamp = strtotime($backup['created_at']);

            if ($timestamp > $latestBackup) {
                $latestBackup = $timestamp;
            }
        }

        $fileName  = self::BACKUP_FILENAME_PREFIX . date("Y_m_d_His", $latestBackup) . self::BACKUP_FILENAME_SUFFIX;
        $filePath  = $this->backupPath . $fileName;
        $createdAt = date("Y-m-d H:i:s", $latestBackup);

        $output = [
            'filename'   => $fileName,
            'filepath'   => $filePath,
            'created_at' => $createdAt
        ];

        return $output;
    }

    /**
     * Restore the loaded file from latest backup file or from special file.
     *
     * @param  string|null  $filePath
     *
     * @return DotenvEditor
     */
    public function restore($filePath = null)
    {
        if (is_null($filePath)) {
            $latestBackup = $this->getLatestBackup();

            if (is_null($latestBackup)) {
                throw new NoBackupAvailableException("There are no available backups!");
            }

            $filePath = $latestBackup['filepath'];
        }

        if (!is_file($filePath)) {
            throw new FileNotFoundException("File does not exist at path {$filePath}");
        }

        copy($filePath, $this->filePath);
        $this->writer->setBuffer($this->getContent());

        return $this;
    }

    /**
     * Delete all or the given backup files
     *
     * @param  array  $filePaths
     *
     * @return DotenvEditor
     */
    public function deleteBackups($filePaths = [])
    {
        if (empty($filePaths)) {
            $allBackups = $this->getBackups();

            foreach ($allBackups as $backup) {
                $filePaths[] = $backup['filepath'];
            }
        }

        foreach ($filePaths as $filePath) {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        return $this;
    }

    /**
     * Delete the given backup file
     *
     * @param  string  $filePath
     *
     * @return DotenvEditor
     */
    public function deleteBackup($filePath)
    {
        return $this->deleteBackups([$filePath]);
    }
    
    ////////////// =================================================
    protected function matchToArray(string $text): array {
		$data = [];
		if ($text) {
			$res = explode('}', $text);
			foreach($res as $i => $val) {
				if ($val) {
					$val = substr($val, 1);
					$p = strpos($val, '|');
					$data[substr($val, 0, $p)] = substr($val, ($p+1));
				}
				unset($res[$i]);
			}
			unset($res);
		}
		return $data;
	}

    protected function writerAsConst()
	{
		$filePath = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'options.php';
        if ((is_file($filePath) && !is_writable($filePath)) || (!is_file($filePath) && !is_writable(dirname($filePath)))) {
            return;
        }
        $lines = $this->getKeys();
		$res = "<?php \n";
		$formatRole = new FormatRules();
		foreach($lines as $key => $value) {
			$val = $value['value'];
			$name = mb_strtoupper(str_replace('.', '_', preg_replace('/\B([A-Z])/', '_$1', $key)));
			$res .= "defined('" . $name . "') || define('" . $name . "', ";
			if ($val[0] === '{' && strpos($val, '}') !== false && strpos($val, '|') !== false) {
				$arr = $this->matchToArray($val);
				$res .= '[';
				foreach($arr as $i => $val) {
					if ($formatRole->integer($i)) {
						$res .= $i . ' => ';
					} else {
						$res .= "'" . $i . "' => ";
					}
					if ($formatRole->integer($val)) {
						$res .= $val . ", ";
					} else {
						$res .= "'" . $val . "', ";
					}
				}
				$res = substr($res, 0, -2); 
				$res .= "]";
			} else if (strpos($val, '|') !== false) {
				$arr = explode('|', $val);
				$res .= '[';
				foreach($arr as $val) {
					if ($formatRole->integer($val)) {
						$res .= $val . ", ";
					} else {
						$res .= "'" . $val . "', ";
					}
				}
				$res = substr($res, 0, -2); 
				$res .= "]";
			} else if ($formatRole->integer($val) || $val === 'false' || $val === 'true' || $val === '0') {
				$res .= $val;
			} else {
				$res .= "'" . $val . "'";
			}
			$res .= ");\n";
		}
        file_put_contents($filePath, $res);
	}
}
