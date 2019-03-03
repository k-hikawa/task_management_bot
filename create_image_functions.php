<?php
	// 矩形の開始座標を更新する関数
	function next_rect_startPoint($startPoint, $rival_rect, $num){
		if(($num+1)%($rival_rect[2]+1) == 0){
			$startPoint[1] += $rival_rect[1];
			$startPoint[0] = 0;
		}else{
			$startPoint[0] += $rival_rect[0];
		}
		return $startPoint;
	}

	function convert($progress, $rgb){
		$progressColor = 255/100 * $progress;
		switch($rgb){
			case 0:
				$color = r($progressColor);
				break;
			case 1:
				$color = g($progressColor);
				break;
			case 2:
				$color = b($progressColor);
				break;
		}
		return $color;
	}

	function r($grey){
		if($grey < 128){
			$r = 0;
		}else if($grey > 127 && $grey < 191){
			$r = ($grey - 127) * 4;
		}else{
			$r = 255;
		}
		return $r;
	}

	function g($grey){
		if($grey < 64){
			$g = $grey * 4;
		}else if($grey >= 64 && $grey <= 191){
			$g = 255;
		}else{
			$g = 256 - ($grey -191) * 4;
		}
		return $g;
	}

	function b($grey){
		if($grey <= 64){
			$b = 255;
		}else if($grey > 64 && $grey < 127){
			$b = 255 - ($grey - 64) * 4;
		}else{
			$b = 0;
		}
		return $b;
	}

	function resize($dir, $width, $height){
		// 新規サイズを取得します
		list($width_orig, $height_orig) = getimagesize($dir."/1040");

		$ratio_orig = $width_orig / $height_orig;

		if($width/$height > $ratio_orig) {
			$width = $height * $ratio_orig;
		}else{
			$height = $width / $ratio_orig;
		}

		// 再サンプル
		$image_p = imagecreatetruecolor($width, $height);
		$image = imagecreatefrompng($dir."/1040");
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

		// 出力
		imagepng($image_p, $dir."/".$width);
		imagedestroy($image_p);
	}
?>