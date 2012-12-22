<?php
/**
 * Original Filename: QuotesDaddy.php
 * User: ericmann
 * Created on: 12/21/12
 * Time: 8:26 AM
 */
class QuotesDaddy {

	public function __construct(){
		pluginManager::addAction( 'channel-message', array( $this, 'checkCommands' ) );

		$this->_install_database();
		//$this->_update_cache();
		//CronSystem::register( 'quotesDaddy.refreshCache', array( $this, '_update_cache' ), array( 'hour' => '0,12' ) );
	}

	/**
	 * Parse the IRC stream for new commands.
	 *
	 * @param string $username User who issued the command
	 * @param string $channel  Channel on which the command was issued
	 * @param string $message  Full text of message possibly containing command
	 *
	 * @uses ircBot::sendChannelMessage()
	 */
	public function checkCommands( $username = '', $channel = '', $message = '' ){
		if( preg_match( '/^!quote/i', $message ) ){
			if ( $quote = $this->_get_quote() ) {
				ircBot::sendChannelMessage( $channel, '"' . $quote['quote_text'] . '" - ' . $quote['quote_author'] );
			}
		}
	}

	/**
	 * Fetch a single quote from the database.
	 *
	 * @uses Database::connect()
	 * @uses Database::get()
	 * @uses Database::disconnect()
	 *
	 * @return array|bool Returns false on failure.
	 */
	private function _get_quote() {
		Database::connect();
		$quote = Database::get( 'quotes', 1, array( 'quote_text', 'quote_author' ) );
		Database::disconnect();

		$quote = $quote[0];

		if ( isset( $quote['quote_author'] ) && isset( $quote['quote_text'] ) ) {
			return $quote;
		}

		return false;
	}

	/**
	 * Create a database to contain the cached messages if it doesn't exist.
	 *
	 * @uses Database::connect()
	 * @uses Database::query()
	 * @uses Database::disconnect()
	 */
	private function _install_database() {
		$query = 'CREATE TABLE IF NOT EXISTS quotes ( id INT(11) UNIQUE AUTO_INCREMENT, quote_hash VARCHAR(100), quote_author VARCHAR(100), quote_text TEXT );';

		Database::connect();
		Database::query( $query, false, false );
		Database::disconnect();
	}

	/**
	 * Update the cache with random quotes from QuotesDaddy's API.
	 */
	public function _update_cache() {
		$api_user_name = 'lennon_22';
		$apiUserKey = 'BJz2LYhjOB4grzD8fhrt49rviSW3yvKT';
		$current_user_name = 'zzzrByte';
		$tag = 'science';
		$max_results = 1;
		$page = 1;
		$query_hash = hash_hmac( 'md5', $current_user_name . 'random_tagged' . $tag . $max_results . $page, $apiUserKey );

		$xml_request_url = 'http://www.quotesdaddy.com/api/' . $api_user_name . '/' . $current_user_name . '/' . $query_hash . '/quotes/random_tagged/' . $tag . '/' . $max_results . '/' . $page;

		// Todo: Abstract HTTP requests so they don't use cURL directly
		$handle = curl_init();

		curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, pluginManager::applyFilter( 'curl_connecttimeout', 5 ) );
		curl_setopt( $handle, CURLOPT_TIMEOUT, pluginManager::applyFilter( 'curl_timeout', 5 ) );
		curl_setopt( $handle, CURLOPT_URL, $xml_request_url);
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, pluginManager::applyFilter( 'curl_ssl_verifyhost', false ) );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, pluginManager::applyFilter( 'curl_ssl_verify_peer', false ) );
		curl_setopt( $handle, CURLOPT_USERAGENT, pluginManager::applyFilter( 'curl_useragent', 'ircBot' ) );
		curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, false );
		curl_setopt( $handle, CURLOPT_CUSTOMREQUEST, 'GET' );
		curl_setopt( $handle, CURLOPT_HEADER, false );
		curl_setopt( $handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );

		$response = curl_exec( $handle );
		$response_code = curl_getinfo( $handle, CURLINFO_HTTP_CODE );

		curl_close( $handle );

		// If there's no response, die.
		if ( 0 == strlen( $response ) || 200 != $response_code ) {
			return;
		}

		var_dump( $response );
	}
}