<?php
/**
 * Original Filename: class-cronSystem.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 7:15 PM
 */
class cronSystem{

	private static $_cronJobs = array();
	private static $_instance = false;

	public function __construct(){
		date_default_timezone_set( Config::$defaultTimeZone );
	}

	public static function getInstance(){
		if( !self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	}

	public static function register( $cronName = '', $callback = array(), $interval = array() ){
		if( isset( self::$_cronJobs[ $cronName ] ) )
			die( 'Cron Job "' . $cronName . '" has already been registered!' );

		// verify that the callback function actually exists as valid method
		if( !method_exists( $callback[ 0 ], $callback[ 1 ] ) )
			die( 'Cron Job "' . $cronName . '" has an invalid callback function!' );

		// sanitize the user's values
		$interval = self::_sanitizeInterval( $interval );

		// now that we know the cron doesn't exist and the method is valid, add the callback to our cronjobs with the specified interval
		self::$_cronJobs[ $cronName ] = array(
			'callback' => $callback,
			'interval' => $interval
		);

		return true;
	}

	private static function _sanitizeInterval( $interval = array() ){
		$new = array();
		$validFormats = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );

		foreach( $validFormats as $validFormat ){
			if( !isset( $interval[ $validFormat ] ) ){

				if( $validFormat === 'year' )
					$new[ $validFormat ] = '*';
				else if( $validFormat === 'month' || $validFormat === 'day' || $validFormat === 'hour' )
					$new[ $validFormat ] = '01';
				else if( $validFormat === 'minute' || $validFormat === 'second' )
					$new[ $validFormat ] = '00';

				continue;
			}

			// now validate all of the times
			if( !( $times = self::_validateTimes( $interval[ $validFormat ], $validFormat ) ) )
				continue;

			$new[ $validFormat ] = $times;
		}

		return $new;
	}

	private static function _validateTimes( $times = '', $format = '' ){
		$times = explode( ',', $times );
		$times = array_map( 'intval', $times );

		return implode( ',', $times );
	}

	public static function remove( $cronName = '' ){
		if( !isset( self::$_cronJobs[ $cronName ] ) )
			return false;

		unset( self::$_cronJobs[ $cronName ] );
	}

	public static function checkJobs(){
		array_walk( self::$_cronJobs, array( self::getInstance(), 'checkIfNeedsToBeRun' ) );
	}

	public static function checkIfNeedsToBeRun( $cron = array() ){
		// these are valid interval values & date formats that can be used and will be checked against
		$validIntervals = array(
			'year' => 'y', 'month' => 'm', 'day' => 'd',
			'hour' => 'h', 'minute' => 'i', 'second' => 's'
		);

		// get the user's defined intervals from the cron to save lookups later
		$userIntervals = $cron[ 'interval' ];

		// assume we can run the callback until proven otherwise
		$canRun = true;

		// check all the valid intervals in order - order matters logically
		foreach( $validIntervals as $interval => $format ){

			// get the intervals set by the user
			$userInterval = $userIntervals[ $interval ];

			// make sure the interval matches exactly now
			if( !self::_compareIntervalToNow( $userInterval, $format ) ){
				// no point in continuing if the interval doesn't match perfectly
				$canRun = false;
				break;
			}
		}

		// check to see if we can still run the cron
		if( $canRun )
			call_user_func_array( $cron[ 'callback' ], array() );
	}

	private static function _compareIntervalToNow( $intervals = '*', $format = 's' ){
		if( $intervals === '*' )
			return true;

		$intervals = explode( ',', $intervals );
		$currentTime = intval( date( $format ) );

		foreach( $intervals as $interval ){
			$interval = intval( $interval );

			if( $interval === $currentTime )
				return true;
		}

		return false;
	}

}
