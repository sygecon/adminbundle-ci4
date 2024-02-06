<?php 
	$nullUrl = [
		'/' . SLUG_ADMIN . '/import', 
		'/' . SLUG_ADMIN . '/user', 
		'/' . SLUG_ADMIN . '/template', 
		'/' . SLUG_ADMIN . '/component'
	];

	if (current_url() != '/') {
		echo '<ol class="breadcrumb float-sm-right">' .
			'<li class="breadcrumb-item"><a href="/" target="_blank">' . lang('Admin.goHome') . '</a></li>';
		$link = uri_string();
		$path = explode('/', $link);
		if (count($path) > 1) {
			$pathPrint = '';
			$len = count($path);
			for ($i = 0; $i < $len; $i++) {
				if ($i == $len - 1) {
					echo '<li class="breadcrumb-item active">' . str_replace(['%7C', '|'], '&#92;', $path[$i]) . '</li>';
				} else {
					$pathPrint .= '/' . $path[$i];
					$resStr = 'javascript:void(0)';
					if (!in_array($pathPrint, $nullUrl)) {
						$resStr = base_url($pathPrint);
					}
					echo '<li class="breadcrumb-item"><a href="' . $resStr . '">' . $path[$i] . '</a></li>';
				}
			}
		} else {
			echo '<li class="breadcrumb-item active">' . $link . '</li>';
		}
		echo '</ol>';
	}
