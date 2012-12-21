<?php
/**
 * Original Filename: QuotesDaddyPlugin.php
 * User: ericmann
 * Created on: 12/21/12
 * Time: 8:26 AM
 */
class SamplePlugin{

	public function __construct(){
		pluginManager::addAction( 'channel-message', array( $this, 'checkCommands' ) );

		// Todo set up database and initial cache of quotes
	}

	public function checkCommands( $username = '', $channel = '', $message = '' ){
		if( preg_match( '/^!quote/i', $message ) ){
			// Todo Fetch random message from the cache
		}
	}
}