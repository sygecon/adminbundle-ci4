<?php

/// Декодирование строки Base64  ------------------------------------------
if (! function_exists('decodeTextBase64')) {
	function decodeTextBase64(string $text): string
	{
		if (! $text = trim($text)) return '';
		$pos = mb_strpos($text, 'base64,');
		if ($pos !== false) {
			$pos = $pos + 7;
			$text = mb_strcut($text, $pos, mb_strlen($text)); 
		}
		unset($pos);
		return base64_decode(trim($text));
	}
}

/// Получение данных файла из PUT запроса   --------------------------------
if (! function_exists('getRequestPut')) {
	function getRequestPut(): object
	{
        $response = (object) ['data'=>'', 'name'=>'', 'path'=>'', 'ext'=>''];
		if ($request = \Config\Services::request()) {
			if ($name = $request->getServer('HTTP_X_FILE_NAME')) {
				$response->name = $name;
				$response->ext = mb_strtolower(pathinfo($name)['extension']);
			} 
			if ($path = $request->getServer('HTTP_X_FILE_PATH')) $response->path = $path;
			if ($data = file_get_contents('php://input')) $response->data = decodeTextBase64($data);
		}
		return $response;
	}
}