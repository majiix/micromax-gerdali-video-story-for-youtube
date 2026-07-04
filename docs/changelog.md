step 1:
1- Refactored Tools submenu callback `micromax_gerdali_tools_page_callback` in `micromax-gerdali-video-story-for-youtube.php` to set up a three-tab pro dashboard.
2- Added inputs for YouTube Channel ID and a range slider synchronized with numerical video counts.
3- Designed live preview containers and FAQ accordion slots.

step 2:
1- Completely redesigned `assets/css/admin.css` using custom modern CSS variables, animations, scrollbars, and active state indicators.
2- Styled the Live Layout Preview widget circle elements to mimic frontend story feeds.

step 3:
1- Rewrote `assets/js/admin.js` to manage dashboard tab navigation switches and FAQ accordion slide transitions.
2- Implemented live preview item generation with high-quality random unsplash thumbnail templates.
3- Adjusted CSS Grid properties (`min-width: 0`) and container rules to fix flex wrap horizontal scroll overflow.

step 4:
1- Incremented plugin version tags to `1.7.0` across main PHP headers, enqueues, and layout displays.
2- Documented all changes under a new release block in `readme.txt`.

step 5:
1- Added strict type and format checks on input parameters to avoid TypeError and Error 500 on PHP 8.x.
2- Removed nonce validation from frontend AJAX fetch call to ensure caching plugin compatibility.
3- Added HTTP response validation and SimpleXML instanceof assertions for safe feed parsing.
4- Added a JS modal is-open guard to prevent background audio playback when the lightbox modal is closed during player loading.
5- Incremented plugin version to 1.7.2 across files.
6- Added PHPCS ignore annotations to clarify the intentional nonce check bypass for public caching compatibility.
Commit message: refactor(core): improve input validation, caching compatibility, and player robustness

