<?php
declare(strict_types=1);
/**
 * @author Panin Aleksei S <https://github.com/sygecon>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */
namespace Sygecon\AdminBundle\Libraries\HTML\Dom;

use App\Libraries\Loader\WebDoc;
use Sygecon\AdminBundle\Config\Paths;
use Sygecon\AdminBundle\Config\PageTypes;
use DOMDocument;
use DOMXPath;
use DOMElement;
use DOMNode;
use DOMNodeList;
use Throwable;

/*
Php $cssFiles = $xpath->evaluate('//link[@type="text/css"][@rel="stylesheet"][@href!=""]');
Php $elems = $xpath->evaluate('/html/body/*');
Php $first_element = $xpath->evaluate('/*[1]')->item(0);
Php $hrefs = $xpath->evaluate("//div[@id='content']");
Php $hrefs = $xpath->evaluate("/html/body//a");
Php $hrefs = $xpath->evaluate('body//a');
Php $imgFiles = $xpath->evaluate('//img[@src!=""]');

$xpathWithSrc = '//script[@src]';
$xpathWithBody = '//script[string-length(text()) > 1]';
preg_match_all('#<script(.*?)</script>#is', $content, $matches);

preg_match_all("/<a\s[^>]*href\s*=\s*([\"\']??)([^\"\' >]*?)\\1[^>]*>(.*)<\/a>/siU", $html, $prelinks, PREG_SET_ORDER);

preg_match_all('/"([^"]+?\.css)"/', $content, $matches);

===========================================
*/

final class Document 
{
    private const CHARSET = 'utf-8';

    private const LIST_HIDDEN_CLASSES = [
        'class=w3-hide', 'class=hide', 'class=hidden'
    ];

    private const RESULT_CALLBACK = [
        'outerHtml' => 1, 
        'html' => 1, 
        'innerText' => 1, 
        'text' => 1
    ];

    private const TAGS = [
        'img'   => 'src', 
        'a'     => 'href',
        'file'  => 'href',
        'pdf'   => 'href',
        'media' => 'src',
        'video' => 'src',
        'audio' => 'src',
    ];

    private const REGEX_JS            = '#(\s*<!--(\[if[^\n]*>)?\s*(<script.*</script>)+\s*(<!\[endif\])?-->)|(\s*<script.*</script>)#isU';
    private const SUBSTITUTION_FORMAT = '<!--<script class="script_%s"></script>-->';

    private $remoteScheme   = '';
    private $remoteHost     = '';
    private $remotePath     = '';
    private $rootPublicUrl  = '';
    private $returnResult   = 'outerHtml';
    private $queryHiddenClasses  = '';
    
    private int $lenRootPublicUrl   = 0;
    private array $allowFileExt     = [];
    private array $docFilesExt      = [];

    private array $metaNodes= [];
    private array $resource = [];
    private array $scripts  = [];
    private bool $asList    = false;

    private $selectedNode   = null;
    private $domXPath       = null;
    private $dom;

    public $rootNode        = null;
    public bool $clearAttrHidden   = false;

    /**
     * Constructor
     * @param string $rootPublicUrl
     */
    public function __construct(string $rootPublicUrl = '')
    {
        $this->setRootPublicUrl(($rootPublicUrl ? $rootPublicUrl : Paths::ROOT_PUBLIC_PATH));
        
        $this->docFilesExt = array_merge(
            PageTypes::FILE_FILTER_EXT['pdf'], PageTypes::FILE_FILTER_EXT['arc'], PageTypes::FILE_FILTER_EXT['doc'],
            PageTypes::FILE_FILTER_EXT['json'], PageTypes::FILE_FILTER_EXT['css'], PageTypes::FILE_FILTER_EXT['js']
        );
        $this->allowFileExt = array_merge(PageTypes::FILE_FILTER_EXT['image_media'], $this->docFilesExt);
    
        $this->queryHiddenClasses = '';
        foreach(self::LIST_HIDDEN_CLASSES as $attr) {
            if ($str = self::formatParameter(trim($attr))) { $this->queryHiddenClasses .= '|//@' . $str; }
        }
        if ($this->queryHiddenClasses) { $this->queryHiddenClasses = substr($this->queryHiddenClasses, 1); }
    }

    /** * Destructor */
    public function __destruct() 
    {
        $this->close();
        $this->scripts      = [];
        $this->resource     = [];
        $this->metaNodes    = [];
    }

