<?php
/**
 * Parser for gamepix.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Parser_Gamepix implements Webgames_Scraper_Parser_Interface {
    
    private $dom;
    private $xpath;
    private $html;
    private $is_fallback = false;

    public function set_dom( DOMDocument $dom, DOMXPath $xpath, $html ) {
        $this->dom   = $dom;
        $this->xpath = $xpath;
        $this->html  = $html;
    }

    private function is_valid_game_page() {
        // Validation: Must have the game-iframe-item or play-button to be considered a game page
        $game_iframe_item = $this->xpath->query('//div[contains(@class, "game-iframe-item")]');
        $play_button = $this->xpath->query('//button[@id="play-button"]');
        return ($game_iframe_item->length > 0 || $play_button->length > 0);
    }

    public function get_title() {
        if ( ! $this->is_valid_game_page() ) return '';

        // Try from DOM
        $nodes = $this->xpath->query('//div[contains(@class, "game-iframe-item")]//span[contains(@class, "title")]');
        if ( $nodes->length > 0 ) {
            $title = $nodes->item(0)->nodeValue;
            return trim( $title );
        }

        // Fallback to og:title
        $meta = $this->xpath->query('//meta[@property="og:title"]/@content');
        if ( $meta->length > 0 ) {
            $title = $meta->item(0)->nodeValue;
            // Remove common suffix
            $title = str_replace( array( '- Play Free Online | GamePix', ': Play Free Online - Cartoon Network Soccer Games | GamePix' ), '', $title );
            // General regex replacement to remove "- Play Free Online..." if needed
            $title = preg_replace('/[:-]\s*Play Free Online.*$/i', '', $title);
            return trim( $title );
        }

        return '';
    }

    public function get_description() {
        if ( ! $this->is_valid_game_page() ) return '';

        $description = '';
        $text_html_nodes = $this->xpath->query('//div[contains(@class, "text-description-html")]');
        
        if ( $text_html_nodes->length > 0 ) {
            $node = $text_html_nodes->item(0);
            $children = $node->childNodes;
            $passed_info = false;
            
            foreach ( $children as $child ) {
                if ( $child instanceof DOMElement ) {
                    $class = $child->getAttribute('class');
                    if ( strpos( $class, 'game-description-info' ) !== false ) {
                        $passed_info = true;
                        continue;
                    }
                }
                
                // Once we pass the info block, append the HTML content
                if ( $passed_info ) {
                    if ( $child instanceof DOMElement || $child instanceof DOMText ) {
                        $description .= $this->dom->saveHTML( $child );
                    }
                }
            }
        }

        // If the structure changed or the block was missing
        if ( empty( trim( $description ) ) && $text_html_nodes->length > 0 ) {
             // Just get everything inside text-description-html
             foreach ( $text_html_nodes->item(0)->childNodes as $child ) {
                 $description .= $this->dom->saveHTML( $child );
             }
        }

        if ( ! empty( trim( $description ) ) ) {
            return trim( $description );
        }

        // Fallback to og:description
        $nodes = $this->xpath->query('//meta[@property="og:description"]/@content');
        return $nodes->length > 0 ? trim( $nodes->item(0)->nodeValue ) : '';
    }

    public function get_image_url() {
        if ( ! $this->is_valid_game_page() ) return '';

        // Try from DOM
        $nodes = $this->xpath->query('//div[contains(@class, "game-iframe-item")]//img/@src');
        if ( $nodes->length > 0 ) {
            return trim( $nodes->item(0)->nodeValue );
        }

        // Fallback to og:image
        $meta = $this->xpath->query('//meta[@property="og:image"]/@content');
        if ( $meta->length > 0 ) {
            return trim( $meta->item(0)->nodeValue );
        }

        return '';
    }

    public function get_iframe_url() {
        if ( ! $this->is_valid_game_page() ) return '';

        // --- LAYER 0: If Play button was clicked, there is a real iframe ---
        $nodes = $this->xpath->query('//iframe[@id="game-iframe"]/@src | //div[contains(@class, "game-iframe-item")]//iframe/@src | //iframe[contains(@src, "games.gamepix.com")]/@src');
        if ( $nodes->length > 0 ) {
            $iframe_url = trim( $nodes->item(0)->nodeValue );
            if ( filter_var( $iframe_url, FILTER_VALIDATE_URL ) ) {
                return esc_url_raw( $iframe_url );
            }
        }

        // --- LAYER 1: Direct Extract from Svelte embedCode ---
        if ( preg_match( '/embedCode\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/i', $this->html, $matches ) ) {
            $embed_code = $matches[1];
            // Decode unicode escapes like \u003C
            $embed_code = json_decode('"' . $embed_code . '"');
            if ( $embed_code ) {
                if ( preg_match( '/src=["\']([^"\']+)["\']/i', $embed_code, $src_matches ) ) {
                    $iframe_url = $src_matches[1];
                    if ( filter_var( $iframe_url, FILTER_VALIDATE_URL ) ) {
                        return esc_url_raw( $iframe_url );
                    }
                }
            }
        }

        // --- LAYER 2: Fallback to /embed URL for exclusive games ---
        $nodes = $this->xpath->query('//meta[@property="og:url"]/@content');
        if ( $nodes->length > 0 ) {
            $og_url = $nodes->item(0)->nodeValue;
            $og_url = rtrim( $og_url, '/' );
            if ( preg_match( '/\/play\/([^\/]+)$/i', $og_url, $matches ) ) {
                $slug = $matches[1];
                $this->is_fallback = true;
                return 'https://play.gamepix.com/' . $slug . '/embed';
            }
        }

        return '';
    }

    public function is_iframe_fallback() {
        return $this->is_fallback;
    }

    public function get_custom_meta() {
        if ( ! $this->is_valid_game_page() ) return null;

        $meta = array();
        
        $dl_nodes = $this->xpath->query('//dl[contains(@class, "game-summary")]');
        if ( $dl_nodes->length > 0 ) {
            $dl = $dl_nodes->item(0);
            
            $dts = $this->xpath->query('.//dt', $dl);
            $dds = $this->xpath->query('.//dd', $dl);
            
            if ( $dts->length === $dds->length ) {
                for ( $i = 0; $i < $dts->length; $i++ ) {
                    $key = trim( $dts->item($i)->nodeValue );
                    $key = rtrim( $key, ':' ); // Remove colon if present
                    
                    if ( stripos( $key, 'Rating' ) !== false ) {
                        continue;
                    }
                    
                    $val = trim( $dds->item($i)->nodeValue );
                    $val = preg_replace('/\s+/', ' ', $val);
                    
                    if ( ! empty( $key ) && ! empty( $val ) ) {
                        $meta[$key] = $val;
                    }
                }
            }
        }
        
        return ! empty( $meta ) ? $meta : null;
    }
}
