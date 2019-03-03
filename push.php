<?php
	// push版を使ってタスク一覧の通知を送る
	define(ACCESSTOKEN, '6fKtFn2rnibKaafF1E54Uf5Cc3iTuDmLzgu7QhBIwB6cb4dNYbtHLNwIsC4i7OxxWocf0cRcE+wWJDnF6IQBmhBE3w3bzLK73ZrcFC1wLnYpREzuxNhzeYratWh+ydTtPhCr9MAaNFFd89H+HepcBwdB04t89/1O/w1cDnyilFU=');
	//DB接続
	$dsn = 'mysql:host=localhost;unix_socket=/tmp/mysql.sock;dbname=hikawa_db;';
	$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
	$username = 'nakamura-lab';
	$password = 'n1k2m3r4fms';
	try{
		$pdo = new PDO($dsn, $username, $password, $options);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}catch(PDOException $e){
		print 'Connection faild '.$e->getMessage();
		die();
	}
	require "linebot_functions.php"; // linebotの機能の関数
	require "table_check.php"; // テーブルがなければ作成する
	require "deadline_check.php";
	require "create_image_functions.php";
	require "status_change.php"; // ユーザのステータスの変更を行う関数
	require "original_functions.php";
	$response_data = array();

	$line_id = array();
	$time = time();
	$type = "push";

	foreach($pdo->query("SELECT line_id, last_time FROM line_task_user_table") as $row){
		if($time-$row['last_time'] > 86400){
			array_push($line_id, $row['line_id']);
			$user_id = $row['line_id'];
			foreach($pdo->query("SELECT * FROM line_task_table WHERE user_id='".$user_id."' AND (active=0 OR active=-1) ORDER BY deadline ASC") as $row){
				$task_cnt++;
				if($task_cnt == 6){
					$list_mode = true;
					break;
				}
			}
			if($list_mode){
				array_push($response_data, push_image_map($pdo, $user_id, $time));
			}else{
				array_push($response_data, push_task_list($pdo, $user_id, $time));
			}
			$sql = "UPDATE line_task_user_table SET last_time=:last_time WHERE line_id=:user_id";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(":last_time", $time, PDO::PARAM_INT);
			$stmt->bindParam(":user_id", $user_id, PDO::PARAM_STR);
			$stmt->execute();
			require "get_log.php";
		}
	}

	push_message(ACCESSTOKEN, $line_id, $response_data);

	if(count($response_data) != 0){
		response(ACCESSTOKEN, $replyToken, $response_data);
	}
	$pdo = null;

	function push_image_map($pdo, $user_id, $time){
		// 古いフォルダは削除
		$list_dir = "../../var/www/html/linebot/hikawa/task_system/img/list";
		$scan = scandir($list_dir);
		foreach($scan as $value){
			if(preg_match("/$user_id/", $value)){
				$delete_dir = $list_dir."/".$value;
				system("rm -rf {$delete_dir}");
			}
		}

		// ユーザ名＋時間で一意な新しいフォルダを作成
		$dir = "../../var/www/html/linebot/hikawa/task_system/img/list/".$user_id."_".$time;
		mkdir($dir);

		// タスク一覧画像生成
		define("PIX", 1040);
		$image = imagecreatetruecolor(PIX, PIX);
		$font = "../../var/www/html/linebot/hikawa/task_system/font/ヒラギノ丸ゴ ProN W4.ttc";
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

			$img_url = "https://nkmr.io/linebot/hikawa/task_system/img/list/".$user_id."_".$time;
			//array_push($response_data, response_data("text", "一覧をタップすると詳細を確認できます。"));
			return image_map($img_url, 1040, "タスクの詳細を見せて！");
		}
		imagedestroy($image);
	}


	function push_task_list($pdo, $user_id, $time){
		// ライバルの進捗の平均が高い順にタスク一覧をカルーセルで表示する
		$task_names = array();
		$task_progresses = array();
		//$task_deadlines = array();
		$task_tags = array();
		$task_texts = array();
		//$task_diffs = array();
		$lanks = array();
		$task_imgs = array();
		$task_ids = array();

		define("WIDTH", 750);
		define("HEIGHT", 500);

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
			shuffle($rival_progress);
			
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

			$filename = $task_id."_".$time.".png";

			imagepng($image, "../../var/www/html/linebot/hikawa/task_system/img/task/".$filename);
			imagedestroy($image);

			$task_img = "https://nkmr.io/linebot/hikawa/task_system/img/task/".$filename;

			$my_lank = get_array_lank($rival_progress, $task_progress);
			if($my_lank == 1 && $task_progress != 0){
				$mark = "👑";
			}else{
				$mark = "";
			}
			$member_num = count($rival_progress)+1;
			$lank_text = $mark.$my_lank."位 /".$member_num;
			$task_text = "進捗：".$task_progress."%"." (".$lank_text.")\n";
			$task_text .= "〆切：".date('Y/m/d H:i', $task_deadline)."\n";
			$count_down = ceil(($task_deadline - $time)/(24*60*60))-1;
			if($count_down > 0){
				$task_text .= "残り：".$count_down."日";
			}else if($count_down == 0){
				$count_down_m = ceil(($task_deadline - $time)/60);
				$count_down = ceil(($task_deadline - $time)/(60*60));
				if($count_down_m%60 <= 30 && $count_down != 0){
					$count_down = ceil(($task_deadline - $time)/(60*60))-1;
				}
				$task_text .= "残り：".$count_down."時間";
			}else{
				$task_text .= "〆切を過ぎています！";
			}

			// 進捗順位の割合を出す
			$lose_cnt = 0;
			foreach($rival_progress as $val){
				if($task_progress < $val){
					$lose_cnt++;
				}
			}
			if(count($rival_progress) != 0){
				$lank = $lose_cnt/count($rival_progress);
			}else{
				$lank = 0;
			}

			// カルーセルに入れる変数たちにセット
			array_push($task_names, $task_name);
			array_push($task_progresses, $task_progress);
			//array_push($task_deadlines, $task_deadline);
			array_push($task_tags, $task_tag_array);
			array_push($task_texts, $task_text);
			array_push($lanks, $lank);
			//array_push($task_diffs, $task_diff);
			array_push($task_imgs, $task_img);
			array_push($task_ids, $task_id);
		}
		// ライバルの進捗の平均とに負けている順のインデックスを取得しておく
		//arsort($lanks);
		$order = array();
		foreach($lanks as $key => $value){
			array_push($order, $key);
		}

		// カルーセルに6以上項目が入らないようにする
		if(count($order) > 5){
			$loop_count = count($order);
			for($i=5; $i<$loop_count; $i++){
				unset($order[5]);
				$order = array_values($order);
			}
		}
		// 5回ずつに分けて送信する
		while(count($order) > 0){
			$labels = array();
			$return_texts = array();
			$task_imgs5 = array();
			$task_names5 = array();
			$task_texts5 = array();
			$task_ids5 = array();
			if(count($order) >= 5){
				$loop_count = 5;
			}else{
				$loop_count = count($order);
			}
			for($i=0; $i<$loop_count; $i++){
				array_push($task_names5, $task_names[$order[0]]);
				array_push($task_imgs5, $task_imgs[$order[0]]);
				array_push($task_texts5, $task_texts[$order[0]]);
				array_push($task_ids5, $task_ids[$order[0]]);
				array_push($labels, array("進捗入力", "タスク削除"));
				array_push($return_texts, array($task_ids[$order[0]]."progressmenu", $task_ids[$order[0]]."delete1"));
				unset($order[0]);
				$order = array_values($order);
			}
			return carousel_message(
				$task_imgs5,
				$task_names5,
				$task_texts5,
				$labels,
				$return_texts,
				"postback"
			);
		}
	}
?>