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

/**
 * Create table to store information of migrated user
 * 
 */
function elgg_connect_install()
{
	global $db;
	//Create the table
	$db->write_query("
		CREATE TABLE IF NOT EXISTS ".TABLE_PREFIX."elggconnect_users
		(`id` int(10) NOT NULL auto_increment,
		`mbb_uid` int(10) NOT NULL default '0',
		`mybb_username` varchar(120) NOT NULL DEFAULT '',
		`mybb_email` varchar(120) NOT NULL DEFAULT '',
		`elgg_guid` bigint(20) unsigned NOT NULL,
		`elgg_password` varchar(32) NOT NULL DEFAULT '',
		`created_date` bigint(30) NOT NULL DEFAULT '0',
		PRIMARY KEY  (`id`))");
	
	$query = $db->query("SELECT MAX(disporder) as disporder
                         FROM ".TABLE_PREFIX."settinggroups");
    $row = $db->fetch_array($query);
    $disporder = $row['disporder'] + 1;
	
	//Create the settings
	$setting_group = array(
		'name'			=> 'elggconnect',
		'title'			=> 'Elgg Connect Settings',
		'description'	=> 'Settings for Elgg Connect.',
		'disporder'		=> $disporder,
		'isdefault'		=> 0
	);
	$db->insert_query("settinggroups", $setting_group);
    $gid = intval($db->insert_id());
	
	$disp = 1;

	$myplugin_setting = array(
		'name'			=> 'elggconnect_setting1',
		'title'			=> 'Menu name',
		'description'	=> 'The text of menu item on Mybb Header',
		'optionscode'	=> 'text',
		'value'			=> 'Elgg connect!',
		"disporder"		=> $disp++,
        "gid"			=> $gid,
	);
	$db->insert_query('settings', $myplugin_setting);
	
	$myplugin_setting = array(
		'name'			=> 'elggconnect_setting2',
		'title'			=> 'Elgg url',
		'description'	=> 'Url of Elgg site',
		'optionscode'	=> 'text',
		'value'			=> $_SERVER['HTTP_HOST'],
		"disporder"		=> $disp++,
        "gid"			=> $gid,
	);
	$db->insert_query('settings', $myplugin_setting);
	
	$myplugin_setting = array(
		'name'			=> 'elggconnect_setting3',
		'title'			=> 'Elgg Authentication Key',
		'description'	=> 'Must set this value in Elgg to connect',
		'optionscode'	=> 'text',
		'value'			=> 'elggconnect',
		"disporder"		=> $disp++,
        "gid"			=> $gid,
	);
	$db->insert_query('settings', $myplugin_setting);

	// This updates the ./inc/settings.php file.
	rebuild_settings();
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

//Drop the settings
$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name IN ('elggconnect_setting1','elggconnect_setting2','elggconnect_setting3')");
$db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name = 'elggconnect'");
rebuild_settings(); 
}

function elgg_connect_activate()
{
	//add link to Elgg site, place it as 1st item in the menu
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("header", '#\t?<ul>#i', '	<ul><li><a  href="{$mybb->settings[\'elggconnect_setting2\']}">{$mybb->settings[\'elggconnect_setting1\']}</a></li>');
}

function elgg_connect_deactivate()
{
	//remove link to Elgg site
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("header", "#".preg_quote('<ul><li><a  href="{$mybb->settings[\'elggconnect_setting2\']}">{$mybb->settings[\'elggconnect_setting1\']}</a></li>')."#i", '<ul>', 0);
}

/**
 * Check if mybb user is already migrated to elgg and check for Elgg session to see if user is authenticated then authenticate the user
 * Otherwise, migrate mybb user to elgg and store user information in table
 *
 * @return bool
 */
function elgg_connect_global_start()
{
	if (!elgg_connect_is_installed())
		return true;

	global $mybb, $db;
	
	//exit if user not logged in
	if($mybb->user['uid'] == 0)
		return true;
	
	//=========================================================
	//check if user already migrated to Elgg
	$query = $db->simple_select("elggconnect_users", "*", "mbb_uid = '{$mybb->user['uid']}'", array("limit" => 1));
	$elgg = $db->fetch_array($query);
	
	require_once(MYBB_ROOT.'KLogger.php');
	$log = new KLogger(dirname(__FILE__) , KLogger::DEBUG );
	
	if ($elgg)
	{
		$log->LogDebug("Found Elgg id: ".print_r($elgg, true));
		
		//=========================================================
		//call elgg api to check if user is already logged in
		$url = $mybb->settings['elggconnect_setting2'].'/elgg/services/api/rest/json/?method=mybb_connect.checkloggedinuser';
		
		$call = array(
			"guid" => $elgg['elgg_guid'],
			);
            
        $result = call_elgg_api($url, $call);
		
		$log->LogDebug('Called to: '.$url);
		
		//check for $result
		$json_output = json_decode($result);
		$log->LogDebug('The returned value is '.print_r($json_output,true));
		
		//something wrong with Elgg plugin
		if ($json_output->{'runtime_errors'})
			return true;
		
		if ($json_output->{'status'}==0 && $json_output->{'result'}>0)
		{
			//user is already logged in, no need to authenticate
			$log->LogDebug('User is already logged in');
			return true;
		}
		
		//=========================================================
		//call elgg api to authenticate user and return the cookie to the browser
		$url = $_SERVER['HTTP_HOST'].'/elgg/services/api/rest/json/?method=mybb_connect.authenticateuser';
		
		$call = array(
			"username" => $mybb->user['username'],
			"password" => $mybb->user['password'],
			);
            
        $result = call_elgg_api($url, $call);
		
		$log->LogDebug('Called to: '.$url);
		
		//check for $result
		$json_output = json_decode($result);
		$log->LogDebug('The returned value is '.print_r($json_output,true));
		
		//something wrong with Elgg plugin
		if ($json_output->{'runtime_errors'})
			return true;
		
		if ($json_output->{'status'}==0)
		{
            setcookie("elggperm", $json_output->{'result'}, (time() + (86400 * 30)), "/");
			$log->LogDebug('Successfully set cookie for Elgg');
		}
	}
	else
	{
		$log->LogDebug("Not Found Elgg id - migrate to Elgg");
		
		//=========================================================
		//call elgg api to create user
		$url = $_SERVER['HTTP_HOST'].'/elgg/services/api/rest/json/?method=mybb_connect.registeruser';
		
		$call = array(
			"username" => $mybb->user['username'],
			"password" => $mybb->user['password'],
			"email" => $mybb->user['email'],
			);
			
		$result = call_elgg_api($url, $call);
		
		$log->LogDebug('Called to: '.$url);
		
		//check for $result
		$json_output = json_decode($result);
		$log->LogDebug('The returned value is '.print_r($json_output,true));
		
		//something wrong with Elgg plugin
		if ($json_output->{'runtime_errors'})
			return true;
		
		if ($json_output->{'status'}==0 && $json_output->{'result'}>0)
		{
			//=========================================================
			//store elgg user profile in mybb		
			$elg_entity=array(
				'mbb_uid' => $mybb->user['uid'],
				'mybb_username' => $mybb->user['username'],
				'mybb_email' => $mybb->user['email'],
				'elgg_guid' => $json_output->{'result'},
				'elgg_password' => $mybb->user['password'],
				'created_date' => TIME_NOW,
			);
			$db->insert_query("elggconnect_users", $elg_entity);
			
			$log->LogDebug('Successfully migrated to Elgg');
		}
	}
}

function call_elgg_api(&$url, array $call) {
	//check for 'http://' in url
	if (!preg_match('/^https?:\/\//', $url)) {
		$url = 'http://' . $url;
	}
    
	global $mybb;
    $call["authentication_key"] = $mybb->settings['elggconnect_setting3'];
    
    $key = array(
		"public" => null,
		"private" => null,
		);
		
	//$url is changed to the full url after the call
    return send_api_get_call($url, $call, $key);
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
function send_api_call(array $keys, &$url, array $call, $method = 'GET', $post_data = '',
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
	$url = $url . "&" . $params;

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
function send_api_get_call(&$url, array $call, array $keys) {
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