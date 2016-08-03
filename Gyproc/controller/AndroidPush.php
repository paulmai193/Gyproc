<?php
class AndroidPush {
	private $API_ACCESS_KEY = 'AIzaSyBBb33R0kbihz9s-3SnkqfAm78FTCmVvuo';
	private $registrationIds;
	private $msg;
	private $headers;
	private $fields;
	/**
	 *
	 * @param unknown $__registrationIds
	 * @param unknown $__title
	 * @param unknown $__message
	 */
	function __construct($__registrationIds, $__title, $__message) {
		// Registration ID(s)
		$this->registrationIds = $__registrationIds;

		// Message
		$this->msg = array (
				'message' => $__message,
				'title' => $__title,
				// 'subtitle' => $__subTitle,

				// 'tickerText' => 'Ticker text here...Ticker text here...Ticker text here',
				'vibrate' => 1,
				'sound' => 1
		);
		// 'largeIcon' => 'large_icon',
		// 'smallIcon' => 'small_icon'


		// prepare the bundle
		$this->headers = array (
				'Authorization: key=' . $this->API_ACCESS_KEY,
				'Content-Type: application/json'
		);
		$this->fields = array (
				'registration_ids' => $this->registrationIds,
				'data' => $this->msg
		);
	}
	/**
	 *
	 * @return result of send push in Json type
	 */
	function sendPush() {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $this->headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( $this->fields ) );

		$result = curl_exec ( $ch );

		curl_close ( $ch );

		return $result;
	}
}

?>