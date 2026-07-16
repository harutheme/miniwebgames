<?php
/**
 * Parser for musicgames.io
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Parser_Musicgames implements Webgames_Scraper_Parser_Interface {
    
    private $dom;
    private $xpath;
    private $html;
    private $is_fallback = false;

    public function set_dom( DOMDocument $dom, DOMXPath $xpath, $html ) {
        $this->dom   = $dom;
        $this->xpath = $xpath;
        $this->html  = $html;
    }

    public function get_title() {
        $nodes = $this->xpath->query('//meta[@property="og:title"]/@content');
        return $nodes->length > 0 ? $nodes->item(0)->nodeValue : '';
    }

    public function get_description() {
        // Priority 1: Extract full HTML description from the game_content div
        $nodes = $this->xpath->query('//div[contains(@class, "game_content")]');
        if ( $nodes->length > 0 ) {
            $node = $nodes->item(0);
            $innerHTML = '';
            $children = $node->childNodes;
            foreach ($children as $child) {
                // Remove unwanted scripts or styles if necessary, but saveHTML is safe for block content
                $innerHTML .= $node->ownerDocument->saveHTML($child);
            }
            $innerHTML = trim($innerHTML);
            if ( ! empty( $innerHTML ) ) {
                return $innerHTML;
            }
        }

        // Priority 2: Fallback to og:description meta tag
        $nodes = $this->xpath->query('//meta[@property="og:description"]/@content');
        return $nodes->length > 0 ? $nodes->item(0)->nodeValue : '';
    }

    public function get_image_url() {
        $nodes = $this->xpath->query('//meta[@property="og:image"]/@content');
        return $nodes->length > 0 ? $nodes->item(0)->nodeValue : '';
    }

    public function get_iframe_url() {
        // musicgames.io hides iframe in button data-iframe attribute
        $nodes = $this->xpath->query('//button[@id="show-embed"]/@data-iframe');
        if ( $nodes->length > 0 ) {
            return $nodes->item(0)->nodeValue;
        }
        
        // Fallback to standard iframe
        $iframes = $this->xpath->query('//iframe/@src');
        foreach ( $iframes as $node ) {
            $src = $node->nodeValue;
            if ( strpos( $src, 'about:blank' ) === false ) {
                $this->is_fallback = true;
                return $src;
            }
        }
        return '';
    }

    public function is_iframe_fallback() {
        return $this->is_fallback;
    }
}
