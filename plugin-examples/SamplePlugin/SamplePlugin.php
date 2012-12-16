<?php
/**
 * Original Filename: SamplePlugin.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 2:37 PM
 */
class SamplePlugin{

	public function __construct(){
		pluginManager::addAction( 'channel-message', array( $this, 'checkCommands' ) );
	}

	public function checkCommands( $username = '', $channel = '', $message = '' ){
		if( preg_match( '/^!test/i', $message ) ){
			ircBot::sendChannelMessage( $channel, $username . ': ♩♫♫♩♬♪♩♫♬♩' );
		}
	}
}