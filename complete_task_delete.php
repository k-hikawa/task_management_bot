<?php
	// 達成したタスクを擬似的に一覧から削除する
	// 削除したタスクは競争には現れるがユーザの一覧からは削除される
	if(preg_match('/true/', $postback)){
		$task_id = intval(str_replace("cleartrue", "", $postback));
		foreach($pdo->query("SELECT name, active FROM line_task_table WHERE id=".$task_id) as $row){
			if($row['active'] == 1){
				$sql = "UPDATE line_task_table SET active = :active WHERE id = :task_id";
				$stmt = $pdo -> prepare($sql);
				$stmt->bindValue(':active', 2, PDO::PARAM_INT);
				$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
				$stmt->execute();
				array_push($response_data, response_data("text", "「".$row['name']."」を削除しました。"));
			}else if($row['active'] == -2){
				$sql = "UPDATE line_task_table SET active = :active WHERE id = :task_id";
				$stmt = $pdo -> prepare($sql);
				$stmt->bindValue(':active', -3, PDO::PARAM_INT);
				$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
				$stmt->execute();
				array_push($response_data, response_data("text", "「".$row['name']."」を削除しました。"));
			}
		}
	}else if(preg_match('/false/', $postback)){
		$task_id = intval(str_replace("cleartrue", "", $postback));
		foreach($pdo->query("SELECT name, active FROM line_task_table WHERE id=".$task_id) as $row){
			if($row['active'] == 1 || $row['active'] == -2){
				array_push($response_data, response_data("text", "わかりました。\nそのままにしておきます。"));
			}
		}
	}else{
		$task_id = intval(str_replace("clear", "", $postback));
		foreach($pdo->query("SELECT name, active FROM line_task_table WHERE id=".$task_id) as $row){
			if($row['active'] == 1 || $row['active'] == -2){
				$task_name = $row['name'];
				array_push($response_data, button_message("", "「".$task_name."」を削除しますか？", "削除したタスクは達成したタスク一覧から見ることができなくなります。", array("削除", "削除しない"), array($task_id."cleartrue", $task_id."clearfalse"), array("postback", "postback")));
			}
		}
	}
?>