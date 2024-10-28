<?php
/**
 *
 * WP_Autoloader
 *
 * Easy to use autoloader
 *
 * @author Ralf Albert (neun12@googlemail.com)
 * @version 1.1
 */

/*
 * Usage
 * =====
 *
 * WP_Autoloader( [config] );
 *
 * $config could be array or object
 *
 * $config should look like this:
 *
 * $config = new stdClass();
 * $config->abspath  = __FILE__;
 * $config->pathes   = array( '/lib' );
 * $config->prefixes = array();
 *
 *  OR
 *
 * $config = array(
 * 		'abspath'  => __FILE__,
 * 		'pathes'   => array( 'models', 'views' ),
 * 		'prefixes' => array( 'model-', 'view-' ),
 * );
 *
 * 'abspath' is the base directory where the plugin is inside
 * 'pathes' are the directories relative to 'abspath' where to search for classes
 * 'prefixes' are file-prefixes for class files
 */

namespace WordPress\Autoloader;

class Autoloader
{
	/**
	 * Absolute path to plugin
	 * @var string
	 */
	public static $abspath  = '';

	/**
	 * Directories realtive to absolute path to search for classes
	 * @var array
	 */
	public static $pathes   = array();

	/**
	 * File-prefixes for class filenames
	 * @var array
	 */
	public static $prefixes = array();

	/**
	 * Initialize the autoloading
	 *
	 * The autoloader will be initialized with default values if no configuration is set.
	 * These values are:
	 *  - the directory where the autoloader file is in as 'abspath'
	 *  - no pathes (empty array)
	 *  - no prefixes (empty array)
	 *
	 *  If a configuration is set, then it will be merged with the default configuration.
	 *  The 'abspath' will be sanitized (right trim slashes and backslashes, add directory seperator at end)
	 *  After this, the pathes will be sanitized to valid pathes. All unreachable pathes will be removed!
	 *  As all pathes are relative to the 'abspath', all pathes will be removed if the 'abspath' is wrong.
	 *  In the last step the autoload function is registered with spl_autoload_register()
	 *
	 * @param array|object $args
	 */
	public static function init( $args = null ) {

		$defaults = array(
			'abspath'  => dirname( __FILE__ ) . DIRECTORY_SEPARATOR,
			'pathes'   => self::$pathes,
			'prefixes' => self::$prefixes,
		);

		$args = array_merge( $defaults, $args );

		self::$abspath = (string) $args['abspath'];
		self::sanitize_abspath();

		self::$pathes   = array_merge( self::$pathes, (array) $args['pathes'] );
		self::sanitize_pathes();

		self::$prefixes = array_merge( self::$prefixes, (array) $args['prefixes'] );

		self::register_autoloader();

	}

	/**
	 * Sanitizing the absolute path
	 *
	 * Removes slashes and backslashes at the end, add a directory seperator at the end
	 */
	public static function sanitize_abspath() {

		if( is_file( self::$abspath ) )
			self::$abspath = dirname( self::$abspath );

		self::$abspath = rtrim( self::$abspath, '/' );
		self::$abspath = rtrim( self::$abspath, '\\' );
		self::$abspath .= DIRECTORY_SEPARATOR;

	}

	/**
	 * Sanitizing the pathes
	 *
	 * Remove slashes and backslashes on the left and right. Add a directory seperator
	 * at the end. Check if the directory exists, use 'abspath' as base. If the directory
	 * does not exists, removes it from the list.
	 */
	public static function sanitize_pathes() {

		foreach ( self::$pathes as $key => &$path ) {
			$path = trim( $path, '/' );
			$path = trim( $path, '\\' );
			$path = sprintf( '%s%s%s', self::$abspath, $path, DIRECTORY_SEPARATOR );

			if ( ! is_dir( $path ) )
				unset( self::$pathes[$key] );

		}

	}

	/**
	 * Registering the autoload function
	 *
	 * Use spl_autoload_register() to register the autoload function.
	 */
	public static function register_autoloader() {

		spl_autoload_register( array( __CLASS__, 'autoload' ), true, true );

	}

	/**
	 * The autoload function itself
	 *
	 * Convert all classnames into lower characters. Try to find a namespace.
	 * If a namespace was found, use the namespace as path. Else use the setup pathes
	 * to search for the class.
	 * Both, namespaced and not namespaced classes, can be prefixed.
	 *
	 * @param string $class Class to be loaded
	 */
	public static function autoload( $class ) {

		$classname = strtolower( $class );

		switch ( self::maybe_namespaced( $classname ) ) {

			case 'namespaced':
				self::load_namespaced( $classname );
				break;

			case 'not_namespaced':
			default:
				self::load_not_namespaced( $classname );
				break;

		}

	}

	/**
	 * Load not namespaced classes
	 *
	 * Tries all pathes with classname plus .php and if the class is not found,
	 * tries all pathes plus prefixes with classname plus .php
	 *
	 * @param string $class Class to be loaded
	 */
	public static function load_not_namespaced( $class ) {

		foreach ( self::$pathes as $path ) {

			$file = sprintf( '%s%s.php', $path, $class );

			if ( file_exists( $file ) ) {

				require_once $file;
				break 1;

			} else {

				foreach ( self::$prefixes as $prefix ) {

					$file = sprintf( '%s%s%s.php', $path, $prefix, $class );

					if ( file_exists( $file ) ) {

						require_once $file;
						break 2;

					} // end if

				} // end foreach 2

			} // end if-else

		} // end foreach 1

	}

	/**
	 * Load namespaced classes
	 *
	 * Try to use the namespace as path, if the class is not found, try to
	 * add registered prefixes to the class
	 *
	 * @param string $class Class to be loaded
	 */
	public static function load_namespaced( $class ) {

		$class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );

		$file = sprintf( '%s%s.php', self::$abspath, $class );

		if ( file_exists( $file ) ) {

			require_once $file;

		} else {

			foreach ( self::$prefixes as $prefix ) {

				$replace = sprintf( '\\%s$1.php', $prefix );
				$file = preg_replace( '/\\\(.+)$/is', $replace, $class );

				if ( file_exists( $file ) ) {

					require_once $file;
					break 1;

				} // end if

			} // end foreach

		} // end if-else

	}

	/**
	 * Whether the class is namespaced or not
	 *
	 * @param string $class Class to be tested
	 * @return string 'namespaced' or 'not_namespaced', depending on the class
	 */
	public static function maybe_namespaced( $class ) {

		return ( false != strpos( $class, '\\' ) ) ?
			'namespaced' : 'not_namespaced';

	}

}
