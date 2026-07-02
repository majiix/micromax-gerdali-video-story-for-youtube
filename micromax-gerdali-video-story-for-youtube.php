<?php
/**
 * Plugin Name: Micromax Gerdali video story for youtube
 * Plugin URI: https://wordpress.org/plugins/micromax-gerdali-video-story-for-youtube
 * Description: Displays a YouTube channel's latest videos in an Instagram-style story circle layout with skeleton loading. Videos open in an overlay.
 * Version: 1.7.1
 * Author: micromax
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: micromax-gerdali-video-story-for-youtube
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 *
 * @package Micromax_Gerdali_Video_Story
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Global variable to track if the shortcode is used on the current page.
global $micromax_gerdali_load_modal;
$micromax_gerdali_load_modal = false;

/**
 * Enqueues frontend scripts and styles.
 */
function micromax_gerdali_enqueue_assets() {
	wp_register_style( 'micromax-gerdali-style', plugin_dir_url( __FILE__ ) . 'assets/css/youtube-story-videos.css', array(), '1.7.1' );
	wp_register_script( 'micromax-gerdali-script', plugin_dir_url( __FILE__ ) . 'assets/js/youtube-story-videos.js', array(), '1.7.1', true );

	wp_localize_script(
		'micromax-gerdali-script',
		'micromax_gerdali_ajax',
		array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'micromax_gerdali_fetch_nonce' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'micromax_gerdali_enqueue_assets' );

/**
 * Fetches videos from the YouTube XML feed with transient caching.
 *
 * @param int    $count      Number of videos to retrieve.
 * @param string $channel_id The YouTube Channel ID.
 * @return array|false Array of video items or false on failure.
 */
function micromax_gerdali_get_youtube_videos( $count, $channel_id ) {
	if ( empty( $channel_id ) || ! is_string( $channel_id ) || ! preg_match( '/^UC[a-zA-Z0-9_-]{22}$/', $channel_id ) ) {
		return false;
	}

	$transient_key = 'micromax_gerdali_v_' . md5( $channel_id . $count );
	$cached_data   = get_transient( $transient_key );

	if ( false !== $cached_data ) {
		if ( 'error' === $cached_data ) {
			return false;
		}
		return $cached_data;
	}

	$feed_url = add_query_arg( 'channel_id', $channel_id, 'https://www.youtube.com/feeds/videos.xml' );
	$response = wp_remote_get( esc_url_raw( $feed_url ) );

	if ( is_wp_error( $response ) ) {
		set_transient( $transient_key, 'error', 10 * MINUTE_IN_SECONDS );
		return false;
	}

	$response_code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $response_code ) {
		set_transient( $transient_key, 'error', 10 * MINUTE_IN_SECONDS );
		return false;
	}

	$body = wp_remote_retrieve_body( $response );

	// Suppress XML parsing warnings if feed is temporarily malformed.
	libxml_use_internal_errors( true );
	$disable_el = false;
	if ( PHP_VERSION_ID < 80000 && function_exists( 'libxml_disable_entity_loader' ) ) {
		$disable_el = libxml_disable_entity_loader( true );
	}
	$xml = simplexml_load_string( $body );
	if ( PHP_VERSION_ID < 80000 && function_exists( 'libxml_disable_entity_loader' ) ) {
		libxml_disable_entity_loader( $disable_el );
	}
	libxml_clear_errors();

	if ( ! $xml instanceof SimpleXMLElement || empty( $xml->entry ) ) {
		set_transient( $transient_key, 'error', 10 * MINUTE_IN_SECONDS );
		return false;
	}

	$videos = array();
	$i      = 0;

	foreach ( $xml->entry as $entry ) {
		if ( $i >= $count ) {
			break;
		}

		if ( ! $entry instanceof SimpleXMLElement ) {
			continue;
		}

		// YouTube specific namespace for extracting the yt:videoId.
		$yt       = $entry->children( 'http://www.youtube.com/xml/schemas/2015' );
		$video_id = '';
		if ( $yt instanceof SimpleXMLElement ) {
			$video_id = (string) $yt->videoId;
		}
		$title = (string) $entry->title;

		if ( empty( $video_id ) ) {
			continue;
		}

		$videos[] = array(
			'video_id'  => $video_id,
			'thumbnail' => 'https://i.ytimg.com/vi/' . $video_id . '/mqdefault.jpg',
			'title'     => $title,
		);

		$i++;
	}

	if ( ! empty( $videos ) ) {
		set_transient( $transient_key, $videos, HOUR_IN_SECONDS );
		return $videos;
	}

	set_transient( $transient_key, 'error', 10 * MINUTE_IN_SECONDS );
	return false;
}

