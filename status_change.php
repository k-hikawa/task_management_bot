<?php
	function status_change($dsn, $options, $username, $password, $user_id, $status){
		try{
			$pdo = new PDO($dsn, $username, $password, $options);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$stmt = $pdo->prepare("UPDATE line_task_user_table SET status=:status WHERE line_id=:line_id");
			$stmt->bindParam(":status", $status, PDO::PARAM_STR);
			$stmt->bindParam(":line_id", $user_id, PDO::PARAM_STR);
			$stmt->execute();
		}catch(PDOException $e){
			print 'Connection faild: '.$e->getMessage();
			die();
		}
	}
?>