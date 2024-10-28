<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    WordPress
 * @subpackage AvatarPlus
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.4
 * @link       http://wordpress.com
 */

/**
 * Plugin Name:	AvatarPlus
 * Plugin URI:	http://yoda.neun12.de
 * Description:	Replacing the standard avatar in comments with a Google+, Facebook or Twitter avatar if a user enter a profile url
 * Version: 	0.4
 * Author: 		Ralf Albert
 * Author URI: 	http://yoda.neun12.de
 * Text Domain: avatarplus
 * Domain Path: /languages
 * Network:
 * License:		GPLv3
 */

namespace AvatarPlus;

use WordPress\Autoloader as Autoloader;
use WordPress\Tools as Tools;

/**
 * Initialize plugin on theme setup.
 * This is a theme specific functionality, but the code store some data
 * in the comment meta. This data can be better removed on plugin uninstall.
 *
 */
add_action(
	'plugins_loaded',
	__NAMESPACE__ . '\plugin_init',
	10,
	0
);

register_activation_hook(
	__FILE__,
	__NAMESPACE__ . '\activate'
);

register_deactivation_hook(
	__FILE__,
	__NAMESPACE__ . '\deactivate'
);

register_uninstall_hook(
	__FILE__,
	__NAMESPACE__ . '\uninstall'
);

/**
 * On activation:
 * - Initialize autoloader
 * - Check if the PHP- and WP versions are correct
 * - Add default options
 */
function activate() {

	require_once 'wordpress/tools.php';
	Tools\check_php_version();

	init_autoloader();

	// default options
	$options = array(
		'metakey'                  => 'avatarplus_profile_url',
		'cachingkey'               => 'avatarplus_caching',
		'use_extra_field'          => false,
		'cache_expiration_value'   => 30,
		'cache_expiration_periode' => 'days',
	);

	add_option( Backend\Backend::OPTION_KEY, $options );

}

/**
 * On deactivation:
 *  - Remove cached urls
 *  - Remove options
 */
function deactivate() {

	global $wpdb;

	// delete caching data
	$sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s;";
	$wpdb->query( $wpdb->prepare( $sql, Backend\Backend::get_option( 'cachingkey' ) ) );

}

/**
 * On uninstall:
 *  - Remove all comment-meta (profile URLs)
 *  - Remove all post-meta (cached URLs)
 *  - Remove options
 */
function uninstall() {

	global $wpdb;

	// delete extra field data
	$sql = "DELETE FROM {$wpdb->commentmeta} WHERE meta_key = %s;";
	$wpdb->query( $wpdb->prepare( $sql, Backend\Backend::get_option( 'metakey' ) ) );

	// delete caching data
	$sql = "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s;";
	$wpdb->query( $wpdb->prepare( $sql, Backend\Backend::get_option( 'cachingkey' ) ) );

	// remove options
	delete_option( Backend\Backend::OPTION_KEY );

}

/**
 * Initialize the autoloader
 * @return boolean Always true
 */
function init_autoloader() {

	require_once 'wordpress/autoloader.php';

	$config = array(
		'abspath' => __FILE__,
	);

	Autoloader\Autoloader::init( $config );

	return true;
}

/**
 * Initialize the plugin
 * - Init autoloader
 * - Add hooks&filters on plugins loaded
 */
function plugin_init() {

	init_autoloader();

	$use_extra_field = Backend\Backend::get_option( 'use_extra_field' );

	if( false !== $use_extra_field ) {

		// add the field to comment form
		add_filter(
			'comment_form_defaults',
			__NAMESPACE__ . '\add_comment_field'
		);

		// save data from new comment field on posting a comment
		add_action(
			'comment_post',
			__NAMESPACE__ . '\save_comment_meta_data',
			10,
			1
		);

	}

	// get avatar
	add_filter(
		'get_avatar',
		__NAMESPACE__ . '\get_aplus_avatar',
		10,
		5
	);

	// create menupage
	if( is_admin() )
		$backend = new Backend\Backend();


	// cleanup cache
	if( ! defined( 'DISABLE_WP_CRON' ) || true != DISABLE_WP_CRON ) {
		add_action(
			'wp',
			__NAMESPACE__ . '\check_cron_cleanup_cache'
		);
	}

	add_action(
		'avatarplus_cleanup_cache',
		__NAMESPACE__ . '\cleanup_cache'
	);

	// debugging
	if( defined( 'WP_DEBUG' ) && true === WP_DEBUG )
		add_action(
			'wp_footer',
			__NAMESPACE__ . '\get_cache_usage',
			10,
			0
		);

}

