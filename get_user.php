<?php
	if(isset($user_id)){
		$profile_data = GET_for_LINEAPI(ACCESSTOKEN, "https://api.line.me/v2/bot/profile/".$user_id);
		$profile = json_decode($profile_data);
		$user_name = $profile->{"displayName"};
	}
?>