<?php

//// Парсинг скриптов
if (!function_exists('ext_style_and_scripts')) {
    function ext_style_and_scripts($url) {
        if (pathinfo($url, PATHINFO_EXTENSION) == 'js') return 1;
        if (in_array(pathinfo($url, PATHINFO_EXTENSION), ['css', 'scss'])) return 2;
        return 0;
    }
}

if (!function_exists('parse_style_and_scripts')) {
    function parse_style_and_scripts($_tag, &$xpath, &$res) {
        $_tag = $_tag;
        if ($_tag == 'img' || $_tag == 'script' || $_tag == 'iframe' || $_tag == 'source')
            $attribute = 'src';
        else
            $attribute = 'href';
        if ($_tag == 'style') $attribute = '';
        $tags = $xpath->query("//$_tag");
        if ($tags->length) {
            $i = 0;
            while ($tag = $tags->item($i++)) {
                if ($attribute) {
                    $src = html_entity_decode(str_replace(["\n", "\r", ' '], '', cleaningText($tag->getAttribute($attribute))));
                    if ($src) {
                        $src = str_replace([chr(39), ' ', '"', ',', ';'], '', $src);
                        $ext = ext_style_and_scripts($src);
                        if ($ext === 1) {
                            $src = '<script src="' . $src . '"></script>';
                        } else if ($ext === 2) {
                            $src = '<link rel="stylesheet" href="' . $src . '">';
                        }
                        if ($ext && !in_array($src, $res)) $res[] = $src;
                    } else {
                        $text = trim(html_entity_decode($tag->textContent));
                        if ($text) $res[] = '<' . $_tag . '> ' . $text . ' </' . $_tag . '>';
                    }
                } else {
                    $text = trim(html_entity_decode($tag->textContent));
                    if ($text) $res[] = '<' . $_tag . ' type="text/css">' . $text . '</' . $_tag . '>';
                }
                $tag->parentNode->removeChild($tag);
            }
            $tags = null;
        }
    }
}

if (!function_exists('parsing_scripts')) {
    function parsing_scripts($html = '', $var_tag) {
        $res = [];
        if (isset($html) && $html && isset($var_tag) && $var_tag) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML('<!DOCTYPE html><html lang=""><head><meta charset="utf-8"></head><body>' . trim($html) . '</body></html>');
            $xpath = new \DOMXPath($dom);
            if ($dom) {
                if (is_array($var_tag)) {
                    foreach ($var_tag as $var) {
                        parse_style_and_scripts($var, $xpath, $res);
                    }
                } else if (is_string($var_tag)) {
                    parse_style_and_scripts($var_tag, $xpath, $res);
                }
                $text = trim($dom->textContent);
                $xpath = null;
                $dom = null;
                if ($text) {
                    $text = str_replace(["\n\r", "\n\r", ' ', ',', ';'], "\n", cleaningText(html_entity_decode($text)));
                    $text = str_replace("\r", "\n", $text);
                    $text = str_replace("\n\n", "\n", $text);
                    $text = str_replace([chr(39), ' ', '"', ',', ';'], '', $text);
                    $text = str_replace("\n\n", "\n", $text);
                }
                if ($text) {
                    $resStr = explode("\n", $text);
                    foreach ($resStr as $i => $var) {
                        $ext = ext_style_and_scripts($var);
                        if ($ext === 1) {
                            $var = '<script src="' . $var . '"></script>';
                        } else if ($ext === 2) {
                            $var = '<link rel="stylesheet" href="' . $var . '">';
                        }
                        if ($ext && !in_array($var, $res)) $res[] = $var;
                    }
                }
            }
            if ($res)
                $res = array_diff($res, array('', NULL, false)); //sort ($res, SORT_NATURAL);

        }
        return $res;
    }
}
