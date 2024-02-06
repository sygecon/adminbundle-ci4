<?php

/// Декодирование строки Base64  ------------------------------------------
if (! function_exists('decodeTextBase64')) {
	function decodeTextBase64(string $data = ''): string
	{
		if (isset($data) && $data) {
			$pos = mb_strpos($data, 'base64,');
			if ($pos !== false) {
				$pos = $pos + 7;
				$data = mb_strcut($data, $pos, mb_strlen($data)); 
			}
			unset($pos);
			$data = base64_decode(trim($data));
		}
		return $data;
	}
}

/// Получение данных файла из PUT запроса   -------------------------------------------
if (! function_exists('getRequestPut')) {
	function getRequestPut(): object
	{
        $response = (object) ['data'=>'', 'name'=>'', 'path'=>'', 'ext'=>''];
		$request = \Config\Services::request();
		if ($request) {
			$buf = $request->getServer('HTTP_X_FILE_NAME');
			if (isset($buf)) {
				$response->name = $buf;
				$response->ext = mb_strtolower(pathinfo($buf)['extension']);
			} 
			$buf = $request->getServer('HTTP_X_FILE_PATH');
			if (isset($buf)) $response->path = $buf;
			$buf = file_get_contents('php://input');
			if (isset($buf)) {
				$response->data = decodeTextBase64($buf);
			}
			unset($buf);
		}
		return $response;
	}
}