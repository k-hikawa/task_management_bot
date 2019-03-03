<?php
	// 送られてきた文字列から日付を判定する
	function date_picker($text){
		$text = mb_convert_kana($text, "n", "utf-8");
		preg_match('/^(\d{1,3})\D*(\d{1,3})\D*$/', $text, $date_array);
		$date_text = "";
		if(count($date_array) != 0){
			$year = date("Y");
			$unix_time = strtotime($year."-".$date_array[1]."-".$date_array[2]." 0:0:0");
			if($unix_time >= time()){
				$date_text = date("Y年n月j日 G時i分", $unix_time);
			}else{
				$unix_time = strtotime(($year+1)."-".$date_array[1]."-".$date_array[2]." 0:0:0");
				if($unix_time >= time()){
					$date_text = date("Y年n月j日 G時i分", $unix_time);
				}
			}
		}
		return $date_text;
	}

	// 年を変更する
	function year_changer($date, $text){
		$text = mb_convert_kana($text, "n", "utf-8");
		preg_match('/^\D*(\d{4})\D*$/', $text, $new_year);
		$new_date = "";
		if(count($new_year) != 0){
			$old_year = substr($date, 0, 4);
			$new_date = str_replace($old_year, strval($new_year[1]), $date);
		}
		return $new_date;
	}

	// 時間を変更する
	function hour_changer($date, $text){
		$text = mb_convert_kana($text, "n", "utf-8");
		if($text === "正午"){
			$new_date = "12時00分";
			$old_hour = explode(" ", $date);
			$new_date = str_replace($old_hour[1], $new_date, $date);
		}else if(preg_match('/^\D*\d{1,2}\D*$/', $text)){
			preg_match('/^(\D*)(\d{1,2})(\D*)$/', $text, $new_hour);
			$new_date = "";
			$old_hour = explode(" ", $date);
			if(preg_match('/半/', $new_hour[3])){
				$minute = "30";
			}else{
				$minute = "00";
			}
			if(pm_checker($new_hour[1])){
				if(intval($new_hour[2]) <= 11 && intval($new_hour[3]) <= 59){
					$new_date = (intval($new_hour[2])+12)."時".$minute."分";
					$new_date = str_replace($old_hour[1], $new_date, $date);
				}
			}else{
				if(intval($new_hour[2]) <= 23 && intval($new_hour[3]) <= 59){
					$new_date = intval($new_hour[2])."時".$minute."分";
					$new_date = str_replace($old_hour[1], $new_date, $date);
				}
			}
		}else{
			preg_match('/^(\D*)(\d{1,2})\D*(\d{1,2})\D*$/', $text, $new_hour);
			$new_date = "";
			if(count($new_hour) == 4){
				if(!isset($new_hour[3])){
					array_push($new_hour, 0);
				}
				if(intval($new_hour[3]) <= 9){
					$zero = "0";
				}else{
					$zero = "";
				}
				$old_hour = explode(" ", $date);
				if(pm_checker($new_hour[1])){
					if(intval($new_hour[2]) <= 11 && intval($new_hour[3]) <= 59){
						$new_date = (intval($new_hour[2])+12)."時".$zero.intval($new_hour[3])."分";
						$new_date = str_replace($old_hour[1], $new_date, $date);
					}
				}else{
					if(intval($new_hour[2]) <= 23 && intval($new_hour[3]) <= 59){
						$new_date = intval($new_hour[2])."時".$zero.intval($new_hour[3])."分";
						$new_date = str_replace($old_hour[1], $new_date, $date);
					}
				}
			}
		}
		return $new_date;
	}

	// 午後ワードチェック
	function pm_checker($text){
		switch($text){
			case "pm":
			case "PM":
			case "Pm":
			case "pM":
			case "午後":
			case "ごご":
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	// 現在時刻より前かのチェック 過去だったらtrueを返す
	function past_checker($input_time){
		$unix_time = str_replace("年", "-", $input_time);
		$unix_time = str_replace("月", "-", $unix_time);
		$unix_time = str_replace("日", "", $unix_time);
		$unix_time = str_replace("時", ":", $unix_time);
		$unix_time = str_replace("分", "", $unix_time);
		if(time() >= strtotime($unix_time)){
			return true;
		}else{
			return false;
		}
	}
?>