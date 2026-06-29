<?php

/**
 * Format a number into K (thousands) or M (millions)
 * Example: 1500 -> 1.5K, 2000000 -> 2M
 */
if ( ! function_exists( 'webgames_format_number' ) ) {
    function webgames_format_number( $number ) {
        if ( ! is_numeric( $number ) ) return $number;
        $number = (int) $number;
        
        if ( $number >= 1000000 ) {
            return round( $number / 1000000, 1 ) . 'M';
        } elseif ( $number >= 1000 ) {
            return round( $number / 1000, 1 ) . 'K';
        }
        return $number;
    }
}
