<?php
/**
 * Parser for sprunkin.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Parser_Sprunkin implements Webgames_Scraper_Parser_Interface {
    
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
        // Ưu tiên og:title vì thường chuẩn xác hơn
        $nodes = $this->xpath->query('//meta[@property="og:title"]/@content');
        if ( $nodes->length > 0 ) {
            $title = $nodes->item(0)->nodeValue;
            // Dọn dẹp các hậu tố phổ biến nếu có
            $title = preg_replace('/(\s*[-|]\s*Play.*|\s*[-|]\s*Sprunkin.*)/i', '', $title);
            return trim($title);
        }

        // Fallback về game-headline
        $nodes = $this->xpath->query('//*[contains(@class, "game-headline")]');
        if ( $nodes->length > 0 ) {
            return trim(preg_replace('/\s+/', ' ', $nodes->item(0)->nodeValue));
        }
        return '';
    }

    public function get_description() {
        $description = '';
        $entry_content = $this->xpath->query('//*[contains(@class, "game-description-inner")]');
        
        if ( $entry_content->length > 0 ) {
            $node = $entry_content->item(0);
            
            // Mảng các class cần loại bỏ
            $classes_to_remove = array('rating', 'breadcrumbs', 'game-headline');
            
            // Tìm và xóa các node này khỏi $node
            foreach ($classes_to_remove as $cls) {
                $removals = $this->xpath->query('.//*[contains(@class, "' . $cls . '")]', $node);
                foreach ($removals as $r) {
                    if ($r->parentNode) {
                        $r->parentNode->removeChild($r);
                    }
                }
            }
            
            // Lấy HTML của các thành phần còn lại
            foreach ( $node->childNodes as $child ) {
                $description .= $this->dom->saveHTML($child);
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
        // Find preloader image container
        $preloader_nodes = $this->xpath->query('//*[contains(@class, "game-preloader-image")]//img');
        
        if ( $preloader_nodes->length > 0 ) {
            $largest_area = 0;
            $best_src = '';

            foreach ( $preloader_nodes as $img ) {
                $src = $img->getAttribute('src');
                if ( empty( $src ) ) {
                    continue; // Skip if no src
                }

                $w = (int) $img->getAttribute('width');
                $h = (int) $img->getAttribute('height');
                $area = $w * $h;

                if ( $area > $largest_area ) {
                    $largest_area = $area;
                    $best_src = $src;
                }
            }
            
            if ( !empty($best_src) ) {
                return $best_src;
            }

            // Fallback to the first image if sizes are missing or 0
            $first_src = $preloader_nodes->item(0)->getAttribute('src');
            if ( !empty($first_src) ) {
                return $first_src;
            }
        }

        // Priority 2: Fallback to og:image meta tag
        $nodes = $this->xpath->query('//meta[@property="og:image"]/@content');
        return $nodes->length > 0 ? $nodes->item(0)->nodeValue : '';
    }

    public function get_iframe_url() {
        // extract from <iframe id="gameframe" data-src="...">
        $iframes = $this->xpath->query('//iframe[@id="gameframe"]');
        if ( $iframes->length > 0 ) {
            $data_src = $iframes->item(0)->getAttribute('data-src');
            if ( !empty($data_src) ) {
                return $data_src;
            }
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
