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
	$user = $content ['user'];
	$device = $content ['device'];

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
				foreach ( $__array as $key => $value ) {
					$userinfo->{$key} = $value;
				}
				$updateResult = MySqlConnection::$database->update('userinfo', $userinfo);
				if ($updateResult == false) {
					throw  new PDOException("Cannot update user info");
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

// Synchronize data from server by version
$app->get ( '/sync', function () use ($app) {
	$ver_source = $app->request ()->params ( 'source' );
	// $ver_filter = $app->request ()->params ( 'filter' );

	$response = array ();
	try {
		$cur_ver = MySqlConnection::$database->select ( 'versioninfo', '*' );
		$cur_ver_source = $cur_ver [0] ['source'];
		// $cur_ver_filter = $cur_ver [0] ['filter'];
		if ($ver_source != $cur_ver_source) {
			$datasource = MySqlConnection::$database->select ( 'wp_postmeta', '*' );
			$source = array ();
			foreach ( $datasource as $value ) {
				// get value of post_id from source
				if (array_key_exists ( 'item_' . $value ['post_id'], $source )) {
					$post = $source ['item_' . $value ['post_id']];
				} else {
					$post = array ();
				}
				// Add meta key & value to post
				$post [$value ['meta_key']] = $value ['meta_value'];

				// push this post to source
				$source ['item_' . $value ['post_id']] = $post;
			}
			$response ['source'] = $source;
		}

		// if ($ver_filter != $cur_ver_filter) {
		// $response ['filter'] = "http://path/to/this/filter/xml";
		// }

		$response ['version'] = $cur_ver;
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
	$user_role = $app->request ()->post ( 'user_role' ); // all, chủ nhà, kiến trúc sư, nhà phân phối, nhà thi công / nhà thầu
	$os_filter = $app->request()->post('os_filter'); // all, ios, android

	try {
		// Get devices info base on user filter and role
		if ($user_filter == 'non_register') {
			// All device with isn't registered by user
			$deviceinfo = MySqlConnection::$database->query ( 'select di.* from deviceinfo di where di.id_device not in (select ui.id_device from userinfo ui)' )->fetchAll ();
		} elseif ($user_filter == 'registered') {
			// All device with isn't registered by user and filter by role
			if ($user_role == 'all') {
				$deviceinfo = MySqlConnection::$database->select ( 'deviceinfo', '*' );
			} else {
				$deviceinfo = MySqlConnection::$database->query ( "select di.* from deviceinfo di where di.id_device not in (select ui.id_device from userinfo ui where ui.role like '$user_role')" )->fetchAll ();
			}
		} else {
			// all
			$deviceinfo = MySqlConnection::$database->select ( 'deviceinfo', '*' );
		}
		if (is_array ( $deviceinfo )) {
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
				$push = new iOSPush ( $list_ios, $title, $msg );
				$result = $push->sendPush ();
			}

			if (sizeof ( $list_android ) > 0 && $os_filter != 'ios') {
				$push = new AndroidPush ( $list_android, $title, $msg );
				$result = $push->sendPush ();
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

$app->post ( '/push/test/ios', function () use ($app) {
	$device_token = array (
			'173ff474b94194519314399115f62aa1ca1b50ca231b1dff5d4ac5f3fab10b06'
	);
	$title = 'Thành cùi bắp';
	$msg = 'alo alo??';
	try {
		$push = new iOSPush ( $device_token, $title, $msg );
		$result = $push->sendPush ();

		$response = array ();
		$response ['result'] = $result;

		jsonResponse ( 200, $response );
	} catch ( Exception $e ) {
		$response = array ();
		// $response ['error'] = true;
		$response ['message'] = $e->getMessage ();
		$response ['detail'] = $e->getTraceAsString ();

		jsonResponse ( 500, $response );
	}
} );

$app->post ( '/push/test/android', function () use ($app) {
	$device_token = array (
			'fTiqVeDhC78:APA91bFn4nnYO55Jsj2_TVFR6fyaHqPcrca3HAHKK2n7G67qJF4OlMPqUe5faXGtouBFqAC5JnYZ-AxaUHmxsETIhjeUlOqouZtCkI8DiVir9Ty3EZ6diliS46tpeyInnPjBwJmuhCn6'
	);
	$title = 'Alo 1 2 3 4';
	$msg = 'Chim sẻ đâu trả lời';
	try {
		$push = new AndroidPush ( $device_token, $msg, $title, '' );
		$result = $push->sendPush ();

		$response = array ();
		$response ['result'] = $result;

		jsonResponse ( 200, $response );
	} catch ( Exception $e ) {
		$response = array ();
		// $response ['error'] = true;
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
