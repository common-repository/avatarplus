=== AvatarPlus ===

Contributors: Ralf Albert, F J Kaiser
Donate link: https://github.com/RalfAlbert/AvatarPlus
Tags: comments, avatar
Requires at least: 3.5
Tested up to: 3.5.1
Stable tag: 0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

AvatarPlus allows users to use their profile image from Google+, Facebook or Twitter as avatar for their comment(s).

AvatarPlus requires PHP v5.3+

== Description ==

AvatarPlus allows users to use the avatar from a social service of their choice (supports: Google+, Twitter and Facebook) as their comments avatar instead of the default from the Gravatar service or your WordPress installation. More and more users avoid typing in their mail address and instead just want to hand out their social profile URL. AvatarPlus adds this feature to WordPress comments, thus making the mail address field not required anymore for displaying an avatar image.

Flexibility for a maximum number of use cases: The plugin allows to either add a new field to the comments section or just use the homepage URL field for the social profile URL. AvatarPlus also recognizes redirects and is able to work with most URL-shortening services like bit.ly or goo.gl.

Environment friendly: AvatarPlus cares about your resources and uses a simple caching mechanism to save the avatar links directly and therefore reducing the number of HTTP requests to a minimum and serving your sites comments section as fast as possible.

Maximum code quality: Every single line of code is written with WordPress "Best Practice" in mind to serve you only the highest quality product.


== Installation ==

1. Search for the plugin name in your admin user interfaces plugin page. Then install it.
2. If needed, adjust the settings under "Settings" » "AvatarPlus" in the admin user interface.

If you want to install the plugin manually:

1. Download "AvatarPlus" from the WordPress repository
2. Unpack the archive.
3. Upload the unpacked archive folder to your plugins folder.
4. Activate the plugin.
5. If needed, adjust the settings under "Settings" » "AvatarPlus".

== Screenshots ==

 1. AvatarPlus backend

== Changelog ==

= 0.4 =

* Optimized caching
* Simplified autoloading
* Simplified php version check
* Fix some smaller bugs

= 0.3 =

* Fix small caching problems 

= 0.2 =

* First public version

= 0.1 =

* First developer version

== Arbitrary section ==

AvatarPlus uses a simple caching mechanism. In some countries, webmaster have to declare if the webpage stores personal data about the user. AvatarPlus stores the following data:

 - The URL which the users entered into the comment form.
 - The profile URL to their social network profile, if the URL (which the user entered) redirects to their respective profile URL.
 - The URL of the profile image they use on the social network.

 - This data will be stored until an expiration is set in the backend/administration interface.
 - The expiration can divert (depending on your settings).
 - The check whether the plugin needs to delete. any data will be run once a day. For further details see the internal mechanisms of the plugin in its source code.