/**
 * Add an extra field to the comment form
 *
 * @uses apply_filters() 'avatarplus_labeltext' Filter the label text for the extra field
 * @param array $default_fields The default comment fields
 * @return arary $default_fields Modified array with extra comment field
 */
function add_comment_field( $default_fields ) {

	if( ! is_array( $default_fields ) || empty( $default_fields ) )
		return $default_fields;

	$metakey    = Backend\Backend::get_option( 'metakey' );
	$label_text = apply_filters( 'avatarplus_labeltext', 'Profile URL' );

	$comment_field_template = '<p class="comment-form-author">
		<label for="%label%">%label_text%</label>
		<input id="%label%" name="%label%" size="30" type="text" />
	</p>';

	$comment_field_template = str_replace( '%label%', $metakey, $comment_field_template );
	$comment_field_template = str_replace( '%label_text%', $label_text, $comment_field_template );

	$default_fields['fields'][$metakey] = $comment_field_template;

	return $default_fields;

}

/**
 * Save the data from extra comment field
 *
 * @param integer $comment_id ID of the current comment
 * @return boolean True on success, false on error.
 */
function save_comment_meta_data( $comment_id ) {

	if( empty( $comment_id ) )
		return false;
	else
		$comment_id = (int) $comment_id;

	$metakey = Backend\Backend::get_option( 'metakey' );

	$url = filter_input( INPUT_POST, $metakey, FILTER_SANITIZE_URL );

	// do not save empty urls
	if( ! empty( $url ) ) {
		add_comment_meta(
			$comment_id,
			$metakey,
			$url,
			false
		);

		return true;
	}

	return false;

}

/**
 * Get the avatar if an url to the user's profile is set. Else return the avatar created by WordPress
 *
 * @uses apply_filters 'avatarplus_apikey' !!!IMPORTANT!!! Setup the api-key for G+ with apply_filters
 * @param string $avatar HTML of the avatar image
 * @param int|string|object $id_or_email User ID or user email
 * @param int $size Size of the avatar
 * @param string $default URL to a default image
 * @param string $alt Alternative text to use in image tag. Defaults to 'AvatarPlus'
 * @return string $aplus_avatar <img>-tag with avatar
 */
function get_aplus_avatar( $avatar, $id_or_email, $size = 96, $default = '', $alt = '' ) {

	global $comment, $post;

	// bail if comment and/or post is missed
	if ( empty( $comment ) || empty( $post ) ) {
		return $avatar;
	} else {
		$comment = (object) $comment;
		$post    = (object) $post;
	}

	$aplus_avatar      = null;
	$aplus_avatar_html = null;
	$profile_url       = '';
	$metakey           = Backend\Backend::get_option( 'metakey' );
	$post_id           = ( isset( $post->ID ) ) ? (int) $post->ID : 0;

	$profile_url = get_profile_url( $comment );

	// if no profile url was found, bail
	if( empty( $profile_url ) )
		return $avatar;

	$aplus_avatar = new Url\Profile_To_Avatar( $profile_url, $size, $post_id );

	// reset to default avatar if faild getting avatar from profile url
	if( false === $aplus_avatar->get_service() )
		return $avatar;

	$aplus_avatar_html = replace_avatar_html( $avatar, $aplus_avatar->get_avatar_url( $size ), $size, $alt );

	return $aplus_avatar_html;

}

/**
 * Get comment author url
 *
 * This function returns the comment author url depending on using an extra field
 *
 * @param object $comment The comment data
 * @return boolean|string $profile_url The comment author url or false if no url is available
 */
function get_profile_url( $comment ) {

	if ( empty( $comment ) )
		return false;

	$profile_url     = false;
	$metakey         = Backend\Backend::get_option( 'metakey' );
	$use_extra_field = Backend\Backend::get_option( 'use_extra_field' );
	// prevent error message on dashboard if the comment ID is not set
	// do NOT use get_comment_ID(), this will raise the error messages again!
	$comment_id = ( isset( $comment->comment_ID ) ) ? (int) $comment->comment_ID : 0;

	// get the profile url depending on use_extra_field
	// if an extra field is in use, prefer the url from the extra filed. Else
	// prefer the url from comment data.
	if ( true == $use_extra_field ) {
		$url = get_comment_meta( $comment_id, $metakey, true );
		$profile_url = ( ! empty( $url ) ) ?
			$url : $comment->comment_author_url;
	} else {
		$profile_url = ( isset( $comment->comment_author_url ) && ! empty( $comment->comment_author_url ) ) ?
			$comment->comment_author_url : get_comment_meta( $comment_id, $metakey, true );
	}

	return $profile_url;

}

