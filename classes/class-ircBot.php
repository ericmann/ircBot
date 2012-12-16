<?php
/**
 * Original Filename: class-ircBot.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 12:48 AM
 */

// require our singletons for plugins and the database stuff
require_once( __DIR__ . '/class-config.php' );
require_once( __DIR__ . '/class-database.php' );
require_once( __DIR__ . '/class-pluginManager.php' );
require_once( __DIR__ . '/class-cronSystem.php' );

class ircBot{

	private static $_instance = false;
	private static $_socket = false;

	// this needs to be updated when the bot joins/parts channels or even gets kicked from a channel
	private static $_channelsConnectedTo = array();

	public function __construct(){
		// setup PHP settings for not expiring this script
		set_time_limit( 0 );

		// load all plugins into play
		pluginManager::loadPlugins();

		// add support for user-joined
		pluginManager::addAction( 'user-join', array( $this, 'checkForNewChannel' ) );
	}

	public function checkForNewChannel( $username = '', $channel = '' ){
		if( !$username === Config::$ircNick )
			return;

		if( in_array( $channel, self::$_channelsConnectedTo ) )
			return;

		self::$_channelsConnectedTo[] = $channel;
	}

	public static function sanitizeString( $str = '' ){
		$str = str_replace( "\n", '', $str );
		$str = str_replace( "\r", '', $str );
		$str = str_replace( "\t", '', $str );

		return $str;
	}

	public function __destruct(){
		if( self::$_socket ){
			socket_close( self::$_socket );
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
		//self::$_socket = fsockopen( gethostbyname( Config::$ircServer ), Config::$ircPort, $error_number, $error_string, -1 );
		self::$_socket = socket_create( AF_INET, SOCK_STREAM, 0 );
		$socketConnection = socket_connect( self::$_socket, Config::$ircServer, Config::$ircPort );

		// check that we actually joined
		if( !self::$_socket || !$socketConnection )
			die( 'Error while connecting to ' . Config::$ircServer );

		// set nonblocking
		socket_set_nonblock( self::$_socket );

		// now actually login to the server and join the channels we need
		self::_login();
		self::_joinChannels();

		// listen for any commands now until this script is closed manually through kill command or user interaction
		while( 1 ){

			// listen for any incoming commands
			self::_listen();

			// check for cron jobs that might need to be run
			cronSystem::checkJobs();
		}

		// close the socket to the IRC server
		fclose( self::$_socket );
	}

	private static function _listen(){
		// timeout: 1 second = 1000000
		$timeout = 1000000;

		// listen to all commands & messages sent to the bot
		$sockets = array( self::$_socket );
		$socketUpdated = socket_select( $sockets, $write = array(), $exception = array(), 0, $timeout );

		if( $socketUpdated === 1 ){
			$data = socket_read( self::$_socket, 1024 );

			// show irc raw output for debugging purposes
			if( Config::$ircDebug )
				echo $data;

			// split by newlines and process each one
			$lines = explode( "\n", $data );

			// handle & process this command if needed
			array_walk( $lines, array( self::getInstance(), 'processIRCMessage' ) );
		}
	}

	public static function processIRCMessage( $data = '' ){
		// sanitize this data
		$data = self::sanitizeString( $data );

		// first off, grab the user name from the message
		$username = self::_extractIRCUsername( $data );

		// start checking the types of messages that can occur and what we really care about
		// i imagine that this will grow in the future, but for now - we are simply implementing as we go
		if( self::_checkPingPong( $data ) )
			return;
		else if( self::_checkUserNameTaken( $data ) )
			die( 'Username "' . Config::$ircNick . '" already taken on ' . Config::$ircServer . "\n" );
		else if( self::_checkChannelMessage( $data, $username ) )
			return;
		else if( self::_checkUserPart( $data, $username ) )
			return;
		else if( self::_checkUserJoin( $data, $username ) )
			return;
	}

	private static function _checkUserNameTaken( $data = '' ){
		return preg_match( '/Nickname is already in use./i', $data );
	}

	private static function _checkPingPong( $data = '' ){
		if( preg_match( '/^PING\s/i', $data ) ){
			$data = str_replace( 'PING ', 'PONG ', $data ) . "\n";
			socket_write( self::$_socket, $data );
			return true;
		}
		return false;
	}

	private static function _checkUserPart( $data = '', $username = '' ){
		if( preg_match( '/\sPART\s#(.*)\s:/i', $data, $channel ) ){
			$channel = $channel[ 1 ];

			pluginManager::doAction( 'user-part', $username, $channel );
		}
		return false;
	}

	private static function _checkUserJoin( $data = '', $username = '' ){
		if( preg_match( '/\sJOIN\s#(.*)/i', $data, $channel ) ){
			$channel = self::sanitizeString( $channel[ 1 ] );

			pluginManager::doAction( 'user-join', $username, $channel );

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

			if( $privateMessage )
				pluginManager::doAction( 'private-message', $username, $channel, $message );
			else
				pluginManager::doAction( 'channel-message', $username, $channel, $message );

			return true;
		}

		return false;
	}

	public static function sendChannelMessage( $channel = '', $message = '' ){
		// strip the hash if it exists
		$channel = str_replace( '#', '', $channel );

		// make sure the bot is connected to this channel before we try sending this message
		if( !in_array( $channel, self::$_channelsConnectedTo ) )
			return;

		// now add the slash back
		$channel = '#' . $channel;

		// write to the socket now
		socket_write( self::$_socket, 'PRIVMSG ' . $channel . ' :' . $message . "\n" );
	}

	private static function _joinChannels(){
		// Join all specified channels
		foreach( Config::$ircChannels as $channel ){
			socket_write( self::$_socket, 'JOIN ' . $channel . "\n" );
		}
	}

	private static function _login(){
		// login to the IRC server
		socket_write( self::$_socket, 'USER ' . Config::$ircNick . ' ' . Config::$ircServiceName . ' ' . Config::$ircServer . ' ircBot' . "\n" );
		socket_write( self::$_socket, 'NICK ' . Config::$ircNick . "\n" );
	}

}
