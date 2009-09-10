<?php

/*
Name:           Picasa for Indigestion+
Description:    Picasa module for Indigestion+ 
Version:        0.5
Author:         Luís Rodrigues
Author URI:     http://goblindegook.net
*/

require_once('indigestion-plus-super.php');
                                                                           
class IPlusPicasa extends IPlusSuper {
    
    var $namespace;
    
    function __construct ( $name = null, $prefix = null ) {
        parent::__construct( $name, $prefix );
        
        if (empty( $name ))
            $this->name         = 'Picasa';
            
        if (empty( $prefix ))
            $this->prefix       = 'picasa';
            
        $this->class            = 'picasa';
        $this->tag              = 'picasa';
        $this->namespace        = 'http://search.yahoo.com/mrss/';
        $this->limit            = 3;
        
        $this->defaults         = array(
            'username'          => '',
            'limit'             => '3',
            'show_albums'       => 0,
            'import_tags'       => 0,
        );
    }
    
    function fetch_item_tags ( $item ) {
        $tags = array();
        
        if ($this->wp_option( 'import_tags' )) {
            $media          = $item->get_item_tags($this->namespace, 'group');
            $keywords       = $media[0]['child'][$this->namespace]['keywords'][0]['data'];
            foreach (explode(',', $keywords) as $keyword) {
                $tags[] = trim( $keyword );
	        }
        }
        return $tags;
    }
    
    function get_feed_url () {
        $username = $this->wp_option( 'username' );
        if (empty( $username )) {
            $url = false;
        } else {
            $url = "http://picasaweb.google.com/data/feed/base/user/$username?kind=";
            $url .= ($this->wp_option( 'show_albums' )) ? "album" : "photo";
        }
        return $url;
    }

    function get_digest_html ($data) {

        $this->limit = $this->wp_option( 'limit' );

        // Proceed as usual...
        return parent::get_digest_html( $data );
    }

    function get_item_html ($item) {
        $media          = $item->get_item_tags($this->namespace, 'group');
        $thumbnail      = $media[0]['child'][$this->namespace]['thumbnail'][0]['attribs'][''];
        
        $html = '<a '
              . 'href'          . '="' . $item->get_link()      . '" '
              . 'title'         . '="' . $item->get_title()     . '" '
              . 'class'         . '="' . 'thumbnail'            . '" '
              . '><img '
              . 'src'           . '="' . $thumbnail['url']      . '" '
              . 'height'        . '="' . $thumbnail['height']   . '" '
              . 'width'         . '="' . $thumbnail['width']    . '" '
              . 'title'         . '="' . $item->get_title()     . '" '
              . 'alt'           . '="' . $item->get_title()     . '" '
              . 'class'         . '="' . 'thumbnail'            . '" '
              . '/></a>';

        return $html;
    }

    function print_options () {
    ?>
    <table class="form-table">
    <?php
        $this->print_options_input( 'User Name', 'username', '', '', '', 30 );
        $this->print_options_input( 'Limit', 'limit', '', 'newest items', '', 2, 2 );
    ?>
    </table>
    
    <table class="form-table">
    <?php
        $this->print_options_checkbox( 'Show Recently Updated Albums', 'show_albums' );
        $this->print_options_checkbox( 'Use Picasa Tags In Post', 'import_tags' );
    ?>
    </table>
    <?php
    }

}

?>