    public function open(string $url = ''): void 
    {
        $this->asList           = false;
        $this->clearAttrHidden  = false;
        $this->metaNodes        = [];
        $this->close();

        if (! $res = parse_url($url)) { return; }
        if (! $this->remoteScheme = strtolower($res['scheme'])) { return; }

        $this->remoteHost       = '/' . strtolower(trim($res['host'], '/ ')) . '/';
        $this->remotePath       = strtolower(trim($res['path'], '/ '));
        $this->newDom(WebDoc::load($url));
    }

    public function openHtml(string $htmlContent = ''): void 
    {
        $this->metaNodes    = [];
        $this->asList       = false;
        $this->close();

        if (! $htmlContent) { return; }
        $this->newDom($htmlContent);
    }

    public function close(): void 
    {
        $this->remoteScheme     = '';
        $this->remoteHost       = '';
        $this->remotePath       = '';
        $this->rootNode         = null;
        $this->dom              = null;
        $this->domXPath         = null;
        $this->selectedNode     = null;
    }

    public function setRootNode(string $xpath = 'body'): void
    {
        if (! is_object($this->dom)) { return; }
        $this->rootNode = $this->query($this->dom, $xpath);
        if ($this->rootNode instanceof DOMDocument) { 
            $this->rootNode = $this->rootNode->documentElement; 
        }
    }

    public function setNode(null|DOMDocument|DOMNodeList|DOMNode $node = null): void
    {
        $this->selectedNode = $node;
        if ($this->selectedNode instanceof DOMDocument) { 
            $this->selectedNode = $this->selectedNode->documentElement; 
        }
    }

    public function notNullNode(): bool
    {
        return is_object($this->selectedNode);
    }

    public function getMetaNodes(): array
    {
        return $this->metaNodes;
    }

    public function getResource(): array
    {
        $result = $this->resource;
        $this->resource = [];
        return $result;
    }

    public function getScripts(): array
    {
        $result = $this->scripts;
        $this->scripts = [];
        return $result;
    }

    public function setReturnResult(string $callback = 'outerHtml', bool $asList = false): void 
    {
        if (isset(self::RESULT_CALLBACK[$callback])) { $this->returnResult = $callback; }
        $this->asList = $asList;
        if ($callback !== 'outerHtml' && $callback !== 'html') { $this->clearAttrHidden = false; }
    }
    
    public function findXPath(string $query = ''): void
    {
        if (! is_object($node = $this->getNode())) { return; }
        $this->selectedNode = $this->query($node, $query);
    }

    /**
     * Evaluates an XPath expression.
     *
     * Since an XPath expression might evaluate to either a simple type or a \DOMNodeList,
     * this method will return either an array of simple types or a new Crawler instance.
     */
    public function findString(string $tagName = '', string $attr = '', string $filter = ''): string 
    {
        if (! $query = $this->setQuery($tagName, $attr, $filter, 'string')) { 
            if ($this->notNullNode() === true) { $this->selectedNode = null; }
            return ''; 
        }
        if (is_object($node = $this->getNode()) === false) { return ''; }
        if (is_object($domxpath = $this->getDomXPath()) === false) { return ''; }
        try {
            $result = $domxpath->evaluate($query, $node);

            $this->removeNode($node);
            if ($this->notNullNode() === true) { $this->selectedNode = null; }
            if (is_string($result) === false) { return ''; }
            if (isset(self::TAGS[$tagName]) === false) { return $result; }
        
            $result = $this->normalizeUrl($result);
            $this->addLinkToResource($this->setFullUrl($result), self::getExtUrl($result));
            return $this->setLocalUrl($result);
        } catch (Throwable $th) {
            if ($this->notNullNode() === true) { $this->selectedNode = null; }
            return '';
        }
    }

    public function findText(string $attr = '', string $xpath = ''): string
    {
        
        if ($xpath) { 
            if (! is_object($this->selectedNode = $this->findQuery($xpath, '', $attr))) { return ''; }
        } else {
            if ($this->notNullNode() === false) { return ''; }
            if ($attr) { 
                if (! is_object($this->selectedNode = $this->selectedNode->getElementsByTagName($attr)->item(0))) { return ''; } 
            }
        }
        
        if ($this->selectedNode instanceof DOMDocument) {
            $result = $this->selectedNode->textContent;
        } else {
            $result = $this->selectedNode->nodeValue;
            $this->removeNode($this->selectedNode);
        }

        $this->selectedNode = null;
        return self::stripTags($result);
    }

