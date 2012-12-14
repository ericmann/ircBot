<?php
/**
 * Original Filename: class-ircBot.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 12:48 AM
 */

// require our singletons for plugins and the database stuff
require_once( __DIR__ . '/class-config-sample.php' );
require_once( __DIR__ . '/class-database.php' );
require_once( __DIR__ . '/class-pluginManager.php' );

class ircBot{

	private static $_instance = false;

	private static $_socket = false;

	public function __construct(){
		// setup PHP settings for not expiring this script
		set_time_limit( 0 );
	}

	public function __destruct(){
		if( self::$_socket ){
			fclose( self::$_socket );
		}
	}

	public static function getInstance(){
		if( !self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	}

	public static function connect(){
		// make sure this class has been instantiated
		self::getInstance();

		// open the socket to the IRC server
		self::$_socket = fsockopen( gethostbyname( Config::$ircServer ), Config::$ircPort, $error_number, $error_string, -1 );

		// check that we actually joined
		if( !self::$_socket )
			die( 'Error while connecting to ' . Config::$ircServer . ': [' . $error_number . ']: ' . $error_string );

		self::_login();
		self::_joinChannels();

		// listen for any commands now until this script is closed manually through kill command or user interaction
		while( 1 ){
			self::_listen();
		}

		// close the socket to the IRC server
		fclose( self::$_socket );
	}

	private static function _listen(){
		// listen to all commands & messages sent to the bot, reading 128 bits at a time
		while( $data = fgets( self::$_socket, 128 ) ){
			// handle & process this command if needed
			self::_processIRCMessage( $data );
		}
	}

	private static function _processIRCMessage( $data = '' ){
		// first off, grab the user name from the message
		$username = self::_extractIRCUsername( $data );

		// start checking the types of messages that can occur and what we really care about
		// i imagine that this will grow in the future, but for now - we are simply implementing as we go
		if( self::_checkPingPong( $data ) )
			return;
		else if( self::_checkChannelMessage( $data, $username ) )
			return;
		else if( self::_checkUserPart( $data, $username ) )
			return;
		else if( self::_checkUserJoin( $data, $username ) )
			return;
	}

	private static function _checkPingPong( $data = '' ){
		if( preg_match( '/^PING\s/i', $data ) ){
			$data = str_replace( 'PING ', 'PONG ', $data ) . "\n";
			fputs( self::$_socket, $data );
			return true;
		}
		return false;
	}

	private static function _checkUserPart( $data = '', $username = '' ){
		if( preg_match( '/\sPART\s#(.*)\s:/i', $data, $channel ) ){
			$channel = $channel[ 1 ];
			$data = array(
				'username' => $username,
				'channel' => $channel,
				'time' => time()
			);

			pluginManager::doAction( 'user-part', $data );
		}
		return false;
	}

	private static function _checkUserJoin( $data = '', $username = '' ){
		if( preg_match( '/\sJOIN\s#(.*)/i', $data, $channel ) ){
			$channel = $channel[ 1 ];

			$data = array(
				'username' => $username,
				'channel' => $channel,
				'time' => time()
			);

			pluginManager::doAction( 'user-join', $data );

			return true;
		}

		return false;
	}

	/**
	 * Determines the username.
	 *
	 * @param string $data The raw IRC message received.
	 * @return bool|string Returns the username if found, otherwise false.
	 */
	private static function _extractIRCUsername( $data = '' ){
		preg_match( '/:([^!]+)!/i', $data, $username );

		// check for the username now
		if( count( $username ) === 2 )
			$username = $username[ 1 ];
		else
			$username = false;

		return $username;
	}

	private static function _checkChannelMessage( $data = '', $username = '' ){
		// check for a channel message - PRIVMSG
		if( preg_match( '/\sPRIVMSG\s(.*)\s:(.*)+/i', $data, $channel ) === 1 ){
			$channel = $channel[ 1 ];
			preg_match( '/' . $channel . '\s:(.*+)/i', $data, $message );
			$message = $message[ 1 ];

			// strip the bad characters in the message
			$message = str_replace( "\r", '', $message );
			$message = str_replace( "\n", '', $message );

			// check to see if this is a private message or not - we know it's private because there will be no hash tag
			// and the channel will match the username
			$privateMessage = false;
			if( substr( $channel, 0, 1 ) !== '#' && $username === Config::$ircNick )
				$privateMessage = true;

			// now we need to pass this data to the callback functions that need it
			$data = array(
				'username' => $username,
				'channel' => $channel,
				'message' => $message,
				'time' => time()
			);

			if( $privateMessage )
				pluginManager::doAction( 'private-message', $data );
			else
				pluginManager::doAction( 'channel-message', $data );

			return true;
		}

		return false;
	}

	public static function sendChannelMessage( $channel = '', $message = '' ){
		$data = 'PRIVMSG ' . $channel . ' :' . $message . "\n";
		fputs( self::$_socket, $data );
	}

	private static function _joinChannels(){
		// Join all specified channels
		foreach( Config::$ircChannels as $channel ){
			fputs( self::$_socket, 'JOIN ' . $channel . "\n" );
		}
	}

	private static function _login(){
		// login to the IRC server
		fputs( self::$_socket, 'USER ' . Config::$ircNick . ' ' . Config::$ircServiceName . ' ' . Config::$ircServer . ' ircBot' . "\n" );
		fputs( self::$_socket, 'NICK ' . Config::$ircNick . "\n" );
	}

}
