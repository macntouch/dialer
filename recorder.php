<?php

	require_once "vendor/autoload.php";

/*
			if (!count($this->channels)) {
				$this->phpari->channels->channel_playback($channel->id, "sound:conf-onlyperson");
			}
			$result = $this->phpari->bridges->bridge_addchannel($this->id, $channel->id);
*/
	class RecorderChannel
	{
		public function __construct($phpari, $event)
		{
			$this->phpari = $phpari;
			$this->phpari->stasisLogger->notice('Channel Created: '.print_r($event,true));
			$this->stasisEvent = new Evenement\EventEmitter();
			$this->channel = $event->channel;

			$this->dtmf = '';

			$this->stasisEvent->on("StasisStart", function ($event) {
				$this->state = $this->channel->state;
				$this->phpari->channels->answer($this->channel->id);
			});

			$this->stasisEvent->on("ChannelStateChange", function ($event) {
				$last_state = $this->channel->state;
				$this->channel = $event->channel;
				$this->phpari->stasisLogger->notice($this->channel->name.' State Change: '.$this->channel->state);
				if ($this->channel->state == 'Up') {
					$this->phpari->channels->channel_playback($this->channel->id, "sound:vm-intro");
				}
			});

			$this->stasisEvent->on("PlaybackFinished", function ($event) {
				$filename = 'record-'.$this->channel->id;
				$this->phpari->stasisLogger->notice($this->channel->name.' recording to: '.$filename);
				$this->phpari->channels->record($this->channel->id, $filename, "wav");
			});

			$this->stasisEvent->on("StasisEnd", function ($event) {
				unset ($this->phpari->channel[$this->channel->id]);
				unset ($this);
			});
			$this->stasisEvent->on("ChannelDtmfReceived", function ($event) {
				$this->dtmf .= $event->digit;
				$this->phpari->stasisLogger->notice($this->channel->name.' DTMF: '.$this->dtmf);
			});
		}
	}

	class RecorderStasisApp extends phpari
	{
		public function __construct()
		{
			$appName="recorder";

			// initialize the underlying phpari class
			parent::__construct($appName);

			// initialize the handler for websocket messages
			$this->WebsocketClientHandler();

			// access to the needed api's
			$this->channels = new channels($this);

			// recorder channels
			$this->channel = array();
		}

		// handle the websocket connection, passing stasis events to class instance
		public function WebsocketClientHandler()
		{
			$this->stasisClient->on("request", function ($headers) {
				$this->stasisLogger->notice("Request received!");
			});

			$this->stasisClient->on("handshake", function () {
				$this->stasisLogger->notice("Handshake received!");
			});

			$this->stasisClient->on("message", function ($message) {
				$event=json_decode($message->getData());
				$this->stasisLogger->notice('Received event: '.$event->type);
				if (!empty($event->channel->id)) {
					if (empty($this->channel[$event->channel->id])) {
						$this->channel[$event->channel->id] = new RecorderChannel($this, $event);
					}
					$this->stasisLogger->notice('Passing event to channel '.$event->channel->id);
					$channel = $this->channel[$event->channel->id];
					$channel->stasisEvent->emit($event->type, array($event));
				} else if (!empty($event->playback)) {
					// cheat and pass playback event to channel it was played on
					if (!empty($event->playback->target_uri) &&
						(substr($event->playback->target_uri, 0, 8) == "channel:"))
					{
						$channel_id = substr($event->playback->target_uri, 8);
						$channel = $this->channel[$channel_id];
						$channel->stasisEvent->emit($event->type, array($event));
					}
					else
					{
						$this->stasisLogger->notice('Playback event not on channel! '.print_r($event,true));
					}
				} else {
					$this->stasisLogger->notice('Unhandled event! '.print_r($event,true));
				}
			});
		}

		// initiate the websocket connection and run the event loop
		public function run()
		{
			$this->stasisClient->open();
			$this->stasisLoop->run();
			// run() does not return
		}

	}

	$app = new RecorderStasisApp();
	$app->run();