    public function findDomQuery(string $tagName = '', string $attr = '', string $filter = ''): mixed 
    {
        if (! $query = $this->setQuery($tagName, $attr, $filter, '')) { 
            if ($this->notNullNode() === true) { $this->selectedNode = null; }
            return null; 
        }
        if (is_object($node = $this->getNode()) === false) { return null; }
        if (is_object($domxpath = $this->getDomXPath()) === false) { return null; }

        try {
            $result = $domxpath->query($query, $node);
            $this->removeNode($node);
            if ($this->notNullNode() === true) { $this->selectedNode = null; }
            if (is_object($result) === false) { return null; }
            return $result;
        } catch (Throwable $th) {
            if ($this->notNullNode() === true) { $this->selectedNode = null; }
            return null;
        }
    }
    
    public function findLang(): string
    {   
        $result = APP_DEFAULT_LOCALE;
        if (isset($this->metaNodes['lang']) && $this->metaNodes['lang']) { 
            $result = $this->metaNodes['lang']; 
        }
        return $result;
    }

    public function findMeta(string $name = '', string $attr = 'name'): string
    {   
        if (! $this->metaNodes) { return ''; }
        $result = '';

        if (isset($this->metaNodes[$attr][$name])) { 
            $result = $this->metaNodes[$attr][$name]; 
            unset($this->metaNodes[$attr][$name]);
        }

        if (isset($this->metaNodes[$name])) {
            $result = $this->metaNodes[$name];
            unset($this->metaNodes[$name]);
        }
        return $result;
    }

    public function findH1(int $pos = 0, string $tagName = 'h1'): string
    {   
        if (! is_object($nodeList = $this->getNode())) { return ''; }
        if (! is_object($listNode = $nodeList->getElementsByTagName($tagName))) { 
            $this->selectedNode = null;
            return ''; 
        }
        if (! is_object($node = $listNode->item($pos))) { 
            $this->selectedNode = null;
            return ''; 
        }

        $result = $node->nodeValue;
        $this->removeNode($node);
        $this->selectedNode = null;
        return $result;
    }

    public function findResource(string $tagName = 'a'): string
    {   
        if ($this->notNullNode() === false) { return '[]'; }

        $result = [];
        if ($this->asList === true && $this->selectedNode->hasChildNodes() === true) {
            foreach ($this->selectedNode->childNodes as $childNode) { 
                $result[] = $this->getMediaResource($childNode, $tagName);
            }
        } else {
            $result[] = $this->getMediaResource($this->selectedNode, $tagName);
        }
        
        $this->removeNode($this->selectedNode);
        $this->selectedNode = null;
        if (! $result) { return '[]'; }
        return self::trimJson(jsonEncode($result, false));
    }

    public function findAttribute(string $tagName = 'img'): string
    {
        if (is_object($node = $this->getNode()) === false) { return ''; }
        if (isset(self::TAGS[$tagName]) === false) { return ''; }
        $link = '';
        $attr = self::TAGS[$tagName];  

        foreach($node->getElementsByTagName($tagName) as $image) {
            if (is_object($image) === true && $image->hasAttribute($attr) === true) {
                $link = $this->normalizeUrl($image->getAttribute($attr));
                $this->addLinkToResource($this->setFullUrl($link), self::getExtUrl($link));
                $link = $this->setLocalUrl($link);
                $this->removeNode($image);
                break;
            }
        }

        if ($this->notNullNode() === true) { $this->selectedNode = null; }
        return $link;
    }

    /**
     * Get an element in the document by it's id attribute
     */
    public function find(): string
    {
        if ($this->notNullNode() === false) { return ($this->asList === true ? '[]' : ''); }

        if ($this->returnResult === 'outerHtml' || $this->returnResult === 'html') { 
            foreach (['img', 'a'] as &$tag) {
                $this->getAttributes($this->selectedNode, $tag);
            }
        }

        $this->clearHtmlEmpty();

        if ($this->asList === true && $this->selectedNode->hasChildNodes() === true) {
            $htmlContent = '';
            $result = [];
            foreach ($this->selectedNode->childNodes as $childNode) { 
                if (! $htmlContent = $this->{$this->returnResult}($childNode)) { continue; }
                $this->getMediaFromContent($htmlContent);
                $result[] = $htmlContent;
            }
        } else {
            $result = $this->{$this->returnResult}($this->selectedNode);
            $this->getMediaFromContent($result);
        }
        
        $this->removeNode($this->selectedNode);
        $this->selectedNode = null;

        if (! $result) { return (is_array($result) ? '[]' : ''); }
        return (is_array($result) 
            ? self::trimJson(jsonEncode($result, false))
            : $result
        );
    }

