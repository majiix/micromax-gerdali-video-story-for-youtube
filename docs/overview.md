# Project Overview - YouTube Video Story for YouTube

Displays a YouTube channel's latest videos in an Instagram-style story circle layout with skeleton loading. Videos open in an overlay on-page modal.

## Tech Stack
- **Core**: PHP (WordPress Plugin API, >= 7.4), JavaScript/jQuery (ES6), CSS (Vanilla CSS).
- **External Integration**: YouTube RSS/XML Feed (`https://www.youtube.com/feeds/videos.xml`).
- **Dependencies**: jQuery, WordPress Core APIs.

## System Architecture
```mermaid
graph TD
    A[WordPress Shortcode: [micromax_gerdali_story_videos]] --> B[Enqueues CSS/JS Assets]
    B --> C[Renders Frontend Skeletons]
    C --> D[AJAX Fetch to YouTube Feed]
    D --> E[Caches feed using WordPress Transients]
    E --> F[Injects Story Circles in Container]
    F --> G[On Click: YouTube Player Modal Overlay opens]
```

## Verification Commands
- PHP Syntax Lint:
  ```bash
  php -l micromax-gerdali-video-story-for-youtube.php
  ```
