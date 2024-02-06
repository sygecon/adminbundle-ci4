<?php 
/**
 * @author  Aspada.ru
 * @license http://www.opensource.org/licenses/bsd-license.php BSD 3-Clause License
 */
namespace Sygecon\AdminBundle\Libraries\HTML; 

use DOMDocument;
use DOMXPath;
use Throwable;

final class DomDoc
{
	private const REMOVE_TAGS = ['footer', 'iframe', 'style', 'script', 'noscript', 'link', 'pre', 'code'];

	public static function docAsArray(string $html = '', bool $onlyText = true): array
    {
        $result = ['h1' => '', 'title' => '', 'description' => '', 'content' => ''];
        if (! $html) { return $result; }
        try {
			if (! $dom = self::newDom($html)) { return $result; }
            
			self::domTagsRemove($dom, self::REMOVE_TAGS);
			$result['description'] = self::getMetaContent($dom, 'description');
            $result['title'] = self::getItemValue($dom, 'title');
            $result['h1'] = self::getItemValue($dom, 'h1');
            if ($onlyText === true) {
                $result['content'] = self::getItemValue($dom, 'body');
                $result['content'] = self::removeDoubleSpace($result['content']);
                return $result;
            }
            $result['content'] = self::getBody($dom, 'body');
            $result['content'] = preg_replace("'<!\[CDATA\[(.*?)\]\]>'is", '', $result['content']);
            $result['content'] = preg_replace("'<!--(.*?)-->'is", '', $result['content']);
            $result['content'] = preg_replace("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is", ' ', $result['content']);
            $result['content'] = trim(str_replace(['  ', '   '], ' ', $result['content']));
            return $result;
        } catch (Throwable $th) {
            return $result;
        }
    }

    public static function getElement(DOMDocument &$dom, string $html = '', string $tagName = '', bool $onlyText = false): string
    {
        if (! $html) { return ''; }
        if (! $tagName) { return ''; }
        try {
            $xpath = new DOMXPath($dom);
            // $xpath->evaluate('string(//head/title)')
            //trim(strip_tags($xpath->evaluate('string(//meta[@name="description"]/@content)')));
            $result = $xpath->evaluate('string(//' . $tagName . ')');
            $xpath = null;
            if ($onlyText === true) {
                return self::removeDoubleSpace(strip_tags($result));
            }
            return trim($result);
        } catch (Throwable $th) {
            return '';
        }
    }

    // private functions

    public static function newDom(string &$html): ?DOMDocument
	{
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);
        return ($dom instanceof DOMDocument ? $dom : null);
    }

    private static function getMetaContent(DOMDocument &$dom, string $tagName): string
	{
        if (! $nodes = $dom->getElementsByTagName('meta')) { return ''; }
        foreach($nodes as $meta) {
            if($meta->getAttribute('name') == $tagName) {
                return trim(strip_tags($meta->getAttribute('content')));
            }
        }
        return '';
    }

    private static function getItemValue(DOMDocument &$dom, string $tagName): string
	{
        if (! $nodes = $dom->getElementsByTagName($tagName)) { return ''; }
        if ($nodes->length === 0) { return ''; } 
        $nodes = $nodes->item(0);
        return trim(strip_tags($nodes->nodeValue));
    }

    private static function getBody(DOMDocument &$dom): string
	{
        if (! $nodes = $dom->getElementsByTagName('body')) { return ''; }
        if ($nodes->length === 0) { return ''; } 
        $nodes = $nodes->item(0);
        if ($body = $dom->saveHTML($nodes)) { return $body; }
        return '';
    }

	private static function domTagsRemove(DOMDocument &$dom, array $tags): void
	{
		$remove = [];
		foreach($tags as $i => $tag){
			$element = $dom->getElementsByTagName($tag);
			foreach($element as $item) { $remove[] = $item; }
			foreach ($remove as $key => $item) { 
				$item->parentNode->removeChild($item);
				unset($remove[$key]); 
			}
			$remove = [];
		}
		unset($remove);
	}

    private static function removeDoubleSpace(string $text): string
	{
        return trim(preg_replace('/\s+/', ' ', $text));
        // eturn trim(preg_replace('/[\s]{2,}/', ' ', $text));
    }
    // private static function removeNoise(string $text): string
	// {
        //$text = strtolower(strip_tags($text));  		
        //$text = preg_replace("/(?!['])\p{P}/u", "", $text);
        // remove punctuation  		 		
        /*$content = preg_replace("/\b(?<')(".implode('|', self::$noiseWords).")(?!')\b/",'',$content); */		 
        //$contentArray = explode(' ', $text);
        //return $content;
	// }
}
