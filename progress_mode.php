<?php
	// 進捗メニュー表示
	if(preg_match('/progressmenu/', $postback)){
		$task_id = intval(str_replace("progressmenu", "", $postback));
		foreach($pdo->query("SELECT name, progress, regist_time, deadline FROM line_task_table WHERE id=".$task_id) as $row){
			$task_name = $row['name'];
			$task_progress = $row['progress'];
			$regist_time = $row['regist_time'];
			$deadline = $row['deadline'];
		}
		$tag_names = array();
		foreach($pdo->query("SELECT tag FROM line_task_log_table WHERE task_id=".$task_id. " GROUP BY tag") as $row){
			array_push($tag_names, $row['tag']);
		}
		$time = time();
		$time_rate = abs(($time - $regist_time) / ($deadline - $regist_time));
		define("WIDTH", 750);
		define("HEIGHT", 500);
		$img_path = "img/task_system/".$task_id."_".$time.".png";
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
		$my_lank = get_array_lank($rival_progress, $task_progress);
		if($my_lank == 1 && $task_progress != 0){
			$mark = "👑";
		}else{
			$mark = "";
		}
		$member_num = count($rival_progress)+1;
		$lank_text = "順位：".$mark.$my_lank."位 /".$member_num;
		$task_text = "進捗：".$task_progress."%\n".$lank_text."\n進捗状況を教えてください。";
		array_push($response_data, button_message("https://nkmr.io/linebot/hikawa/task_system/".$img_path, $task_name, $task_text, array("進捗プラス1%", "進捗プラス10%", "タスク達成！🎉(100%)", "進捗リセット😥(0%)"), array($task_id."progressone", $task_id."progressten", $task_id."progresscomplete", $task_id."rcheck"), array("postback", "postback", "postback", "postback")));
	// 進捗プラス処理
	}else{
		$task_id = intval(str_replace("progressone", "", $postback));
		$task_id = intval(str_replace("progressten", "", $postback));
		$task_id = intval(str_replace("progressreset", "", $postback));
		$task_id = intval(str_replace("progresscomplete", "", $postback));
		foreach($pdo->query("SELECT name, progress, regist_time, deadline FROM line_task_table WHERE id=".$task_id) as $row){
			$task_name = $row['name'];
			$task_progress = $row['progress'];
			$regist_time = $row['regist_time'];
			$deadline = $row['deadline'];
		}
		$time = time();
		$time_rate = abs(($time - $regist_time) / ($deadline - $regist_time));

		if($task_progress != 100){
			$temp_progress = $task_progress;
			if(preg_match('/one/', $postback)){
				$new_progress = $task_progress + 1;
			}else if(preg_match('/ten/', $postback)){
				$new_progress = $task_progress + 10;
			}else if(preg_match('/reset/', $postback)){
				$new_progress = 0;
				array_push($response_data, response_data("text", "「".$task_name."」"."の進捗をリセットしました。"));
			}
			if($new_progress >= 100 || preg_match('/complete/', $postback)){
				$new_progress = 99;
				array_push($response_data, button_message("", "「".$task_name."」を完了しますか？", "タスク達成した場合は、下のボタンを押してください。", array("達成！"), array($task_id."complete"), array("postback")));
			}else{
				$task_progress = $new_progress;
				$tag_names = array();
				foreach($pdo->query("SELECT tag FROM line_task_log_table WHERE task_id=".$task_id. " GROUP BY tag") as $row){
					array_push($tag_names, $row['tag']);
				}
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
				$my_lank = get_array_lank($rival_progress, $task_progress);
				if($my_lank == 1 && $task_progress != 0){
					$mark = "👑";
				}else{
					$mark = "";
				}
				$temp_lank = get_array_lank($rival_progress, $temp_progress);
				if($my_lank < $temp_lank){
					$mark2 = "↗️";
				}else{
					$mark2 = "";
				}
				$member_num = count($rival_progress)+1;
				$lank_text = "順位：".$mark.$my_lank."位 /".$member_num." ".$mark2;
				$task_text = "進捗：".$task_progress."%\n".$lank_text."\n進捗状況を教えてください。";
				array_push($response_data, button_message("https://nkmr.io/linebot/hikawa/task_system/".$img_path, $task_name, $task_text, array("進捗プラス1%", "進捗プラス10%", "タスク達成！🎉(100%)", "進捗リセット😥(0%)"), array($task_id."progressone", $task_id."progressten", $task_id."progresscomplete", $task_id."rcheck"), array("postback", "postback", "postback", "postback")));
				for($i=0; $i<count($tag_names); $i++){
					$stmt = $pdo->prepare("INSERT INTO line_task_log_table(task_id, user_id, progress, tag, load_time, time_rate) VALUES(?, ?, ?, ?, ?, ?)");
					$stmt->bindParam(1, $task_id);
					$stmt->bindParam(2, $user_id);
					$stmt->bindParam(3, $new_progress);
					$stmt->bindParam(4, $tag_names[$i]);
					$stmt->bindParam(5, $time);
					$stmt->bindParam(6, $time_rate);
					$stmt->execute();
				}
				$sql = "UPDATE line_task_table SET progress = :progress WHERE id = :task_id";
				$stmt = $pdo -> prepare($sql);
				$stmt->bindParam(':progress', $new_progress, PDO::PARAM_INT);
				$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
				$stmt->execute();
			}
		}
	}
?>