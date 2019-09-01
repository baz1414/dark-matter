<?php
defined( 'ABSPATH' ) || die;

class DM_Request_Cache {
    /**
     * @var string Cache Key.
     */
    private $key = '';

    /**
     * @var string Request URL.
     */
    private $url = '';

    /**
     * @var string Cache Key for the URL - minus any variants - of the Request.
     */
    private $url_cache_key = '';

    /**
     * @var string Key distinguishing the variant.
     */
    private $variant_key = '';

    /**
     * DM_Request_Cache constructor.
     *
     * @param string $url URL to retrieve the Request Cache Entry.
     */
    public function __construct( $url = '' ) {
        $this->set_url_and_key();

        $this->set_variant_key();
    }

    /**
     * Delete the Request Cache Entry.
     */
    public function delete() {

    }

    /**
     * Retrieve the Request Cache Entry - if available - and return it.
     */
    public function get() {

    }

    /**
     * Returns the cache key for storing the request.
     *
     * @return string Cache Key, formatted using the MD5 of the base URL and the MD5 of the variant (if there is one).
     */
    public function get_key() {
        /**
         * Check to see if we have already generated the Key.
         */
        if ( ! empty( $this->key ) ) {
            return $this->key;
        }

        $this->key = $this->url_cache_key;

        /**
         * Append the Variant Key if there is one.
         */
        if ( ! empty( $this->variant_key ) ) {
            $this->key .= '-' . $this->variant_key;
        }

        return $this->key;
    }

    /**
     * Store the generate HTML in cache.
     *
     * @param string $output HTML to be added to the Request Cache entry.
     */
    public function set( $output = '' ) {

    }

    /**
     * Generates a URL Key for the Request. This can be used to retrieve
     *
     * @return string MD5 hash key.
     */
    private function set_url_and_key() {
        $host = rtrim( trim( $_SERVER['HTTP_HOST'] ), '/' );
        $path = trim( strtok( $_SERVER['REQUEST_URI'], '?' ) );

        $this->url           = $host . '/' . $path;
        $this->url_cache_key = md5( $this->url );
    }

    /**
     * Allows third parties to determine if the request should be treated differently from the standard caching logic.
     *
     * @return string MD5 hash key for the Variant.
     */
    private function set_variant_key() {
        $variant = apply_filters( 'dark_matter_request_variant', '', $this->url, $this->url_cache_key );

        $this->variant_key = md5( strval( $variant ) );
    }
}