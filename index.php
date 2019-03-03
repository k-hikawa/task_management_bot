<?php
	require "secret.php"; // botのアクセストークンやデータベースの接続を設定する
	require "linebot_functions.php"; // linebotの機能の関数
	require "linebot_message.php"; // メッセージのステータス等を取得
	require "get_user.php"; // botの会話相手の名前を取ってくる
	require "table_check.php"; // テーブルがなければ作成する
	require "user_check.php"; // ユーザの登録、ステータスのチェックを行う
	require "deadline_check.php"; //〆切が過ぎたタスクのチェック
	require "status_change.php"; // ユーザのステータスの変更を行う関数
	require "original_functions.php"; // 俺が考えた便利な関数
	require "get_log.php"; // 実験用にログを取得

	$tags = array("研究室", "授業", "個人");
	$response_data = array();

	switch($type){
		case "text":
			switch($status){
				case "free":
					switch($text){
						case "タスクを登録する！":
							array_push($response_data, response_data("text", "タスクを登録します。\nまずはタスク名を教えてください。"));
							status_change($dsn, $options, $username, $password, $user_id, "task_regist_name");
						break;
						case "タスク一覧を見せて！":
							$list_mode = false;
							$task_cnt = 0;
							foreach($pdo->query("SELECT * FROM line_task_table WHERE user_id='".$user_id."' AND (active=0 OR active=-1) ORDER BY deadline ASC") as $row){
								$task_cnt++;
								if($task_cnt == 6){
									$list_mode = true;
									break;
								}
							}
							if($list_mode){
								require "image_map.php";
							}else{
								require "get_task_list.php";
							}
						break;
						case "タスクの詳細を見せて！":
							require "get_task_list.php";
						break;
						case "遅れているタスクを教えて！":
							require "get_task_climax_list.php";
						break;
						case "達成したタスクを見せて！":
							require "get_complete_task_list.php";
						break;
					}
				break;
				case "task_regist_name":
					array_push($response_data, button_message("", $text, "次は〆切日時を教えてください。\nやり直す場合はボタンを押さずにもう一度教えてください。", array("〆切日時を入力", "タスク登録をやめる"), array(array($text, "datetime"), "regist_reset"), array("datetimepicker", "postback")));
				break;
				case "task_regist_deadline":
					
				break;
				case "task_regist_tag":
					foreach($pdo->query("SELECT * FROM line_task_regist_table WHERE id IN (SELECT MAX(id) AS now FROM line_task_regist_table WHERE user_id='".$user_id."')") as $row){
						$name = $row['name'];
					}
					array_push($response_data, response_data("text", "「".$name."」の種類を教えてください。"));
					require "tag_carousel_message.php";
				break;
				
			}

		break;

		case "postback":
			switch($status){
				// タスク名登録モード
				case "task_regist_name":
					switch($postback){
						// タスク登録中止
						case "regist_reset":
							array_push($response_data, response_data("text", "タスクを再び登録する場合はシステムメニューから行ってください。"));
							status_change($dsn, $options, $username, $password, $user_id, "free");
						break;
						
						// 締め切り登録モードに移行
						default:
							if(isset($postback_datetime)){
								$stmt = $pdo->prepare("INSERT INTO line_task_regist_table(name, user_id) VALUES(?, ?)");
								$stmt->bindParam(1, $postback);
								$stmt->bindParam(2, $user_id);
								$stmt->execute();
								array_push($response_data, button_message("", date_change_for_human($postback_datetime), "〆切日はこれでよろしいですか？", array("OK", "〆切日時を入力", "タスク登録をやめる"), array($postback_datetime, array("datetime", "datetime"), "regist_reset"), array("postback", "datetimepicker", "postback","postback")));
								status_change($dsn, $options, $username, $password, $user_id, "task_regist_deadline");
							}else if(preg_match('/#/', $postback)){
								$postback = str_replace("#", "", $postback);
								$stmt = $pdo->prepare("INSERT INTO line_task_regist_table(name, user_id) VALUES(?, ?)");
								$stmt->bindParam(1, $postback);
								$stmt->bindParam(2, $user_id);
								$stmt->execute();
								array_push($response_data, button_message("", $postback, "〆切日は入力せずに進みますか？", array("OK", "〆切日時を入力", "タスク登録をやめる"), array("2147483647", array("datetime", "datetime"), "regist_reset"), array("postback", "datetimepicker", "postback")));
								status_change($dsn, $options, $username, $password, $user_id, "task_regist_deadline");
							}
						break;
					}
				break;
				// 締め切り登録モード
				case "task_regist_deadline":
					switch($postback){
						// タスク登録中止
						case "regist_reset":
							array_push($response_data, response_data("text", "タスクを再び登録する場合はシステムメニューから行ってください。"));
							status_change($dsn, $options, $username, $password, $user_id, "free");
						break;
						case "datetime":
							array_push($response_data, button_message("", date_change_for_human($postback_datetime), "〆切日はこれでよろしいですか？", array("OK", "〆切日時を入力", "タスク登録をやめる"), array($postback_datetime, array("datetime", "datetime"), "regist_reset"), array("postback", "datetimepicker", "postback")));
						break;
						case "2147483646":
							foreach($pdo->query("SELECT * FROM line_task_regist_table WHERE user_id='".$user_id."'") as $row){
								$task_name = $row['name'];
							}
							array_push($response_data, button_message("", $task_name, "〆切日は入力せずに進みますか？", array("OK", "〆切日時を入力", "タスク登録をやめる"), array("2147483647", array("datetime", "datetime"), "regist_reset"), array("postback", "datetimepicker", "postback")));
						break;
						default:
							if($postback != "2147483647"){
								$unix_time = strtotime($postback);
								if(time() >= $unix_time){
									array_push($response_data, button_message("", date_change_for_human($postback), "⚠️この時間は入力できません。", array("〆切日時を入力", "登録をやめる"), array(array("datetime", "datetime"), "regist_reset"), array("datetimepicker", "postback")));
								}else{
									$sql = "UPDATE line_task_regist_table, (SELECT MAX(id) AS now FROM line_task_regist_table WHERE user_id='".$user_id."') A SET deadline = :deadline WHERE id = A.now";
									$stmt = $pdo -> prepare($sql);
									$stmt->bindParam(':deadline', $unix_time, PDO::PARAM_INT);
									$stmt->execute();
									array_push($response_data, response_data("text", "最後にタスクの種類をこの中から選んでください。"));
									require "tag_carousel_message.php";
									status_change($dsn, $options, $username, $password, $user_id, "task_regist_tag");
								}
							}else{
								$deadline = intval($postback);
								$sql = "UPDATE line_task_regist_table, (SELECT MAX(id) AS now FROM line_task_regist_table WHERE user_id='".$user_id."') A SET deadline = :deadline WHERE id = A.now";
								$stmt = $pdo -> prepare($sql);
								$stmt->bindParam(':deadline', $deadline, PDO::PARAM_INT);
								$stmt->execute();
								array_push($response_data, response_data("text", "最後にタスクの種類をこの中から選んでください。"));
								require "tag_carousel_message.php";
								status_change($dsn, $options, $username, $password, $user_id, "task_regist_tag");
							}
						break;
					}
				break;
				case "task_regist_tag":
					for($i=0; $i<count($tags); $i++){
						if($postback === $tags[$i]){
							require "task_regist_tag.php";
							status_change($dsn, $options, $username, $password, $user_id, "free");
							break;
						}
					}
				break;
				// フリーモード
				case "free":
					// タスク削除
					if(preg_match('/delete/', $postback)){
						require "task_delete.php";
					// 進捗リセット確認
					}else if(preg_match('/rcheck/', $postback)){
						$task_id = intval(str_replace("rcheck", "", $postback));
						foreach($pdo->query("SELECT name FROM line_task_table WHERE id=".$task_id." AND active = 0") as $row){
							array_push($response_data, button_message("", "「".$row['name']."」の進捗をリセットしますか？", "※この操作は取り消せません。", array("リセット"), array($task_id."progressreset"), array("postback")));
						}
					// 進捗モード
					}else if(preg_match('/clear/', $postback)){
						require "complete_task_delete.php";
					}else if(preg_match('/progress/', $postback)){
						require "progress_mode.php";
					// タスク達成
					}else if(preg_match('/complete/', $postback)){
						require "task_complete.php";
					}
				break;
			}
		break;
	}

	if(count($response_data) != 0){
		response(ACCESSTOKEN, $replyToken, $response_data);
	}
	$pdo = null;
?>