<?php
/**
 * Groups plugin settings
 */

// set default value
if (!isset($vars['entity']->enable_authenticationkey)) {
	$vars['entity']->enable_authenticationkey = 1;
}

// set default value
if (!isset($vars['entity']->authentication_key)) {
	$vars['entity']->authentication_key = 'mybbconnect';
}

echo '<div>';
echo elgg_echo('mybbconnect:enable_authenticationkey');
echo ' ';
echo elgg_view('input/checkbox', array(
	'name' => 'params[enable_authenticationkey]',	
	'value' => $vars['entity']->enable_authenticationkey,
	'checked' => $vars['entity']->enable_authenticationkey==1,
));
echo '</div>';

echo '<div>';
echo elgg_echo('mybbconnect:authentication_key');
echo ' ';
echo elgg_view('input/text', array(
	'name' => 'params[authentication_key]',
	'value' => $vars['entity']->authentication_key,
));
echo '</div>';