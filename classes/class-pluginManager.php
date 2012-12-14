<?php
/**
 * Original Filename: class-pluginManager.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 2:51 AM
 */

class pluginManager{

	private static $_plugins = array();
	private static $_actions = array();
	private static $_filters = array();
	private static $_instance = false;

	/**
	 * Relative to where this pluginManager file is actually included
	 *
	 * @var string
	 */
	private static $_pluginsDirectory = '/../plugins/';

	public function __construct(){
		//
	}

	public static function getInstance(){
		if( !self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	}

	public static function loadPlugins(){
		// make sure that an instance of this pluginManager exists already

		// check to make sure the plugins directory exists
		if( !self::_pluginsDirectoryExists() )
			return;

		// scan the directory, searching for plugins that fit the stereotype
		self::_scanForPlugins();
	}

	protected static function _scanForPlugins(){
		// open a handle to the directory filesystem
		$directory = __DIR__ . self::$_pluginsDirectory;
		$handle = opendir( $directory );

		// check to make sure we could open the directory handle
		if( !$handle )
			return;

		// now begin looping through all of the contents of the plugin directory
		while( ( $dir = readdir( $handle ) ) !== false ){
			if( $dir === '.' || $dir === '..' )
				continue;

			// now we need to verify that the current "file" is a directory before continuing
			if( !is_dir( $directory . $dir ) )
				continue;

			// expect a class file to exist in the format "class-<DirectoryName>.php"
			$classFile = $directory . $dir . '/class-' . $dir . '.php';
			if( !file_exists( $classFile ) )
				continue;

			// now actually include the code
			require_once( $classFile );

			// now expect the class name to match whatever the $dir name was
			if( !class_exists( $dir ) )
				continue;

			// now we can instantiate the class name and store it now
			self::$_plugins[] = new $dir();
		}
	}

	protected static function _pluginsDirectoryExists(){
		$directory = __DIR__ . self::$_pluginsDirectory;
		return is_dir( $directory );
	}

	public static function doAction( /* $action = '', ( $arg1, $arg2, ... ) */ ){
		// setup the arguments so we can pass whatever was passed to us
		$arguments = func_get_args();
		$action = array_shift( $arguments );

		if( !isset( self::$_actions[ $action ] ) )
			return;

		// loop through all of the callbacks for this action
		foreach( self::$_actions[ $action ] as $callback ){
			call_user_func_array( $callback[ 'callback' ], $arguments );
		}
	}

	public static function addAction( $action = '', $callback = array(), $priority = 10 ){
		if( !method_exists( $callback[ 0 ], $callback[ 1 ] ) )
			return false;

		if( !isset( self::$_actions[ $action ] ) )
			self::$_actions[ $action ] = array();

		// add the action with it's priority as well
		self::$_actions[ $action ][] = array(
			'callback' => $callback,
			'priority' => $priority
		);

		// now sort the array to make sure that we're good to go
		usort( self::$_actions[ $action ], array( self::getInstance(), 'sortByPriority' ) );

		return true;
	}

	private function sortByPriority( $a, $b ){
		if( $a[ 'priority' ] > $b[ 'priority' ] )
			return true;

		return false;
	}

	public static function removeAction( $action = '' ){
		if( !isset( self::$_actions[ $action ] ) )
			return;

		unset( self::$_actions[ $action ] );
	}

	public static function applyFliter( $filter = '', $data = '' ){
		if( !isset( self::$_filters[ $filter ] ) )
			return $data;

		// loop through all of the callbacks for this action
		foreach( self::$_filters[ $filter ] as $callback ){
			$data = call_user_func_array( $callback[ 'callback' ], array( $data ) );
		}

		return $data;
	}

	public static function addFilter( $filter = '', $callback = array(), $priority = 10 ){
		if( !method_exists( $callback[ 0 ], $callback[ 1 ] ) )
			return false;

		if( !isset( self::$_filters[ $filter ] ) )
			self::$_filters[ $filter ] = array();

		// add the action with it's priority as well
		self::$_filters[ $filter ][] = array(
			'callback' => $callback,
			'priority' => $priority
		);

		// now sort the array to make sure that we're good to go
		usort( self::$_filters[ $filter ], array( self::getInstance(), 'sortByPriority' ) );

		return true;
	}

	public static function removeFilter( $filter = '' ){
		if( !isset( self::$_filters[ $filter ] ) )
			return;

		unset( self::$_filters[ $filter ] );
	}

}
