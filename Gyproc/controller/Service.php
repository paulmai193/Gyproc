<?php
require_once 'Slim/Slim.php';
require_once '../dao/connection/Connection.php';

\Slim\Slim::registerAutoloader ();

$app = new \Slim\Slim ();

$app->get ( '/index', function () {
	$response = array ();
	$response ['message'] = "Welcome to Gyproc Webservice";

	jsonResponse ( 200, $response );
} );

// Get user information by id
$app->get ( '/user(/)(:type/?)(:id/?)', function ($type = null, $id = null) {
	$response = array ();
	try {
		$condition;
		switch ($type) {
			case 'id' :
				$condition = $id == null ? null : array (
						'iduser' => $id
				);
				break;

			case 'email' :
				$condition = $id == null ? null : array (
						'email' => $id
				);
				break;

			case 'phone' :
				$condition = $id == null ? null : array (
						'phone' => $id
				);
				break;

			case 'name' :
				$condition = $id == null ? null : array (
						'name[~]' => $id
				);
				break;
			default :
				$condition = null;
				break;
		}

		$userinfo = MySqlConnection::$database->select ( 'userinfo', '*', $condition );

		$countElement = sizeof ( $userinfo );
		if ($countElement == 0) {
			$userinfo = "";
		} else if ($countElement == 1) {
			$userinfo = $userinfo [0];
		}
		$response ["error"] = false;
		$response ["userinfo"] = $userinfo;
		jsonResponse ( 200, $response );
	} catch ( Exception $e ) {
		$response ["error"] = true;
		$response ["message"] = $e->getMessage ();
		jsonResponse ( 500, $response );
	}
} );

// Add new user
$app->post ( '/info/add', function () use ($app) {
	$content = json_decode ( $app->request ()->getBody (), true );
	$device = $content ['device'];
	if (array_key_exists ( 'user', $content )) {
		$user = $content ['user'];
	} else {
		$user = null;
	}

	try {
		$response = array ();

		// Get device info first
		$deviceinfo = MySqlConnection::$database->select ( 'deviceinfo', "*", array (
				'uuid' => $device ['uuid']
		) );
		if (sizeof ( $deviceinfo ) == 0) {
			// Create new device
			$idDevice = MySqlConnection::$database->insert ( 'deviceinfo', $device );
			if ($idDevice == 0) {
				throw new PDOException ( "Cannot add new device" );
			}
		} else {
			foreach ( $device as $key => $value ) {
				$deviceinfo [0] [$key] = $value;
			}
			// Update current information to this device
			MySqlConnection::$database->update ( 'deviceinfo', $deviceinfo [0], array (
					'uuid' => $device ['uuid']
			) );
			$idDevice = $deviceinfo [0] ['id_device'];
		}

		// Create / update user info
		if ($user != null) {
			// Get user info first
			$userinfo = MySqlConnection::$database->select ( 'userinfo', "*", array (
					'email' => $user ['email']
			) );
			if (sizeof ( $userinfo ) == 0) {
				$user ['id_device'] = $idDevice;

				$idUser = MySqlConnection::$database->insert ( 'userinfo', $user );
				if ($idUser == 0) {
					throw new PDOException ( "Cannot add new user" );
				}
			} else {
				$userinfo [0] ['id_device'] = $idDevice;
				foreach ( $user as $key => $value ) {
					$userinfo [0] [$key] = $value;
				}
				$updateResult = MySqlConnection::$database->update ( 'userinfo', $userinfo [0], array (
						'email' => $user ['email']
				) );
				if ($updateResult == false) {
					throw new PDOException ( "Cannot update user info" );
				}
			}
		}

		$response ['error'] = false;
		jsonResponse ( 200, $response );
	} catch ( PDOException $e ) {
		$response ["error"] = true;
		$response ["message"] = $e->getMessage ();
		$response ["detail"] = MySqlConnection::$database->error ();

		jsonResponse ( 500, $response );
	} catch ( Exception $e ) {
		$response ["error"] = true;
		$response ["message"] = $e->getMessage ();
		$response ["detail"] = $e->getTraceAsString ();

		jsonResponse ( 500, $response );
	}
} );

