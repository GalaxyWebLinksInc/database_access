<?php
/**
 * @link              https://www.galaxyweblinks.com
 * @since             1.0.0
 * @package           database_access
 *
 * @wordpress-plugin
 * Plugin Name:       Database Access
 * Plugin URI:        https://wordpress.org/plugins/database_access/
 * Description:       Database Access is a powerful database administration tool.
 * Version:           1.0.0
 * Author:            Galaxy Weblinks
 * Author URI:        https://profiles.wordpress.org/galaxyweblinks/#content-plugins
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       database_access
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0
 * develop by Nayan gupta
 */
define( 'DATABASE_ACCESS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class_database_access-activator.php
 */
function activate_database_access() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/class_database_access-activator.php';
	database_access_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp_database_access-deactivator.php
 */
function deactivate_database_access() {
	require_once plugin_dir_path( __FILE__ ) . 'inc/class_database_access-deactivator.php';
	database_access_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_database_access' );
register_deactivation_hook( __FILE__, 'deactivate_database_access' );

// avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
} elseif ( version_compare( phpversion(), '5.0.0', '<' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit( 'The plugin require PHP 5 or newer' );
}

define( 'ADMINER_BASE_FILE', plugin_basename( __FILE__ ) );

add_action( 'plugins_loaded', array( 'AdminerForWP', 'get_object' ) );
class AdminerForWP {

	private static $classobj;

	protected $pagehook;

	public function __construct() {

		if ( ! is_admin() ) {
			return null;
		}

		if ( is_multisite() && ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		add_action( 'init', array( $this, 'register_styles' ) );
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'admin_init', array( $this, 'text_domain' ) );
	}

	/**
	 * Handler for the action 'init'. Instantiates this class.
	 *
	 * @since   1.2.2
	 * @access  public
	 * @return \AdminerForWP $classobj
	 */
	public static function get_object() {

		if ( null === self::$classobj ) {
			self::$classobj = new self();
		}

		return self::$classobj;
	}

	/**
	 * Call functions on init of WP
	 *
	 * @return   void
	 */
	public function on_init() {

		// active for MU ?
		if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
			add_action( 'network_admin_menu', array( $this, 'on_network_admin_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );
		}
	}

	public function text_domain() {

		load_plugin_textdomain( 'adminer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function register_styles() {

		wp_register_style( 'adminer-settings', plugins_url( 'admin/css/wp_database_access-admin.css', __FILE__ ) );

		if ( is_multisite() && is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
			add_action( 'admin_bar_menu', array( $this, 'add_wp_admin_bar_item' ), 20 );
		}
	}

	public function on_load_page() {

		add_thickbox();
		wp_enqueue_style( 'adminer-settings' );
		add_action( 'contextual_help', array( $this, 'contextual_help' ), 10, 3 );
	}

	public function on_admin_menu() {

		if ( current_user_can( 'unfiltered_html' ) ) {
			wp_enqueue_style( 'adminer-menu' );

			$menutitle      = __( 'Database Access', 'database_access' );
			$this->pagehook = add_menu_page(
				__( 'Database Access', 'database_access' ),
				$menutitle,
				'unfiltered_html',
				plugin_basename( __FILE__ ),
				array( $this, 'on_show_page' ),
				'dashicons-database',
				50
			);

			add_action( 'load-' . $this->pagehook, array( $this, 'on_load_page' ) );
		}
	}

	public function on_network_admin_menu() {

		if ( current_user_can( 'unfiltered_html' ) ) {
			wp_enqueue_style( 'adminer-menu' );

			$menutitle      = __( 'Database Access', 'database_access' );
			$this->pagehook = add_submenu_page(
				'settings.php',
				__( 'Database Access', 'database_access' ),
				$menutitle,
				'unfiltered_html',
				plugin_basename( __FILE__ ),
				array( $this, 'on_show_page' )
			);

			add_action( 'load-' . $this->pagehook, array( $this, 'on_load_page' ) );
		}
	}

	public function add_wp_admin_bar_item( $wp_admin_bar ) {

		if ( is_super_admin() ) {
			$args = array(
				'parent'    => 'network-admin',
				'secondary' => false,
				'id'        => 'network-adminer',
				'title'     => __( 'Adminer' ),
				'href'      => network_admin_url( 'settings.php?page=adminer/adminer.php' ),
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	public function contextual_help( $contextual_help, $screen_id, $screen ) {

		if ( 'tools_page_adminer/adminer' !== $screen_id ) {
			return false;
		}

		$contextual_help  = '<p>';
		$contextual_help .= __( 'Start the Thickbox inside the Adminer-tool with the button &rsaquo;<em>Start Adminer inside</em>&lsaquo;.', 'adminer' );
		$contextual_help .= '<br />';
		$contextual_help .= __( 'Alternatively, you can use the button for use &rsaquo;<em>Adminer in a new Tab</em>&lsaquo;.', 'adminer' );
		$contextual_help .= '</p>' . "\n";
		$contextual_help .= '<p>' . __( '<a href="http://wordpress.org/extend/plugins/adminer/">Documentation on Plugin Directory</a>', 'adminer' );
		$contextual_help .= ' &middot; ' . __( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=6069955">Donate</a>', 'adminer' );
		$contextual_help .= ' &middot; ' . __( '<a href="http://bueltge.de/">Blog of Plugin author</a>', 'adminer' );
		$contextual_help .= ' &middot; ' . __( '<a href="http://www.adminer.org/">Adminer website</a></p>', 'adminer' );

		return $contextual_help;
	}

	/**
	 * Strip slashes for different var
	 *
	 * @param   array|string $value optional
	 * @return  array|null   $value
	 */
	static function gpc_strip_slashes( $value = null ) {

		// crazy check, WP change the rules and also Adminer core
		// result; we must check wrong to the php doc
		if ( ! get_magic_quotes_gpc() ) {

			if ( null !== $value ) {
				$value = self::array_map_recursive( 'stripslashes_deep', $value );
			}

			// stripslashes_deep or stripslashes
			$_REQUEST = self::array_map_recursive( 'stripslashes_deep', $_REQUEST );
			$_GET     = self::array_map_recursive( 'stripslashes_deep', $_GET );
			$_POST    = self::array_map_recursive( 'stripslashes_deep', $_POST );
			$_COOKIE  = self::array_map_recursive( 'stripslashes_deep', $_COOKIE );
		}

		return $value;
	}

	/**
	 * Deeper array_map()
	 *
	 * @param   string        $callback Callback function to map
	 * @param   array, string $value Array to map
	 * @see     http://www.sitepoint.com/blogs/2005/03/02/magic-quotes-headaches/
	 * @return  array, string
	 */
	static function array_map_recursive( $callback, $values ) {

		$r = null;
		if ( is_string( $values ) ) {
			$r = $callback( $values );
		} elseif ( is_array( $values ) ) {
			$r = array();

			foreach ( $values as $k => $v ) {
				$r[ $k ] = is_scalar( $v )
					? $callback( $v )
					: self::array_map_recursive( $callback, $v );
			}
		}

		return $r;
	}

	/**
	 * Return page content for start Adminer
	 *
	 * @return   void
	 */
	public function on_show_page() {

		if ( '' == DB_USER ) {
			$db_user = __( 'empty', 'adminer' );
		} else {
			$db_user = DB_USER;
		}

		if ( '' == DB_PASSWORD ) {
			$db_password = __( 'empty', 'adminer' );
		} else {
			$db_password = DB_PASSWORD;
		}
		?>
		<div class="wrap">
			
		
			<p>&nbsp;</p>

			<noscript>
				<iframe src="inc/adminer/loader.php?username=<?php echo DB_USER; ?>" width="100%" height="600" name="adminer">
					<?php _e( 'Your browser does not support embedded frames.', 'adminer' ); ?>
				</iframe>
			</noscript>

			<div class="metabox-holder has-right-sidebar">

				<div id="post-body">
					<div id="post-body-content">

						<div class="postbox">
							<h3 class="tableMainheading"><span><?php _e( 'Database Details', 'adminer' ); ?></span></h3>
							<div class="inside">

								<table class="widefat post fixed">
									<thead class="tableHead">
										<tr>
											<th><?php _e( 'Typ', 'adminer' ); ?></th>
											<th><?php _e( 'Value', 'adminer' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<tr valign="top" class="alternate">
											<th scope="row"><?php _e( 'Server', 'adminer' ); ?></th>
											<td><code><?php echo DB_HOST; ?></code></td>
										</tr>
										<tr valign="top">
											<th scope="row"><?php _e( 'Database', 'adminer' ); ?></th>
											<td><code><?php echo DB_NAME; ?></code></td>
										</tr>
										<tr valign="top" class="alternate">
											<th scope="row"><?php _e( 'User', 'adminer' ); ?></th>
											<td><code><?php echo $db_user; ?></code></td>
										</tr>
										<tr valign="top" class="password">
											<th scope="row"><label for="dbpassword"><?php _e( 'Password', 'adminer' ); ?></label></th>
											<td><input  value="<?php echo $db_password; ?>" id="dbpassword" type="password" readonly></td>
										</tr>
									</tbody>
								</table>
							  <p>
				<script type="text/javascript">
				<!--
					var viewportwidth,
						viewportheight;

					if (typeof window.innerWidth != 'undefined' ) {
						viewportwidth = window.innerWidth-80;
						viewportheight = window.innerHeight-100
					} else if (typeof document.documentElement != 'undefined'
						&& typeof document.documentElement.clientWidth !=
						'undefined' && document.documentElement.clientWidth != 0)
					{
						viewportwidth = document.documentElement.clientWidth;
						viewportheight = document.documentElement.clientHeight
					} else { // older versions of IE
						viewportwidth = document.getElementsByTagName('body' )[0].clientWidth;
						viewportheight = document.getElementsByTagName('body' )[0].clientHeight
					}
					//document.write('<p class="textright">Your viewport width is '+viewportwidth+'x'+viewportheight+'</p>' );
					document.write('<div class="phpmyadminButton"><a onclick="return false;"  href="<?php echo WP_PLUGIN_URL . '/' . dirname( plugin_basename( __FILE__ ) ); ?>/inc/adminer/loader.php?username=<?php echo DB_USER . '&amp;db=' . DB_NAME; ?>&amp;?KeepThis=true&amp;TB_iframe=true&amp;height=' + viewportheight + '&amp;width=' + viewportwidth + '" class="thickbox button innerButton"><?php _e( 'Open Database', 'wp_database_access' ); ?></a></div>' );

					// hide and readable password
					function clear_password() {

						document.getElementById( "dbpassword" ).setAttribute( 'type', 'text' );
					}
					function hide_password() {

						document.getElementById( "dbpassword" ).setAttribute( 'type', 'password' );
					}
					//-->
				</script>
			
			</p>
							</div> <!-- .inside -->
						</div> <!-- .postbox -->

					</div> <!-- #post-body-content -->
				</div> <!-- #post-body -->

			</div> <!-- .metabox-holder -->

		</div>
		<?php
	}

	/**
	 * return plugin comment data
	 *
	 * @uses   get_plugin_data
	 * @access public
	 * @since  1.1.0
	 * @param  $value string, default = 'TextDomain'
	 *         Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
	 * @return string
	 */
	private static function get_plugin_data( $value = 'TextDomain' ) {

		static $plugin_data = array();

		// fetch the data just once.
		if ( isset( $plugin_data[ $value ] ) ) {
			return $plugin_data[ $value ];
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( __FILE__ );

		return empty( $plugin_data[ $value ] ) ? '' : $plugin_data[ $value ];
	}

} // end class
