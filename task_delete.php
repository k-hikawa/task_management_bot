<?php
	$task_id = intval(str_replace("delete".substr($postback, -1), "", $postback));
	foreach($pdo->query("SELECT name FROM line_task_table WHERE id=".$task_id) as $row){
		$task_name = $row['name'];
	}
	switch(intval(substr($postback, -1))){
		// タスク削除確認
		case 1:
			array_push($response_data, button_message("", "「".$task_name."」を削除しますか？", "⚠️この操作は取り消せません。", array("削除", "削除しない"), array($task_id."delete2", "delete3"), array("postback", "postback")));
		break;
		// タスク削除処理
		case 2:
			foreach($pdo->query("SELECT name FROM line_task_table WHERE id=".$task_id) as $row){
				$sql = "DELETE FROM line_task_table WHERE id=:task_id";
				$stmt = $pdo -> prepare($sql);
				$stmt -> bindParam(':task_id', $task_id, PDO::PARAM_INT);
				$stmt -> execute();
				$sql = 'DELETE FROM line_task_log_table WHERE task_id=:task_id';
				$stmt = $pdo -> prepare($sql);
				$stmt -> bindParam(':task_id', $task_id, PDO::PARAM_INT);
				$stmt -> execute();
				array_push($response_data, response_data("text", "「".$row['name']."」を削除しました。"));
			}
		break;
		case 3:
			array_push($response_data, response_data("text", "わかりました。\nそのままにしておきます。"));
		break;
	}
?>