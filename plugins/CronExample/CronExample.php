<?php
/**
 * Original Filename: CronExample.php
 * User: carldanley
 * Created on: 12/15/12
 * Time: 11:04 PM
 */

class CronExample{

	public function __construct(){
		// register a new cron job that will output a sample message in each IRC channel
		CronSystem::register( 'cronExample.timeUpdater', array( $this, 'runCronJob' ), array( 'second' => '00,30' ) );
	}

	public function runCronJob(){
		// assume we've successfully connected to each channel we specified
		foreach( Config::$ircChannels as $channel ){
			ircBot::sendChannelMessage( $channel, 'The time is now: ' . date( 'g:i:sa' ) . ' on ' . date( 'l, F j, Y' ) . ' (' . Config::$defaultTimeZone . ')' );
		}
	}

}