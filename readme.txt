=== BP BookMarklet ===
Contributors: imath
Donate link: http://imathi.eu/donations/
Tags: BuddyPress, activity, bookmarklet, share, links, members
Requires at least: 4.4
Tested up to: 4.5
Stable tag: 3.0.0
License: GPLv2

Let the members of your BuddyPress powered community add a Bookmarklet to their browser to share interesting web pages

== Description ==

Members will find their bookmarklet within a sub menu of their profile's Account Settings if the BuddyPress Settings component is active, else a new profile navigation will be created by the plugin. From this settings page, members can drag the bookmarklet within their browser's bookmarks bar.

Then sharing any internet pages in their activity stream or in a specific group's activity stream can be achieved simply by clicking on this bookmarklet!

A popup window will help them set their preferences about the activity to publish.

<strong>NB</strong>: Since version 3.0.0, this plugin requires at least BuddyPress 2.5 and WordPress 4.4.

This plugin is available in french and english.

== Installation ==

You can download and install BP BookMarklet using the built in WordPress plugin installer. If you download BP BookMarklet manually, make sure it is uploaded to "/wp-content/plugins/bp-bookmarklet/".

Activate BP BookMarklet in the "Plugins" admin panel using the "Network Activate" (or "Activate" if you are not running a network) link.

== Frequently Asked Questions ==

= If you have a question =

Please add a comment <a href="http://imathi.eu/tag/bp-bookmarklet/">here</a>

== Screenshots ==

1. window to share bookmarks in activity streams.

== Changelog ==

= 3.0.0 =
* Completely rewritten!
* Now uses the WordPress "Press This" class to get informations about the Page (eg: images, description, ..)
* Uses the embed code if the webpage is embeddable within the BuddyPress Activity stream
* Backbone & Underscore to the rescue for a revamped Activity Post Form UI.
* Bookmarks can be filtered using the activity dropdown filters (Administration Screen/Directory/Profiles & Groups)

= 2.0.2 =
* corrects a bug : after the use of the bookmarklet, the image attached to it was also attached to each activity post during the same session

= 2.0.1 =
* corrects a bug : now the attach image checkbox is only loaded in the bookmarklet page

= 2.0 =
* brings BuddyPress 1.7 compatibility
* adds the ability to attach one of the images of the link into the activity content

= 1.1 =
* widget added to put the bookmarklet where you want
* css bug fix
* when on an internet page, the selected text will be copied to the bookmarklet activity form.

= 1.0 =
* BP BookMarklet can run on BP 1.5.x and BP 1.2.x

== Upgrade Notice ==

= 3.0.0 =
Requires (at least) WordPress 4.4 & BuddyPress 2.5. Users will need to add the new bookmarklet and remove the older one.

= 2.0.2 =
Requires at least BuddyPress 1.7.

= 2.0.1 =
Requires at least BuddyPress 1.7.

= 2.0 =
Requires at least BuddyPress 1.7.

= 1.1 =
nothing particular..

= 1.0 =
no upgrades, just a first install..
