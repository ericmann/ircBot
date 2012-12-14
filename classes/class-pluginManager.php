<?php
/**
 * Original Filename: class-pluginManager.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 2:51 AM
 */
class pluginManager{

	public function __construct(){
		//
	}

	public static function doAction( $action = '', $data = '' ){
		echo 'fired action: "', $action, '" with data: ', json_encode( $data ), "\n";
	}

	public static function addAction( $action = '', $callback = array() ){
		//
	}

	public static function removeAction( $action = '' ){
		//
	}

	public static function applyFliter( $filter = '', $data = '' ){
		//
	}

	public static function addFilter( $filter = '', $callback = array() ){
		//
	}

	public static function removeFilter( $filter = '' ){
		//
	}

}
