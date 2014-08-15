<?php

/**
 *
 * @package "geopIP" Mod for Simple Machines Forum (SMF) V2.0
 * @author Spuds
 * @copyright (c) 2011 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 1.0
 *
 */

/**
 * ilp_geoIP()
 *
 * Permissions hook, integrate_load_permissions, called from ManagePermissions.php
 * used to add new permisssions
 *
 * @param mixed $permissionGroups
 * @param mixed $permissionList
 * @param mixed $leftPermissionGroups
 * @param mixed $hiddenPermissions
 * @param mixed $relabelPermissions
 * @return
 */
function ilp_geoIP(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	// Permissions hook, integrate_load_permissions, called from ManagePermissions.php
	// used to add new permisssions
	$permissionList['membergroup']['geoIP_view'] = array(false, 'general', 'view_basic_info');
	$permissionList['membergroup']['geoIP_viewdetail'] = array(false, 'general', 'view_basic_info');
}

/**
 * iaa_geoIP()
 * Admin Hook, integrate_admin_areas, called from Menu.subs
 * Used to add/modify admin menu areas
 *
 * @param mixed $admin_areas
 */
function iaa_geoIP(&$admin_areas)
{
	global $txt;

	loadLanguage('geoIP');

	// our geoip tab, under
	//$admin_areas['config']['areas']
	$geoIP = array('geoip' => array(
		'label' => $txt['geoIP'],
		'file' => 'ManageGeoIP.controller.php',
		'controller' => 'ManageGeoip_Controller',
		'function' => 'action_index',
		'icon' => 'geoip.png',
		'permission' => array('admin_forum'),
		'subsections' => array(
			'main' => array($txt['geoIPMain']),
			'settings' => array($txt['geoIPSettings']),
			'map' => array($txt['geoIPMap']),
		))
	);
	$insert_after = 'addonsettings';
	$admin_areas['config']['areas'] = elk_array_insert($admin_areas['config']['areas'], $insert_after, $geoIP, 'after');
}

/**
 * ipa_geoIP()
 *
 * Profile hook, integrate_profile_areas, called from Profile.controller
 * used to add new items to the profile area array
 *
 * @param mixed $profile_areas
 */
function ipa_geoIP(&$profile_areas)
{
	global $context;

	// Lets be sure to have geoIP information available when in the profile area.
	$ip = (isset($_GET['searchip'])) ? $_GET['searchip'] : $context['member']['ip'];
	include_once(SUBSDIR . '/GeoIP.subs.php');

	$context['geoIP'] =	geo_search($ip);
}

/**
 * ilt_geoIP()
 *
 * integrate_load_theme called from load.php
 * used to add theme files, etc
 *
 * @param mixed $profile_areas
 */
function ilt_geoIP()
{
	global $context, $modSettings;

	// Some people can't see the online map button, enabled, full database and perms are needed
	$context['can_see_onlinemap'] = !empty($modSettings['geoIP_enablemap']) && allowedTo('geoIP_view');
}

/**
 * Called from the dispatcher, integrate_action_register_before
 * @param string $sa
 */
function iarb_geoIP($sa)
{
	global $modSettings, $user_info;

	if ($sa === 'action_register' && !empty($modSettings['geoIP_enablereg']))
	{
		// Lots of reasons to just return and let action_register handle processing
		if (isset($_GET['sa']) && $_GET['sa'] == 'usernamecheck')
			return;
		// already disabled.
		elseif (!empty($modSettings['registration_method']) && $modSettings['registration_method'] == '3')
			return;
		// The user is an admin
		elseif (allowedTo('moderate_forum') && !$user_info['is_guest'])
			return;
		// The user is a member
		elseif (empty($user_info['is_guest']))
			return;

		// OK, a guest wants to register, geoIP check to see if this is from an allowed country
		if (!empty($modSettings['geoIPCC']) && !allowedTo('moderate_forum'))
		{
			include_once(SUBSDIR . '/GeoIP.subs.php');
			$check = geo_search($user_info['ip']);
			if ($check && count($check))
			{
				// We know (well have a very good idea) of where they are ...
				$country = $check['cc'];
				$cc_found = strpos($modSettings['geoIPCC'], $country);

				// Country code is in list and we are blocking -OR- county code is not in list and we are only allowing
				if (($cc_found !== false && !empty($modSettings['geoIP_cc_block'])) || ($cc_found === false && empty($modSettings['geoIP_cc_block'])))
					fatal_lang_error('registration_disabled', false);
			}
		}
	}
}

/**
 * Show geoip info at the top of the search IP template
 *
 * Called from the dispatcher, integrate_action_ProfileHistory_before
 *
 * @param string $sa
 */
function iaphb_geoIP($sa)
{
	global $context;

	if ($sa === 'action_trackip')
	{
		require_once(SUBSDIR . '/GeoIP.subs.php');
		$temp = geo_search(trim($_REQUEST['searchip']));

		if (!empty($temp))
		{
			$context['geoIP'] = $temp[0];
			loadLanguage('geoIP');
			loadTemplate('geoIP');
			$template_layers = Template_Layers::getInstance();
			$template_layers->add('geotrackIP');
		}
	}
}

/**
 * integrate_mark_read_button, called from BoardIndex.controller
 *
 * Here we just add to $context, it has nothing to do with the hook :P
 */
function imrb_geoIP()
{
	global $context, $txt, $scripturl;

	if (!empty($context['can_see_onlinemap']))
	{
		loadLanguage('geoIP');
		$context['list_users_online'][] = '<a class="linkbutton" href="' . $scripturl . '?action=geoIP">' . $txt['geoIPOnlineMap'] . '</a>';
	}
}

/**
 * integrate_action_who_after
 *
 * Called from the site dispatcher
 * Used to add the view online map (to those with permissions) on the whos online list
 *
 * @param string $sa
 */
function iawa_geoIP($sa)
{
	global $context;

	if ($sa === 'action_index' && !empty($context['can_see_onlinemap']))
	{
		loadLanguage('geoIP');
		loadTemplate('geoIP');
		$template_layers = Template_Layers::getInstance();
		$template_layers->add('who_geomap');
	}
}