/**
 * Replacing the attributes in the WP avatar <img>-tag
 *
 * @param string $html The html to modify
 * @param string $url URL replacement
 * @param number $size Size replacement
 * @param string $alt Alternate text replacement
 * @return string Modified <img>-tag
 */
function replace_avatar_html( $html = '', $url = '', $size = 0, $alt = '' ) {

	if( empty( $html ) )
		return '';

	$search_and_replace = array(
			'src'    => 'url',
			'alt'    => 'alt',
			'width'  => 'size',
			'height' => 'size',
	);

	foreach ( $search_and_replace as $attrib => $var ) {

		if( ! empty( $$var ) )
			$html = preg_replace(
				sprintf( '#%s=(["|\'])(.*)(["|\'])#Uuis', $attrib ),
				sprintf( '%s=${1}%s${3}', $attrib, $$var ),
				$html
			);


	}

	return $html;

}

/**
 * Simple debugging function
 *
 * Print out the cache usage to the footer
 */
function get_cache_usage() {

	$cache = new Cache\Cache();

	printf( '<p style="text-align:center">Cache hits: %d / Chache missed: %d</p>', $cache::$chache_hits, $cache::$chache_miss );

}

/**
 * Test if the cron to cleanup the cach data is scheduled
 *
 * @return boolean
 */
function check_cron_cleanup_cache() {

	if( ! wp_next_scheduled( 'avatarplus_cleanup_cache' ) ) {

		wp_schedule_event( time(), 'daily', 'avatarplus_cleanup_cache' );

		return true;

	}

	return false;

}

/**
 * Delete avatarplus caching data
 *
 * @return array Number of found post and deleted meta data (cache data)
 */
function cleanup_cache() {

	global $wpdb;

	// define time constants for WP < 3.5
	// define additional constant MONTH_IN_SECONDS ( = 30 DAYS_IN_SECONDS )
	if( ! defined( 'MINUTE_IN_SECONDS' ) ) define( 'MINUTE_IN_SECONDS', 60 );
	if( ! defined( 'HOUR_IN_SECONDS' ) )   define( 'HOUR_IN_SECONDS',   60 * MINUTE_IN_SECONDS );
	if( ! defined( 'DAY_IN_SECONDS' ) )    define( 'DAY_IN_SECONDS',    24 * HOUR_IN_SECONDS   );
	if( ! defined( 'WEEK_IN_SECONDS' ) )   define( 'WEEK_IN_SECONDS',    7 * DAY_IN_SECONDS    );
	if( ! defined( 'MONTH_IN_SECONDS' ) )  define( 'MONTH_IN_SECONDS',  30 * DAY_IN_SECONDS    );
	if( ! defined( 'YEAR_IN_SECONDS' ) )   define( 'YEAR_IN_SECONDS',  365 * DAY_IN_SECONDS    );

	$value   = Backend\Backend::get_option( 'cache_expiration_value' );
	$periode = Backend\Backend::get_option( 'cache_expiration_periode' );
	$metakey = Backend\Backend::get_option( 'cachingkey' );
	$counter = array( 'found' => 0, 'deleted' => 0 ); 	// for testing & debugging

	$transform = array(
			'days'	=> DAY_IN_SECONDS,
			'weeks'	=> WEEK_IN_SECONDS,
			'month'	=> MONTH_IN_SECONDS,
			'years'	=> YEAR_IN_SECONDS,
	);

	$seconds = ( key_exists( $periode, $transform ) ) ?
		(int) ($transform[$periode] * $value) : 0;

	if( 0 === $seconds )
		return false;

	$timestamp = date( 'Y-m-d H:i:s', ( time() - $seconds ) );

	$sql  = "SELECT ID FROM {$wpdb->posts} WHERE post_date_gmt < %s";
	$pids = $wpdb->get_results( $wpdb->prepare( $sql, $timestamp ) );

	$counter['found'] = sizeof( $pids );

	foreach ( $pids as $pid ) {
		$pm = get_post_meta( $pid->ID, $metakey, true );

		if( ! empty( $pm ) ) {

			delete_post_meta( $pid->ID, $metakey );
			$counter['deleted']++;
		}
	}

	return $counter;

}
