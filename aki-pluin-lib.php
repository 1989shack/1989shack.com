	exit;
}

define( 'AKISMET_VERSION', '5.6' );
define( 'AKISMET__MINIMUM_WP_VERSION', '5.8' );
define( 'AKISMET__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AKISMET_DELETE_LIMIT', 10000 );

register_activation_hook( __FILE__, array( 'Akismet', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Akismet', 'plugin_deactivation' ) );

require_once AKISMET__PLUGIN_DIR . 'class.akismet.php';
require_once AKISMET__PLUGIN_DIR . 'class.akismet-widget.php';
require_once AKISMET__PLUGIN_DIR . 'class.akismet-rest-api.php';
require_once AKISMET__PLUGIN_DIR . 'class-akismet-compatible-plugins.php';

add_action( 'init', array( 'Akismet', 'init' ) );

add_action( 'rest_api_init', array( 'Akismet_REST_API', 'init' ) );

add_action( 'init', array( 'Akismet_Compatible_Plugins', 'init' ) );

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once AKISMET__PLUGIN_DIR . 'class.akismet-admin.php';
	add_action( 'init', array( 'Akismet_Admin', 'init' ) );
}

// add wrapper class around deprecated akismet functions that are referenced elsewhere
require_once AKISMET__PLUGIN_DIR . 'wrapper.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once AKISMET__PLUGIN_DIR . 'class.akismet-cli.php';
}
