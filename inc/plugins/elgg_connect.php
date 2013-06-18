<?php
/**
 * Elgg Connect Plugin
 * Copyright 2013 Thang Nguyen, All Rights Reserved
 *
 * Website: http://atemix.com
 * License: LGPL
 *
 * 
 */
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_start", "elgg_connect_global_start");

function elgg_connect_info()
{
	/**
	 * Array of information about the plugin.
	 * name: The name of the plugin
	 * description: Description of what the plugin does
	 * website: The website the plugin is maintained at (Optional)
	 * author: The name of the author of the plugin
	 * authorsite: The URL to the website of the author (Optional)
	 * version: The version number of the plugin
	 * guid: Unique ID issued by the MyBB Mods site for version checking
	 * compatibility: A CSV list of MyBB versions supported. Ex, "121,123", "12*". Wildcards supported.
	 */
	return array(
		"name"			=> "Elgg Connect!",
		"description"	=> "Connect to Elgg elgg.org",
		"website"		=> "http://mybb.com",
		"author"		=> "Thang Nguyen",
		"authorsite"	=> "http://mybb.com",
		"version"		=> "1.0",
		"guid" 			=> "",
		"compatibility" => "16*",
	);
}

function elgg_connect_install()
{
global $db;
$db->write_query("
	CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."elggconnect_users
	(`id` int(10) NOT NULL auto_increment,
	`mbb_uid` int(10) NOT NULL default '0',
	`mybb_username` varchar(120) NOT NULL DEFAULT '',
	`elgg_guid` bigint(20) unsigned NOT NULL,
	`elgg_password` varchar(32) NOT NULL DEFAULT '',
	`elgg_salt` varchar(8) NOT NULL DEFAULT '',
	PRIMARY KEY  (id))");
}


function elgg_connect_is_installed()
{
global $db;
if($db->table_exists("elggconnect_users"))
{
	return true;
}
return false;
}

function elgg_connect_uninstall()
{
global $db;

// Drop the Table
$db->drop_table("elggconnect_users");
}

function elgg_connect_activate()
{
}

function elgg_connect_deactivate()
{
}

function elgg_connect_global_start()
{
	global $mybb, $db;
	
	//exit if user not logged in
	if($mybb->user['uid'] == 0)
		exit;
	
	//check if user already migrated to Elgg
	$query = $db->simple_select("elggconnect_users", "*", "mbb_uid = '{$mybb->user['uid']}'", array("limit" => 1));
	$elgg = $db->fetch_field($query);
	
	require_once(MYBB_ROOT.'KLogger.php');
	$log = new KLogger(dirname(__FILE__) , KLogger::DEBUG );
	
	if ($elgg['id'])
	{
		$log->LogDebug("Found Elgg id");
	}
	else
	{
		//migrate to elgg and update db
		$log->LogDebug("Not Found Elgg id");
		
		//call elgg api to create user
		$eggusername = $mybb->user['username'];
		$eggpassword = $mybb->user['password'];
		$eggemail = $mybb->user['email'];
		$url = $_SERVER['HTTP_HOST'].'elgg/services/api/rest/xml/?method=mybb_connect.registeruser';
		
		$log->LogDebug('Start to call Elgg api: '.$url);
		
		$call = array(
			"username" => $mybb->user['username'],
			"password" => $mybb->user['password'],
			"email" => $mybb->user['email'],
			);
			
		$key = array(
			"public" => null,
			"private" => null,
			);
		
		$result = send_api_get_call($url, $call, $key);
		
		$log->LogDebug('The returned value is '.$result);
/*
		//store elgg user profile in mybb		
		$elg_entity=array(
			'mbb_uid' => $mybb->user['uid'],
			'mybb_username' => $mybb->user['username'],
			'elgg_guid' => '1',
			'elgg_password' => 'password',
			'elgg_salt' => 'salt',
		);
		$db->insertquery("elggconnect_users", $elg_entity);*/
	}
}

/**
 * Send a raw API call to an elgg api endpoint. Copied from Elgg engine/lib/web_services.php
 *
 * @param array  $keys         The api keys.
 * @param string $url          URL of the endpoint.
 * @param array  $call         Associated array of "variable" => "value"
 * @param string $method       GET or POST
 * @param string $post_data    The post data
 * @param string $content_type The content type
 *
 * @return string
 */
function send_api_call(array $keys, $url, array $call, $method = 'GET', $post_data = '',
$content_type = 'application/octet-stream') {

	global $CONFIG;

	$headers = array();
	$encoded_params = array();

	$method = strtoupper($method);
	switch (strtoupper($method)) {
		case 'GET' :
		case 'POST' :
			break;
		default:
			$msg = elgg_echo('NotImplementedException:CallMethodNotImplemented', array($method));
			throw new NotImplementedException($msg);
	}

	// Time
	$time = time();

	// Nonce
	$nonce = uniqid('');

	// URL encode all the parameters
	foreach ($call as $k => $v) {
		$encoded_params[] = urlencode($k) . '=' . urlencode($v);
	}

	$params = implode('&', $encoded_params);

	// Put together the query string
	$url = $url . "?" . $params;

	// Construct headers
	$posthash = "";
	if ($method == 'POST') {
		$posthash = calculate_posthash($post_data, 'md5');
	}

	if ((isset($keys['public'])) && (isset($keys['private']))) {
		$headers['X-Elgg-apikey'] = $keys['public'];
		$headers['X-Elgg-time'] = $time;
		$headers['X-Elgg-nonce'] = $nonce;
		$headers['X-Elgg-hmac-algo'] = 'sha1';
		$headers['X-Elgg-hmac'] = calculate_hmac('sha1',
			$time,
			$nonce,
			$keys['public'],
			$keys['private'],
			$params,
			$posthash
		);
	}
	if ($method == 'POST') {
		$headers['X-Elgg-posthash'] = $posthash;
		$headers['X-Elgg-posthash-algo'] = 'md5';

		$headers['Content-type'] = $content_type;
		$headers['Content-Length'] = strlen($post_data);
	}

	// Opt array
	$http_opts = array(
		'method' => $method,
		'header' => serialise_api_headers($headers)
	);
	if ($method == 'POST') {
		$http_opts['content'] = $post_data;
	}

	$opts = array('http' => $http_opts);

	// Send context
	$context = stream_context_create($opts);

	// Send the query and get the result and decode.
	$results = file_get_contents($url, false, $context);

	return $results;
}

/**
 * Send a GET call. Copied from Elgg engine/lib/web_services.php
 *
 * @param string $url  URL of the endpoint.
 * @param array  $call Associated array of "variable" => "value"
 * @param array  $keys The keys dependant on chosen authentication method
 *
 * @return string
 */
function send_api_get_call($url, array $call, array $keys) {
	return send_api_call($keys, $url, $call);
}
	
/**
 * Utility function to serialise a header array into its text representation.  Copied from Elgg engine/lib/web_services.php
 *
 * @param array $headers The array of headers "key" => "value"
 *
 * @return string
 * @access private
 */
function serialise_api_headers(array $headers) {
	$headers_str = "";

	foreach ($headers as $k => $v) {
		$headers_str .= trim($k) . ": " . trim($v) . "\r\n";
	}

	return trim($headers_str);
}
?>