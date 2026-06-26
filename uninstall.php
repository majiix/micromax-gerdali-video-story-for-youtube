<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Micromax_Gerdali_Video_Story
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Find and delete all transients and their timeouts securely, ensuring object caching is supported.
global $wpdb;

// Securely prepare the wildcard strings.
$micromax_gerdali_transient_like = $wpdb->esc_like( '_transient_micromax_gerdali_v_' ) . '%';
$micromax_gerdali_timeout_like   = $wpdb->esc_like( '_transient_timeout_micromax_gerdali_v_' ) . '%';

// We must query the DB directly to find the keys since wildcards aren't supported natively.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$micromax_gerdali_options = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$micromax_gerdali_transient_like,
		$micromax_gerdali_timeout_like
	)
);

if ( is_array( $micromax_gerdali_options ) ) {
	$micromax_gerdali_keys = array();
	foreach ( $micromax_gerdali_options as $micromax_gerdali_option ) {
		if ( 0 === strpos( $micromax_gerdali_option, '_transient_timeout_' ) ) {
			$micromax_gerdali_keys[] = str_replace( '_transient_timeout_', '', $micromax_gerdali_option );
		} else {
			$micromax_gerdali_keys[] = str_replace( '_transient_', '', $micromax_gerdali_option );
		}
	}

	$micromax_gerdali_keys = array_unique( $micromax_gerdali_keys );

	foreach ( $micromax_gerdali_keys as $micromax_gerdali_key ) {
		// delete_transient() will cleanly remove it from both the DB and the object cache.
		delete_transient( $micromax_gerdali_key );
	}
}