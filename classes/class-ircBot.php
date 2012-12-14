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
	public static $nick = 'cb-ircBOT';

	private static $_socket = false;

	public function __construct(){
		// tell PHP to ignore timing this script out
		set_time_limit( 0 );
	}

	public static function getInstance(){
		if( !self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	}

	public function connect(){
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
		// listen to all commands & messages sent to the bot
		while( $data = fgets( self::$_socket, 128 ) ){
			echo $data;
		}
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

}
