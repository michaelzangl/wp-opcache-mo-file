=== Opcache MO Files ===
Contributors: michael.zangl
Tags: performance
Requires at least: 4.9.0
Tested up to: 4.9.4
Requires PHP: 7.0
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Store mo-files in a format that is cacheable by Opcache. Saves up to ~30% loading time for non-english locales.
 
== Description ==

This plugin caches the contents of the mo-Files used to translate wordpress. Since mo-file parsing is one of the most expensive tasks wordpress does, this may reduce your page load time.

This may reduce your page load times by ~30% for a typical wordpress install with several plugins.

Feel free to provide feedback about speed improvements in your environments.

== Installation ==

Install and activate like any other wordpress plugin.

No settings - it just works out of the box.

Deactivate / uninstall using the wordpress UI (do not just remove the plugins dir: The plugin registers a mu-plugin hook that needs to be unregistered)

== Frequently Asked Questions ==

= Why doesn't it work on windows =

Because I don't know anyone who runs wordpress on windows and could test it.
 
= What PHP version is required? =
 
PHP 7.2 is recommended, 7.0 required
 
= What PHP plugins are required? =

You should install opcache for best performance.
 
= Does it work with multiple languages? =
 
Yes
 
= Does it make sense to install this on an english site? =
 
Probably no
 
== Screenshots ==
 
Nothing to show: No config, it just works.
 
== Changelog ==
 
= 1.0 =
* First version