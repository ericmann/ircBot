<?php
/**
 * Original Filename: class-config-sample.php
 * User: carldanley
 * Created on: 12/14/12
 * Time: 12:26 PM
 */
class Config{

	public static $ircChannels = array( '#channel-a', '#channel-b' );
	public static $ircServer = 'irc.freenode.net';
	public static $ircPort = 6667;
	public static $ircNick = 'nick';
	public static $ircServiceName = 'ircBot-v1.0';
	public static $ircDebug = false;

	public static $pluginsDirectory = '/../plugins/';

	public static $dbHost = 'localhost';
	public static $dbUsername = 'root';
	public static $dbPassword = 'root';
	public static $dbDatabase = 'ircBot';

}
