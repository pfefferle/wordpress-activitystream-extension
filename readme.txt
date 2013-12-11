=== ActivityStream extension ===
Contributors: pfefferle
Donate link: http://14101978.de
Tags: ActivityStreams, Activity Stream, feed, RSS, Atom, JSON
Requires at least: 3.2
Tested up to: 3.7.1
Stable tag: 1.0.0

ActivityStrea.ms feeds for WordPress (Atom and JSON)

== Description ==

ActivityStreams ([activitystrea.ms](http://www.activitystrea.ms)) support for your WordPress-blog

ActivityStreams is an open format specification for activity stream protocols, which are used to syndicate activities taken in social web applications and services, similar to those in Facebook's Newsfeed, FriendFeed, the Movable Type Action Streams plugin, etc.

== Installation ==

* Upload the whole folder to your `wp-content/plugins` folder
* Activate it at the admin interface

Thats it

== Changelog ==

Project maintined on github at
[pfefferle/wordpress-activitystream-extension](https://github.com/pfefferle/wordpress-activitystream-extension/).

= 1.0.0 =
* changes based on the 7B plugin <https://github.com/pento/7B>
* json-feed for comments
* cleanup

= 0.8 =
* some JSON changes to match spec 1.0
* changed the HTML discovery-links
* added post_thumbnail support

= 0.7.1 =
* updated to new JSON-Activity changes

= 0.7 =
* deprecated `<activity:subject>`
* enriched Atom `<author />`

= 0.6 =
* added json feed
* pubsubhubbub for json

= 0.5 =
* some OStatus compatibility fixes
* added `<activity:subject>`
* added `<activity:target>`

= 0.3 =
* Fixed a namespace bug
* Added autodiscovery link
