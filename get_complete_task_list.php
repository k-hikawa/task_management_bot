<?php
	// 直近25までのタスク達成時のライバル進捗画像と達成した日付をカルーセルで表示する
	$img_array = scandir("img/task/", 1);
	/*$res = "";
	for($i=0; $i<count($img_array)-2; $i++){
		$res .= $img_array[$i]."\n";
	}
	array_push($response_data, response_data("text", $res));*/
	$domain = "https://nkmr.io/linebot/hikawa/task_ststem/img/task/";
	$task_ids = array();
	$imgs = array();
	$task_names = array();
	$texts = array();
	foreach($pdo->query("SELECT t.id AS task_id, t.name AS task_name, MAX(l.load_time) AS complete_time, t.lank AS lank, t.rival_num AS rival_num FROM line_task_log_table AS l INNER JOIN line_task_table AS t ON l.task_id=t.id WHERE t.user_id='".$user_id."' AND (t.active=1 OR t.active=-2) GROUP BY t.id ORDER BY l.load_time DESC") as $row){
		array_push($task_ids, $row['task_id']);
		for($i=0; $i<count($img_array)-2; $i++){
			$temp = explode("_", $img_array[$i]);
			if(intval($temp[0]) == $task_ids[count($task_ids)-1]){
				array_push($imgs, $domain.$img_array[$i]);
				break;
			}
		}
		array_push($task_names, $row['task_name']);
		$complete_time = "達成した日：".date('Y/m/d H:i', $row['complete_time']);
		$my_lank = $row['lank'];
		$member_num = $row['rival_num'];
		if($my_lank == 1){
			$mark = "👑";
		}else{
			$mark = "";
		}
		$lank_text = "順位：".$mark.$my_lank."位 /".$member_num;
		array_push($texts, $complete_time."\n".$lank_text);
		if(count($imgs) < count($task_ids)){
			array_push($imgs, $domain.$task_ids);
		}
	}
	
	// カルーセルに26以上項目が入らないようにする
	if(count($task_names) > 25){
		$loop_count = count($task_names);
		for($i=25; $i<$loop_count; $i++){
			//unset($task_ids[25]);
			unset($task_names[25]);
			/*unset($imgs[25]);
			unset($complete_times[25]);*/
			//$task_ids = array_values($task_ids);
			$task_names = array_values($task_names);
			/*$imgs = array_values($imgs);
			$complete_times = array_values($complete_times);*/
		}
	}
	// 5回ずつに分けて送信する
	while(count($task_names) > 0){
		$labels = array();
		$return_texts = array();
		$task_ids5 = array();
		$imgs5 = array();
		$task_names5 = array();
		$texts5 = array();
		if(count($task_names) >= 5){
			$loop_count = 5;
		}else{
			$loop_count = count($task_names);
		}
		for($i=0; $i<$loop_count; $i++){
			array_push($task_names5, $task_names[0]);
			array_push($imgs5, $imgs[0]);
			array_push($texts5, $texts[0]);
			array_push($labels, array("達成一覧から削除"));
			array_push($return_texts, array($task_ids[0]."clear"));
			unset($task_ids[0]);
			unset($task_names[0]);
			unset($imgs[0]);
			unset($texts[0]);
			$task_ids = array_values($task_ids);
			$task_names = array_values($task_names);
			$imgs = array_values($imgs);
			$texts = array_values($texts);
		}
		array_push($response_data,
			carousel_message(
				$imgs5,
				$task_names5,
				$texts5,
				$labels,
				$return_texts,
				"postback"
			)
		);
	}
	if(count($response_data) == 0){
		array_push($response_data, response_data("text", "達成したタスクはありません。"));
	}
?>