<?php
/**
 * Original Filename: DatabaseExample.php
 * User: carldanley
 * Created on: 12/16/12
 * Time: 2:09 AM
 */

class DatabaseExample{

	public function __construct(){
		// setup our database before-hand
		self::_setupDatabase();

		// register our hook now
		pluginManager::addAction( 'channel-message', array( $this, 'logChannelMessage' ) );
	}

	private static function _setupDatabase(){
		$query = 'CREATE TABLE IF NOT EXISTS messages ( id INT(11) UNIQUE AUTO_INCREMENT, message TEXT, username VARCHAR(100), channel VARCHAR(100), timestamp INT(11) );';
		Database::connect();
		Database::query( $query, false, false );
		Database::disconnect();
	}

	public function logChannelMessage( $username = '', $channel = '', $message = '' ){
		Database::connect();
		Database::insert( 'messages', array(
			'username' => $username,
			'channel' => $channel,
			'message' => $message,
			'timestamp' => time()
		) );
		Database::disconnect();
	}

}