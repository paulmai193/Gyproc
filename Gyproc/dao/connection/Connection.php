<?php
require_once 'Medoo.php';
require_once '../model/UserInfo.php';
require_once '../model/DeviceInfo.php';
require_once '../model/VersionInfo.php';
class MySqlConnection {
	// Initialize
	public static $database;
	private static $numTables = 3;
	// Enjoy
	public static function connect() {
		$config = include_once '../define/Config.php';
		self::$database = new medoo ( $config ['db'] );
		$count = self::$database->query ( 'SELECT COUNT(*) FROM information_schema.tables WHERE TABLE_SCHEMA = \'gyproc_2\' AND TABLE_NAME IN (\'deviceinfo\', \'userinfo\', \'versioninfo\')' )->fetchColumn ();

		if ($count != self::$numTables) {
			self::initializeDb ();
		}
	}
	public static function testConnect() {
		// $this::database;
		if (self::$database == null) {
			self::connect ();
		}

		return json_encode ( self::$database->select ( 'userinfo', array (
				'name'
		) ) );
	}
	private static function initializeDb() {
		$sqlFile = dirname ( __FILE__ ) . '/gyproc.sql';
		$sqls = file_get_contents ( $sqlFile );
		foreach ( explode ( ';', $sqls ) as $sql ) {
			if (trim ( $sql ) !== '')
				self::$database->exec ( $sql );
		}
	}
}

// Bootstrap MySQL connection
MySqlConnection::connect ();

?>
