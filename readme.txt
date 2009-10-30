==== FB Share ====
Contributors: Jesse Stay
Tags: posts, Facebook, facebook share, facebook_share, share button
Requires at least: 2.8
Tested up to: 2.8.4
	
Adds a Facebook Share button to your WordPress posts

== Description ==

FB Share is a WordPress Plugin, which lets you add a Facebook Share button for your WordPress posts, together with the total number of share.

###Usage###

There are three ways you can add the facebook share button. Automatic way, manual way and using shortcodes

#### Automatic way

Install the Plugin and choose the type and position of the button from the Plugin’s settings page. You can also specifically enable/disable the button for each post or page from the write post/page screen.

#### Manual way

If you want more control over the way the button should be positioned, then you can manually call the button using the following code.

if (function_exists('fbshare_button')) echo fbshare_button();

#### Using shortcodes

You can also place the shortcode [fbshare] anywhere in your post. This shortcode will be replaced by the button when the post is rendered.

More information available at the [Plugins home page][1].

 [1]: http://staynalive.com/fbshare
	

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Screenshots ==
1. Settings page

2. Enable/Disable button in the write post/page page

== Changelog ==

###v0.1 (2009-10-26)

*   Initial Release