/**
 * Handles the AJAX request to fetch video HTML, replacing skeletons.
 */
function micromax_gerdali_ajax_fetch_videos() {
	// Nonce verification bypassed for frontend AJAX requests to ensure compatibility with page caching plugins.

	$count      = isset( $_POST['count'] ) ? absint( $_POST['count'] ) : 5;
	$count      = min( 50, max( 1, $count ) );
	$channel_id = isset( $_POST['channel_id'] ) && is_string( $_POST['channel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['channel_id'] ) ) : '';
	$channel_id = trim( $channel_id );

	if ( empty( $channel_id ) || ! preg_match( '/^UC[a-zA-Z0-9_-]{22}$/', $channel_id ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid or missing YouTube Channel ID.', 'micromax-gerdali-video-story-for-youtube' ) ) );
	}

	$videos = micromax_gerdali_get_youtube_videos( $count, $channel_id );

	if ( empty( $videos ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'No videos found in the feed.', 'micromax-gerdali-video-story-for-youtube' ) ) );
	}

	$html = '';
	foreach ( $videos as $video ) {
		$video_id  = isset( $video['video_id'] ) ? $video['video_id'] : '';
		$thumbnail = isset( $video['thumbnail'] ) ? $video['thumbnail'] : '';
		$title     = isset( $video['title'] ) ? wp_strip_all_tags( $video['title'] ) : '';

		if ( empty( $video_id ) || empty( $thumbnail ) ) {
			continue;
		}

		$html .= sprintf(
			'<div class="micromax-gerdali-story-item" data-video-id="%1$s" role="button" tabindex="0" aria-label="%2$s">
				<div class="micromax-gerdali-story-circle">
					<img src="%3$s" alt="%2$s" />
				</div>
				<span class="micromax-gerdali-story-title">%4$s</span>
			</div>',
			esc_attr( $video_id ),
			esc_attr( $title ),
			esc_url( $thumbnail ),
			esc_html( $title )
		);
	}

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_micromax_gerdali_fetch_videos', 'micromax_gerdali_ajax_fetch_videos' );
add_action( 'wp_ajax_nopriv_micromax_gerdali_fetch_videos', 'micromax_gerdali_ajax_fetch_videos' );

/**
 * Shortcode to display the story circles skeletons.
 * Usage: [micromax_gerdali_story_videos id="UC..." count="5"]
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function micromax_gerdali_render_shortcode( $atts ) {
	global $micromax_gerdali_load_modal;
	$micromax_gerdali_load_modal = true;

	$atts = shortcode_atts(
		array(
			'count' => 5,
			'id'    => '',
		),
		$atts,
		'micromax_gerdali_story_videos'
	);

	$atts['count'] = min( 50, max( 1, absint( $atts['count'] ) ) );

	if ( ! empty( $atts['id'] ) && is_string( $atts['id'] ) ) {
		$atts['id'] = trim( $atts['id'] );
	}

	if ( empty( $atts['id'] ) || ! is_string( $atts['id'] ) || ! preg_match( '/^UC[a-zA-Z0-9_-]{22}$/', $atts['id'] ) ) {
		return '<p>' . esc_html__( 'Please provide a valid YouTube Channel ID in the shortcode attributes.', 'micromax-gerdali-video-story-for-youtube' ) . '</p>';
	}

	wp_enqueue_style( 'micromax-gerdali-style' );
	wp_enqueue_script( 'micromax-gerdali-script' );

	ob_start();
	?>
	<div class="micromax-gerdali-stories-container micromax-gerdali-loading" data-count="<?php echo esc_attr( $atts['count'] ); ?>" data-channel-id="<?php echo esc_attr( $atts['id'] ); ?>">
		<?php for ( $i = 0; $i < absint( $atts['count'] ); $i++ ) : ?>
			<div class="micromax-gerdali-story-item micromax-gerdali-skeleton" aria-hidden="true">
				<div class="micromax-gerdali-story-circle"></div>
				<span class="micromax-gerdali-story-title-skeleton"></span>
			</div>
		<?php endfor; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'micromax_gerdali_story_videos', 'micromax_gerdali_render_shortcode' );

/**
 * Outputs the modal markup in the footer if the shortcode is used.
 */
function micromax_gerdali_render_modal_footer() {
	global $micromax_gerdali_load_modal;
	if ( ! $micromax_gerdali_load_modal ) {
		return;
	}
	?>
	<div id="micromax-gerdali-video-modal" class="micromax-gerdali-modal" aria-hidden="true">
		<div class="micromax-gerdali-modal-overlay" tabindex="-1"></div>
		<div class="micromax-gerdali-modal-content">
			<button class="micromax-gerdali-modal-close" aria-label="<?php esc_attr_e( 'Close video', 'micromax-gerdali-video-story-for-youtube' ); ?>">&times;</button>

			<button class="micromax-gerdali-modal-nav micromax-gerdali-modal-prev" aria-label="<?php esc_attr_e( 'Previous video', 'micromax-gerdali-video-story-for-youtube' ); ?>">&#10094;</button>

			<div class="micromax-gerdali-iframe-container">
				<div id="micromax-gerdali-video-player"></div>
			</div>

			<button class="micromax-gerdali-modal-nav micromax-gerdali-modal-next" aria-label="<?php esc_attr_e( 'Next video', 'micromax-gerdali-video-story-for-youtube' ); ?>">&#10095;</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'micromax_gerdali_render_modal_footer' );

/**
 * Cleans non-breaking spaces (NBSP) from our shortcode inside content before it is parsed.
 *
 * @param string $content The post content.
 * @return string Cleaned content.
 */
function micromax_gerdali_clean_shortcode_spaces( $content ) {
	if ( false === strpos( $content, 'micromax_gerdali_story_videos' ) ) {
		return $content;
	}

	// Match the shortcode with its attributes
	$content = preg_replace_callback(
		'/\[micromax_gerdali_story_videos[^\]]*\]/u',
		function ( $matches ) {
			// Replace non-breaking spaces (UTF-8 \xc2\xa0 / \xa0) and normal NBSPs with standard spaces
			$cleaned = str_replace( array( "\xc2\xa0", "\xa0", "&nbsp;" ), ' ', $matches[0] );
			return $cleaned;
		},
		$content
	);

	return $content;
}
add_filter( 'the_content', 'micromax_gerdali_clean_shortcode_spaces', 9 );
add_filter( 'widget_text', 'micromax_gerdali_clean_shortcode_spaces', 9 );
add_filter( 'widget_block_content', 'micromax_gerdali_clean_shortcode_spaces', 9 );

/**
 * Registers the Tools submenu page.
 */
function micromax_gerdali_add_tools_menu_page() {
	add_submenu_page(
		'tools.php',
		esc_html__( 'YouTube Video Story Shortcode Generator', 'micromax-gerdali-video-story-for-youtube' ),
		esc_html__( 'YouTube Video Story', 'micromax-gerdali-video-story-for-youtube' ),
		'manage_options',
		'micromax-gerdali-youtube-story',
		'micromax_gerdali_tools_page_callback'
	);
}
add_action( 'admin_menu', 'micromax_gerdali_add_tools_menu_page' );

/**
 * Enqueues admin styles and scripts for the Shortcode Generator page.
 *
 * @param string $hook_suffix The current admin page hook.
 */
function micromax_gerdali_admin_enqueue_assets( $hook_suffix ) {
	if ( 'tools_page_micromax-gerdali-youtube-story' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'micromax-gerdali-admin-style',
		plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
		array(),
		'1.7.1'
	);

	wp_enqueue_script(
		'micromax-gerdali-admin-script',
		plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
		array( 'jquery' ),
		'1.7.1',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'micromax_gerdali_admin_enqueue_assets' );

/**
 * Renders the shortcode generator tools page markup.
 */
function micromax_gerdali_tools_page_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<div class="micromax-gerdali-admin-container">
			<div class="micromax-gerdali-dashboard">
				<!-- Header -->
				<header class="micromax-gerdali-header">
					<div class="micromax-gerdali-header-left">
						<span class="micromax-gerdali-badge"><?php esc_html_e( 'Pro Dashboard', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
						<h1><?php esc_html_e( 'YouTube Video Story', 'micromax-gerdali-video-story-for-youtube' ); ?></h1>
						<p><?php esc_html_e( 'Configure and generate dynamic YouTube video feeds in a modern Instagram-like layout.', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
					</div>
					<div class="micromax-gerdali-header-right">
						<span class="micromax-gerdali-version-tag">v1.7.1</span>
					</div>
				</header>

				<div class="micromax-gerdali-main-grid">
					<!-- Sidebar Navigation -->
					<nav class="micromax-gerdali-sidebar">
						<ul>
							<li>
								<button class="micromax-gerdali-nav-item active" data-tab="generator">
									<span class="nav-icon">⚡</span>
									<span class="nav-text"><?php esc_html_e( 'Shortcode Generator', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
								</button>
							</li>
							<li>
								<button class="micromax-gerdali-nav-item" data-tab="documentation">
									<span class="nav-icon">📖</span>
									<span class="nav-text"><?php esc_html_e( 'Guide & FAQs', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
								</button>
							</li>
							<li>
								<button class="micromax-gerdali-nav-item" data-tab="system">
									<span class="nav-icon">⚙️</span>
									<span class="nav-text"><?php esc_html_e( 'System Status', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
								</button>
							</li>
						</ul>
					</nav>

					<!-- Content Panels -->
					<div class="micromax-gerdali-content">
						<!-- PANEL 1: Generator -->
						<div id="tab-generator" class="micromax-gerdali-tab-panel active">
							<div class="micromax-gerdali-panel-grid">
								<!-- Settings Input -->
								<section class="micromax-gerdali-card">
									<h2><?php esc_html_e( 'Generator Settings', 'micromax-gerdali-video-story-for-youtube' ); ?></h2>
									
									<div class="micromax-gerdali-form-group">
										<label for="micromax-gerdali-channel-id"><?php esc_html_e( 'YouTube Channel ID', 'micromax-gerdali-video-story-for-youtube' ); ?></label>
										<input type="text" id="micromax-gerdali-channel-id" placeholder="e.g., UCx7QlhVvF5h4Bq4p6Z38b1A" value="" />
										<p class="description"><?php esc_html_e( 'Enter the unique ID of the YouTube channel you want to display.', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
									</div>

									<div class="micromax-gerdali-form-group">
										<label for="micromax-gerdali-video-count"><?php esc_html_e( 'Video Count', 'micromax-gerdali-video-story-for-youtube' ); ?></label>
										<div class="range-input-wrapper">
											<input type="range" id="micromax-gerdali-video-count-range" min="1" max="50" value="5" />
											<input type="number" id="micromax-gerdali-video-count" min="1" max="50" value="5" />
										</div>
										<p class="description"><?php esc_html_e( 'Specify how many recent videos to load. Default is 5. Max is 50.', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
									</div>
								</section>

								<!-- Shortcode Preview and Live Design Mockup -->
								<div class="micromax-gerdali-panel-right">
									<section class="micromax-gerdali-card shortcode-output-card">
										<h2><?php esc_html_e( 'Your Shortcode', 'micromax-gerdali-video-story-for-youtube' ); ?></h2>
										
										<div class="micromax-gerdali-generator-preview-wrapper">
											<label><?php esc_html_e( 'Copy Code Below', 'micromax-gerdali-video-story-for-youtube' ); ?></label>
											<div class="micromax-gerdali-shortcode-code" id="micromax-gerdali-shortcode-display" role="textbox" aria-readonly="true"></div>
										</div>

										<button id="micromax-gerdali-copy-btn" class="micromax-gerdali-btn-copy" aria-label="<?php esc_attr_e( 'Copy shortcode to clipboard', 'micromax-gerdali-video-story-for-youtube' ); ?>">
											<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
												<path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
											</svg>
											<span><?php esc_html_e( 'Copy Shortcode', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
											<span class="micromax-gerdali-copy-tooltip" aria-hidden="true"><?php esc_html_e( 'Copied!', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
										</button>
									</section>

									<section class="micromax-gerdali-card live-preview-card">
										<h2><?php esc_html_e( 'Live Layout Preview', 'micromax-gerdali-video-story-for-youtube' ); ?></h2>
										<p class="description"><?php esc_html_e( 'Here is a simulation of how the story circles look on the frontend widget.', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
										<div class="micromax-gerdali-live-preview-container" id="micromax-gerdali-live-preview">
											<!-- Dynamic preview items inserted via JS -->
										</div>
									</section>
								</div>
							</div>
						</div>

						<!-- PANEL 2: Guide & FAQs -->
						<div id="tab-documentation" class="micromax-gerdali-tab-panel">
							<section class="micromax-gerdali-card">
								<h2><?php esc_html_e( 'How to Find YouTube Channel ID', 'micromax-gerdali-video-story-for-youtube' ); ?></h2>
								<div class="faq-list">
									<div class="faq-item active">
										<button class="faq-trigger">
											<span><?php esc_html_e( '1. Locate the channel ID in the browser URL', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
											<span class="faq-icon">▼</span>
										</button>
										<div class="faq-content">
											<p><?php esc_html_e( 'Navigate to the YouTube Channel in your web browser. Look at the address bar. The channel ID is the string starting with "UC" in the URL (e.g., youtube.com/channel/UCx7QlhVvF5h4Bq4p6Z38b1A).', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
										</div>
									</div>
									<div class="faq-item">
										<button class="faq-trigger">
											<span><?php esc_html_e( '2. Find it in YouTube Advanced Settings', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
											<span class="faq-icon">▼</span>
										</button>
										<div class="faq-content">
											<p><?php esc_html_e( 'Alternatively, go to Advanced Settings in your YouTube Studio account settings to find it listed under account details.', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
										</div>
									</div>
									<div class="faq-item">
										<button class="faq-trigger">
											<span><?php esc_html_e( '3. How to use the shortcode?', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
											<span class="faq-icon">▼</span>
										</button>
										<div class="faq-content">
											<p><?php esc_html_e( 'Copy the generated shortcode and paste it into any WordPress post, page, widget, or template file. In the Block Editor, you can use the Shortcode Block.', 'micromax-gerdali-video-story-for-youtube' ); ?></p>
										</div>
									</div>
								</div>
							</section>
						</div>

						<!-- PANEL 3: System Status -->
						<div id="tab-system" class="micromax-gerdali-tab-panel">
							<section class="micromax-gerdali-card">
								<h2><?php esc_html_e( 'System Information', 'micromax-gerdali-video-story-for-youtube' ); ?></h2>
								<div class="status-grid">
									<div class="status-row">
										<span class="status-label"><?php esc_html_e( 'Plugin Version', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
										<span class="status-value highlight">1.7.1</span>
									</div>
									<div class="status-row">
										<span class="status-label"><?php esc_html_e( 'PHP Version', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
										<span class="status-value"><?php echo esc_html( phpversion() ); ?></span>
									</div>
									<div class="status-row">
										<span class="status-label"><?php esc_html_e( 'WordPress Version', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
										<span class="status-value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
									</div>
									<div class="status-row">
										<span class="status-label"><?php esc_html_e( 'Shortcode Status', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
										<span class="status-value success"><?php esc_html_e( 'Registered & Active', 'micromax-gerdali-video-story-for-youtube' ); ?></span>
									</div>
								</div>
							</section>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'micromax_gerdali_add_action_links' );

/**
 * Adds shortcode generator action link on the plugins page.
 *
 * @param array $links Existing action links.
 * @return array Updated action links.
 */
function micromax_gerdali_add_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=micromax-gerdali-youtube-story' ) ),
		esc_html__( 'Shortcode Generator', 'micromax-gerdali-video-story-for-youtube' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}