<?php
	$sql = "UPDATE line_task_regist_table, (SELECT MAX(id) AS now FROM line_task_regist_table WHERE user_id='".$user_id."') A SET tag = :tag WHERE id = A.now";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(':tag', $postback, PDO::PARAM_STR);
	$stmt->execute();
	foreach($pdo->query("SELECT * FROM line_task_regist_table WHERE id IN (SELECT MAX(id) AS now FROM line_task_regist_table WHERE user_id='".$user_id."')") as $row){
		$task_name = $row['name'];
		$deadline = $row['deadline'];
		$tag = $row['tag'];
	}
	$time = time();
	$zero = 0;
	$stmt = $pdo->prepare("INSERT INTO line_task_table(name, user_id, progress, regist_time, deadline, active) VALUES(?, ?, ?, ?, ?, ?)");
	$stmt->bindParam(1, $task_name);
	$stmt->bindParam(2, $user_id);
	$stmt->bindParam(3, $zero);
	$stmt->bindParam(4, $time);
	$stmt->bindParam(5, $deadline);
	$stmt->bindParam(6, $zero);
	$stmt->execute();
	$task_id = $pdo->lastInsertId('id');
	$stmt = $pdo->prepare("INSERT INTO line_task_log_table(task_id, user_id, progress, tag, load_time, time_rate) VALUES(?, ?, ?, ?, ?, ?)");
	$stmt->bindParam(1, $task_id);
	$stmt->bindParam(2, $user_id);
	$stmt->bindParam(3, $zero);
	$stmt->bindParam(4, $tag);
	$stmt->bindParam(5, $time);
	$stmt->bindParam(6, $zero);
	$stmt->execute();

	$regist_time = $time;
	$tag_names = array();
	foreach($pdo->query("SELECT tag FROM line_task_log_table WHERE task_id=".$task_id. " GROUP BY tag") as $row){
		array_push($tag_names, $row['tag']);
	}
	$time_rate = abs(($time - $regist_time) / ($deadline - $regist_time));
	define("WIDTH", 750);
	define("HEIGHT", 500);
	$img_path = "img/task/".$task_id."_".$time.".png";
	$tag_sql = "";
	for($i=0; $i<count($tag_names); $i++){
		if($tag_sql != ""){
			$tag_sql .= " OR ";
		}
		$tag_sql .= "tag='".$tag_names[$i]."'";
	}
	$rival_progress = array();
	foreach($pdo->query("SELECT tag, task_logs.task_id, task_logs.progress, task_logs.time_rate FROM line_task_log_table AS task_logs INNER JOIN (SELECT task_id, MAX(time_rate) AS near FROM line_task_log_table WHERE time_rate<=".$time_rate." AND task_id != ".$task_id." GROUP BY task_id) AS task_log ON task_logs.task_id=task_log.task_id AND task_logs.time_rate=task_log.near WHERE (".$tag_sql.") GROUP BY task_id") as $row){
		$rival_id = $row['task_id'];
		foreach($pdo->query("SELECT * FROM line_task_table WHERE (active>0 OR (active=0 AND deadline<=86400+".$deadline.")) AND id=".$rival_id) as $row2){
			array_push($rival_progress, $row['progress']);
		}
	}
	shuffle($rival_progress);
	require "create_image_functions.php";
	require "create_image.php";
	$task_text = "〆切：".date("Y/m/d H:i", $deadline)."\n";
	$count_down = ceil(($deadline - $time)/(24*60*60))-1;
	if($count_down > 0){
		$task_text .= "残り：".$count_down."日";
	}else if($count_down == 0){
		$count_down_m = ceil(($deadline - $time)/60);
		$count_down = ceil(($deadline - $time)/(60*60));
		if($count_down_m%60 <= 30 && $count_down != 0){
			$count_down = ceil(($deadline - $time)/(60*60))-1;
		}
		$task_text .= "残り：".$count_down."時間";
	}else{
		$task_text .= "〆切を過ぎています！";
	}
	array_push($response_data, button_message("https://nkmr.io/linebot/hikawa/task_system/".$img_path, $task_name, $task_text, array("進捗入力", "タスク削除"), array($task_id."progressmenu", $task_id."delete1"), array("postback", "postback")));
?>