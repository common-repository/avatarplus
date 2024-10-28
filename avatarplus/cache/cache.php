<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    AvatarPlus
 * @subpackage AvatarPlus\Cache
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    20130224
 * @link       http://wordpress.com
 */

namespace AvatarPlus\Cache;

use AvatarPlus as Base;
use AvatarPlus\Backend\Backend as Backend;


/**
 * AvatarPlus caching class
 * Simple caching for urls to avatar images
 *
 * @author Ralf Albert
 *
 */
class Cache
{
	/**
	 * Internal cache
	 * @var array
	 */
	public static $cache = array();

	/**
	 * Metakey for storing cache data in the options db
	 * @var string
	 */
	public $cachekey = '';

	/**
	 * ID of the current post
	 * @var integer
	 */
	public $post_id = 0;

	/**
	 * Number of cache hits
	 * @var integer
	 */
	public static $chache_hits = 0;

	/**
	 * Number of cache missed
	 * @var integer
	 */
	public static $chache_miss = 0;

	/**
	 * Reading cache if internal cache is empty
	 */
	public function __construct( $post_id = 0 ) {

		$this->post_id = (int) filter_var( $post_id, FILTER_SANITIZE_NUMBER_INT );

		$this->cachekey = Backend::get_option( 'cachingkey' );

		if( empty( self::$cache[ $this->post_id ] ) )
			self::$cache[ $this->post_id ] = $this->read_cache();

	}

	/**
	 * Test if the url data of a specific url are already cached
	 * @param string $url URL to test
	 * @return boolean True if the url data rae cached, else false
	 */
	public function is_cached( $url = '' ) {

		if( isset( self::$cache[ $this->post_id ][ md5( $url ) ] ) )
			self::$chache_hits++;

		return isset( self::$cache[ $this->post_id ][ md5( $url ) ] );

	}

	/**
	 * Returns the cached url data
	 * @param string $url URL
	 * @return object URL data
	 */
	public function get_cached_url( $url = '' ) {

		return ( isset( self::$cache[ $this->post_id ][ md5( $url ) ] ) ) ?
			self::$cache[ $this->post_id ][ md5( $url ) ] : null;

	}

	/**
	 * Caching the url data
	 * @param AvatarPlus_Profile_To_Avatar $urldata Object with the url data
	 * @return boolean Always true
	 */
	public function cache_url( \stdClass $urldata ) {

		// setup the service to false if no avatar-url is set
		if ( empty( $urldata->avatar_url ) && ! isset( $urldata->service ) ) {
			$urldata->service = false;
		}

		self::$cache[ $this->post_id ][ md5( $urldata->url ) ] = $urldata;

		self::$chache_miss++;

		$this->write_cache();

		return self::$cache;

	}

	/**
	 * Read the external cache
	 * @param int $post_id Post ID
	 * @return array|boolean Cached url data, false on error
	 */
	public function read_cache() {

		return get_post_meta( $this->post_id, $this->cachekey, true );

	}

	/**
	 * Writing the external cache
	 * @param int $post_id Post ID
	 * @return boolean True on success, false on error
	 */
	public function write_cache() {

		return update_post_meta( $this->post_id, $this->cachekey, self::$cache[ $this->post_id ] );

	}

	/**
	 * Delete the cache of a given post ID
	 * @param int $post_id Post ID
	 * @return boolean True on success, false on error
	 */
	public function reset_cache() {

		return delete_post_meta( $this->post_id, $this->cachekey );

	}

}
