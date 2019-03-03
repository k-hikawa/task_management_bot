<?php					
	$domain = "https://nkmr.io/linebot/hikawa/task_system/img/tag/";
	/*$texts = array(
		"受験勉強、テスト対策、資格の勉強、授業の予習 など",
		"授業の宿題、レポート、プレゼン資料の作成 など",
		"アルバイト、仕事 など",
		"ゲームの目標、旅行のための貯金 など",
		"どれにも当てはまらないタスク"
	);*/
	$texts = array(
		"研究室関連のタスクです。\n論文、実験 など",
		"授業関連のタスクです。\n授業課題、試験勉強 など",
		"個人的なタスクです。\nアルバイト、就活、ゲーム など"
	);
	$tag_imgs = array();
	$labels = array();
	$return_texts = array();
	for($i=0; $i<count($tags); $i++){
		array_push($tag_imgs, $domain.$tags[$i].".png");
		array_push($labels, array("選択"));
		array_push($return_texts, array($tags[$i]));
	}
	array_push($response_data,
		carousel_message(
			$tag_imgs,
			$tags,
			$texts,
			$labels,
			$return_texts,
			"postback"
		)
	);
?>