netstat.php - simple network status
===================================

netstat.php is a PHP script intended to provide a simplified, easily comprehensible and aesthetically pleasing overview of the online status of hosts and services.
Background story

We use the usual suspects when it comes to network monitoring. There is lots of very good open source software which does all the tricks. However, we always missed a simple, intuitive interface with lots of green traffic lights which we could make available to our users (and clients).

netstat.php only checks whether a specific port is open. It can be configured to check for instance whether some software is listening on port 25 (for SMTP) but it cannot tell whether this software is still working. Technically, netstat.php uses PHP's `fsockopen()` to create an Internet or *nix domain socket connection.

Point is that we do not need more since we are running full-blown monitoring software anyway. In most cases, it is sufficient for my users to see a red light only when the network or the servers are down or the daemon is not running at all. And in general, an admin will (or at least *should*) know before users do.


Screenshot
----------

  ![netstat.php 0.x screenshot](http://www.fam.tuwien.ac.at/~schamane/sysadmin/netstat/netstat.png)

This screenshot shows also a tooltip which is included as mouse-over in case of errors. The default layout of netstat.php 1.x is practically equivalent.

For an HTML example see [netstat.php 0.x's main page](http://www.fam.tuwien.ac.at/~schamane/sysadmin/netstat/).


Features
--------

* Checks for open ports
* Can do ICMP pings
* Supports IPv6
* Aesthetically pleasing
* Can display an optional alert message
* Support for including alert messages in an RSS feed
* Timeout (sensitivity) is adjustable
* Self-contained but supports an extra configuration file
* 2 versions available:
  * v0.x: Simple, straight forward, procedural code
  * v1.x (GitHub master): Still simple, but more features & more OO
* HTML + CSS compatible with text only browsers (tested with lynx and links)
* Shows timing diagnostics and error messages hidden in mouse-hovers
* Free, open source, and GPL licensed
* Still sticks to the KISS principle


Instructions for use
--------------------

netstat.php comes as a single PHP file. Download it, rename appropriately, edit the source code or create a configuration file to change settings, and have fun. Out of the box, the script looks for a configuration file named `netstat.conf.php`. It is recommended to put all settings into this file.

### netstat.conf.php

Here is sort of a minimal example. For all supported settings see below.

	<?php /* netstat.conf.php example */
	$title = "Our network status";
	$headline = $title;
	$checks = array(
		'router.example.com|ping| ICMP ping (ping)',
		'www.mydomain.not  | 80 | WWW server (port 80)',
		'localhost         | 22 | SSH server (port 22)',
		'   Other checks   | headline',
		'www.example.com   | 80 | WWW server example.com' // no colon here!
	)
	$ping_command = '/usr/bin/ping -l3 -c3 -w1 -q';

### $checks

* Basic syntax: " host | port | description "
* Supported variations:
  * ping: If port = "ping" or "ping6" ICMP pings are sent to host.
  * Negative ports and "-ping" can be used to temporarily disable a check.
  * Headlines: If port = "headline" description is printed as a (sub)heading.
  * Lines with no | (pipe symbol) or empty port entries are ignored.

### netstat.txt

netstat.php can also be used to show an alert message. By default, the script looks for a file named `netstat.txt` and includes it. Use HTML for formatting your alert message. Example:

	<p><b>Status as of 2009-10-26 11:47</b><br />
	Mail services are currently down. On-site engineers are already 
	investigating the cause of this unexpected interruption. Further
	information will be provided in about 30 minutes.</p>

In order to disable the inclusion of the file either rename it or change its permissions.

All configuration variables
---------------------------

Here is a full example of a configuration file (except for `$checks` and `$htmlheader`):

	<?php
	/**
	 * netstat.php - Settings example for $configfile with defaults (mostly)
	 */
	
	$configfile = 'netstat.conf.php'; // name of file where to put these variables
	$error_reporting = 0; // See http://php.net/error_reporting
	
	$title = "Our Network Status"; // page title
	$headline = $title; // page headline
	
	$alertfile = 'netstat.txt'; // extra alert file included in HTML + RSS
	// $checks = allthemagic(); // See script or homepage for how to specify
	
	$ping_command = 'ping -c3 -w1 -q'; // external command to use for ICMP ping
	$ping6_command = 'ping6 -c3 -w1 -q'; // external command to use for ICMP ping
	$timeout = 4; // fsockopen timeout (sort of the sensitivity)
	
	$progressindicator = TRUE; // show a simple progress indicator (req. Javascript)
	$rssfeed = TRUE; // use to enable or disable RSS feeds (see also below)
	
	$online = 'Online'; // strings for online and offline
	$offline = 'Offline'; // note that by default these are used in CSS, too

	$datetime = 'l, F j, Y, H:i:s T'; // date/time format; skipped if empty
	
	$rssfeedurl = $_SERVER['SCRIPT_NAME'].'?rss'; // URL of RSS feed
	$rsstitle = "RSS alert feed of $title"; // RSS feed title
	$rssheader = '<link rel="alternate" type="application/rss+xml" '
	. "title=\"$rsstitle\" href=\"$rssfeedurl\" />"; // RSS header
	$rsslink = $_SERVER['SCRIPT_NAME'].'?noprogress'; // RSS alert link
	$rssdatetime = 'o-m-d H:i:s T'; // RSS date and/or time format
	
	// $htmlheader = ... see script for a full example; note that by default it
	//   uses other variables, too: $title, $rssheader, $online, $offline
	
	$htmlfooter = "</div>\n</body>\n</html>"; // well, guess what :-)
	
	// $version ... version of script, link to homepage and author


Requirements
------------

For basic operation, the script mostly uses PHP's `fsockopen()`, which needs to be enabled. Unfortunately, some web hosting providers apparently disable this function.

For ICMP pings also `exec()` needs to be enabled which many providers disable, and a command line version of `ping` and/or `ping6` must be available.