// Synchronize data from server by version (OLD IMPLEMENT)
$app->get ( '/sync/old', function () use ($app) {
	$ver_source = $app->request ()->params ( 'version' );

	$response;
	try {
		$cur_ver = MySqlConnection::$database->select ( 'versioninfo', '*' );
		$cur_ver_source = $cur_ver [0] ['source'];
		if ($ver_source != $cur_ver_source) {

			// get data from url
			$ch = curl_init ();

			curl_setopt ( $ch, CURLOPT_URL, 'http://gyproc.akadigital.vn/load/app.php?category=furnitre' );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

			$result = curl_exec ( $ch );

			curl_close ( $ch );

			// Modify response
			$response = json_decode ( $result, true );
			unset ( $response ['resulf'] ); // Unused

			$time = array ();
			foreach ( $response ['data'] as $data ) {
				foreach ( $data ['time'] as $each_time ) {
					array_push ( $time, $each_time ['from'] );
				}
			}
			for($i = 0; $i < sizeof ( $time ); $i ++) {
				$response ['time_' . $i] = $time [$i];
			}

			unset ( $response ['data'] );
		}

		$response ['version'] = $cur_ver [0] ['source'];
		$response ['error'] = false;

		jsonResponse ( 200, $response );
	} catch ( Exception $e ) {
		$response ['error'] = true;
		$response ['message'] = $e->getMessage ();
		$response ['detail'] = $e->getTraceAsString ();

		jsonResponse ( 500, $response );
	}
} );

// Synchronize data from server by version (NEW IMPLEMENT)
$app->get ( '/sync', function () use ($app) {
	$response = array ();
	try {
		// ///// Check version
		$chk_version = $app->request ()->params ( 'version' );
		$cur_ver = MySqlConnection::$database->select ( 'versioninfo', '*' );
		$cur_ver_source = $cur_ver [0] ['source'];

		if ($chk_version != $cur_ver_source) {

			// get data from url
			$ch = curl_init ();

			curl_setopt ( $ch, CURLOPT_URL, 'http://' . $_SERVER ['HTTP_HOST'] . '/load/update.php' );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );

			$result = curl_exec ( $ch );

			curl_close ( $ch );

			// ////////// Work with gyproc response
			$tmp_response = json_decode ( $result, true );

			// ///// Check version
			$cur_version = $tmp_response ['version'] ['meta'] + $tmp_response ['version'] ['post'];

			// ///// Modify response
			// // Video
			$tmp_furniture = $tmp_response ['furnitre'];
			$response ['url_video'] = $tmp_furniture ['url_video'];
			$tmp_video = array ();
			foreach ( $tmp_furniture ['data'] as $data ) {
				$tmp_video [$data ['type_name']] = array (); // Ex: Phong khach
				$tmp_video [$data ['type_name']] ['begin'] = $data ['time_furnitre'];
				foreach ( $data ['time'] as $each_time ) {
					$tmp_time = array (
							$each_time ['type'] => $each_time ['from']
					); // Ex: Chia nho phong => 0
					$tmp_video [$data ['type_name']] [$each_time ['type']] = $each_time ['from'];
				}
			}
			$response ['time_video'] = $tmp_video;
			unset ( $tmp_furniture );
			unset ( $tmp_response ['furnitre'] );
			// // Done video

			// // Webview
			$tmp_news = $tmp_response ['news'];
			$tmp_webview = array ();
			foreach ( $tmp_news ['data'] as $data ) {
				$tmp_webview [$data ['name']] = $data ['url'];
			}
			$response ['webview'] = $tmp_webview;
			unset ( $tmp_news );
			unset ( $tmp_response ['news'] );
			// // Done Webview

			// // Photo
			$tmp_designroom = $tmp_response ['designroom'];
			$tmp_photo = array ();
			foreach ( $tmp_designroom ['data'] as $data ) {
				$tmp_photo [$data ['key']] = $data ['image']; // Photo level 1
				foreach ( $data ['next_step_2'] as $step_2 ) {
					foreach ( $step_2 ['children'] as $children ) {
						$tmp_photo [$children ['key']] = $children ['image'];
						foreach ( $children ['next_step_3'] as $step_3 ) {
							if ($step_3 ['image'] != null) {
								$tmp_photo [$step_3 ['key']] = $step_3 ['image'];
							}
						}
					}
				}
			}
			$response ['photo'] = $tmp_photo;
			unset ( $tmp_designroom );
			unset ( $tmp_response ['designroom'] );
			// // Done photo
		}

		$response ['version'] = $cur_ver_source;
		$response ['error'] = false;

		jsonResponse ( 200, $response );
	} catch ( Exception $e ) {
		$response ['error'] = true;
		$response ['message'] = $e->getMessage ();
		$response ['detail'] = $e->getTraceAsString ();

		jsonResponse ( 500, $response );
	}
} );

