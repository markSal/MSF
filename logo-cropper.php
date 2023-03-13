<?php
// A very complicated but hopefully reliable logo resizer/cropper

// Checking for transparency in PNG files with PHP
// Source: https://christianwood.net/posts/png-files-are-complicate/
function isPngTransparent(string $file): bool{
	// PNG Types
	$PNG_GRAYSCALE = 0;
	$PNG_RGB = 2;
	$PNG_PALETTE = 3;
	$PNG_GRAYSCALE_ALPHA = 4;
	$PNG_RGBA = 6;

	// Bit offsets
	$ColorTypeOffset = 25;

	if($colorTypeByte = file_get_contents($file, false, null, $ColorTypeOffset, 1)){
		$type = ord($colorTypeByte);
		$image = imagecreatefrompng($file);

		// Palette-based PNGs may have one or more values that correspond to the color to use as transparent
		// PHP returns the first fully transparent color for palette-based images
		$transparentColor = imagecolortransparent($image);

		// Grayscale, RGB, and Palette-based images must define a color that will be used for transparency
		// if none is set, we can bail early because we know it is a fully opaque image
		if ($transparentColor === -1 && in_array($type, [$PNG_GRAYSCALE, $PNG_RGB, $PNG_PALETTE])){
			return false;
		}

		$xs = imagesx($image);
		$ys = imagesy($image);

		for ($x = 0; $x < $xs; $x++){
			for ($y = 0; $y < $ys; $y++){
				$color = imagecolorat($image, $x, $y);

				if($transparentColor === -1){
					$shift = $type === $PNG_RGBA ? 3 : 1;
					$transparency = ($color >> ($shift * 8)) & 0x7F;

					if(
					($type === $PNG_RGBA && $transparency !== 0) ||
					($type === $PNG_GRAYSCALE_ALPHA && $transparency === 0)
					){
						return true;
					}
				}else if($color === $transparentColor){
					return true;
				}
			}
		}
	}

	return false;
}


// Get average color brightness of image
function getImgColorBrightness($img_src){
	// Get image
	$image = imagecreatefromstring(file_get_contents($img_src));
	
	// Get width and height of image
	$width = imagesx($image);
	$height = imagesy($image);
	
	// Resize image down to 1x1 to get average color
	$pixel = imagescale($image, 1, 1);
	imagecopyresampled($pixel, $image, 0, 0, 0, 0, 1, 1, $width, $height);
	
	// Get average color of 1x1 image
	$rgb = imagecolorat($pixel, 0, 0);
	
	// Extract separate RGB values
	$r = ($rgb >> 16) & 0xFF;
	$g = ($rgb >> 8) & 0xFF;
	$b = $rgb & 0xFF;
	
	// Get average color as RGBA
	//$avg_color = imagecolorsforindex($pixel, $rgb);
	
	// Get average color as HEX
	//$hex_color = sprintf('#%02x%02x%02x', $r, $g, $b);

	// Calculate average color brightness
	$color_brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
	
	return $color_brightness;
}


// Create a new imagick object
$im = new Imagick();

// Define defaults
$bg_color = 'transparent';
$whiteLogo = false;

// Set crop/resize dimenions and added padding
$resize_w = 600;
$resize_h = 400;
$padding = 20;

// Check if PNG is transparent
if(isPngTransparent($img_src)){
	
	// Check if white logo
	$color_brightness_threshold = 230;
	$color_brightness = getImgColorBrightness($img_src);
	if($color_brightness > $color_brightness_threshold){
		// If only white add black background
		$bg_color = 'black';
		$whiteLogo = true;
	}

	// If transparent, read into imagick
	$im->readImage($img_src);
	
}else{
	
	// If not transparent, check for and remove any extra whitespace, then read into imagick
	$img = imagecreatefrompng($img_src);

	// Crop the extra white area of an image
	$crop_whitepace = imagecropauto($img, IMG_CROP_WHITE);
	
	// Capture blob from croppping result
	ob_start();
	imagepng($crop_whitepace);
	$img = ob_get_contents();
	ob_end_clean();
	
	// Destory cropping result
	imagedestroy($crop_whitepace);
	
	// Read blob into imagick
	$im->readImageBlob($img);
}

// Define background color of image
$im->setImageBackgroundColor(new ImagickPixel($bg_color));


// Deal with white logos on transparent backgrounds
if($whiteLogo){
	// Trim to logo edges
	$im->trimImage(0);
	
	// Resize image and offset to pad image (also accounts for transparent padding)
	$im->thumbnailImage(($resize_w - ($padding * 3)), ($resize_h - ($padding * 3)), true, true);
	
	// Add border to pad image
	$im->borderImage($bg_color, $padding, $padding);
}else{
	
	// Resize image and offset for transparent padding
	$im->thumbnailImage(($resize_w - $padding), ($resize_h - $padding), true, true);
}


// Make sure image is output as PNG
$im->setImageFormat('png');

// Add transparent padding and make sure image is exactly the height and width specified
$geometry = $im->getImageGeometry();
$width = $geometry['width'];
$height = $geometry['height'];
$im->setImageExtent($resize_w, $resize_h, ($width / 2), ($height / 2));
$im->borderImage('transparent', $padding, $padding);

// Save resized image
$im->writeImage($output_src);

?>
