<?php
/**
 * Parser Interface for Webgames Scraper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Webgames_Scraper_Parser_Interface {
    /**
     * Set the DOM object to parse
     */
    public function set_dom( DOMDocument $dom, DOMXPath $xpath, $html );

    /**
     * Get the game title
     */
    public function get_title();

    /**
     * Get the game description
     */
    public function get_description();

    /**
     * Get the game cover image URL
     */
    public function get_image_url();

    /**
     * Get the game iframe/source URL
     */
    public function get_iframe_url();

    /**
     * Check if the returned iframe URL is from a fallback method
     */
    public function is_iframe_fallback();
}
