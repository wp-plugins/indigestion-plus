=== Plugin Name ===
Author: goblindegook
Tags: lifestream, digest, feed, rss, atom, post, delicious, google reader, picasa, flickr
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: 0.5

Indigestion+ generates a periodic digest from different customisable feed sources.

== Description ==

Indigestion+ periodically collects a number of public RSS or Atom feeds and posts a list containing all new items.  Each feed may provide additional display and metadata options, such as the ability to import comments or tags.

Indigestion+ began as a fork of the [Indigestion](http://wordpress.org/extend/plugins/indigestion/) plugin by [Evelio Tarazona Cáceres](http://wordpress.org/extend/plugins/profile/evelio), but eventually required a complete rewrite.  It uses Wordpress's "cron" function for scheduling and Simplepie to download, cache and manipulate feeds.

== Installation ==

1. Upload all the files to the `/wp-content/plugins/indigestion-plus` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure Indigestion+ and your feeds

== Frequently Asked Questions ==

= I have multiple service accounts that I'd like to include. How do I fetch more than one feed from the same service? =

At the moment, Indigestion+ allows only one feed per service via the administration panel, and there is no simple way to duplicate accounts.  Expert users may attempt to work around this issue by adding new instances to the plugin source files.  In order to do so, follow these steps:

1. Enter `/wp-content/plugins/indigestion-plus` and open `indigestion-plus-main.php` for editing
2. Locate `$this->class_loader();` near the start of the file and add new instances for the services you want to duplicate immediately below:

> `$this->class_loader();`
> `$this->feeds[] = new IPlusDelicious( 'Delicious 2', 'delicious-2' ); // Example duplicate Delicious account `
> `$this->feeds[] = new IPlusCustom( 'Custom Feed 2', 'custom-2' ); // Example duplicate custom source `

The first parameter is a user friendly name for the feed, which can be anything you like, while the second should be a *unique* identifier used by the plugin to store your feed options.

= How do I create custom feeds? =

Users looking to make their own feed handlers should create a new class that inherits from `IPlusSuper` (found in `indigestion-plus-super.php`), which offers several useful methods that you can use, and a few others that should be redefined to suit your specific needs:

* `get_feed_url()`: Returns the feed URL to fetch based on user options **(required)**
* `print_options()`: Prints the administration panel for your feed (optional)
* `get_item_html()`: Customize how feed items are displayed in the digest (optional)

== Screenshots ==

1. Main administration panel for Indigestion+
2. Sample digest post

== Changelog ==

= 0.5 =
* Initial version
* Supports Delicious, Google Reader, Flickr, Picasa and generic feeds

