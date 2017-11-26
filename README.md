## geoIP Location Mod

## Introduction
This modification adds the ability to use the latitude & longitude of a member given their IP address. This is commonly referred to as geolocation. The mod makes use of the geoip capabilities in Nginx or Apache web servers.  **You must have this capability enabled in your web server** and they must make the following PHP enviroment variables available to PHP ('GEOIP_LATITUDE', 'GEOIP_LONGITUDE' 'GEOIP_CITY_COUNTRY_NAME', 'GEOIP_CITY', 'GEOIP_REGION', 'GEOIP_CITY_COUNTRY_CODE')

**A note on accuracy:**
Maxmind shows that this database (which is updated on a monthly basis) is over 99.5% accurate on a country level making it a viable source for registration blocking.

When it comes to the city level accuracy this number is and 79% for the US (within a 25 mile radius).  That is the best accuracy, and other countries city/region location accuracy tapper off from that.  Even with that level of accuracy, it still makes for an entertaining online member map.

In some instances the IP address will not be found, or will have incomplete data.  In this case the mod will make use of secondary sources to gather information.

## Licensed
The software is licensed under [Mozilla Public License 1.1 (MPL-1.1)](http://www.mozilla.org/MPL/1.1/).

## Features
* Adds an on-line member map which will show a map pin for each IP currently on your forum
* Adds the ability to block or allow member registrations on a per country basis
* Adds in the geoIP information under the track IP sections, allows you to see city / region (state) / country of the IP address
* @todo add country flag to online member list

There are admin settings available with this mod, go to admin - configuration - modification settings - geoIP.
Installation

**IMPORTANT NOTES:**

The package will install on all systems, however you **must** have geoip capabilities enabled in the web server and the enviroment variables made avaliable to PHP.  If you are on shared hosting, check with your host, or if you are on a VPS you can read more about it here: http://www.howtoforge.com/using-geoip-with-nginx-on-ubuntu-12.04 as an example of what is needed.