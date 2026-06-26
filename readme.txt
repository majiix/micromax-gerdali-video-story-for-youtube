=== Micromax Gerdali video story for youtube ===
Contributors: micromax2
Tags: youtube, story, video, widget, instagram style
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays a YouTube channel's latest videos in an Instagram-style story circle layout with skeleton loading.

== Description ==

Micromax Gerdali Video Story for YouTube allows you to seamlessly fetch and display the latest videos from your favorite YouTube channels directly on your WordPress site.

The videos are displayed using an attractive, Instagram-style stories layout featuring gradient rings. Upon clicking a story, the video smoothly opens and auto-plays within an on-page modal overlay, providing an engaging user experience without leaving your website.

**Features**

Beautiful Instagram-Style Stories Layout.
Front-end Skeleton Loading Animation for better UX.
Built-in Modal Overlay with YouTube Player.
"Next" and "Previous" buttons in the player.
Auto-plays the next story when a video concludes.
Public XML feed integration.
Add directly via shortcodes anywhere on the site.
Mobile responsive.

== Installation ==

Upload the youtube-story-videos directory to the /wp-content/plugins/ directory.

Activate the plugin through the 'Plugins' menu in WordPress.

Use the shortcode directly in your posts, pages, or widgets by adding your desired channel ID: [micromax_gerdali_story_videos id="CHANNEL_ID" count="5"].

== Frequently Asked Questions ==

= Where do I find my YouTube Channel ID? =
You can find your Channel ID by going to your YouTube account's advanced settings, or by inspecting the URL of your channel homepage (it usually starts with UC...).

= How do I display multiple channels on one page? =
Simply use different shortcodes with their respective channel IDs wherever you want them to display. Example: [micromax_gerdali_story_videos id="UC123456789"] and [micromax_gerdali_story_videos id="UC987654321"].

== Changelog ==

= 1.6.1 =

* Fixed orphaned transient timeout options accumulating in options table on plugin uninstall.
* Clamped shortcode count attribute and AJAX requests to 50 items max to maintain performance.
* Enhanced XML parsing safety on legacy PHP environments by preventing XML external entity loads.
* Added support for triggering video stories using the Space key and implemented focus restoration when modal closes.

= 1.6.0 =

* Added a dedicated shortcode generator page under the Tools menu.
* Integrated modern responsive styling, real-time shortcode preview, and copy-to-clipboard functionality for easier configuration.

= 1.5.3 =

* Modified the PHP rendering layout to pass the full video title to the HTML span.
* Integrated CSS smooth transition rules so the circular story titles expand to their full multiline text on hover without breaking page alignment.

= 1.5.2 =

* Improved shortcode parsing to automatically clean non-breaking space (NBSP) characters pasted from email, rich-text editors, or PDFs.
* Added trim sanitization to YouTube Channel ID inputs to prevent leading/trailing space conflicts.

= 1.5.1 =

Standardized all internal function names, variables, handles, and classes to the micromax_gerdali prefix for full compatibility and conflict avoidance.

= 1.5.0 =

Removed centralized settings page for a simpler architecture.
Shortcode now accepts raw YouTube Channel IDs directly instead of aliases.

= 1.4.0 =

Complete refactor for stricter prefix guidelines.
Separated admin inline CSS into proper stylesheet enqueues.
Improved frontend responsive design for small mobile devices.

= 1.3.0 =

Added Next and Previous navigation buttons inside the video modal.
Implemented YouTube Iframe API for auto-playing the next video upon completion.

= 1.2.0 =

Shifted from traditional YouTube API to using the public XML feed to prevent rate limits.
Added multi-channel alias capability via a single text-area setting.
Better visual UI and instructions in the admin panel.

= 1.0.0 =

Initial release.