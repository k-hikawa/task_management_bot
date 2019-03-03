<?php
	// タスク達成時にtask_tableとtask_log_tableを書き換えて、達成メニューを表示する
	$task_id = intval(str_replace("complete", "", $postback));
	foreach($pdo->query("SELECT name, progress, regist_time, deadline, active FROM line_task_table WHERE id=".$task_id) as $row){
		$task_name = $row['name'];
		$progress = $row['progress'];
		$regist_time = $row['regist_time'];
		$deadline = $row['deadline'];
		$active = $row['active'];
	}
	$new_progress = 100;
	$tag_names = array();
	$time = time();
	$time_rate = abs(($time - $regist_time) / ($deadline - $regist_time));
	foreach($pdo->query("SELECT tag FROM line_task_log_table WHERE task_id=".$task_id. " GROUP BY tag") as $row){
		array_push($tag_names, $row['tag']);
	}
	$tag_sql = "";
	for($i=0; $i<count($tag_names); $i++){
		$stmt = $pdo->prepare("INSERT INTO line_task_log_table(task_id, user_id, progress, tag, load_time, time_rate) VALUES(?, ?, ?, ?, ?, ?)");
		$stmt->bindParam(1, $task_id);
		$stmt->bindParam(2, $user_id);
		$stmt->bindParam(3, $new_progress);
		$stmt->bindParam(4, $tag_names[$i]);
		$stmt->bindParam(5, $time);
		$stmt->bindParam(6, $time_rate);
		$stmt->execute();
		if($tag_sql != ""){
			$tag_sql .= " OR ";
		}
		$tag_sql .= "tag='".$tag_names[$i]."'";
	}

	define("WIDTH", 750);
	define("HEIGHT", 500);
	$task_progress = 100;
	$img_path = "img/task/".$task_id."_".$time.".png";
	$rival_progress = array();
	foreach($pdo->query("SELECT tag, task_logs.task_id, task_logs.progress, task_logs.time_rate FROM line_task_log_table AS task_logs INNER JOIN (SELECT task_id, MAX(time_rate) AS near FROM line_task_log_table WHERE time_rate<=".$time_rate." AND task_id != ".$task_id." GROUP BY task_id) AS task_log ON task_logs.task_id=task_log.task_id AND task_logs.time_rate=task_log.near WHERE (".$tag_sql.") GROUP BY task_id") as $row){
		$rival_id = $row['task_id'];
		foreach($pdo->query("SELECT * FROM line_task_table WHERE (active>0 OR (active=0 AND deadline<=86400+".$deadline.")) AND id=".$rival_id) as $row2){
			if($time_rate <= 1){
				array_push($rival_progress, $row['progress']);
			}else{
				array_push($rival_progress, 100);
			}
		}
	}
	shuffle($rival_progress);
	require "create_image_functions.php";
	require "create_image.php";
	$my_lank = 1;
	foreach($rival_progress as $value){
		if($value == 100){
			$my_lank++;
		}
	}
	if($my_lank == 1 && $task_progress != 0){
		$mark = "👑";
	}else{
		$mark = "";
	}
	$member_num = count($rival_progress)+1;
	$lank_text = "順位：".$mark.$my_lank."位 /".$member_num;
	$sql = "UPDATE line_task_table SET progress = :progress, active = :active, lank = :lank, rival_num = :rival_num WHERE id = :task_id";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(':progress', $new_progress, PDO::PARAM_INT);
	if($active == 0){
		$active = 1;
	}else{
		$active = -2;
	}
	$stmt->bindParam(':active', $active, PDO::PARAM_INT);
	$stmt->bindParam(':lank', $my_lank, PDO::PARAM_INT);
	$stmt->bindParam(':rival_num', $member_num, PDO::PARAM_INT);
	$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
	$stmt->execute();

	array_push($response_data, button_message("https://nkmr.io/linebot/hikawa/task_system/".$img_path, $task_name, "タスク達成おめでとう！🎉\n".$lank_text, array("達成したタスク一覧"), array("達成したタスクを見せて！"), array("message")));
?>