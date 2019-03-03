<?php
	$time = time();
	$sql = "UPDATE line_task_table SET active = :active WHERE active=0 AND deadline<".$time;
	$stmt = $pdo -> prepare($sql);
	$stmt->bindValue(':active', -1, PDO::PARAM_INT);
	$stmt->execute();
?>