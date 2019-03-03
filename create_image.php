<?php
	$image = imagecreatetruecolor(WIDTH, HEIGHT);
	define("MY_RANGE", 100);
	$rival_height = HEIGHT - MY_RANGE;
	$rival_num = count($rival_progress); // ライバルの数
	$startPoint = array(0, 0); // ライバルの矩形を描画する開始座標
	$map_x = 30; // ヒートマップ見本の幅

	// ライバルの矩形の分かれ目を白く見せるために背景色を白にする
	$color = imagecolorallocate($image, 255, 255, 255);
	imagefill($image, 0, 0, $color);

	// 自分の進捗エリアの描画
	$color = imagecolorallocate($image, convert($task_progress, 0), convert($task_progress, 1), convert($task_progress, 2));
	imagefilledrectangle($image, 0, $rival_height+5, WIDTH-$map_x, HEIGHT, $color);
			$color = imagecolorallocate($image, 0, 0, 0);
	imagefilledrectangle($image, 0, $rival_height-2, WIDTH-$map_x, $rival_height+5, $color);
	imagefilledrectangle($image, 0, HEIGHT-10, WIDTH-$map_x, HEIGHT, $color);
	imagefilledrectangle($image, 0, $rival_height-2, 7, HEIGHT, $color);
	imagefilledrectangle($image, WIDTH-$map_x-7, $rival_height-2, WIDTH-$map_x, HEIGHT, $color);


	// ヒートマップ見本を表示
	for($i=0; $i<=100; $i++){
		$color = imagecolorallocate($image, convert(100-$i, 0), convert(100-$i, 1), convert(100-$i, 2));
		imagefilledrectangle($image, WIDTH-$map_x, ($i)*(HEIGHT/100), WIDTH, ($i)*(HEIGHT/100)+(HEIGHT/100), $color);
	}

	// ライバル矩形の大きさを設定するロジック
	$check = 2;
	$plus = 1;
	$once = false;
	$xline = 0;
	$yline = 0;
	$cnt = 0;
	while($rival_num >= $check){
		if($once){
			$plus++;
			$once = false;
		}else{
			$once = true;
		}
		$check += $plus;
		$cnt++;
	}
	if($rival_num == 0 || $rival_num == 1){
		$xline = 0;
		$yline = 0;
	}else{
		if($cnt%2 == 0){
			$xline = $cnt/2;
			$yline = $cnt/2;
		}else{
			$xline = ceil($cnt/2);
			$yline = floor($cnt/2);
		}
	}
	$xsize = WIDTH-$map_x;
	$ysize = $rival_height;
	if($xline != 0){
		$xsize = (WIDTH-$map_x)/($xline+1);
	}
	if($yline != 0){
		$ysize = $rival_height/($yline+1);
	}
	$rival_rect = array($xsize, $ysize, $xline, $yline); // ライバル矩形情報

	for($i=0; $i<$rival_num; $i++){
		$color = imagecolorallocate($image, convert($rival_progress[$i], 0), convert($rival_progress[$i], 1), convert($rival_progress[$i], 2));
		imagefilledrectangle($image, $startPoint[0], $startPoint[1], $startPoint[0]+$rival_rect[0]-2, $startPoint[1]+$rival_rect[1]-2, $color);
		$startPoint = next_rect_startPoint($startPoint, $rival_rect, $i); // 矩形の開始座標を更新する
	}

	$color = imagecolorallocate($image, 255, 255, 255);
	imagestring($image, 5, WIDTH-20, HEIGHT-30, "0", $color);
	$color = imagecolorallocate($image, 0, 0, 0);
	imagestring($image, 5, WIDTH-25, HEIGHT/2-5, "50", $color);
	imagestring($image, 5, WIDTH-30, 20, "100", $color);

	imagepng($image, $img_path);
	imagedestroy($image);
?>