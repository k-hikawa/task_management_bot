<?php
	// ユーザのメッセージ取得
	$json_string = file_get_contents('php://input');
	$jsonObj = json_decode($json_string);

	$type = $jsonObj->{"events"}[0]->{"message"}->{"type"};
	if(!isset($type)){
		$type = $jsonObj->{"events"}[0]->{"type"};
	}

	// メッセージの種類からデータ取得
	switch($type){
		case "text":
			$text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
		break;
		case "image":
			$MessageID = $jsonObj->{"events"}[0]->{"message"}->{"id"};
			$timestamp = $jsonObj->{"events"}[0]->{"timestamp"};
		break;
		case "location":
			$lat = $jsonObj->{"events"}[0]->{"message"}->{"latitude"};
			$lng = $jsonObj->{"events"}[0]->{"message"}->{"longitude"};
		break;
		case "audio":
			$MessageID = $jsonObj->{"events"}[0]->{"message"}->{"id"};
			$timestamp = $jsonObj->{"events"}[0]->{"timestamp"};
		break;
		case "video":
			$MessageID = $jsonObj->{"events"}[0]->{"message"}->{"id"};
			$timestamp = $jsonObj->{"events"}[0]->{"timestamp"};
		break;
		case "postback";
			$postback = $jsonObj->{"events"}[0]->{"postback"}->{"data"};
			$postback_date = $jsonObj->{"events"}[0]->{"postback"}->{"params"}->{"date"};
			$postback_time = $jsonObj->{"events"}[0]->{"postback"}->{"params"}->{"time"};	
			$postback_datetime = $jsonObj->{"events"}[0]->{"postback"}->{"params"}->{"datetime"};
		break;
	}

	// ReplyToken取得
	$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};
	// userID取得
	$user_id = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
	// groupID取得
	$groupID = $jsonObj->{"events"}[0]->{"source"}->{"groupId"};
	// roomID取得
	$roomID = $jsonObj->{"events"}[0]->{"source"}->{"roomId"};
?>