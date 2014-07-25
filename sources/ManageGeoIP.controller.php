<?php

/**
 *
 * @package "geopIP" Mod for ElkArte
 * @author Spuds
 * @copyright (c) 2014 Spuds
 * @license Mozilla Public License version 1.1 http://www.mozilla.org/MPL/1.1/.
 *
 * @version 1.0
 *
 */

if (!defined('ELK'))
	die('No access...');

/**
 * GeoIP administration controller.
 * This class allows to modify geoip settings for the forum.
 */
class ManageGeoip_Controller extends Action_Controller
{
	/**
	 * geoip settings forms
	 *
	 * @var _gipSettingsForm
	 * @var _geoipMainSettingsForm
	 */
	protected $_gipSettingsForm;
	protected $_geoipMainSettingsForm;

	/**
	 * geoIPEntry()
	 *
	 * Traffic cop, checks permissions
	 * calls the appropriate sub-function
	 *
	 */
	public function action_index()
	{
		// The entrance point for all 'geoIP' actions.
		global $context, $txt;

		// Admins only
		isAllowedTo('admin_forum');

		// Language and template stuff, the usual.
		loadLanguage('geoIP');
		loadTemplate('geoIP');

		// Subaction array ... function to call
		$subActions = array(
			'main' => array($this, 'action_geoMainSettings_display'),
			'settings' => array($this, 'action_geoIPRegSettings'),
			'map' => array($this, 'action_geoIPMapSettings_display'),
		);

		// Get ready for some action
		$action = new Action('manage_geoip');

		// Default page title is good.
		$context['page_title'] = $txt['attachments_avatars'];

		// Get the subAction, call integrate_sa_manage_geoip
		$subAction = $action->initialize($subActions, 'main');
		$context['sub_action'] = $subAction;

		// This uses admin tabs - as it should!
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['geoIP'],
			'help' => $txt['geoIP_help'],
			'description' => $txt['geoIP_description_' . $subAction],
		);

