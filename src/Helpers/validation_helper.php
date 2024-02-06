<?php

use org\bovigo\vfs\content\StringBasedFileContent;

if (! function_exists('poststrtoint'))
{
      function poststrtoint ( $text ) {
            if (! is_numeric($text)) {
                  return (int) preg_replace('/[^0-9]/', '', $text);
            } else {
                  return (int) $text;
            }
      }
}


if (! function_exists('reduce_double_slashes'))
{
      function reduce_double_slashes (string $str): string
      {
            return preg_replace('#(^|[^:])//+#', '\\1/', $str);
      }
}

if (! function_exists('escape_html'))
{
      function escape_html ( $text ) {
            return str_replace(['<', '>', '"', chr(39)], ['&lt;', '&gt;', '&quot;', '&#039;'], stripslashes (trim($text)));
      }
}

if (! function_exists('htmltobase'))
{
      function htmltobase ( $value ) {  
            $value = is_array($value) ? array_map('htmltobase', $value) : escape_html ($value);
            return $value;
      }
}
if (! function_exists('decode_html')) {
      function decode_html ( $text ) {
               return str_replace(['&lt;', '&gt;', '&quot;', '&#039;'], ['<', '>', '"', chr(39)], stripslashes ($text));
      }
}

if (! function_exists('basetohtml')) {
      function basetohtml ( $value ) {         
            $value = is_array($value) ? array_map('basetohtml', $value) : decode_html ($value);
            return $value;
      }
}

if (! function_exists('stringlen'))
{
      function stringlen ( string $text, int $len = 250, $marker = '' ) {         
            return mb_strimwidth ($text, 0, $len, $marker);
      }
}       

if (! function_exists('blocks_to_option'))
{
      function blocks_to_option (array $data, $addname = false, $valName = true, int $selId = 0) {
            $res = '';
            if (isset($data) && $data) {
                  foreach($data as $var) {
                        $val = (int) $var['id'];
                        $title = '';
                        $sel   = '';
                        if (isset($var['hint']) && $var['hint']) { $title = ' title="' . $var['hint'] . '" '; }
                        if (isset($selId) && $selId && $val == $selId) $sel = 'selected="1"';
                        if ($valName && isset($var['name'])) { $val .= '#' . $var['name']; }
                        if ($addname && isset($var['name'])) {
                              $res .= '<option ' . $title . 'value="' . $val . '" ' . $sel . '>' . ucfirst (mb_strimwidth ($var['description'], 0, 30, ' ..')) . ' [' . mb_strimwidth ($var['name'], 0, 15, ' ..') . '] </option>';
                        } else {
                              $res .= '<option ' . $title . 'value="' . $val . '" ' . $sel . '>' . ucfirst (mb_strimwidth ($var['description'], 0, 30, ' ..')) . '</option>';
                        }
                  }
            } 
            return $res;
      }
}

if (! function_exists('array_to_option'))
{
      function array_to_option (array $array) {
            $res = '';
            if (isset($array) && $array) {
                  foreach($array as $i => $var) {
                        $res .= '<option value="' . $i . '">' . ucfirst (mb_strimwidth ($var, 0, 30, ' ..')) . '</option>';
                  }
            } 
            return $res;
      }
}
// Удалить PHP код
if (! function_exists('php_clear'))
{
      function php_clear (string $str): StringBasedFileContent
      {
           return preg_replace('/<\?(?:php|=|\s+).*?\?>/s', '', $str);
      }
}