    /**
     * Returns the first node of the list as HTML.
     * @param null|DOMDocument|DOMNodeList|DOMNode $node
     * @param string $xpath
     */
    public function query(null|DOMDocument|DOMNodeList|DOMNode $node = null, string $xpath = ''): mixed
    {
        if (! $xpath) { return null; }
        if (is_object($node) === false) { 
            if (is_object($node = $this->rootNode) === false) { return null; }
        }
        
        if (! $tags = $this->explodeQuery($xpath)) { return null; } 

        foreach($tags as &$value) {
            if (! is_object($node = $this->treatNode($node, $value))) { return null; }
        }
        return $node;
    }

    public function findQuery(string $xpath = '', string $filter = '', string $attr = '', string $prefix = ''): mixed
    {
        if (! $query = $this->setQuery($xpath, $attr, $filter, $prefix)) { return null; }
        if (! is_object($domxpath = $this->getDomXPath())) { return null; }
        
        if ($this->notNullNode() === true) { 
            return $domxpath->query($query, $this->selectedNode);
        }
        
        if (! is_object($this->rootNode)) { return null; }
        return $domxpath->query($query, $this->rootNode);
    }

    // Deleting a node
    public function clearNode(string $query = ''): void
    {
        if (! $query) { return; }
        if (! is_object($node = $this->query($this->getNode(), $query))) { return; }
        $this->removeNode($node);
    }

    /**
     * Returns the text of the first node of the list.
     * Pass true as the second argument to normalize whitespaces.
     * @param string|null $default             When not null: the value to return when the current node is empty
     * @param bool        $normalizeWhitespace Whether whitespaces should be trimmed and normalized to single spaces
     */
    public function text(DOMNodeList|DOMNode|null $node, bool $normalizeWhitespace = true): string
    {
        if (! is_object($node)) { return ''; }

        $text = $node->nodeValue;
        if ($normalizeWhitespace) { return self::normalizeWhitespace($text); }
        return $text;
    }

    public function innerText(DOMNodeList|DOMNode|null $node): string
    {
        if (! is_object($node)) { return ''; }
        $normalizeWhitespace = 1 <= \func_num_args() ? func_get_arg(0) : true;

        foreach ($node->childNodes as $childNode) {
            if (\XML_TEXT_NODE !== $childNode->nodeType) { continue; }
            if (! $normalizeWhitespace) { return $childNode->nodeValue; }
            if ('' !== trim($childNode->nodeValue)) {
                return self::normalizeWhitespace($childNode->nodeValue);
            }
        }
        return '';
    }

