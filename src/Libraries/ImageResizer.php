<?php
namespace Sygecon\AdminBundle\Libraries;

class ImageResizer
{
	protected $imageInfo;
	protected $imageFile; 
	protected $imageData; 
	protected $resize = false;

	public $err = '';

	public $newWidth;
	public $newHeight;

	/**
	 * @param string $imgfile [ image path or url ]
	*/
	public function __construct(string $imgfile) {
		$this->err !== '';
		if ($imgfile) {
			$imgex = mb_strtolower(pathinfo($imgfile)['extension']);
			if($imgex === 'png' || $imgex === 'jpg' || $imgex === 'jpeg' || $imgex === 'pjpeg' || $imgex === 'gif' || $imgex === 'webp' || $imgex === 'xbm') {
				$this->imageInfo = getimagesize($imgfile);
				if($this->imageInfo === false) { $this->err = 'Error! Not Info.'; }
				$this->imageFile = $imgfile;
			} else {
				$this->err = "Error! Not load Extension.";
			}
		}
	}

	/**
	 * @param intiger $width  [new image width 100 = 100px]
	 * @param intiger $height [new image width 100 = 100px]
	 */
	public function setSize(?int $width = null, ?int $height = null)
	{
		$this->resize = FALSE;
		if ($this->err !== '') return;	
		if(!is_null($width)) {
			$this->newWidth  = $width;
			$this->resize = TRUE;
		} else {
			$this->newWidth = (int) $this->imageInfo[0];
		}	
		if(!is_null($height)) {
           $this->newHeight = $height;
		   $this->resize = TRUE;
		} else {
 		   $this->newHeight = (int) $this->imageInfo[1];
		}
	}

	// Set maximum image size (pixels)
    public function setMaxSize(int $size = 175) {
		$this->resize = FALSE;
		if ($this->err !== '') return;	
		$width = (int) $this->imageInfo[0];
		$height = (int) $this->imageInfo[1];
        // Resize
        if($width > $size && $height > $size) {
            // Wide
            if($width >= $height) {
                $this->newWidth = $size;
                $this->newHeight = ($this->newWidth / $width) * $height;
            } else {
                $this->newHeight = $size;
                $this->newWidth = ($this->newHeight / $height) * $width;
            }
            // Ready
            $this->resize = TRUE;
        }
    }

	/**
	 * 
	 * @param  string  $showType [image show type : png or jpg or gif]
	 * @param  integer $quality  [image show quality 100 = 100%]
	 * @return void   
	 */
	public function show(string $showType = 'jpeg', int $quality = 80) {
		if ($this->err !== '') return;
		if (headers_sent($file, $line)) {
			$this->err = 'Error';
			return;
		}
		$showType = mb_strtolower($showType);
		$quality = $this->getQuality($showType, $quality);
		$img = $this->getResizedImage();
		if ($img) {
			if($showType === 'jpeg' || $showType === 'jpg' || $showType === 'pjpeg') {
				header('Content-type: image/jpeg');
				imagejpeg($img, null, $quality);
			} else if($showType === 'png') {
				header('Content-type: image/png');
				imagepng($img, null, $quality);
			} else if($showType === 'gif') {
				header('Content-type: image/gif');
				imagegif($img);
			} else if($showType === 'webp') {
				header('Content-type: image/webp');
				imagewebp($img, null, $quality);
			} else if($showType === 'xbm') {
				header('Content-type: image/xbm');
				imagexbm($img, $this->imageFile);
			}
			imagedestroy($img);
		}
	}

	/**
	 * 
	 * @param  string $pathName [ full path and file name ex : 'images_dir/image_name.png']
	 * @return boolean
	 */
	public function saveTo(string $pathName, int $quality = 80) {
		if ($this->err !== '') { return copy($this->imageFile, $pathName); } 
		if (!$this->resize) { return copy($this->imageFile, $pathName); }
		$type = mb_strtolower(pathinfo($pathName)['extension']);
		if(!is_writable(dirname($pathName))) exit('failed to open stream: Permission denied');
		$quality = $this->getQuality($type, $quality);
		$img = $this->getResizedImage();
		if ($img) {
			if($type === 'jpeg' || $type === 'jpg' || $type === 'pjpeg') {
				$res = imagejpeg($img, $pathName, $quality);	
			} else if($type === 'png') {
				$res = imagepng($img, $pathName, round($quality / 10));	
			} else if($type === 'gif') {
				$res = imagegif($img, $pathName);		
			} else if($type === 'webp') {
				$res = imagewebp($img, $pathName, $quality);	
			} else if($type === 'xbm') {
				$res = imagexbm($img, $pathName);	
			} else {
				$res = false;
			}
			imagedestroy($img);
			return $res;
		}
		return false;
	}

	protected function getQuality(string $type, int $quality)
	{
		$type = mb_strtolower($type);
		if($type === 'png') {
			if($quality <= 90) {
				return $quality / 10;
			} else {
				return 9;
			}
		} else {
			return $quality;
		}
	}

	protected function getResizedImage()
	{
		if ($this->resize) {
			$type = mb_strtolower(substr($this->imageInfo['mime'], 6));
			if($type === 'jpeg' || $type === 'jpg' || $type === 'pjpeg') {
				$this->imageData = imagecreatefromjpeg($this->imageFile);
			} else if($type === 'png') {
				$this->imageData = imagecreatefrompng($this->imageFile);
			} else if($type === 'gif') {
				$this->imageData = imagecreatefromgif($this->imageFile);
			} else if($type === 'webp') {
				$this->imageData = imagecreatefromwebp($this->imageFile);
			} else if($type === 'xbm') {
				$this->imageData = imagecreatefromxbm($this->imageFile);
			}
		}
		$resizedImage = imagecreatetruecolor($this->newWidth, $this->newHeight);
		if ($this->resize) {
			// preserve transparency
			if($type == "gif" || $type == "png"){
				imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
			} else {
				imagecolortransparent($resizedImage, imagecolorallocate($resizedImage, 0, 0, 0));
			}
			imagealphablending($resizedImage, false);
			imagesavealpha($resizedImage, true);
			imagecopyresampled($resizedImage, $this->imageData, 0, 0, 0, 0, $this->newWidth, $this->newHeight, $this->imageInfo[0], $this->imageInfo[1]);
		}
		return $resizedImage; 
	}


}
