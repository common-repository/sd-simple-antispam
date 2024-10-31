=== SD Simple Antispam ===
Tags: spam, antispam, comment, captcha, network
Requires at least: 3.2
Tested up to: 3.2
Stable tag: trunk
Contributors: Sverigedemokraterna IT
A simple comment spam prevention plugin.

== Description ==

Prevents comment spam using various, admin-selectable techniques.

Available in:

* English
* Swedish

Logging is handled by <a href="http://wordpress.org/extend/plugins/threewp-activity-monitor/">ThreeWP Activity Monitor</a>. See the list of logged activities to enable logging of caught spam.

Most spam is caught using "extra fields". Rarely will spam require the use of "no author website" also. Extremely rarely will a spam bot be smart enough to include the hidden fields, leave them empty <em>and</em> leave the author URL empty.

Catching that last kind of spambot will require other measures.

= Extra fields =

Two extra fields are inserted into the comment form and hidden from view using CSS. They are to be left empty, and say as much, in order for the comment to pass.

Bots that spam by inserting fake POSTs will not have the required, invisible fields in the POST - rejecting the comment.

Bots that fill in all form fields will automatically fill in these extra fields - the comment will be rejected.

= No author website =

Comment authors are usually allowed to fill in their URLs. Selecting this technique will only allow comments that have an empty author URL field.

Bots that fill in all form fields will fail this test.

Note that you will need to edit your template to hide the author url input from view using `display: none`.

== Installation ==

1. Unzip and copy the zip contents (including directory) into the `/wp-content/plugins/` directory
1. Activate the plugin sitewide through the 'Plugins' menu in WordPress.
1. After updating the plugin settings, clear your cache.

== Screenshots ==

1. Settings window
1. ThreeWP Activity Monitor showing that a spam comment was stopped 14 hours ago.

== Changelog ==

= 1.1 =
* Logged in users aren't shown the extra fields.

= 1.0 =
* Initial public release

== Upgrade Notice ==

= 1.0 =
No upgrade necessary.

