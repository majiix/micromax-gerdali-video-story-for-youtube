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

// Find and delete all transients securely, ensuring object caching is supported.
global $wpdb;

// Securely prepare the wildcard string.
$micromax_gerdali_like = $wpdb->esc_like( '_transient_micromax_gerdali_v_' ) . '%';

// We must query the DB directly to find the keys since wildcards aren't supported natively.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$micromax_gerdali_transients = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $micromax_gerdali_like ) );

if ( is_array( $micromax_gerdali_transients ) ) {
	foreach ( $micromax_gerdali_transients as $micromax_gerdali_transient ) {
		// Strip the database prefix to get the exact transient key.
		$micromax_gerdali_key = str_replace( '_transient_', '', $micromax_gerdali_transient );

		// delete_transient() will cleanly remove it from both the DB and the object cache.
		delete_transient( $micromax_gerdali_key );
	}
}