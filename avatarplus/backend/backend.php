<?php
/**
 * WordPress-Plugin AvatarPlus
 *
 * PHP version 5.3
 *
 * @category   PHP
 * @package    AvatarPlus
 * @subpackage AvatarPlus\Backend
 * @author     Ralf Albert <me@neun12.de>
 * @license    GPLv3 http://www.gnu.org/licenses/gpl-3.0.txt
 * @version    0.1.20130112
 * @link       http://wordpress.com
 */

namespace AvatarPlus\Backend;

class Backend
{
	/**
	 * Constant for option key
	 * @var string
	 */
	const OPTION_KEY = 'avatarplus_options';

	/**
	 * Constant for menu slug
	 * @var string
	 */
	const MENU_SLUG = 'avatarplus';

	/**
	 * Constant for textdomain
	 * @var string
	 */
	const TEXTDOMAIN = 'avatarplus';

	/**
	 * Basename of this file
	 * @var string
	 */
	public $basename = '';

	/**
	 * Text files with translations
	 * @var array
	 */
	public $html_files = array();

	/**
	 * Option values from db
	 * @var array
	 */
	public static $options = array();

	/**
	 * Pagehook for the menu page
	 * @var string
	 */
	public $pagehook = '';

	/**
	 * Constructor - Initialize the backend
	 * - Initialize the settings api
	 * - add menu page
	 */
	public function __construct() {

		$this->basename = plugin_dir_path( dirname( __FILE__ ) );

		$this->init_translation();

		add_action( 'admin_init', array( $this, 'settings_api_init' ), 1, 0 );
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 10, 0 );

	}

	/**
	 * Returns a single option or all options for this plugin from db
	 * @param string $option_name Name of the option to retrieve (optional)
	 * @return mixed Depending on the option name, the single option if available or all option values if no option name was given
	 */
	public static function get_option( $option_name = '' ) {

		if( empty( self::$options ) )
			self::$options = get_option( self::OPTION_KEY );

		if( empty( $option_name ) )
			return self::$options;

		return ( isset( self::$options[$option_name] ) ) ?
			self::$options[$option_name] : null;

	}

	/**
	 * Initialize the translation
	 * - Load plugin textdomain
	 * - Read translated text files for backend
	 * @return boolean Always true
	 */
	public function init_translation() {

		// load_plugin_textdomain need a different basename
		// _td = load_plugin_textdomain()
		// _tf = load text files
		$lang_dir_td = basename( $this->basename ) . '/languages';
		$lang_dir_tf = $this->basename . '/languages';

		load_plugin_textdomain( self::TEXTDOMAIN, false, $lang_dir_td );

		$lang = ( defined( 'WPLANG' ) ) ?
		    substr( WPLANG, 0, 2 ) : 'en';

		if( is_dir( $lang_dir_tf . '/' . $lang ) )
			$lang_dir_tf .= '/' . $lang . '/';
		else
			$lang_dir_tf .= '/en/';


		$html_files = glob( $lang_dir_tf . '*.{htm,html}', GLOB_BRACE );

		foreach ( $html_files as $file ) {
			preg_match( '#.+/([^/]+)\.html?$#Uuis', $file, $match );

			if ( isset( $match[1] ) && ! empty( $match[1] ) ) {
				$this->html_files[ $match[1] ] = $match[0];
			}
		}

		return true;

	}

	/**
	 * Initialise the WordPress Settings-API
	 * - Register the settings
	 * - Register the sections
	 * - Register the fields for each section
	 * @return boolean Always true
	 */
	public function settings_api_init() {

		// the sections
		$sections = array(
			// section-id => title, callback
			'aplus' => array( 'title' => __( 'AvatarPlus settings', self::TEXTDOMAIN ), 'callback' => 'aplus_section' ),
			'gplus' => array( 'title' => __( 'Google+ settings', self::TEXTDOMAIN ), 'callback' => 'gplus_section' ),
		);

		// fields for the sections
		$fields = array(
			// field-id => in-section, title, callback
			'field_1'	=> array( 'section' => 'aplus', 'title' => __( 'Extra field', self::TEXTDOMAIN ), 'callback' => 'comment_field' ),
			'field_2'	=> array( 'section' => 'aplus', 'title' => __( 'Cache', self::TEXTDOMAIN ), 'callback' => 'cache_field' ),
			'field_3'	=> array( 'section' => 'gplus', 'title' => __( 'Google+ API key', self::TEXTDOMAIN ), 'callback' => 'gplus_field' ),
		);

		// register settings
		register_setting(
		    self::OPTION_KEY,
		    self::OPTION_KEY,
		    array( $this, 'options_validate' )
		);

		// register each section
		foreach ( $sections as $id => $args ) {
			$title    = $args['title'];
			$callback = array( $this, $args['callback'] );

			add_settings_section( $id, $title, $callback, self::MENU_SLUG );
		}

		// register each field in it's section
		foreach ( $fields as $id => $args ) {
			$title    = $args['title'];
			$section  = $args['section'];
			$callback = array( $this, $args['callback'] );

			add_settings_field( $id, $title, $callback,	self::MENU_SLUG, $section );
		}

		return true;

	}

	/**
	 * Add a page to the dashboard-menu
	 * @return boolean Always true
	 */
	public function add_menu_page(){

		if( ! current_user_can( 'manage_options' ) )
			return false;

		$this->pagehook = add_options_page(
		    'AvatarPlus',
		    'AvatarPlus',
		    'manage_options',
		    self::MENU_SLUG,
		    array( $this, 'main_section' ),
		    false,
		    'bottom'
		);

		add_action(
		    'load-'.$this->pagehook,
		    array( $this, 'add_help_tab' ),
		    10,
		    0
		);

		return true;

	}

	/**
	 * Add a help tab to the AvatarPlus options page
	 * @return boolean True if the help tab was created, otherwise false
	 */
	public function add_help_tab() {

		$screen = get_current_screen();

		if( $screen->id !== $this->pagehook )
			return false;

		$screen->add_help_tab(
		    array(
                'id'       => 'avatarplus',
                'title'    => 'AvatarPlus',
                'content'  => $this->get_text( 'help_tab_content' ),
            )
		);

		return true;

	}

	/**
	 * Validate saved options
	 * @param array $input Options send
	 * @return array $input Validated options
	 */
	public function options_validate( $input ) {

		$options = self::get_option();

		$input = array_merge( $options, $input );

		$input['use_extra_field'] = ( isset( $input['use_extra_field'] ) && 'on' === $input['use_extra_field'] ) ? true : false;

		$input['cache_expiration_value']   = filter_var( $input['cache_expiration_value'], FILTER_SANITIZE_NUMBER_INT );
		$input['cache_expiration_periode'] = ( in_array( (string) $input['cache_expiration_periode'], array( 'days', 'weeks', 'month', 'years' ) ) ) ?
			(string) $input['cache_expiration_periode'] : 'days';

		return $input;

	}

	/**
	 * Return content of a text file
	 * @param string $section Section/identifier of the text file
	 * @return string $anonymous File content
	 */
	public function get_text( $section = '' ) {

		if( empty( $section ) )
			return false;

		return ( isset( $this->html_files[ $section ] ) && file_exists( $this->html_files[ $section ] ) ) ?
			file_get_contents( $this->html_files[ $section ] ) : false;

	}

	/**
	 * Main section of the settings page
	 * @return boolean Always true
	 */
	public function main_section() {

		if( ! current_user_can( 'manage_options' ) )
			return;

		echo '<div class="wrap"><h1>AvatarPlus</h1>';

		echo $this->get_text( __FUNCTION__ );

		echo '<form action="options.php" method="post">';

		settings_fields( self::OPTION_KEY );
		do_settings_sections( self::MENU_SLUG );

		submit_button( __( 'Save Changes', self::TEXTDOMAIN ), 'primary', 'submit_options', true );

		echo '</form>';
		echo '</div>';

		return true;

	}

	/**
	 * AvatarPlus settings section
	 * @return boolean Always true
	 */
	public function aplus_section() {

		echo $this->get_text( __FUNCTION__ );

		return true;

	}

	/**
	 * Callback for comment field settings
	 * @return boolean Always true
	 */
	public function comment_field() {

		$use_extra_field = self::get_option( 'use_extra_field' );
		$checked = checked( $use_extra_field, true, false );

        printf(
            '<input type="checkbox" name="%1$s[use_extra_field]" id="%1$s-use_extra_field"%2$s> %3$s',
            self::OPTION_KEY,
            $checked,
            __( 'Use extra field in comment form', self::TEXTDOMAIN )
        );

		return true;

	}

	/**
	 * Callback for cache management field
	 * @return boolean Always true
	 */
	public function cache_field() {

		$cache_value   = self::get_option( 'cache_expiration_value' );
		$cache_periode = self::get_option( 'cache_expiration_periode' );

		$disabled = ( defined( 'DISABLE_WP_CRON' ) && true == DISABLE_WP_CRON ) ?
			' disabled' : '';

        printf(
            '<input type="text" size="1" style="text-align:right" name="%1$s[cache_expiration_value]" id=name="%1$s-cache_value" value="%2$s"%3$s>',
            self::OPTION_KEY,
            esc_attr( $cache_value ),
            $disabled
        );

		$option_values = array(
			'days'   => __( 'Day(s)', self::TEXTDOMAIN ),
			'weeks'  => __( 'Week(s)', self::TEXTDOMAIN ),
			'months' => __( 'Month(s)', self::TEXTDOMAIN ),
			'years'  => __( 'Year(s)', self::TEXTDOMAIN )
		);

		$options_output = '';

		$select_skeleton = '<select name="%1$s[cache_expiration_periode]" id="%1$scache_periode"%3$s>%2$s</select>';

		foreach ( $option_values as $value => $text ) {
			$selected = ( $value === $cache_periode ) ?
				' SELECTED' : '';

			$options_output .= sprintf( "\t<option value=\"%s\"%s>%s</option>\n", $value, $selected, $text );
		}

		printf( $select_skeleton, self::OPTION_KEY, $options_output, $disabled );

		if( ! empty( $disabled ) )
			printf( '<div class="error-message"><p>%s</p></div>', __( "Your WordPress Cronjobs are disabled. The cache won't be deleted!", self::TEXTDOMAIN ) );

		return true;

	}

	/**
	 * Google+ section
	 * @return boolean Always true
	 */
	public function gplus_section() {

		echo $this->get_text( __FUNCTION__ );

		return true;

	}

	/**
	 * Callback for Google+ field (G+ API key)
	 * @return boolean Always true
	 */
	public function gplus_field() {

		$apikey = self::get_option( 'gplus_apikey' );

        printf(
            '<input type="text" size="50" name="%1$s[gplus_apikey]" id="%1$s-gplus_apikey" value="%2$s">',
            self::OPTION_KEY,
            esc_attr( $apikey )
        );

		return true;

	}

}