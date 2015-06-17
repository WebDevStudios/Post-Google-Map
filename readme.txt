=== Post Google Map ===
Contributors: williamsba1, Messenlehner, webdevstudios, tw2113
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3084056
Tags: map, google, posts, plot, widget, address, API, sidebar, v3
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 1.6.2
License: GPLv2

Add multiple addresses to a post or page.  Addresses will be plotted on a Google Map in your post/page or in the Post Google Map sidebar widget.

== Description ==

This plugin allows you to add multiple addresses to a post/page and have those addresses automatically plotted on a Google Map in a sidebar widget or directly in the post/page. This is a great plugin to help localize your stories, events, news, etc.
Easily add and delete a single or multiple addresses to each post/page. Viewing a single post/page displays only addresses attached to that post/page, viewing anything else will show the most recent plots across your entire site.
You can also set a custom title and description for each address saved.

Custom plot markers can be upload to the /markers folder.

== Screenshots ==

1. Google Map Shortcode displaying multiple plots on a map in a post
2. Google Map Widget displaying multiple plots on a map for a given post
3. Add new address form.  Multiple addresses can be added to each post/page

== Changelog ==

= 1.6.2 =
* Adding Spanish Translations and .pot file. Props Andrew Kurtis of http://www.webhostinghub.com/

= 1.6.1 =
* Fix map pin issue where multiple color selections didn't work
* Updated to non-deprecated Google API for geolocation coordinates.
* Translation ready

= 1.6 =
* Full plugin rewrite
* Uses current WordPress coding standards
* Tightened security
* Updated to use Google Maps API v3

= 1.5.1 =
* Re-released to fix zip file that wasn't created properly in the plugin directory

= 1.5 =
* Added in security checks on all content being passed to and from the plugin
* Switched settings to be stored in an array
* Optimized the plugin in different areas for speed
* Some minor bug fixes

= 1.4.5 =
* Added a title option on the Post Google Map widget

= 1.4.4 =
* Fixed a bug causing the content in a post without a shortcode to disappear

= 1.4.3 =
* Fixed "headers already sent" error when deleting addresses

= 1.4.2 =
* updated marker path for subdirectories
* fixed to show markers on map

= 1.4.1 =
* Bug fix

= 1.4 =
* Added shortcode support: [google-map]

= 1.3.2 =
* Removed link

= 1.3.1 =
* uploaded correct source

= 1.3 =
* Fixed headers already sent error

= 1.2 =
* Incorporated WordPress HTTP API for wider support of Google Maps API call

= 1.1 =
* Fixed issue with address not saving if post wasn't saved first

= 1.0 =
* First official release

== Upgrade Notice ==

= 1.6 =
* Complete plugin rewrite using updated WordPress procedures and Google Map API v3

== Installation ==

1. Upload the post-google-map folder to the plugins directory in your WordPress installation
2. Activate the plugin
3. Add the "Post Google Map" widget to your sidebar or use shortcode [google-map] in your post/page to embed the Google Map
4. Add addresses to any posts/pages

That's it! Addresses will automatically be plotted on the map for each post/page that contains addresses

== Frequently Asked Questions ==

= Does this plugin work with WordPress Multisite? =

Absolutely!  This plugin has been tested and verified to work on the most current version of WordPress and Multisite
