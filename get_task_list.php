<?php
	// 〆切が近い順にタスク一覧をカルーセルで表示する
	$task_names = array();
	$task_progresses = array();
	$task_deadlines = array();
	$task_tags = array();
	$task_texts = array();
	$task_imgs = array();
	$task_ids = array();

	$time = time();
	define("WIDTH", 750);
	define("HEIGHT", 500);
	require "create_image_functions.php";
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
		$count_down = ceil(($task_deadline - $time)/(24*60*60))-1;
		$deadline_late = false;
		if($count_down > 0){
			$deadline_text = "残り：".$count_down."日";
		}else if($count_down == 0){
			$count_down_m = ceil(($task_deadline - $time)/60);
			$count_down = ceil(($task_deadline - $time)/(60*60));
			if($count_down_m%60 <= 30 && $count_down != 0){
				$count_down = ceil(($task_deadline - $time)/(60*60))-1;
			}
			$deadline_text = "残り：".$count_down."時間";
		}else{
			$deadline_text = "〆切を過ぎています！";
			$deadline_late = true;
		}
		// 現在の時間レート以下の最も大きい値を他者の進捗とする
		foreach($pdo->query("SELECT tag, task_logs.task_id, task_logs.progress, task_logs.time_rate FROM line_task_log_table AS task_logs INNER JOIN (SELECT task_id, MAX(time_rate) AS near FROM line_task_log_table WHERE time_rate<=".$time_rate." AND task_id != ".$task_id." GROUP BY task_id) AS task_log ON task_logs.task_id=task_log.task_id AND task_logs.time_rate=task_log.near WHERE (".$tag_sql.") GROUP BY task_id") as $row){
			$rival_id = $row['task_id'];
			foreach($pdo->query("SELECT * FROM line_task_table WHERE (active>0 OR (active=0 AND deadline<=86400+".$task_deadline.")) AND id=".$rival_id) as $row2){
				if(!$deadline_late){
					array_push($rival_progress, $row['progress']);
				}else{
					array_push($rival_progress, 100);
				}
			}
		}
		shuffle($rival_progress);

		$img_path = "img/task/".$task_id."_".$time.".png";

		require "create_image.php";

		$task_img = "https://nkmr.io/linebot/hikawa/task_system/".$img_path;

		$my_lank = get_array_lank($rival_progress, $task_progress);
		if($my_lank == 1 && $task_progress != 0){
			$mark = "👑";
		}else{
			$mark = "";
		}
		$member_num = count($rival_progress)+1;
		$lank_text = $mark.$my_lank."位 /".$member_num;
		$task_text = "進捗：".$task_progress."%"." (".$lank_text.")\n";
		$task_text .= "〆切：".date('Y/m/d H:i', $task_deadline)."\n".$deadline_text;
		
		
			/* カルーセルの文章にタグをつける場合はコメントアウトを外してください
			for($i=0; $i<count($task_tag_array); $i++){
				if($i != 0){
					$task_text .= " ";
				}
				$task_text .= "#".$task_tag_array[$i];
			}
			*/

		// カルーセルに入れる変数たちにセット
		array_push($task_names, $task_name);
		array_push($task_progresses, $task_progress);
		array_push($task_deadlines, $task_deadline);
		array_push($task_tags, $task_tag_array);
		array_push($task_texts, $task_text);
		//array_push($task_rival_progresses, $rival_progress);
		array_push($task_imgs, $task_img);
		array_push($task_ids, $task_id);
	}
	// カルーセルに26以上項目が入らないようにする
	if(count($task_names) > 25){
		$loop_count = count($task_names);
		for($i=25; $i<$loop_count; $i++){
			unset($task_names[25]);
			$task_names = array_values($task_names);
		}
	}
	// 5回ずつに分けて送信する
	while(count($task_names) > 0){
		$labels = array();
		$return_texts = array();
		$task_imgs5 = array();
		$task_names5 = array();
		$task_texts5 = array();
		$task_ids5 = array();
		if(count($task_names) >= 5){
			$loop_count = 5;
		}else{
			$loop_count = count($task_names);
		}
		for($i=0; $i<$loop_count; $i++){
			array_push($task_names5, $task_names[0]);
			array_push($task_imgs5, $task_imgs[0]);
			array_push($task_texts5, $task_texts[0]);
			array_push($task_ids5, $task_ids[0]);
			array_push($labels, array("進捗入力", "タスク削除"));
			array_push($return_texts, array($task_ids[0]."progressmenu", $task_ids[0]."delete1"));
			unset($task_names[0]);
			unset($task_imgs[0]);
			unset($task_texts[0]);
			unset($task_ids[0]);
			$task_names = array_values($task_names);
			$task_imgs = array_values($task_imgs);
			$task_texts = array_values($task_texts);
			$task_ids = array_values($task_ids);
		}
		array_push($response_data,
			carousel_message(
				$task_imgs5,
				$task_names5,
				$task_texts5,
				$labels,
				$return_texts,
				"postback"
			)
		);
	}
	if(count($response_data) == 0){
		array_push($response_data, response_data("text", "登録しているタスクはありません。"));
	}
?>