// Push new notify to all mobile client
$app->post ( '/push', function () use ($app) {
	// Get title and message of push
	$title = $app->request ()->post ( 'title' );
	$msg = $app->request ()->post ( 'message' );
	$user_filter = $app->request ()->post ( 'user_filter' ); // all, registered, non_register
	$user_role = $app->request ()->post ( 'user_role' ); // all, chủ nhà, kiến trúc sư, nhà phân phối, nhà thi công/ nhà thầu
	$os_filter = $app->request ()->post ( 'os_filter' ); // all, ios, android
	$screen_id = $app->request ()->post ( 'screen_id' ); // ID

	try {
		// Get devices info base on user filter and role
		if ($user_filter == 'non_register') {
			// All device with isn't registered by user
			$deviceinfo = MySqlConnection::$database->query ( 'select di.* from deviceinfo di where di.id_device not in (select ui.id_device from userinfo ui)' )->fetchAll ();
		} elseif ($user_filter == 'registered') {
			// All device with isn't registered by user and filter by role
			if ($user_role == 'all') {
				$deviceinfo = MySqlConnection::$database->query ( 'select di.* from deviceinfo di where di.id_device in (select ui.id_device from userinfo ui)' )->fetchAll ();
			} else {
				$deviceinfo = MySqlConnection::$database->query ( "select di.* from deviceinfo di where di.id_device in (select ui.id_device from userinfo ui where ui.role like '$user_role')" )->fetchAll ();
			}
		} else {
			// all
			$deviceinfo = MySqlConnection::$database->select ( 'deviceinfo', '*' );
		}
		if (is_array ( $deviceinfo )) {
			$send_message = array ();
			$send_message ['message'] = $msg;
			$send_message ['screen_id'] = $screen_id;
			if ($screen_id == 'KGSHD') {
				$time_video = $app->request ()->post ( 'time_video' ); // Time video
				$send_message ['time_video'] = $time_video;
			}

			$list_android = array ();
			$list_ios = array ();
			foreach ( $deviceinfo as $value ) {
				if (strpos ( $value ['os'], 'iphone' ) !== false || strpos ( $value ['os'], 'ipad' ) !== false || strpos ( $value ['os'], 'ios' ) !== false) {
					array_push ( $list_ios, $value ['push_token'] );
				} elseif (strpos ( $value ['os'], 'android' ) !== false) {
					array_push ( $list_android, $value ['push_token'] );
				}
			}

			// Send push to these devices
			if (sizeof ( $list_ios ) > 0 && $os_filter != 'android') {
				$push = new iOSPush ( $list_ios, $title, $send_message );
				$result = $push->sendPush ();
				// var_dump ( $result );
			}

			if (sizeof ( $list_android ) > 0 && $os_filter != 'ios') {
				$push = new AndroidPush ( $list_android, $title, $send_message );
				$result = $push->sendPush ();
				// var_dump ( $result );
			}

			jsonResponse ( 200, 'Success' );
		} else {
			jsonResponse ( 200, 'Not find any device' );
		}
	} catch ( Exception $e ) {
		$response = array ();
		$response ['message'] = $e->getMessage ();
		$response ['detail'] = $e->getTraceAsString ();
		jsonResponse ( 500, $response );
	}
} );

$app->put ( '/putSomething', function () use ($app) {

	$response = array ();
	$input = $app->request->put ( 'input' ); // reading post params

	// add your business logic here
	$result = true;
	if ($result) {
		// Updated successfully
		$response ["error"] = false;
		$response ["message"] = "Updated successfully";
	} else {
		// Failed to update
		$response ["error"] = true;
		$response ["message"] = "Failed to update. Please try again!";
	}
	jsonResponse ( 200, $response );
} );

$app->delete ( '/deleteSomething', function () use ($app) {

	$response = array ();
	$input = $app->request->put ( 'input' ); // reading post params

	// add your business logic here
	$result = true;
	if ($result) {
		// deleted successfully
		$response ["error"] = false;
		$response ["message"] = "Deleted succesfully";
	} else {
		// failed to delete
		$response ["error"] = true;
		$response ["message"] = "Failed to delete. Please try again!";
	}
	jsonResponse ( 200, $response );
} );
function jsonResponse($status_code, $response) {
	$app = \Slim\Slim::getInstance ();
	$app->status ( $status_code );
	$app->contentType ( 'application/json;charset=utf-8' );

	echo json_encode ( $response );
}
function xmlResponse($status_code, $filePath) {
	$app = \Slim\Slim::getInstance ();
	$app->status ( $status_code );
	$app->contentType ( 'application/xml' );

	readfile ( $filePath );
}

$app->run ();

?>
