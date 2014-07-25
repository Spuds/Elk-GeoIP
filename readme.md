##geoIP Location Mod

##Introduction

This modification adds the ability to determine the latitude & longitude of a member given their IP address. This is commonly referred to as geolocation. The mod makes use of the geoip capabilities in nginx.

In some instances the IP address will not be found in the installed database, or will have incomplete data. In this case the mod will make use of secondary sources to gather its information.

A note on accuracy: Maxmind shows that this database (which is updated on a monthly basis) is ~99.5% accurate on a country level making it a viable source for registration blocking.

When it comes to the city level accuracy this number is and ~79% for the US (within a 25 mile radius). That is the best accuracy, and other countries city/region location accuracy tapper off from that. Even with that it still makes for an entertaining online member map.

##Licensed
The software is licensed under [Mozilla Public License 1.1 (MPL-1.1)](http://www.mozilla.org/MPL/1.1/).

##Features
* Adds the ability to block or allow member registrations on a per country basis
* Adds an on-line member map which will show a map pin for each IP currently on your forum
* Adds in the geoIP information under the track IP sections, allows you to see city / region (state) / country of the IP address

There are admin settings available with this mod, go to admin - configuration - modification settings - geoIP.
Installation

**IMPORTANT NOTES:**

The package will install on all systems, however you **must** have geoip capabilities enabled in the nginx web server and the enviroment variables made avaliable to PHP.  If you are on shared hosting, check with your host (there is also an Appache version), or if you are on a VPS you can read more about it here: http://www.howtoforge.com/using-geoip-with-nginx-on-ubuntu-12.04 as an example.