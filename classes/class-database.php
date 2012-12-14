<?php
/**
 * Original Filename: class-database.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 2:52 AM
 */

class Database{

	private static $_instance;
	private static $_db_host = 'localhost';
	private static $_db_user = 'root';
	private static $_db_pass = 'TtIeIMUL4368!';
	private static $_db_name = 'address_verified';
	private static $_mysql = false;
	private static $_query = '';
	private static $_query_type = '';
	private static $_where = array();
	private static $_variable_binds = array();
	private static $_query_data = array();

	public function __construct(){}

	public function __destruct(){
		self::disconnect( true );
	}

	public static function instance(){
		if( !( self::$_instance instanceof self ) )
			self::$_instance = new self();

		return self::$_instance;
	}

	public static function connect(){
		self::disconnect();
		self::$_mysql = new mysqli( self::$_db_host, self::$_db_user, self::$_db_pass, self::$_db_name );
	}

	public static function disconnect(){
		if( false === self::$_mysql || !( self::$_mysql instanceof mysqli ) )
			return;

		self::$_mysql->close();
		self::$_mysql = false;
	}

	public static function query( $query = '', $num_rows = false, $get_results = true ){
		self::$_query = $query;
		$statement = self::_prepare_query();
		self::_execute( $statement );

		if( $get_results )
			return self::_fetch_results( $statement );

		return true;
	}

	public static function get( $table, $num_rows = false, $fields = array( '*' ) ){
		if( ! is_array( $fields ) )
			$fields = array( $fields );

		self::_set_query_type( 'GET' );
		self::$_query = 'SELECT ' . implode( ', ', $fields ) . ' FROM ' . $table;
		$statement = self::_build_query( $num_rows );
		self::_execute( $statement );
		$results = self::_fetch_results( $statement );
		return $results;
	}

	public static function delete( $table ){
		self::_set_query_type( 'DELETE' );
		self::$_query = ' DELETE FROM ' . $table;
		$statement = self::_build_query();
		self::_execute( $statement );

		return ( 0 < $statement->affected_rows ) ? true : false;
	}

	public static function insert( $table, $data = array() ){
		self::_set_query_type( 'INSERT' );
		self::$_query = 'INSERT INTO ' . $table;
		self::$_query_data = $data;
		$statement = self::_build_query();
		self::_execute( $statement );

		return ( 0 < $statement->affected_rows ) ? true : false;
	}

	public static function update( $table, $data = array() ){
		self::_set_query_type( 'UPDATE' );
		self::$_query = 'UPDATE ' . $table . ' SET ';
		self::$_query_data = $data;
		$statement = self::_build_query();
		self::_execute( $statement );

		return ( 0 < $statement->affected_rows ) ? true : false;
	}

	public static function where( $field, $value ){
		self::$_where[ $field ] = $value;
	}

	private static function _set_query_type( $type = '' ){
		self::$_query_type = $type;
	}

	private static function _bind_variables( $statement ){
		if( 0 === count( self::$_variable_binds ) )
			return;

		$types = '';
		$values = array();

		array_walk( self::$_variable_binds, function( &$item ) use ( &$types, &$values ) {
			$types .= $item[ 'type' ];
			$values[] = &$item[ 'value' ];
		} );

		array_unshift( $values, $types );

		call_user_func_array( array( $statement, 'bind_param' ), $values );
	}

	private static function _build_query( $num_rows = false ){
		self::_build_insert_clause();
		self::_build_update_clause();
		self::_append_where_clause();

		self::$_query .= ( false !== $num_rows ) ? ' LIMIT ' . intval( $num_rows ) : '';

		$statement = self::_prepare_query();
		self::_bind_variables( $statement );
		return $statement;
	}

	private static function _build_insert_clause(){
		if( 'INSERT' !== self::$_query_type || empty( self::$_query_data ) )
			return;

		$keys = array_keys( self::$_query_data );
		$values = array_fill( 0, count( $keys ), '?' );

		$clause = ' ( ' . implode( ', ', $keys ) . ' ) VALUES ( ' . implode( ', ', $values ) . ' ) ';

		array_walk( self::$_query_data, function( $item ){
			self::_add_variable_binding( $item );
		} );

		self::$_query .= $clause;
	}

	private static function _build_update_clause(){
		if( 'UPDATE' !== self::$_query_type || empty( self::$_query_data ) )
			return;

		$clauses = array();
		array_walk( self::$_query_data, function( $item, $key ) use( &$clauses ){
			$clauses[] = $key . '= ?';
			self::_add_variable_binding( $item );
		} );

		self::$_query .= implode( ', ', $clauses );
	}

	private static function _append_where_clause(){
		if( empty( self::$_where ) )
			return '';

		$clauses = array();
		foreach( self::$_where as $field => $value ){
			$clauses[] = $field . ' = ?';
			self::_add_variable_binding( $value );
		}

		self::$_query .= ' WHERE ' . implode( ' AND ', $clauses );
	}

	private static function _add_variable_binding( $value ){
		self::$_variable_binds[] = array(
			'type' => self::_determine_variable_type( $value ),
			'value' => $value
		);
	}
	private static function _determine_variable_type( $value ){
		return substr( gettype( $value ), 0, 1 );
	}
	private static function _prepare_query(){
		if( !( $statement = self::$_mysql->prepare( self::$_query ) ) )
			trigger_error( 'Query could not be prepared.<br/>Query: <b>' . self::$_query . '</b><br/>' . self::$_mysql->error, E_USER_ERROR );

		return $statement;
	}

	private static function _execute( $statement ){
		$statement->execute();
		$statement->store_result();
		self::_reset_cache();
	}

	private static function _reset_cache(){
		self::$_query = '';
		self::$_query_type = '';
		self::$_where = array();
		self::$_variable_binds = array();
		self::$_query_data = array();
	}

	private static function _fetch_results( $statement ){
		$params = array();
		$results = array();
		$metadata = $statement->result_metadata();

		while( $field = $metadata->fetch_field() )
			$params[] = &$row[ $field->name ];

		call_user_func_array( array( $statement, 'bind_result' ), $params );

		while( $statement->fetch() ){
			$tmp = array();
			foreach( $row as $key => $value ){
				if( is_string( $value ) )
					$value = stripslashes( $value );

				$tmp[ $key ] = $value;
			}

			$results[] = $tmp;
		}

		return $results;
	}

}
