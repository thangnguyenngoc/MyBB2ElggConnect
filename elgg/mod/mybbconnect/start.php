<?php
/**
 * MyBB Connect: allow mybb connect to Elgg and update activity
 * Author: Thang Nguyen
 */

elgg_register_event_handler('init', 'system', 'mybb_connect_init');

function mybb_connect_init() {
	// Rename this function based on the name of your plugin and update the
	// elgg_register_event_handler() call accordingly

	// Register a script to handle (usually) a POST request (an action)
	$base_dir = elgg_get_plugins_path() . 'my_plugin/actions/my_plugin';
	//elgg_register_action('my_plugin', "$base_dir/my_action.php");

	// Extend the main CSS file
	//elgg_extend_view('css/elgg', 'my_plugin/css');

	// Add a menu item to the main site menu
	//$item = new ElggMenuItem('my_plugin', elgg_echo('my_plugin:menu'), 'my_url');
	//elgg_register_menu_item('site', $item);
	
	expose_function("mybb_connect.registeruser", 
                "mybb_connect_register_user", 
                 array( 'username' => array ('type' => 'string'),
                       'password' => array ('type' => 'string'),
                       'email' => array ('type' => 'string'),
                       'authentication_key' => array ('type' => 'string'),
                     ),
                 'MyBB connect - register new user to Elgg',
                 'GET',
                 false,	//no user authentication key as the api only allow calls from the same server
                 false
                );
				
	expose_function("mybb_connect.authenticateuser", 
                "mybb_connect_authenticate_user", 
                 array( 'username' => array ('type' => 'string'),
                       'password' => array ('type' => 'string'),
                       'authentication_key' => array ('type' => 'string'),
                     ),
                 'MyBB connect - authenticate an user to Elgg',
                 'GET',
                 false,	//no user authentication key as the api only allow calls from the same server
                 false
                );
				
	expose_function("mybb_connect.checkloggedinuser", 
                "mybb_connect_checkloggedin_user", 
                 array( 'guid' => array ('type' => 'int'),
                        'authentication_key' => array ('type' => 'string'),
                     ),
                 'MyBB connect - check if a guid is already logged in Elgg. If another guid logged in, force it to log out',
                 'GET',
                 false,	//no user authentication key as the api only allow calls from the same server
                 false
                );
}

//sample call: http://127.0.0.1/elgg/services/api/rest/xml/?method=mybb_connect.registeruser&username=test&password=123&email=test@email.com
function mybb_connect_register_user($username, $password, $email, $authentication_key) {
	//todo: check if the request comes from the same server
	require_once '../../engine/lib/users.php';
	require_once '../../engine/classes/SuccessResult.php';
	require_once 'KLogger.php';
    
    if (!is_valid_authentication_key($authentication_key))
        return SuccessResult::getInstance(false);
	
	$log = new KLogger(dirname(__FILE__), KLogger::DEBUG );
	
	$elgg_user = get_user_by_username($username);
	if ($elgg_user)	//user already exist
	{
		$log->LogDebug('Existing user: '.$username.'. Registration cancelled.');
		return SuccessResult::getInstance($elgg_user->{'guid'});
	}

	$log->LogDebug('Start to register new user: '.$username);
	
	//return user guid to MyBB
	$result = register_user($username, $password, $username, $email);
	return SuccessResult::getInstance($result);
}

//sample call: http://127.0.0.1/elgg/services/api/rest/xml/?method=mybb_connect.authenticateuser&username=test&password=123
function mybb_connect_authenticate_user($username, $password, $authentication_key) {
	//todo: check if the request comes from the same server
	require_once '../../engine/lib/sessions.php';
	require_once '../../engine/classes/SuccessResult.php';
	require_once '../../engine/classes/ElggUser.php';
	require_once 'KLogger.php';
    
    if (!is_valid_authentication_key($authentication_key))
        return SuccessResult::getInstance(false);
	
	$log = new KLogger(dirname(__FILE__), KLogger::DEBUG );
	
	if (true===elgg_authenticate($username, $password))
	{
		$elgg_user = new ElggUser($username);
		//return result to MyBB
		$result = login($elgg_user, true);
        
        $log->LogDebug('Session: '.print_r($_SESSION['code'], true));
		
		if ($result==true)
            return SuccessResult::getInstance($_SESSION['code']);
	}	
	return  SuccessResult::getInstance(false);
}

function mybb_connect_checkloggedin_user($guid, $authentication_key)
{
	require_once '../../engine/lib/sessions.php';
	require_once '../../engine/classes/SuccessResult.php';
	require_once 'KLogger.php';
    
    if (!is_valid_authentication_key($authentication_key))
        return SuccessResult::getInstance(false);
	
	$log = new KLogger(dirname(__FILE__), KLogger::DEBUG );
	
	if ($guid==0)
		return  SuccessResult::getInstance(false);
	
	$current_guid = elgg_get_logged_in_user_guid();
	
	$log->LogDebug('Current user: '.$current_guid);
	
	//log out if different user is logged in
	if ($current_guid!=$guid && $current_guid>0)
	{
		logout();

		return SuccessResult::getInstance(0);
	}
	else
	{
		//return the guid 
		return SuccessResult::getInstance($current_guid);
	}
}

function is_valid_authentication_key($authentication_key)
{
    if (get_plugin_setting('enable_authenticationkey', 'elggconnect')==0)
        return true;
        
    $key = get_plugin_setting('authentication_key', 'elggconnect');
    
    if (strcmp($authentication_key, $key)==0)
        return true;
        
    return false;
}