<?php
/**
 * Parser for sprunkia.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Parser_Sprunkia implements Webgames_Scraper_Parser_Interface {
    
    private $dom;
    private $xpath;
    private $html;

    public function set_dom( DOMDocument $dom, DOMXPath $xpath, $html ) {
        $this->dom   = $dom;
        $this->xpath = $xpath;
        $this->html  = $html;
    }

    public function get_title() {
        $nodes = $this->xpath->query('//meta[@property="og:title"]/@content');
        if ( $nodes->length > 0 ) {
            $title = $nodes->item(0)->nodeValue;
            // Optionally strip out " | Play Here"
            $title = str_replace(' | Play Here', '', $title);
            return trim($title);
        }
        return '';
    }

    public function get_description() {
        $description = '';
        $entry_content = $this->xpath->query('//div[contains(@class, "entry-content")]');
        if ( $entry_content->length > 0 ) {
            $node = $entry_content->item(0);
            $children = $node->childNodes;
            $has_blocks = false;
            
            // First pass: try to find only wp-block- elements as requested
            foreach ( $children as $child ) {
                if ( $child instanceof DOMElement ) {
                    $class = $child->getAttribute('class');
                    if ( strpos($class, 'wp-block-') !== false ) {
                        $description .= $this->dom->saveHTML($child);
                        $has_blocks = true;
                    }
                }
            }

            // Fallback: If no wp-block- found, grab everything except game container & sidebar
            if ( ! $has_blocks ) {
                $description = '';
                foreach ( $children as $child ) {
                    if ( $child instanceof DOMElement ) {
                        $class = $child->getAttribute('class');
                        if ( 
                            strpos($class, 'sprunki-game-container') === false && 
                            strpos($class, 'sprunki-social-share') === false && 
                            strpos($class, 'game-grid') === false
                        ) {
                            $description .= $this->dom->saveHTML($child);
                        }
                    } else if ( $child instanceof DOMText ) {
                        $description .= $this->dom->saveHTML($child);
                    }
                }
            }
        }

        if ( ! empty( trim( $description ) ) ) {
            return trim( $description );
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
        // extract from <button class="start-button" onclick="startSprunkiGame(..., 'URL')">
        $buttons = $this->xpath->query('//button[contains(@class, "start-button") and contains(@onclick, "startSprunkiGame")]');
        if ( $buttons->length > 0 ) {
            $onclick = $buttons->item(0)->getAttribute('onclick');
            // Match URL in single or double quotes
            if ( preg_match('/startSprunkiGame\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/', $onclick, $matches) ) {
                return $matches[1];
            }
        }
        
        // Fallback to standard iframe
        $iframes = $this->xpath->query('//iframe/@src');
        foreach ( $iframes as $node ) {
            $src = $node->nodeValue;
            if ( strpos( $src, 'about:blank' ) === false ) {
                return $src;
            }
        }
        return '';
    }
}
