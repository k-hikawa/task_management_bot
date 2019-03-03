<?php
	$sql = "CREATE TABLE IF NOT EXISTS `line_task_exlog_table`"
	."("
	. "`id` INT auto_increment primary key,"
	. "`user_id` TEXT NOT NULL,"
	. "`put_time` INT NOT NULL,"
	. "`type` TEXT NOT NULL,"
	. "`content` TEXT NOT NULL"
	.");";
	$stmt = $pdo -> prepare($sql);
	$stmt -> execute();

	switch($type){
		case "text":
			$content = $text;
		break;
		case "postback":
			$content = $postback;
		break;
		case "push":
			$content = "Regularly";
		break;
		default:
			$content = "エラー";
		break;
	}
	$stmt = $pdo->prepare("INSERT INTO line_task_exlog_table(user_id, put_time, type, content) VALUES(?, ?, ?, ?)");
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $time);
	$stmt->bindParam(3, $type);
	$stmt->bindParam(4, $content);
	$stmt->execute();
?>