		// Finally go to where we want to go
		$action->dispatch($subAction);
	}

	/**
	 * Show the main form
	 */
	public function action_geoMainSettings_display()
	{
		global $txt, $context, $scripturl;

		// We're working with them settings here.
		require_once(SUBSDIR . '/SettingsForm.class.php');

		// Initialize the form
		$this->_initGeoipMainSettingsForm();
		$config_vars = $this->_geoipMainSettingsForm->settings();

		// Saving?
		if (isset($_GET['save']))
		{
			// You can save, maybe
			checkSession();
			Settings_Form::save_db($config_vars);
			redirectexit('action=admin;area=geoip;sa=main');
		}

		// Setup the title and template.
		$context['page_title'] = $txt['geoIP'];
		$context['sub_template'] = 'show_settings';
		$context['post_url'] = $scripturl . '?action=admin;area=geoip;sa=main;save';
		$context['settings_title'] = $txt['geoIP_basic_settings'];

		Settings_Form::prepare_db($config_vars);
	}

	/**
	 * Prepare the main form
	 * @return type
	 */
	private function _initGeoipMainSettingsForm()
	{
		// Instantiate the form
		$this->_geoipMainSettingsForm = new Settings_Form();

		// Initialize settings
		$config_vars = $this->_geoip_mainsettings();

		return $this->_geoipMainSettingsForm->settings($config_vars);
	}

	/**
	 * Load the main options for the addon
	 */
	private function _geoip_mainsettings()
	{
		global $txt;

		$config_vars = array(
			array('check', 'geoIP_enablemap', 'subtext' => $txt['geoIP_enablemap_desc']),
			array('check', 'geoIP_enablepinid', 'subtext' => $txt['geoIP_enablepinid_desc']),
			array('check', 'geoIP_enablereg', 'subtext' => $txt['geoIP_enablereg_desc']),
			//array('check', 'geoIP_enableflags', 'subtext' => $txt['geoIP_enableflags_desc']),
		);

		return $config_vars;
	}

	/**
	 * Allows to show/change geoIP map settings.
	 *
	 * @uses 'attachments' sub template.
	 */
	public function action_geoIPMapSettings_display()
	{
		global $scripturl, $context, $txt;

		// We're working with them settings here.
		require_once(SUBSDIR . '/SettingsForm.class.php');

		// Initialize the form
		$this->_initGeoipSettingsForm();
		$config_vars = $this->_geoipSettingsForm->settings();

		// Saving?
		if (isset($_GET['save']))
		{
			checkSession();
			Settings_Form::save_db($config_vars);
			redirectexit('action=admin;area=geoip;sa=map');
		}

		// Setup the title and template.
		$context['page_title'] = $txt['geoIP'];
		$context['sub_template'] = 'show_settings';
		$context['post_url'] = $scripturl . '?action=admin;area=geoip;sa=map;save';
		$context['settings_title'] = $txt['geoIPFO'];

		Settings_Form::prepare_db($config_vars);
	}

	/**
	 * Initialize attachmentForm.
	 *
	 * - Retrieve and return the administration settings for geoIP Map settings
	 */
	private function _initGeoipSettingsForm()
	{
		// Instantiate the form
		$this->_geoipSettingsForm = new Settings_Form();

		// Initialize settings
		$config_vars = $this->_geoip_settings();

		return $this->_geoipSettingsForm->settings($config_vars);
	}

	/**
	 * geoIPMapSettings()
	 *
	 * Updates the maps settings
	 */
	private function _geoip_settings()
	{
		global $txt;

		$config_vars = array(
			// Geoip - sidebar?
			array('select', 'geoIPSidebar', array(
				'none' => $txt['nosidebar'],
				'right' => $txt['rightsidebar'])
			),
			'',
			// Map Type
			array('select', 'geoIPType', array(
				'ROADMAP' => $txt['roadmap'],
				'SATELLITE' => $txt['satellite'],
				'HYBRID' => $txt['hybrid'])
			),
			array('select', 'geoIPNavType', array(
				'SMALL' => $txt['gsmallzoomcontrol'],
				'LARGE' => $txt['glargezoomcontrol'],
				'DEFAULT' => $txt['gdefaultzoomcontrol'])
			),
			'',
			// Default Location/Zoom
			array('float', 'geoIPDefaultLat', '25'),
			array('float', 'geoIPDefaultLong', '25'),
			array('int', 'geoIPDefaultZoom'),
			'',
			// Member Pin Style
			array('text', 'geoIPPinBackground', '6'),
			array('text', 'geoIPPinForeground', '6'),
			array('select', 'geoIPPinStyle',
				array(
					'plainpin' => $txt['plainpin'],
					'textpin' => $txt['textpin'],
					'iconpin' => $txt['iconpin']
				)
			),
			array('check', 'geoIPPinShadow'),
			array('int', 'geoIPPinSize', '2'),
			array('text', 'geoIPPinText'),
			array('select', 'geoIPPinIcon',
				$this->_gip_pinArray(),
			),
		);

		return $config_vars;
	}

	/**
	 * geoIPRegSettings()
	 *
	 * Updates the registration settings
	 * Allows the admin to select countries to block or allo
	 *
	 */
	public function action_geoIPRegSettings()
	{
		global $txt, $scripturl, $context;

		// Saving?
		if (isset($_POST['save']))
		{
			checkSession();

			// Clean up the response
			if (!isset($_POST['geoIPCC']))
				$_POST['geoIPCC'] = array();
			elseif (!is_array($_POST['geoIPCC']))
				$_POST['geoIPCC'] = array($_POST['geoIPCC']);

			// all the country codes selected as just a single string please
			$_POST['geoIPCC'] = implode(',', $_POST['geoIPCC']);
			$_POST['geoIP_cc_block'] = empty($_POST['geoIP_cc_block']) ? 0 : 1;

			// save the updates
			updateSettings(array(
				'geoIPCC' => $_POST['geoIPCC'],
				'geoIP_cc_block' => $_POST['geoIP_cc_block'],
			));
		}

		// Load the country data in to context for selection
		$this->_geoIP_country();

		// Setup the title and template, etc
		$context['page_title'] = $txt['geoIP'];
		$context['sub_template'] = 'geoIPreg';
		$context['post_url'] = $scripturl . '?action=admin;area=geoip;sa=settings';
		$context['settings_title'] = $txt['geoIPFO'];
	}

	/**
	 * geoIP_country()
	 *
	 * - loads all of the counties from the database for display
	 * - loads the currently selected counties from the settings table
	 * - builds the context array with the information for display via geoIPRegSettings
	 */
	private function _geoIP_country()
	{
		global $context, $modSettings;

		// load all the country codes in to an array for use
		$geoIPCCs = array_unique($this->_gip_ccArray());

		// load up what has been selected to date
		$geoIP_cc_checked = array();
		if (!empty($modSettings['geoIPCC']))
		{
			$temp = explode(',', $modSettings['geoIPCC']);

			// Set the cc as the key, just easier
			foreach ($temp as $cc)
				$geoIP_cc_checked[$cc] = 1;
		}

		// Start working out the context stuff.
		$context['geoCC'] = array();
		foreach ($geoIPCCs as $geoCC => $geoCN)
		{
			$context['geoCC'][] = array(
				'cn' => $geoCN,
				'cc' => $geoCC,
				'checked' => isset($geoIP_cc_checked[$geoCC])
			);
		}

		loadCSSFile('geoIP.css');
	}

	/**
	 * Defines an array of pins icons for use in the settings form
	 */
	private function _gip_pinArray()
	{
		global $txt;

		return array(
			'academy' => $txt['academy'],
			'activities' => $txt['activities'],
			'airport' => $txt['airport'],
			'amusement' => $txt['amusement'],
			'aquarium' => $txt['aquarium'],
			'art-gallery' => $txt['art-gallery'],
			'atm' => $txt['atm'],
			'baby' => $txt['baby'],
			'bank-dollar' => $txt['bank-dollar'],
			'bank-euro' => $txt['bank-euro'],
			'bank-intl' => $txt['bank-intl'],
			'bank-pound' => $txt['bank-pound'],
			'bank-yen' => $txt['bank-yen'],
			'bar' => $txt['bar'],
			'barber' => $txt['barber'],
			'beach' => $txt['beach'],
			'beer' => $txt['beer'],
			'bicycle' => $txt['bicycle'],
			'books' => $txt['books'],
			'bowling' => $txt['bowling'],
			'bus' => $txt['bus'],
			'cafe' => $txt['cafe'],
			'camping' => $txt['camping'],
			'car-dealer' => $txt['car-dealer'],
			'car-rental' => $txt['car-rental'],
			'car-repair' => $txt['car-repair'],
			'casino' => $txt['casino'],
			'caution' => $txt['caution'],
			'cemetery-grave' => $txt['cemetery-grave'],
			'cemetery-tomb' => $txt['cemetery-tomb'],
			'cinema' => $txt['cinema'],
			'civic-building' => $txt['civic-building'],
			'computer' => $txt['computer'],
			'corporate' => $txt['corporate'],
			'fire' => $txt['fire'],
			'flag' => $txt['flag'],
			'floral' => $txt['floral'],
			'helicopter' => $txt['helicopter'],
			'home' => $txt['home1'],
			'info' => $txt['info'],
			'landslide' => $txt['landslide'],
			'legal' => $txt['legal'],
			'location' => $txt['location1'],
			'locomotive' => $txt['locomotive'],
			'medical' => $txt['medical'],
			'mobile' => $txt['mobile'],
			'motorcycle' => $txt['motorcycle'],
			'music' => $txt['music'],
			'parking' => $txt['parking'],
			'pet' => $txt['pet'],
			'petrol' => $txt['petrol'],
			'phone' => $txt['phone'],
			'picnic' => $txt['picnic'],
			'postal' => $txt['postal'],
			'repair' => $txt['repair'],
			'restaurant' => $txt['restaurant'],
			'sail' => $txt['sail'],
			'school' => $txt['school'],
			'scissors' => $txt['scissors'],
			'ship' => $txt['ship'],
			'shoppingbag' => $txt['shoppingbag'],
			'shoppingcart' => $txt['shoppingcart'],
			'ski' => $txt['ski'],
			'snack' => $txt['snack'],
			'snow' => $txt['snow'],
			'sport' => $txt['sport'],
			'star' => $txt['star'],
			'swim' => $txt['swim'],
			'taxi' => $txt['taxi'],
			'train' => $txt['train'],
			'truck' => $txt['truck'],
			'wc-female' => $txt['wc-female'],
			'wc-male' => $txt['wc-male'],
			'wc' => $txt['wc'],
			'wheelchair' => $txt['wheelchair'],
		);
	}

	/**
	 * Defines an array of pins icons for use in the settings form
	 */
	private function _gip_ccArray()
	{
		return array(
			'AD' => 'Andorra',
			'AE' => 'United Arab Emirates',
			'AF' => 'Afghanistan',
			'AG' => 'Antigua and Barbuda',
			'AI' => 'Anguilla',
			'AL' => 'Albania',
			'AM' => 'Armenia',
			'AN' => 'Netherlands Antilles',
			'AO' => 'Angola',
			'AP' => 'Asia/Pacific Region',
			'AQ' => 'Antarctica',
			'AR' => 'Argentina',
			'AS' => 'American Samoa',
			'AT' => 'Austria',
			'AU' => 'Australia',
			'AW' => 'Aruba',
			'AX' => 'Aland Islands',
			'AZ' => 'Azerbaijan',
			'BA' => 'Bosnia and Herzegovina',
			'BB' => 'Barbados',
			'BD' => 'Bangladesh',
			'BE' => 'Belgium',
			'BF' => 'Burkina Faso',
			'BG' => 'Bulgaria',
			'BH' => 'Bahrain',
			'BI' => 'Burundi',
			'BJ' => 'Benin',
			'BL' => 'Saint Bartelemey',
			'BM' => 'Bermuda',
			'BN' => 'Brunei Darussalam',
			'BO' => 'Bolivia',
			'BR' => 'Brazil',
			'BS' => 'Bahamas',
			'BT' => 'Bhutan',
			'BV' => 'Bouvet Island',
			'BW' => 'Botswana',
			'BY' => 'Belarus',
			'BZ' => 'Belize',
			'CA' => 'Canada',
			'CC' => 'Cocos (Keeling) Islands',
			'CD' => 'Congo ( Republic of)',
			'CF' => 'Central African Republic',
			'CG' => 'Congo',
			'CH' => 'Switzerland',
			'CI' => 'Cote d\'Ivoire',
			'CK' => 'Cook Islands',
			'CL' => 'Chile',
			'CM' => 'Cameroon',
			'CN' => 'China',
			'CO' => 'Colombia',
			'CR' => 'Costa Rica',
			'CU' => 'Cuba',
			'CV' => 'Cape Verde',
			'CX' => 'Christmas Island',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DE' => 'Germany',
			'DJ' => 'Djibouti',
			'DK' => 'Denmark',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'DZ' => 'Algeria',
			'EC' => 'Ecuador',
			'EE' => 'Estonia',
			'EG' => 'Egypt',
			'EH' => 'Western Sahara',
			'ER' => 'Eritrea',
			'ES' => 'Spain',
			'ET' => 'Ethiopia',
			'EU' => 'Europe',
			'FI' => 'Finland',
			'FJ' => 'Fiji',
			'FK' => 'Falkland Islands (Malvinas)',
			'FM' => 'Micronesia',
			'FO' => 'Faroe Islands',
			'FR' => 'France',
			'FX' => 'France Metropolitan',
			'GA' => 'Gabon',
			'GB' => 'United Kingdom',
			'GD' => 'Grenada',
			'GE' => 'Georgia',
			'GF' => 'French Guiana',
			'GG' => 'Guernsey',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GL' => 'Greenland',
			'GM' => 'Gambia',
			'GN' => 'Guinea',
			'GP' => 'Guadeloupe',
			'GQ' => 'Equatorial Guinea',
			'GR' => 'Greece',
			'GS' => 'South Georgia / South Sandwich Islands',
			'GT' => 'Guatemala',
			'GU' => 'Guam',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HK' => 'Hong Kong',
			'HM' => 'Heard Island and McDonald Islands',
			'HN' => 'Honduras',
			'HR' => 'Croatia',
			'HT' => 'Haiti',
			'HU' => 'Hungary',
			'ID' => 'Indonesia',
			'IE' => 'Ireland',
			'IL' => 'Israel',
			'IM' => 'Isle of Man',
			'IN' => 'India',
			'IO' => 'British Indian Ocean Territory',
			'IQ' => 'Iraq',
			'IR' => 'Iran',
			'IS' => 'Iceland',
			'IT' => 'Italy',
			'JE' => 'Jersey',
			'JM' => 'Jamaica',
			'JO' => 'Jordan',
			'JP' => 'Japan',
			'KE' => 'Kenya',
			'KG' => 'Kyrgyzstan',
			'KH' => 'Cambodia',
			'KI' => 'Kiribati',
			'KM' => 'Comoros',
			'KN' => 'Saint Kitts and Nevis',
			'KP' => 'Korea (People\'s Republic)',
			'KR' => 'Korea  (Republic of)',
			'KW' => 'Kuwait',
			'KY' => 'Cayman Islands',
			'KZ' => 'Kazakhstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LB' => 'Lebanon',
			'LC' => 'Saint Lucia',
			'LI' => 'Liechtenstein',
			'LK' => 'Sri Lanka',
			'LR' => 'Liberia',
			'LS' => 'Lesotho',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'LV' => 'Latvia',
			'LY' => 'Libyan Arab Jamahiriya',
			'MA' => 'Morocco',
			'MC' => 'Monaco',
			'MD' => 'Moldova',
			'ME' => 'Montenegro',
			'MF' => 'Saint Martin',
			'MG' => 'Madagascar',
			'MH' => 'Marshall Islands',
			'MK' => 'Macedonia',
			'ML' => 'Mali',
			'MM' => 'Myanmar',
			'MN' => 'Mongolia',
			'MO' => 'Macao',
			'MP' => 'Northern Mariana Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MS' => 'Montserrat',
			'MT' => 'Malta',
			'MU' => 'Mauritius',
			'MV' => 'Maldives',
			'MW' => 'Malawi',
			'MX' => 'Mexico',
			'MY' => 'Malaysia',
			'MZ' => 'Mozambique',
			'NA' => 'Namibia',
			'NC' => 'New Caledonia',
			'NE' => 'Niger',
			'NF' => 'Norfolk Island',
			'NG' => 'Nigeria',
			'NI' => 'Nicaragua',
			'NL' => 'Netherlands',
			'NO' => 'Norway',
			'NP' => 'Nepal',
			'NR' => 'Nauru',
			'NU' => 'Niue',
			'NZ' => 'New Zealand',
			'OM' => 'Oman',
			'PA' => 'Panama',
			'PE' => 'Peru',
			'PF' => 'French Polynesia',
			'PG' => 'Papua New Guinea',
			'PH' => 'Philippines',
			'PK' => 'Pakistan',
			'PL' => 'Poland',
			'PM' => 'Saint Pierre and Miquelon',
			'PN' => 'Pitcairn',
			'PR' => 'Puerto Rico',
			'PS' => 'Palestinian Territory',
			'PT' => 'Portugal',
			'PW' => 'Palau',
			'PY' => 'Paraguay',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RS' => 'Serbia',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'SA' => 'Saudi Arabia',
			'SB' => 'Solomon Islands',
			'SC' => 'Seychelles',
			'SD' => 'Sudan',
			'SE' => 'Sweden',
			'SG' => 'Singapore',
			'SH' => 'Saint Helena',
			'SI' => 'Slovenia',
			'SJ' => 'Svalbard and Jan Mayen',
			'SK' => 'Slovakia',
			'SL' => 'Sierra Leone',
			'SM' => 'San Marino',
			'SN' => 'Senegal',
			'SO' => 'Somalia',
			'SR' => 'Suriname',
			'ST' => 'Sao Tome and Principe',
			'SV' => 'El Salvador',
			'SY' => 'Syrian Arab Republic',
			'SZ' => 'Swaziland',
			'TC' => 'Turks and Caicos Islands',
			'TD' => 'Chad',
			'TF' => 'French Southern Territories',
			'TG' => 'Togo',
			'TH' => 'Thailand',
			'TJ' => 'Tajikistan',
			'TK' => 'Tokelau',
			'TL' => 'Timor-Leste',
			'TM' => 'Turkmenistan',
			'TN' => 'Tunisia',
			'TO' => 'Tonga',
			'TR' => 'Turkey',
			'TT' => 'Trinidad and Tobago',
			'TV' => 'Tuvalu',
			'TW' => 'Taiwan',
			'TZ' => 'Tanzania',
			'UA' => 'Ukraine',
			'UG' => 'Uganda',
			'UM' => 'United States Minor Outlying Islands',
			'US' => 'United States',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VA' => 'Holy See (Vatican City State)',
			'VC' => 'Saint Vincent and the Grenadines',
			'VE' => 'Venezuela',
			'VG' => 'Virgin Islands British',
			'VI' => 'Virgin Islands U.S.',
			'VN' => 'Vietnam',
			'VU' => 'Vanuatu',
			'WF' => 'Wallis and Futuna',
			'WS' => 'Samoa',
			'YE' => 'Yemen',
			'YT' => 'Mayotte',
			'ZA' => 'South Africa',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);
	}
}