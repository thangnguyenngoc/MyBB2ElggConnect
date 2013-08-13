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
	$vars['entity']->authentication_key = 'elggconnect';
}

echo '<p>' . elgg_echo('mybbconnect:settings:explanation') . '</p>';
echo '<div>';
echo '<label>';
echo elgg_view('input/checkbox', array(
	'name' => 'params[enable_authenticationkey]',	
	'value' => $vars['entity']->enable_authenticationkey,
	'checked' => $vars['entity']->enable_authenticationkey==1,
));
echo elgg_echo("mybbconnect:label:enable_authenticationkey") . '</label>';
echo '<span class="elgg-text-help">' . elgg_echo("mybbconnect:help:enable_authenticationkey") . '</span>';
echo '</div>';

echo '<div>';
echo elgg_echo('<label>'."mybbconnect:label:authentication_key") . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[authentication_key]',
	'value' => $vars['entity']->authentication_key,
));
echo '<span class="elgg-text-help">' . elgg_echo("mybbconnect:help:authentication_key") . '</span>';
echo '</div>';