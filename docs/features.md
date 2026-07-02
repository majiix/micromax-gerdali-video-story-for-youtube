# Project Features - YouTube Video Story for YouTube

## Shortcode Rendering & Feeds
- **Instagram-Style Story Layout**: Displays YouTube channel video feeds inside modern circles matching popular social media widgets.
- **On-Page Modal Playback**: Video playback opens inside a customizable lightbox modal, complete with YouTube Iframe API support for next/previous navigation.
- **Smart Transient Caching**: Prevents hitting YouTube RSS XML feed limits by storing parsed entries within transients for up to 1 hour.
- **Shortcode Parser Cleanup**: Automatically strips non-breaking space (NBSP) characters pasted into pages/posts to prevent parser validation errors.
- **Page Caching Compatibility**: Removed public AJAX nonce validation to prevent 403 Forbidden errors when page caching plugins (such as Litespeed or Cloudflare) are used.
- **Strict Input & Request Validation**: Prevents PHP 8.x TypeErrors (Error 500) and security issues by verifying parameter types and enforcing strict channel ID regex patterns.

## Pro Dashboard Settings (Generator)
- **Three-Panel Navigation Tab**: Seamless switching between the Shortcode Generator, Guide & FAQ instructions, and System Status checks.
- **Live Shortcode Builder**: Instantly formats valid shortcodes as user changes Channel ID or Count values.
- **Synchronized Selectors**: Lets administrators adjust count numbers via either slider elements or range boxes concurrently.
- **Horizontal Story Previewer**: A functional UI mock that shows real-time rendering of active story circles with responsive scrollbars.
- **Copy-to-Clipboard Action**: Copy button with visual success state overlays.
