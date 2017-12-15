<?php

/**
 *
 * @package "geopIP" Addon for ElkArte
 * @author Spuds
 * @copyright (c) 2011-2015 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 1.5
 *
 */

if (!defined('ELK'))
{
	die('No access...');
}

/**
 * Returns the users geoIP values as set by nginx/fpm params
 */
function geo_logon()
{
	// For < php 5.5
	$city = getenv('GEOIP_CITY');
	$region = getenv('GEOIP_REGION');
	$region_name = getenv('GEOIP_REGION_NAME');

	return array(
		'latitude' => getenv('GEOIP_LATITUDE'),
		'longitude' => getenv('GEOIP_LONGITUDE'),
		'country' => getenv('GEOIP_CITY_COUNTRY_NAME'),
		'city' => !empty($city) ? $city : (!empty($region) ? $region : ''),
		'region' => !empty($region_name) ? $region_name : (!empty($region) ? $region : ''),
		'cc' => getenv('GEOIP_CITY_COUNTRY_CODE')
	);
}

/**
 * geo_search()
 *
 * Takes an array of ip address and determines the geo location
 * uses network lookups to find the location
 * returns the information in an array
 *
 * @param mixed[] $ip_input
 *
 * @return array
 */
function geo_search($ip_input)
{
	global $user_info;
	require_once(SUBSDIR . '/Package.subs.php');
	$memberIPData = array();
	$ips = array();

	// It must be an array, even if we are only looking up one IP
	if (!is_array($ip_input))
	{
		$ips = array($ip_input);
	}
	else
	{
		// Passed an array from the log_online, this should contain all geoip info established at logon
		foreach ($ip_input as $member => $data)
		{
			// already have the data?
			if (!empty($data['latitude']) && !empty($data['longitude']) && !empty($data['country']) && !empty($data['cc']) && !empty($data['city']))
			{
				// data is available, use it and save a lookup
				$memberIPData[$member]['country'] = $data['country'];
				$memberIPData[$member]['city'] = $data['city'];
				$memberIPData[$member]['region'] = $data['region'];
				$memberIPData[$member]['latitude'] = $data['latitude'];
				$memberIPData[$member]['longitude'] = $data['longitude'];
				$memberIPData[$member]['cc'] = $data['cc'];
				$memberIPData[$member]['session'] = $data['session'];
			}
			// Or look it up instead :\
			else
			{
				$ips[$member] = $data['ip'];
			}
		}
	}

	// Look up what we don't know
	foreach ($ips as $member => $ip)
	{
		// Look up some geo info, lets try this first
		$geo_data = fetch_web_data('http://geoip.spudsdesign.com/geoip/' . $ip);
		if (!empty($geo_data))
		{
			$geo_data = json_decode($geo_data, true);
			$memberIPData[$member] = $geo_data;
			$memberIPData[$member]['cc'] = !empty($memberIPData[$member]['country_code']) ? $memberIPData[$member]['country_code'] : '';
			$memberIPData[$member]['city'] = !empty($memberIPData[$member]['city']) ? $memberIPData[$member]['city'] : '';
			$memberIPData[$member]['region'] = !empty($memberIPData[$member]['region']) ? $memberIPData[$member]['region'] : '';
			$memberIPData[$member]['country'] = !empty($memberIPData[$member]['country']) ? $memberIPData[$member]['country'] : '';
		}

		// Missing anything?
		if ((empty($geo_data['city']) || empty($geo_data['region'])) && !empty($geo_data['country_code']))
		{
			// will return country_code, country_name, region_code(state), region_name, city, zip_code
			// time_zone, latitude, longitude, metro_code
			$geo_data2 = fetch_web_data('http://freegeoip.net/json/' . $ip);
			$geo_data2 = json_decode($geo_data2, true);
			if (isset($geo_data2['latitude'], $geo_data2['longitude'], $geo_data2['city'], $geo_data2['country_name']))
			{
				// Place this result into our result for this user
				$memberIPData[$member]['country'] = empty($geo_data['country']) ? $geo_data2['country_name'] : $geo_data['country'];
				$memberIPData[$member]['city'] = $geo_data2['city'];
				$memberIPData[$member]['region'] = !empty($geo_data2['region_name']) ? $geo_data2['region_name'] : (!empty($geo_data2['region_code']) ? $geo_data2['region_code'] : '');
				$memberIPData[$member]['latitude'] = empty($geo_data['latitude']) ? $geo_data2['latitude'] : $geo_data['latitude'];
				$memberIPData[$member]['longitude'] = empty($geo_data['longitude']) ? $geo_data2['longitude'] : $geo_data['longitude'];
			}
		}

		// Update the online log so we don't do this again if it was for an online user of course
		if (!empty($memberIPData[$member]['session']))
		{
			geo_save_data($memberIPData[$member]);
		}
	}

	return $memberIPData;
}

/**
 * Updates the online log with the geoip information
 *
 * @param array $data
 */
function geo_save_data($data = array())
{
	$db = database();

	// Simply update this session with the newly found data
	$db->query('', '
		UPDATE {db_prefix}log_online
		SET latitude = {float:latitude}, longitude = {float:longitude}, country = {string:country}, city = {string:city}, region = {string:region}, cc = {string:cc}
		WHERE session = {string:session}',
		array(
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
			'country' => $data['country'],
			'city' => $data['city'],
			'region' => $data['region'],
			'cc' => $data['cc'],
			'session' => $data['session'],
		)
	);
}
