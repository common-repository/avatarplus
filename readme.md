# AvatarPlus #

**Contributors:** Ralf Albert, F J Kaiser

## Short Description ##
AvatarPlus allows users to use their profile image from Google+, Facebook or Twitter as avatar for their comment(s).

AvatarPlus requires PHP v5.3+

## Description ##
AvatarPlus allows users to use the avatar from a social service of their choice (supports: Google+, Twitter or Facebook) as their comments avatar instead of the default from the Gravatar service or your WordPress installation. More and more users avoid typing in their mail address and instead want just want to hand out their social profile URL. AvatarPlus adds this feature to WordPress comments, thus making the mail address field not required anymore.

**Environment friendly**: AvatarPlus cares about your resources and uses a simple caching mechanism to save the avatar links directly and therefore reducing the number of HTTP requests to a minimum and serving your sites comments section as fast as possible.

**Maximum code quality**: Every single line of code is written with WordPress "Best Practice" in mind to serve you only the highest quality product.


## Installation ##
1. Search for the plugin name in your admin user interfaces plugin page. Then install it.
2. If needed, adjust the settings under "Settings" » "AvatarPlus" in the admin user interface.

If you want to install the plugin manually:

1. Download "AvatarPlus" from the WordPress repository
2. Unpack the archive.
3. Upload the unpacked archive folder to your plugins folder.
4. Activate the plugin.
5. If needed, adjust the settings under "Settings" » "AvatarPlus".

## Changelog ##

### 0.4 ###

* Optimized caching
* Simplified autoloading
* Simplified php version check
* Fix some smaller bugs


### 0.3 ###
* Fix small caching problem

### 0.2 ###
* First public version

### 0.1 ###
* First developer version

## Arbitrary section ##
AvatarPlus uses a simple caching mechanism. In some countries, webmaster have to declare if the webpage stores personal data about the user. AvatarPlus stores the following data:

 - The URL which the users entered into the comment form.
 - The profile URL to their social network profile, if the URL (which the user entered) redirects to their respective profile URL.
 - The URL of the profile image they use on the social network.

 - This data will be stored until an expiration is set in the backend/administration interface.
 - The expiration can divert (depending on your settings).
 - The check whether the plugin needs to delete. any data will be run once a day. For further details see the internal mechanisms of the plugin in its source code.
