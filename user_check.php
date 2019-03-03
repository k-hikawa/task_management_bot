<?php
	$time = time();
	try{
		foreach($pdo->query("SELECT * FROM line_task_user_table WHERE line_id='".$user_id."'") as $row){
			$status = $row['status'];
		}
		if(!isset($status)){
			$status = "free";
			$stmt = $pdo->prepare("INSERT INTO line_task_user_table(line_id, name, status, last_time) VALUES(?, ?, ?, ?)");
			$stmt->bindParam(1, $user_id);
			$stmt->bindParam(2, $user_name);
			$stmt->bindParam(3, $status);
			$stmt->bindParam(4, $time);
			$stmt->execute();
		}else{
			$sql = "UPDATE line_task_user_table SET last_time = :last_time WHERE line_id = :line_id";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(':last_time', $time, PDO::PARAM_INT);
			$stmt->bindParam(':line_id', $user_id, PDO::PARAM_STR);
			$stmt->execute();
		}
	}catch(PDOException $e){
		print 'Connection faild: '.$e->getMessage();
		die();
	}
?>