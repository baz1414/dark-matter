<?php
defined( 'ABSPATH' ) || die;

/**
 * Only cache requests which are GET or HEAD.
 */
if ( ! empty( $_SERVER['REQUEST_METHOD'] ) && ! in_array( $_SERVER['REQUEST_METHOD'], array( 'GET', 'HEAD' ) ) ) {
    return;
}

class DM_Advanced_Cache {
    /**
     * Determines the appropriate logic for response that WordPress has provided.
     *
     * @var string Type of request; `page`, `redirect`, `error`, `notfound`, and `unknown` are valid values.
     */
    private $response_type = 'page';

    /**
     * Stores the Status Code in state for use through Advanced Cache.
     *
     * @var int HTTP Status Code for the current request.
     */
    private $status_code = -1;

    /**
     * Constructor
     */
    public function __construct() {
        ob_start( array( $this, 'cache_output' ) );

        add_filter( 'status_header', array( $this, 'status_header' ), 10, 2 );
    }

    /**
     * Handle the output caching for the request. This is done by utilising the
     * output buffering feature of PHP.
     *
     * @param  string $output HTML as generated by WordPress.
     * @return string         HTML, either from Cache or by WordPress.
     */
    public function cache_output( $output = '' ) {
        $debug = '';

        if ( ! $this->do_cache() ) {
            return $output;
        }

        if ( false !== strpos( $output, '<head' ) ) {
            $debug = <<<HTML
<!--
________  ________  ________  ___  __            _____ ______   ________  _________  _________  _______   ________
|\   ___ \|\   __  \|\   __  \|\  \|\  \         |\   _ \  _   \|\   __  \|\___   ___\\___   ___\\  ___ \ |\   __  \
\ \  \_|\ \ \  \|\  \ \  \|\  \ \  \/  /|_       \ \  \\\__\ \  \ \  \|\  \|___ \  \_\|___ \  \_\ \   __/|\ \  \|\  \
 \ \  \ \\ \ \   __  \ \   _  _\ \   ___  \       \ \  \\|__| \  \ \   __  \   \ \  \     \ \  \ \ \  \_|/_\ \   _  _\
  \ \  \_\\ \ \  \ \  \ \  \\  \\ \  \\ \  \       \ \  \    \ \  \ \  \ \  \   \ \  \     \ \  \ \ \  \_|\ \ \  \\  \|
   \ \_______\ \__\ \__\ \__\\ _\\ \__\\ \__\       \ \__\    \ \__\ \__\ \__\   \ \__\     \ \__\ \ \_______\ \__\\ _\
    \|_______|\|__|\|__|\|__|\|__|\|__| \|__|        \|__|     \|__|\|__|\|__|    \|__|      \|__|  \|_______|\|__|\|__|
-->
HTML;
        }

        return $output . $debug;
    }

    /**
     * Determine if the current response should be cached.
     *
     * @return boolean Return true if the current response should be cached. False if it should not.
     */
    public function do_cache() {
        $cache = true;

        if ( 5 === ( $this->status_code / 100 ) ) {
            $cache = false;
        }

        return $cache;
    }

    /**
     * Retrieve the Status Code of the current request.
     *
     * @param  string  $header The header as generated by WordPress.
     * @param  integer $code   Status - i.e. 200 / 404 / 500 - which corresponds to the current request.
     * @return string          WordPress generated header string, returned unchanged.
     */
    public function status_header( $header = '', $code = 0 ) {
        $this->status_code = absint( $code );

        /**
         * Set the response type property based on the status code. This will be used later for determining the best way
         * for Dark Matter to respond.
         */
        if ( 200 === $this->status_code ) {
            $this->response_type = 'page';
        } elseif ( 404 === $this->status_code ) {
            $this->response_type = 'notfound';
        } elseif ( in_array( $this->status_code, [ 301, 302, 303, 307 ], true ) ) {
            $this->response_type = 'redirect';
        } elseif ( 5 === intval( $this->status_code / 100 ) ) {
            $this->response_type = 'error';
        }

        return $header;
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

DM_Advanced_Cache::instance();