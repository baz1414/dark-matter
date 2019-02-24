<?php
/**
 * There are three parts to the Domain Mapping single-sign on;
 *
 * 1) the JavaScript include on the header for each blog accessed through domain
 *    mapping.
 * 2) Create an authentication token and then redirect back to the mapped
 *    domain.
 * 3) Validate the token and then login the user with that token.
 *
 * The authentication token has to be created on the WordPress Network domian
 * and **NOT** the mapped domain. This is because the process uses Third Party
 * cookies and therefore the user is logged in on the WordPress Network domain.
 * Therefore an authentication token can only be generated by the WordPress
 * Network domain.
 *
 * The token is then provided in a URL request to the mapped domain blog and
 * then the token is used to create an session cookie to login the user.
 */
defined( 'ABSPATH' ) or die();

class DM_SSO_Cookie {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_post_dark_matter_dmsso', array( $this, 'login_token' ) );
        add_action( 'admin_post_nopriv_dark_matter_dmcheck', array( $this, 'logout_token' ) );
        add_action( 'wp_head', array( $this, 'head_script' ) );
        add_action( 'plugins_loaded', array( $this, 'validate_token' ) );
    }

    /**
     * Create the JavaScript output include for logging a user in to the admin
     * when on the Mapped domain. This is ultimately what makes the Admin Bar
     * appear.
     *
     * @return void
     */
    public function login_token() {
        header( 'Content-Type: text/javascript' );

        /**
         * Ensure that the JavaScript is never empty.
         */
        echo "// dm_sso" . PHP_EOL;

        if ( is_user_logged_in() ) {
            /**
             * Construct an authentication token which is passed back along with an
             * action flag to tell the front end to
             */
            $url = add_query_arg( array(
                '__dm_action' => 'authorise',
                'auth' => wp_generate_auth_cookie( get_current_user_id(), time() + ( 2 * MINUTE_IN_SECONDS ) )
            ), $_SERVER['HTTP_REFERER'] );

            printf( 'window.location.replace( "%1$s" );', esc_url_raw( $url ) );
        }

        die();
    }

    /**
     * Create the JavaScript output for handling the logout functionality.
     * Without this, users can end up in a state where they are logged out of
     * the Admin domain but remain perpetually logged in to the Mapped domains.
     *
     * @return void
     */
    public function logout_token() {
        header( 'Content-Type: text/javascript' );

        /**
         * Ensure that the JavaScript is never empty.
         */
        echo "// dm_sso" . PHP_EOL;

        if ( false === is_user_logged_in() ) {
            $url = add_query_arg( array(
                '__dm_action' => 'logout'
            ), $_SERVER['HTTP_REFERER'] );
            printf( 'window.location.replace( "%1$s" );', esc_url_raw( $url ) );
        }

        die();
    }

    /**
     * Adds the <script> tag which references the admin action(s) for handling
     * cross-domain login and logout.
     *
     * @return void
     */
    public function head_script() {
        if ( is_main_site() ) {
            return;
        }

        /**
         * Determine if the mapped domain is HTTPS and if so, ensure that the
         * Admin domain is also HTTPS. If it isn't, then we cannot do the third
         * party cookie authentication due to the differing protocols.
         */
        if ( is_ssl() && ( ! defined( 'FORCE_SSL_ADMIN' ) || ! FORCE_SSL_ADMIN ) ) {
            return;
        }

        $script_url = add_query_arg( [
            'action' => 'dark_matter_' . ( false === is_user_logged_in() ? 'dmsso' : 'dmcheck' )
        ], network_site_url( '/wp-admin/admin-post.php' ) );

        /**
         * Check to see if the user is logged in to the current website on the mapped
         * domain. We then check the setting "Allow Logins?" to see if it is enabled.
         * In this scenario, the administrator has decided to let users log in only to
         * the map domain in some scenarios; likely utilising a Membership-like or
         * WooCommerce style plugin.
         */
        if ( is_user_logged_in() && 'yes' === get_option( 'dark_matter_allow_logins' , 'no' ) ) {
            $user = wp_get_current_user();

            /**
             * Finally we check the user role to see if the user can edit content and
             * apply the default functionality for Contributor's and above. The logic
             * is like this because any one with Administrative or Content curation
             * ability will have access to the /wp-admin/ area which is on the admin
             * domain. Therefore ... users will need to login through the admin first.
             */
            if ( is_a( $user, 'WP_User' ) && false === current_user_can( 'edit_posts' ) ) {
                return;
            }
        }
        ?><script type="text/javascript" src="<?php echo( esc_url( $script_url ) ); ?>"></script><?php
    }

    /**
     * Handle the validation of the login token and logging in of a user. Also
     * handle the logout if that action is provided.
     *
     * @return void
     */
    public function validate_token() {
        /**
         * First check to see if the authorise action is provided in the URL.
         */
        if ( 'authorise' === filter_input( INPUT_GET, '__dm_action' ) ) {
            /**
             * Validate the token provided in the URL.
             */
            $user_id = wp_validate_auth_cookie( filter_input( INPUT_GET, 'auth' ), 'auth' );

            /**
             * Check if the validate token worked and we have a User ID. It will
             * display an error message or login the User if all works out well.
             */
            if ( false === $user_id ) {
                wp_die( 'Oops! Something went wrong with logging in.' );
            }
            else {
                /**
                 * Create the Login session cookie and redirect the user to the
                 * current page with the URL querystrings for Domain Mapping SSO
                 * removed.
                 */
                wp_set_auth_cookie( $user_id );
                wp_redirect( esc_url( remove_query_arg( array( '__dm_action', 'auth' ) ) ) );
                die();
            }
        }
        else if ( 'logout' === filter_input( INPUT_GET, '__dm_action' ) ) {
            wp_logout();
            wp_redirect( esc_url( remove_query_arg( array( '__dm_action' ) ) ) );

            die();
        }
    }

    /**
     * Return the Singleton Instance of the class.
     *
     * @return void
     */
    public static function instance() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }
}

DM_SSO_Cookie::instance();