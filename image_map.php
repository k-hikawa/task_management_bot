<?php
	// 古いフォルダは削除
	$list_dir = "img/list";
	$scan = scandir($list_dir);
	foreach($scan as $value){
		if(preg_match("/$user_id/", $value)){
			$delete_dir = $list_dir."/".$value;
			system("rm -rf {$delete_dir}");
		}
	}

	// ユーザ名＋時間で一意な新しいフォルダを作成
	$time = time();
	$dir = "img/list/".$user_id."_".$time;
	mkdir($dir);

	// タスク一覧画像生成
	require "create_image_functions.php";
	define("PIX", 1040);
	$image = imagecreatetruecolor(PIX, PIX);
	$font = "font/ヒラギノ丸ゴ ProN W4.ttc";
	$color = imagecolorallocate($image, 244, 244, 244);
	imagefill($image, 0, 0, $color);

	// 〆切が近い順にタスク一覧をカルーセルで表示する
	$task_names = array();
	$rival_progresses = array();
	foreach($pdo->query("SELECT * FROM line_task_table WHERE user_id='".$user_id."' AND (active=0 OR active=-1) ORDER BY deadline ASC") as $row){
		$task_id = $row['id'];
		$task_name = $row['name'];
		$task_progress = $row['progress'];
		$task_regist = $row['regist_time'];
		$task_deadline = $row['deadline'];
		// 他者の進捗を取得
		$time_rate = abs(($time - $task_regist) / ($task_deadline - $task_regist));
		$tag_sql = "";
		$task_tag_array = array();
		$rival_progress = array();
		foreach($pdo->query("SELECT * FROM line_task_log_table WHERE task_id=".$task_id." GROUP BY tag") as $row){
			array_push($task_tag_array, $row['tag']);
			if($tag_sql != ""){
				$tag_sql .= " OR ";
			}
			$tag_sql .= "tag='".$task_tag_array[count($task_tag_array)-1]."'";
		}
		// 現在の時間レート以下の最も大きい値を他者の進捗とする
		foreach($pdo->query("SELECT tag, task_logs.task_id, task_logs.progress, task_logs.time_rate FROM line_task_log_table AS task_logs INNER JOIN (SELECT task_id, MAX(time_rate) AS near FROM line_task_log_table WHERE time_rate<=".$time_rate." AND task_id != ".$task_id." GROUP BY task_id) AS task_log ON task_logs.task_id=task_log.task_id AND task_logs.time_rate=task_log.near WHERE (".$tag_sql.") GROUP BY task_id") as $row){
			$rival_id = $row['task_id'];
			foreach($pdo->query("SELECT * FROM line_task_table WHERE (active>0 OR (active=0 AND deadline<=86400+".$task_deadline.")) AND id=".$rival_id) as $row2){
				if($time_rate <= 1){
					array_push($rival_progress, $row['progress']);
				}else{
					array_push($rival_progress, 100);
				}
			}
		}
		array_push($task_names, $task_name);
		shuffle($rival_progress);
		array_push($rival_progresses, $rival_progress);
	}
	if(count($task_names) != 0){
		$line_num = count($task_names);
		$strlen = array();
		foreach($task_names as $val){
			array_push($strlen, strlen(mb_convert_encoding($val, 'SJIS', 'UTF-8')));
		}
		$charSize = PIX/max($strlen)+7;
		$lineSize = (PIX-300)/$line_num;
		if($charSize < $lineSize){
			$fontSize = $charSize;
		}else{
			$fontSize = $lineSize;
		}
		$fontX = 30;
		$line_color = imagecolorallocate($image, 0, 0, 0);
		$font_color = imagecolorallocate($image, 130, 130, 130);
		// タスク一覧の描画
		for($i=0; $i<$line_num; $i++){
			$fontY = floor(PIX/$line_num*$i+$fontSize)+10;

			// 背景にライバルの進捗を色で表現する
			for($j=0; $j<count($rival_progresses[$i]); $j++){
				$color = imagecolorallocate($image, convert($rival_progresses[$i][$j], 0), convert($rival_progresses[$i][$j], 1), convert($rival_progresses[$i][$j], 2));
				$setY = PIX/$line_num;
				$rivalY = $setY * $i;
				$rivalY2 = $setY * ($i+1);
				// タスク数が少なければライバル色の範囲が広くなりすぎないように狭める
				if($rivalY2 - $rivalY > 100){
					$rivalY2 = $setY * $i + 100;
				}
				imagefilledrectangle($image, PIX/count($rival_progresses[$i])*$j, $rivalY, PIX/count($rival_progresses[$i])*($j+1), $rivalY2, $color);
			}
			if($i != 0){
				imageline($image, 0, PIX/$line_num*$i, PIX, PIX/$line_num*$i, $line_color);
			}

			imagettftext($image, $fontSize, 0, $fontX, $fontY, $font_color, $font, $task_names[$i]);
		}

		// まずは1040サイズの画像を作る
		$img_path = $dir."/1040";
		imagepng($image, $img_path);
		
		// image_map用にさらに4種類のサイズにリサイズ	
		$size = array(240, 300, 460, 700);
		for($i=0; $i<count($size); $i++){
			resize($dir, $size[$i], $size[$i]);
		}

		$img_url = "https://nkmr.io/linebot/hikawa/task_system/".$dir;
		array_push($response_data, response_data("text", "一覧をタップすると詳細を確認できます。"));
		array_push($response_data, image_map($img_url, 1040, "タスクの詳細を見せて！"));
	}else{
		array_push($response_data, response_data("text", "登録しているタスクはありません。"));
	}
	imagedestroy($image);
?>