<?php
/**
 * Source Registry for Webgames Scrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Source_Registry {

    /**
     * Get all registered sources.
     *
     * @return array
     */
    public static function get_sources() {
        return array(
            'gamepix' => array(
                'label'        => 'GamePix',
                'domain'       => 'gamepix.com',
                'parser_class' => 'Webgames_Parser_Gamepix',
            ),
            'musicgames' => array(
                'label'        => 'musicgames.io',
                'domain'       => 'musicgames.io',
                'parser_class' => 'Webgames_Parser_Musicgames',
            ),
            'sprunkia' => array(
                'label'        => 'Sprunkia.com',
                'domain'       => 'sprunkia.com',
                'parser_class' => 'Webgames_Parser_Sprunkia',
            ),
            'generic' => array(
                'label'        => __( 'Auto Detect (Generic)', 'webgames-scrapper' ),
                'domain'       => '', // Accepts any domain
                'parser_class' => 'Webgames_Parser_Generic',
            ),
        );
    }
}