    /**
     * Returns the first node of the list as HTML.
     * @param DOMNodeList|DOMNode|array|null $node
     */
    public function html(DOMNodeList|DOMNode|null $node): string
    {
        if (! is_object($node)) { return ''; }

        $owner = $node->ownerDocument;
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $owner->saveHTML($child);
        }
        return $html;
    }

    public function outerHtml(DOMNodeList|DOMNode|array|null $node): string
    {
        if (! is_object($node)) { return ''; }
        if (! isset($node->ownerDocument)) { return ''; }
        $owner = $node->ownerDocument;
        return $owner->saveHTML($node);
    }

    // ==== Private functions ====================================================

    private function treatNode(DOMDocument|DOMNodeList|DOMNode|null &$node, array $attr): mixed
    {
        if (! $node->hasChildNodes()) { return null; }
        $step = (int) 0;
        $nodes = [];
        foreach ($node->childNodes as $childNode) { // iterator_to_array
            if ($childNode->nodeName === $attr['tag']) {

                if (! $attr['selector']) {
                    if (isset($attr['pos']) === false) {
                        return $childNode;
                    }

                    if ($step === $attr['pos']) { 
                        return $childNode;
                    }
                    ++$step;
                } else

                if ($childNode->hasAttribute($attr['selector']) === true && $childNode->getAttribute($attr['selector']) === $attr['value']) {
                    return $childNode;
                }
            } 

            if ($childNode->hasChildNodes()) { $nodes[] = $childNode; }
        }

        if ($nodes) {
            foreach ($nodes as &$childNode) { 
                if (is_object($childNode = $this->treatNode($childNode, $attr)) === true) {
                    return $childNode;
                }
            }
        }
        return null;
    }

    // ================================================
    private function getMediaResource(DOMDocument|DOMNodeList|DOMNode|null $node, string $tagName): array
    {
        if (! isset(self::TAGS[$tagName])) { return []; }
        if ($tagName === 'file' || $tagName === 'link') { $tagName = 'a'; }

        if (in_array($tagName, ['img', 'a'])) {
            return $this->getAttributes($node, $tagName);
        }

        if (! $text = $this->outerHtml($node)) { return []; }

        if ($tagName !== 'media') {
            return $this->getMediaTagsFromHTML($text, $tagName);
        }

        $result = [];
        foreach(['video', 'audio'] as $value) {
            if ($res = $this->getMediaTagsFromHTML($text, $value)) {
                $result[$value] = $res;
                $res = [];
            }
        }
        return $result;
    }

    private function getMediaFromContent(string &$htmlContent): void
    {
        if ($this->returnResult === 'outerHtml' || $this->returnResult === 'html') { 
            foreach (['video', 'audio'] as $value) {
                $this->getMediaTagsFromHTML($htmlContent, $value);
            }
        }
    }

    // ================================================
    
    private function removeNode(DOMDocument|DOMNodeList|DOMNode|null &$node): void
    {
        if ($node === null) { return; }
        try {
            $parentNode = $node->parentNode;
            if (isset($parentNode) === true && is_null($parentNode) === false) { 
                $parentNode->removeChild($node); 
            }
        } catch (Throwable $th) { return; }
    }

    private function clearHtmlEmpty(): void 
    {
        if ($this->notNullNode() === false) { return; }

        foreach ($this->selectedNode as $htmlAttribute) {
            if (isset($htmlAttribute->nodeValue) === false) { continue; }
            $trimmedValue = trim($htmlAttribute->nodeValue);
            if ($htmlAttribute->childNodes->length === 0 ||
                ltrim($htmlAttribute->firstChild->nodeName, '#') === 'text' && 
                $htmlAttribute->childNodes->length === 1 && 
                empty($trimmedValue)) {
                    $this->removeNode($html_attribute);
            }
        }

        if (is_object($domxpath = $this->getDomXPath()) === false) { return; }

        if (is_object($nodeList = $domxpath->query('//meta', $this->selectedNode))) {
            foreach ($nodeList as $entry) { $this->removeNode($entry); }
        }

        if (is_object($nodeList = $domxpath->query('//link', $this->selectedNode))) {
            foreach ($nodeList as $entry) { $this->removeNode($entry); }
        }

        if (is_object($nodeList = $domxpath->query("//@itemscope|//@itemprop|//@itemtype|//@itemid|//@itemref", $this->selectedNode))) {
            foreach ($nodeList as $entry) {
                $nodeName = $entry->nodeName;
                if ($nodeName === 'meta' || $nodeName === 'link') {
                    $this->removeNode($entry);
                } else {
                    $entry->parentNode->removeAttribute($nodeName);
                }
            }
        }

        if ($this->clearAttrHidden === false) { return; } 
        if (! $this->queryHiddenClasses) { return; } 

        if (is_object($nodeList = $domxpath->query($this->queryHiddenClasses, $this->selectedNode))) {
            foreach ($nodeList as $entry) { $this->removeNode($entry); }
        }
    }

    private function setQuery(string $xpath = '', string $attr = '', string $filter = '', string $prefix = ''): string
    {
        $query = '';
        if ($filter) { 
            $query = '[@' . trim(self::formatParameter($filter), '[@] ') . ']'; 
        }
        if ($attr) { $query .= '/@' . ltrim($attr, '/@ '); }
        $xpath .= $query;
        if (! $xpath) { return ''; }
        if ($prefix) { return $prefix . '(//'. trim($xpath, '(/) ') . ')'; }
        return '//'. trim($xpath, '/ ');
    }

    private function &getNode(DOMDocument|DOMNodeList|DOMNode|null $node = null): mixed
    {
        if (is_object($node)) { return $node; }
        if ($this->notNullNode() === true) { return $this->selectedNode; }
        return $this->rootNode;
    }

    private function &getDomXPath(): ?DOMXPath
    {
        if ($this->domXPath instanceof DOMXPath) { return $this->domXPath; }
        if (! $this->dom instanceof DOMDocument) { return $this->domXPath; }

        $this->domXPath = new DOMXPath($this->dom);
        return $this->domXPath;
    }

    private function addLinkToResource(string $link, string $ext): void
    {
        if (! $ext) { return; }
        if (! $link) { return; }

        if ($ext === 'js') {
            if (isset($this->resource['script']) === false) { $this->resource['script'] = []; }
            $resource = &$this->resource['script'];
        } else
        if ($ext === 'css') {
            if (isset($this->resource['style']) === false) { $this->resource['style'] = []; }
            $resource = &$this->resource['style'];
        } else
        if (in_array($ext, $this->docFilesExt)) {
            if (isset($this->resource['doc']) === false) { $this->resource['doc'] = []; }
            $resource = &$this->resource['doc'];
        } else 
        if (in_array($ext, PageTypes::FILE_FILTER_EXT['image'])) {
            if (isset($this->resource['image']) === false) { $this->resource['image'] = []; }
            $resource = &$this->resource['image'];
        } else 
        {
            if (isset($this->resource['media']) === false) { $this->resource['media'] = []; }
            $resource = &$this->resource['media'];
        }

        if (in_array($link, $resource) === false) { $resource[] = $link; }
    }

    /**
	 * @param DOMDocument|DOMElement|null $node
     * @param string $tag
	 */
	private function getAttributes(DOMDocument|DOMNodeList|DOMNode|null $node, string $tag): array
    {
        if (! is_object($node)) { return []; }
        if (isset(self::TAGS[$tag]) === false) { return []; }
        try {
            if (! is_object($nodeList = $node->getElementsByTagName($tag))) { return []; }
        } catch (Throwable $th) {
            return [];
        }

        $attr = self::TAGS[$tag];
        $type = ($tag === 'img' ? 'image' : 'link');
        $result = [];

        foreach($nodeList as $item) {
            if (! $linkBuf = $item->getAttribute($attr)) { continue; }
            
            $link = $this->normalizeUrl($linkBuf);
            $ext = self::getExtUrl($link);
            $elem = PageTypes::FILE_DEFAULT_VARIABLES[$ext] ?? PageTypes::FILE_DEFAULT_VARIABLES[$type];
            if (array_key_exists('text', $elem) === true) { $elem['text'] = self::stripTags($item->nodeValue); }
            if (array_key_exists($attr, $elem) === false) {
                if ($attr === 'href') { $attr = 'src'; } else { $attr = 'href'; }
            }

            if (in_array($ext, $this->allowFileExt) === true) { 
                $this->addLinkToResource($this->setFullUrl($link), $ext);
            }
            
            $link = $this->setLocalUrl($link);

            if ($type === 'link') {
                if ($imag = $item->getElementsByTagName('img'))
                    try {
                        $img = $imag->item(0);
                        if ($src = $img->getAttribute('src')) {
                            $src = $this->normalizeUrl($src);
                            $this->addLinkToResource($this->setFullUrl($src), self::getExtUrl($src));
                            
                            $src = $this->setLocalUrl($src);
                            $img->setAttribute('src', $src); 
                            $elem['img-text'] .= ' src="' . $src . '" alt="' . self::stripTags($img->getAttribute('alt')) . '" title="' . self::stripTags($img->getAttribute('title')) . '"';
                        }
                        $img = null;
                    } catch (Throwable $th) { $imag = null; }
            }

            $elem[$attr] = $link;
            foreach(['title', 'alt'] as $key) {
                try {
                    if (! $value = $item->getAttribute($key)) { continue; }
                    $elem[$key] = self::stripTags($value);
                } catch (Throwable $th) { continue; }
            } 
            $result[] = $elem;

            if ($linkBuf !== $link) { $item->setAttribute($attr, $link); }
        }
        return $result;
    }

    /**
	 * @param string $сontent
     * @param string $tag
	 */
	private function getMediaTagsFromHTML(string &$content, string $tag): array 
    {
        if (! $content) { return []; }
        if (isset(self::TAGS[$tag]) === false) { return []; }
        
        $attr = self::TAGS[$tag];
        $pattern = '/\s*(src|href|poster|width|height|alt|title)\s*="?([^>"]*)"/si';
        $change = false;
        $pos = 1;
        $result = [];
        
        while ($start = mb_stripos($content, '<' . $tag, $pos)) { 
            $pos = $start + 1;
            if (! $end = mb_stripos($content, '</' . $tag . '>', ++$start)) { continue; }
            $pos = $end + 1;
            $text = mb_substr($content, $start, $end - $start);
            $matches = [];
            // find attributes links
            if (! preg_match_all($pattern, $text, $matches, PREG_PATTERN_ORDER)) { continue; }
            if (! isset($matches[2])) { continue; }

            $item = PageTypes::FILE_DEFAULT_VARIABLES[$tag];

            foreach(array_unique($matches[2]) as $i => $value) {
                $paramName = $matches[1][$i];

                if ($paramName !== 'src' && $paramName !== 'href' && $paramName !== 'poster') {
                    $item[$paramName] = self::stripTags($value);
                    continue;
                }

                $link = $this->normalizeUrl($value);
                $ext = self::getExtUrl($link); 
                
                if (! in_array($ext, PageTypes::FILE_FILTER_EXT['image_media']) && ! isset(PageTypes::FILE_DEFAULT_VARIABLES[$ext])) { continue 2; }
                
                $this->addLinkToResource($this->setFullUrl($link), $ext);

                $link = $this->setLocalUrl($link);
                $item[$paramName] = $link;
                
                if ($value !== $link) {
                    $change = true;
                    $text = str_replace($value, $link, $text);
                }
            }

            if ($change === true) {
                $content = mb_substr($content, 0, $start) . $text . mb_substr($content, $end);
            }

            if ($item[$attr]) { $result[] = $item; }
        }
        return $result;
    }

	/**
	 *
	 * @param string $HTMLContent
	 */
	private function getMetaTagsFromHTML(string &$HTMLContent): void 
    {
        $this->metaNodes = [];
        if (! $pos = mb_stripos($HTMLContent, '</head>')) { return; }
        $header = mb_substr($HTMLContent, 0, $pos);

        // find lang tag
        $this->metaNodes['lang'] = preg_match('/<html lang="([^"]+)"/si', $header, $matches) ? $matches[1] : '';
        
        // find title tag
        $matches = [];
        preg_match('/<title>([^>]*)<\/title>/si', $header, $matches);
        if (isset($matches[1])) {
            $this->metaNodes['title'] = self::stripTags($matches[1]);
            unset($matches[1], $matches[0]);
        }
        
        // find meta tags
        $matches = [];
        preg_match_all('/<meta([^>]*)>/si', $header, $matches, PREG_PATTERN_ORDER);
        if (! isset($matches[1])) { return; }
        
        foreach ($matches[1] as &$value) {
            preg_match('/\s*(http\-equiv|property|itemprop|name)="?([^>"]*)"?\s*content="?([^>"]*)"?[\s]*[\/]?[\s]*/si', $value, $match);
            if (isset($match[3])) {
                $this->metaNodes[strtolower(trim($match[1]))][strtolower(trim($match[2]))] = self::stripTags($match[3]);
                continue;
            }
            $match = explode('=', $value, 2);
            if (isset($match[1])) {
                $this->metaNodes[strtolower(trim($match[0], '\\/" '))] = strtolower(trim(self::stripTags($match[1]), '\\/"'));
            }
        }
	}

    private function clearScripts(string &$htmlContent): void
    {
        $this->scripts = [];
        $matches = [];
        if (! preg_match_all(self::REGEX_JS, $htmlContent, $matches, PREG_PATTERN_ORDER)) {
            return;
        }

        $rootLink = $this->rootPublicUrl . 'js/';
        $lenRoorPath = strlen($rootLink);

        foreach ($matches[0] as $match) {
            $storedScript = trim($match, '\n\r\t ');
            
            if (preg_match('/src[\s]?=[\s]?"?([^>"]*.js)"?/is', $storedScript, $output_array)) {
                if (! $link = trim($output_array[1], chr(39) . '"\n\r\t ')) { continue; }
                $ext = self::getExtUrl($link);
                if ($ext !== 'js') { continue; }

                if (! $res = parse_url($link)) { continue; }
                if (! $path = (isset($res['path']) === true ? strtolower(trim($res['path'], '/ ')) : '')) { continue; }
                $host = (isset($res['host']) === true ? '/' . strtolower(trim($res['host'], '/ ')) . '/' : '');
                if ($host && $host !== $this->remoteHost) { continue; }
                $path = '/' . $path;

                $this->addLinkToResource($this->setFullUrl($path), $ext);

                if (substr($path, 0, $lenRoorPath) !== $rootLink) { 
                    if (substr($path, 0, 4) !== '/js/') { 
                        $path = $rootLink . ltrim($path, '/'); 
                    } else {
                        $path = $this->rootPublicUrl . ltrim($path, '/');
                    }
                }

                $var = '';
                if (preg_match('/\s(async|defer|nomodule|type\s?=\s?"?module"?)\s/is', $storedScript, $output_array)) {
                    $var = trim($output_array[1]) . ' ';
                }

                $storedScript = '<script ' . $var . 'src="' . $path . '"></script>';
            } 

            $key = md5($storedScript);
            $this->scripts[$key] = $storedScript;
            $htmlContent = str_replace($match, sprintf(self::SUBSTITUTION_FORMAT, $key), $htmlContent);
        }
    }

    /**
     * Converts charset to HTML-entities to ensure valid parsing.
     */
    private function convertToHtmlEntities(string $htmlContent, string $charset): string
    {
        set_error_handler(function () { throw new Throwable(); });

        try {
            return mb_encode_numericentity($htmlContent, [0x80, 0x10FFFF, 0, 0x1FFFFF], $charset);
        } catch (Throwable $th) {
            try {
                $htmlContent = iconv($charset, 'UTF-8', $htmlContent);
                $htmlContent = mb_encode_numericentity($htmlContent, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
            } catch (Throwable $th) { }

            return $htmlContent;
        } finally {
            restore_error_handler();
        }
    }

    private function newDom(string $htmlContent): void
	{
        if (! $htmlContent) { 
            $this->close();
            return; 
        }

        $this->getMetaTagsFromHTML($htmlContent);
        $charset = strtoupper(self::CHARSET);
        if (isset($this->metaNodes['charset']) && $this->metaNodes['charset'] && 
            strtoupper($this->metaNodes['charset']) !== $charset) {
                $charset = strtoupper($this->metaNodes['charset']);
        }
        $htmlContent = $this->convertToHtmlEntities($htmlContent, $charset);

        $this->clearScripts($htmlContent);

        $internalErrors = libxml_use_internal_errors(true);

        $this->dom = new DOMDocument('1.0', $charset);
        $this->dom->validateOnParse = true;
        $this->dom->preserveWhiteSpace = false;
        if ('' !== trim($htmlContent)) {
            @$this->dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }

        libxml_use_internal_errors($internalErrors);
        // $this->rootNode = $this->dom->getElementsByTagName('body')->item(0);
        $this->rootNode = $this->treatNode($this->dom, ['tag' => 'body', 'selector' => '']);
    }

    private function setRootPublicUrl(string $link): void
    {
        $this->rootPublicUrl = str_replace('//', '/', '/' . strtolower(trim(str_replace('\\', '/', $link), '/')) . '/');
        $this->lenRootPublicUrl = strlen($this->rootPublicUrl);
    }
    
    private function normalizeUrl(string $url): string
    {
        $url = strtolower(trim(cutQueryFromUrl($url), chr(39) . '"\n\r\t '));
        $pos = strpos($url, $this->remoteHost);
        if ($pos !== false) {
            $pos += strlen($this->remoteHost);
            return '/' . ltrim(substr($url, $pos), '/');
        }

        if (! $url) { return ''; }
        if (substr($url, 0, 1) === '/') { return $url; }
        return '/' . $this->remotePath . '/' . $url;
    }

    private function setFullUrl(string $url): string
    {
        return $this->remoteScheme . ':/' . $this->remoteHost . substr($url, 1);
    }

    private function setLocalUrl(string $url): string
    {
        if (substr($url, 0, $this->lenRootPublicUrl) === $this->rootPublicUrl) { return $url; }
        return $this->rootPublicUrl . ltrim($url, '/');
    }

    // Parse Query
    private function explodeQuery(string $query): array
    {
        $result = [];
        $query = trim($query);
        $pos = 0;
        $start = 0;
        $len = strlen($query);
        while ($pos = strpos($query, '/', $pos)) {
            if ($res = self::explodeQueryAttr(substr($query, $start, ($pos - $start)))) { 
                $result[] = $res; 
            }
            $start = ++$pos;
        }
        if ($len > $start) {
            if ($res = self::explodeQueryAttr(substr($query, $start, ($len - $pos)))) { 
                $result[] = $res; 
            }
        }
        return $result;
    }
    
    //
    private static function getExtUrl(string $url): string
    {
        return getName($url, '.');
    }

    private static function explodeQueryAttr(string $query = ''): array
    {
        $result = ['tag' => 'div', 'selector' => '', 'value' => ''];
        $tag = trim($query);
        $pos = strpos($tag, '@');
        if ($pos !== false) {
            $result['selector'] = substr($tag, ($pos + 1));
            $tag = substr($tag, 0, $pos);
        }
        $pos = strpos($tag, ':');
        if ($pos !== false) {
            $result['pos'] = (int) substr($tag, ($pos + 1));
            $tag = substr($tag, 0, $pos);
        }
        if ($tag) { $result['tag'] = $tag; }

        if (! $result['selector']) { return $result; }
        if ($pos = strpos($result['selector'], '=')) {
            $result['value'] = str_replace([chr(39), '"'], '', substr($result['selector'], ($pos + 1)));
            $result['selector'] = substr($result['selector'], 0, $pos);
        }
        return $result;
    }

    private static function normalizeWhitespace(string $string): string
    {
        return trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $string), " \n\r\t\x0C");
    }

    private static function trimJson(string $text): string
    {
        if (mb_substr($text, 0, 2) === '[[' && mb_substr($text, -2) === ']]') {
            $text = mb_substr(mb_substr($text, 1), 0, -1);
        }
        return $text;
    }

    private static function stripTags(string $text): string 
    {
        return stripslashes(trim(strip_tags($text)));
    }

    private static function formatParameter(string $text): string 
    {
        if (! $pos = strpos($text, '=')) { return $text; }
        return trim(substr($text, 0, $pos)) . '="' . trim(substr($text, ++$pos), chr(39) . '" ') . '"';
    }
}