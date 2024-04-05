=== ActivityStream extension ===
Contributors: pfefferle
Donate link: https://notiz.blog/donate/
Tags: ActivityStreams, feed, RSS, Atom, JSON-LD
Requires at least: 4.2
Tested up to: 6.5
Stable tag: 1.3.8

ActivityStrea.ms feeds for WordPress (Atom and JSON(-LD))

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

= 1.3.8 =

* CI/CD changes
* optimized "flash rewrite rules"

= 1.3.7 =

* fix "Non-static method" warning

= 1.3.6 =

* Updated WebSub support

= 1.3.4 =

* version bump

= 1.3.4 =

* version bump

= 1.3.3 =

* update dependencies

= 1.3.2 =

* added missing enclosure mime-type

= 1.3.1 =

* replaced json_encode with wp_json_encode
* added enclosures to Atom `<activity:object>`

= 1.3.0 =

* Added enclosure support
* Fixed summary js-summary

= 1.2.3 =

* added WebFinger and host-meta discovery

= 1.2.2 =

* updated requirement
* fixed licenses

= 1.2.1 =

* removed charset

= 1.2.0 =

* updated to latest spec: <http://www.w3.org/TR/2016/WD-activitystreams-core-20160712/>
* fixed comment feed

= 1.1.0 =

* initial AS2 feed (beta)
* WordPress coding standard

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
