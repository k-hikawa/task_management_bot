<?php
	function date_change_for_human($date_data){
		$temp = str_replace("-", "/", $date_data);
		$temp = str_replace("T", " ", $temp);
		return $temp;
	}

	// 配列と自分のスコアを比較してその配列の中の何位に当たるかを計算する関数
	function get_array_lank($array, $my_score){
		array_push($array, $my_score);
		arsort($array);
		$lank = 0;
		$my_lank = 0;
		foreach($array as $key=>$value){
			$lank++;
			if($value == $my_score){
				$my_lank = $lank;
				break;
			}
		}
		return $my_lank;
	}
?>