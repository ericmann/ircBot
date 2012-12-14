<?php
/**
 * Original Filename: class-ircBot.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 12:48 AM
 */
class ircBot{

	private static $_instance = false;

	public static $channels = array( '#team10up-dev' );
	public static $server = 'irc.freenode.net';
	public static $port = 6667;
	public static $nick = 't10ircBOT';

	private static $_socket = false;

	public function __construct(){
		// tell PHP to ignore timing this script out
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
		// open the socket to the IRC server
		self::$_socket = fsockopen( gethostbyname( self::$server ), self::$port, $error_number, $error_string, -1 );

		// check that we actually joined
		if( !self::$_socket )
			die( 'Error while connecting to ' . self::$server . ': [' . $error_number . ']: ' . $error_string );

		self::_login();
		self::_joinChannels();

		// listen for any commands now until this script is closed
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
			echo $data;
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
				'type' => 'part',
				'username' => $username,
				'channel' => $channel,
				'time' => time()
			);
		}
		return false;
	}

	private static function _checkUserJoin( $data = '', $username = '' ){
		if( preg_match( '/\sJOIN\s#(.*)/i', $data, $channel ) ){
			$channel = $channel[ 1 ];

			$data = array(
				'type' => 'join',
				'username' => $username,
				'channel' => $channel,
				'time' => time()
			);

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

			// check to see if this is a private message or not
			$privateMessage = false;
			if( substr( $channel, 0, 1 ) !== '#' )
				$privateMessage = true;

			// now we need to pass this data to the callback functions that need it
			$data = array(
				'type' => ( $privateMessage ) ? 'private-message' : 'channel-message',
				'username' => $username,
				'channel' => $channel,
				'message' => $message,
				'time' => time()
			);

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
		foreach( self::$channels as $channel ){
			fputs( self::$_socket, 'JOIN ' . $channel . "\n" );
		}
	}

	private static function _login(){
		// login to the IRC server
		fputs( self::$_socket, 'USER ' . self::$nick . ' carldanley.com ' . self::$server . ' ircBot' . "\n" );
		fputs( self::$_socket, 'NICK ' . self::$nick . "\n" );
	}

}
