=== Podamibe Twilio Private Call ===
Contributors: podamibe
Tags: twilio, twilio call, private call, podamibe
Requires at least: 3.0.0
Tested up to: 5.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Free plugin for private using twilio api.

== Description ==
Wordpress plugin for twilio voice calling. Uses twilio caller id as your caller id while calling thus hiding your personal number to your friends and collegues.

== Installation ==

1. Upload the plugin 'Podamibe Twilio Private Call' to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' menu in WordPress.

<strong>How to use?</strong><br/>
Following shortdoces are available after installing this plugin.
1. [pod-twilio-activate]
2. [pod-twilio-userprofile]
3. [pod-twilio-userslist]
4. [pod-twilio-contactlist]

1. [pod-twilio-activate]
Display twilio activation form if user is logged in and has not activated twilio account.

2. [pod-twilio-userprofile]
Shows user details including user's twilio number and can change personal number here.
User can also buy call duration here.

3. [pod-twilio-userslist]
Shows all users who have registered for twilio activation here.
Users can add desired user to their contact list.
Pass limit argument to shortcode for pagination limit e.g [pod-twilio-user-signup limit=5]

4. [pod-twilio-contactlist]
Shows all the users in the contactlist of the user.
Users can verify their number to the contact. ( Note: Users cannot call if user's are not verified to each other )
Shows twilio number for verified users.

In addition to these plugins we have included a user sign up shortcode. Add [pod-twilio-user-signup] shortcode to show user signup form.

We've also included a login widget.
Drag the Twilio login widget to desired sidebar. Choose the page you want to redirect after login and save.

== Frequently Asked Questions ==

= What does this plugin do? =
- This plugin hides your personal number while calling using twilio voice calling.

= Why can't I see the phone number even after adding a user to contact list?
- You cannot view the contact number if you haven't verified your number. Verify your number first. Also the user in your contact list also need to verify his/her number too.

= Can I call a number not in my contact list?
- No, you cannot.

= Can I call a non verified number?
- No, you cannot. Both users in the contact list must verify their numbers in each other's account. Only then the call is possible.

== Screenshots ==

1. Screenshot 1 - Backend Twilio Settings
2. Screenshot 2 - User Twilio Account Details
3. Screenshot 3 - User Contact List

== Changelog ==
= 1.0.1 =
* add the support link

= 1.0.0 =
* Initial release

== Upgrade Notice ==
There is an update available for the Podamibe Twilio Private Call. Please update to recieve new updates and bug fixes.