<?php
	$sql = "CREATE TABLE IF NOT EXISTS `line_task_table`"
	."("
	. "`id` INT auto_increment primary key,"
	. "`name` TEXT NOT NULL,"
	. "`user_id` TEXT NOT NULL,"
	. "`progress` INT NOT NULL,"
	. "`regist_time` INT NOT NULL,"
	. "`deadline` INT NOT NULL,"
	. "`active` INT NOT NULL,"
	. "`lank` INT,"
	. "`rival_num` INT"
	.");";
	$stmt = $pdo -> prepare($sql);
	$stmt -> execute();

	$sql = "CREATE TABLE IF NOT EXISTS `line_task_tag_table`"
	."("
	. "`id` INT auto_increment primary key,"
	. "`name` TEXT NOT NULL"
	.");";
	$stmt = $pdo -> prepare($sql);
	$stmt -> execute();

	$sql = "CREATE TABLE IF NOT EXISTS `line_task_user_table`"
	."("
	. "`id` INT auto_increment primary key,"
	. "`line_id` TEXT NOT NULL,"
	. "`name` TEXT NOT NULL,"
	. "`status` TEXT NOT NULL,"
	. "`last_time` INT NOT NULL"
	.");";
	$stmt = $pdo -> prepare($sql);
	$stmt -> execute();

	$sql = "CREATE TABLE IF NOT EXISTS `line_task_log_table`"
	."("
	. "`id` INT auto_increment primary key,"
	. "`task_id` INT NOT NULL,"
	. "`user_id` TEXT NOT NULL,"
	. "`progress` INT NOT NULL,"
	. "`tag` TEXT NOT NULL,"
	. "`load_time` INT NOT NULL,"
	. "`time_rate` DOUBLE NOT NULL"
	.");";
	$stmt = $pdo -> prepare($sql);
	$stmt -> execute();

	$sql = "CREATE TABLE IF NOT EXISTS `line_task_regist_table`"
	."("
	. "`id` INT auto_increment primary key,"
	. "`name` TEXT NOT NULL,"
	. "`user_id` TEXT NOT NULL,"
	. "`tag` TEXT,"
	. "`deadline` INT"
	.");";
	$stmt = $pdo -> prepare($sql);
	$stmt -> execute();
?>