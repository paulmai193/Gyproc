<?php
class iOSPush {
	private $tHost = 'gateway.sandbox.push.apple.com';
	private $tPort = 2195;

	// Provide the Certificate and Key Data.
	private $tCert;

	// Provide the Private Key Passphrase (alternatively you can keep this secrete
	// and enter the key manually on the terminal -> remove relevant line from code).

	// Replace XXXXX with your Passphrase
	private $tPassphrase = 'gyproc';

	// The Badge Number for the Application Icon (integer >=0).
	private $tBadge = 0;

	// Audible Notification Option.
	private $tSound = 'default';

	// Provide the Device Identifier (Ensure that the Identifier does not have spaces in it).

	// Replace this token with the token of the iOS device that is to receive the notification.
	private $tToken;

	// The message that is to appear on the dialog.
	private $tAlert;

	// The content that is returned by the LiveCode "pushNotificationReceived" message.
	private $tPayload = 'APNS Message Handled by LiveCode';

	// Create the message content that is to be sent to the device.
	private $tBody;
	function __construct($__registrationId, $__title, $__message) {
		$this->tToken = $__registrationId;
		$this->tAlert = $__title;
		$this->tPayload = $__message;
		$this->tBody ['aps'] = array (

				'alert' => $this->tAlert,

				'badge' => $this->tBadge,

				'sound' => $this->tSound
		);
		$this->tBody ['payload'] = $this->tPayload;
		// Encode the body to JSON.
		$this->tBody = json_encode ( $this->tBody );
		$this->tCert = dirname ( __FILE__ ) . '/../asset/ck.pem';
	}
	function sendPush() {
		$tContext = stream_context_create ();
		stream_context_set_option ( $tContext, 'ssl', 'local_cert', $this->tCert );

		// Remove this line if you would like to enter the Private Key Passphrase manually.
		stream_context_set_option ( $tContext, 'ssl', 'passphrase', $this->tPassphrase );

		// Open the Connection to the APNS Server.
		$tSocket = stream_socket_client ( 'ssl://' . $this->tHost . ':' . $this->tPort, $error, $errstr, 30, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $tContext );

		// Check if we were able to open a socket.
		if (! $tSocket) {
			exit ( "APNS Connection Failed: $error $errstr" . PHP_EOL );
		}

		// Build the Binary Notification.
		if (is_array ( $this->tToken )) {
			foreach ( $this->tToken as $token ) {
				$tMsg = chr ( 0 ) . chr ( 0 ) . chr ( 32 ) . pack ( 'H*', $token ) . pack ( 'n', strlen ( $this->tBody ) ) . $this->tBody;

				// Send the Notification to the Server.
				$tResult = fwrite ( $tSocket, $tMsg, strlen ( $tMsg ) );

				if ($tResult) {
					$result = 'Delivered Message to APNS' . PHP_EOL;
				} else {
					$result = 'Could not Deliver Message to APNS' . PHP_EOL;
					break;
				}
			}
		} elseif (is_string ( $this->tToken )) {
			$tMsg = chr ( 0 ) . chr ( 0 ) . chr ( 32 ) . pack ( 'H*', $this->tToken ) . pack ( 'n', strlen ( $this->tBody ) ) . $this->tBody;

			// Send the Notification to the Server.
			$tResult = fwrite ( $tSocket, $tMsg, strlen ( $tMsg ) );

			if ($tResult) {
				$result = 'Delivered Message to APNS' . PHP_EOL;
			} else {
				$result = 'Could not Deliver Message to APNS' . PHP_EOL;
			}
		}

		// Close the Connection to the Server.
		fclose ( $tSocket );

		return $result;
	}
}
?>