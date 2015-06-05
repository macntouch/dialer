<?php

	require_once "vendor/autoload.php";

	$ari = new phpari("");
	$channel = new channels($ari);

	$trunk = 'voipms';

	// set this to the number you want to dial
	$number = '12565806090';

	// this is the unique id of the channel and also the recorded file name
	$channel_id = uniqid();

	$destination = 'PJSIP/'.$number.'@'.$trunk;
	$destination = 'PJSIP/265';

	$result = $channel->originate(
		$destination,
		$channel_id,
		array(
			"app"		=> "recorder",
			"callerid"	=> "18005551212",
			"timeout"	=> 60,
		),
		NULL
	);

	print_r($result);
