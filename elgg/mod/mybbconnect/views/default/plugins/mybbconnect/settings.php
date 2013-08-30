<?php
/**
 * Groups plugin settings
 */

// set default value
if (!isset($vars['entity']->authentication_key)) {
	$vars['entity']->authentication_key = 'elggconnect';
}

echo '<div>';
echo elgg_echo('<label>'."mybbconnect:label:authentication_key") . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[authentication_key]',
	'value' => $vars['entity']->authentication_key,
));
echo '<span class="elgg-text-help">' . elgg_echo("mybbconnect:help:authentication_key") . '</span>';
echo '</div>';