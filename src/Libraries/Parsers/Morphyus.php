<?php 
/**
 *
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\Parsers;

use phpMorphy;
use Throwable;
use App\Config\Boot\Morphy;

final class Morphyus 
{
    private array $profileDefault = [
        'ru' => [
            'С' => 5,
            'П' => 3,
            'КР_ПРИЛ' => 2,
            'ИНФИНИТИВ' => 4,
            'Г' => 5,
            'Н' => 3,
            'ДЕЕПРИЧАСТИЕ' => 0,
            'ПРИЧАСТИЕ' => 4,
            'КР_ПРИЧАСТИЕ' => 0,
            'МС' => 0,
            'МС-П' => 1,
            'МС-ПРЕДК' => 2,
            'ЧИСЛ' => 1,
            'ЧИСЛ-П' => 1,
            'ПРЕДК' => 0,
            'ПРЕДЛ' => 0,
            'ПОСЛ' => 0,
            'СОЮЗ' => 0,
            'МЕЖД' => 0,
            'ЧАСТ' => 0,
            'ВВОДН' => 0,
            'ФРАЗ' => 5
        ],
        'en' => [
            'ADJECTIVE' => 3,
            'NUMERAL' => 1,
            'ADVERB' => 3,
            'VERB' => 3,
            'MOD' => 0,
            'VBE' => 0,
            'PN' => 0,
            'PN_ADJ' => 0,
            'PRON' => 0,
            'NOUN' => 5,
            'CONJ' => 0,
            'INT' => 1,
            'PREP' => 0,
            'PART' => 0,
            'ARTICLE' => 4,
            'ORDNUM' => 0,
            'POSS' => 0
        ],
        'de' => [
            'ADJ' => 4,
            'PA1' => 0,
            'PA2' => 0,
            'ADV' => 4,
            'ART' => 0,
            'EIG' => 0,
            'INJ' => 0,
            'KON' => 0,
            'NEG' => 0,
            'PRO' => 0,
            'PROBEG' => 0,
            'PRP' => 0,
            'SUB' => 5,
            'ZUS' => 0,
            'VER' => 5,
            'INF' => 0,
            'ZAL' => 0
        ]
    ];

    private $locale;
    private $profile;
    private $phpmorphy;
    private $storeInFile;

    function __construct(string $lang = '') {
        if (defined('APP_DEFAULT_LOCALE') === false) {
           require APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Boot' . DIRECTORY_SEPARATOR . 'options.php';
        }
        include_once(Morphy::FILE_PHPMORPHY);

        if ($lang) { $this->setLanguage($lang); }
    }

    public function setStoreInFile(string $fileName = ''): void 
    {
        $this->storeInFile = $fileName; 
    }

    public function setLanguage(string $lang = APP_DEFAULT_LOCALE): void 
    {
        if (isset($this->phpmorphy) && $this->locale !== $lang) {
            $this->phpmorphy = null;
        }

        $this->storeInFile = '';
        $this->locale = (! $lang ? APP_DEFAULT_LOCALE : $lang);
        if (isset(Morphy::LANG_STANDART[$this->locale]) === false) { $this->locale = APP_DEFAULT_LOCALE; }

        $this->profile = (isset($this->profileDefault[$this->locale]) 
            ? $this->profileDefault[$this->locale]
            : $this->profileDefault['en']
        ); 

        if (! isset($this->phpmorphy))
        try {
            $this->phpmorphy = new phpMorphy(Morphy::PATH_DICTIONARY, Morphy::LANG_STANDART[$this->locale], Morphy::OPTIONS);
        } catch(Throwable $th) {
            die('Error occured while creating phpMorphy instance: ' . $th->getMessage());
        }
    }

    /**
     * Выполняет индексирование текста
     *
     * @param  {string}  content Текст для индексирования
     * @return {array}          Результат индексирования
     */
    public function makeIndex(string $text = ''): array 
    {
        $text = mb_strtoupper(strip_tags($text), 'UTF-8');
        if (! $text) { return []; } 
        // Фильтрация HTML-тегов и HTML-сущностей //
        //$text = preg_replace(self::REGEXP_ENTITY, ' ', $text);
        if ($this->locale === 'ru') { $text = str_ireplace('Ё', 'Е', $text); }
        // Выделение слов из контекста
        $wordsSrc = [];
        if (! preg_match_all(Morphy::REGEXP_WORD, $text, $wordsSrc)) { return []; }
        if (! isset($wordsSrc[1]) || ! $wordsSrc[1]) { return []; }
        $wordsSrc = $wordsSrc[1];
        
        $result = [];
        foreach($wordsSrc as $i => &$word) {
            $str = trim($word);
            $len = strlen($str);
            if (! isset($result[$str])) {
                $this->addWord($str, $result);
                if (Morphy::SEARCH_ONE_TO_MANY && $len > 2) { $this->lemmatize($str, $result); }
            } else if ($len > 6) {
                ++$result[$str]['count'];
            }
            unset($wordsSrc[$i]);
        }
        $wordsSrc = $this->endMake($result);
        if (! $this->storeInFile) { return $wordsSrc; }
        $this->indexTofile($wordsSrc);
        return $wordsSrc;
    }

    public function indexToStr(array $indexWords = []): string 
    {
        //return serialize($indexWords);
        return jsonEncode($indexWords, false);
    }

    public function strToIndex(string $text): array 
    {
        //return unserialize($text, ['allowed_classes' => true]);
        return jsonDecode($text);
    }

    private function indexTofile(array &$wordsSrc): void 
    {
        helper('path');
        $text = '';
        foreach ($wordsSrc as $word => &$range) {
            $text .= ",'" . $word . "'=>" . (int) $range;
            unset($wordsSrc[$word]);
        }
        if ($text) { $text = mb_substr($text, 1); }
        $text = '<?php return [' . $text . ']; ?>' . PHP_EOL;
        writingDataToFile($this->storeInFile, $text);
    }

    /**
     * Выполняет поиск ключевых слов одного индексного объекта в другом
     *
     * @param  string word строка
     * @param  array resurce Данные
     * @return void
     */
    private function lemmatize(string $word, array &$resurce): void
    {
        if (is_numeric($word) || strlen($word) < 3) { return; }
        $lemma = (Morphy::LEMMAT_ALL_FORMS === true 
            ? $this->phpmorphy->getAllForms($word) 
            : $this->phpmorphy->lemmatize($word)
        );
        if (! $lemma) { return; }
        foreach($lemma as $i => $str) {
            if (! isset($resurce[$str])) { $this->addWord($str, $resurce); }
            unset($lemma[$i]);
        }
        unset($lemma);
    }

    private function addWord(string $word, array &$resurce): void
    {
        $range = (strlen($word) > 6 ? $this->weigh($word) : Morphy::PART_SPEECH_DEFAULT);
        $resurce[$word] = [
            'count' => (int) 1,
            'range' => $range
        ];
    }

    private function endMake(array &$resurce): array
    {
        $result = [];
        foreach($resurce as $word => &$item) {
            $result[$word] = ($item['range'] ? $item['range'] * $item['count'] : $item['count']);
            unset($item['count'], $item['range'], $resurce[$word]);
        }
        unset($resurce);
        return $result;
    }
    
    /**
     * Оценивает значимость слова
     * 
     * @param  {string}  word    Исходное слово
     * @return {integer}         Оценка значимости
     */
    private function weigh(string $word): int 
    {
        if (Morphy::ASC_PART_OF_SPEECH === false) { return (int) Morphy::PART_SPEECH_DEFAULT; }
        // Попытка определения части речи 
        if (! $partsOfSpeech = $this->phpmorphy->getPartOfSpeech($word)) { 
            return (int) Morphy::PART_SPEECH_DEFAULT; 
        }
        // Определение ранга 
        $res = (int) 0;
        foreach($partsOfSpeech as $i => $ps) {
            $e = (int) Morphy::PART_SPEECH_DEFAULT;
            if (isset($this->profile[$ps])) {
                $e = (int) $this->profile[$ps];
            }
            if ($e > $res) { $res = $e; }
            unset($partsOfSpeech[$i]);
        }
        unset($partsOfSpeech);
        return $res;
    }
}