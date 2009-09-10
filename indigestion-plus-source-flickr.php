<?php

/*
Name:           Flickr for Indigestion+
Description:    Flickr module for Indigestion+ 
Version:        0.5
Author:         Luís Rodrigues
Author URI:     http://goblindegook.net
*/

require_once('indigestion-plus-super.php');
                                                                           
class IPlusFlickr extends IPlusSuper {
    
    var $namespace;
    
    function __construct ( $name = null, $prefix = null ) {
        parent::__construct( $name, $prefix );
        
        if (empty( $name ))
            $this->name         = 'Flickr';
            
        if (empty( $prefix ))
            $this->prefix       = 'flickr';
            
        $this->class            = 'flickr';
        $this->tag              = 'flickr';
        $this->namespace        = 'http://search.yahoo.com/mrss/';
        $this->limit            = 3;
        
        $this->defaults         = array(
            'userid'            => '',
            'limit'             => '3',
            'import_tags'       => 0,
        );
    }
    
    function fetch_item_tags ( $item ) {
        $tags = array();

        if ($this->wp_option( 'import_tags' )) {
            $category =  $item->get_category();
            if ($category) {
                $tags[] = $category->get_label();
            }
        }
        return $tags;
    }
    
    function get_feed_url () {
        $url = false;
        if ($userid = $this->wp_option( 'userid' )) {
            $url = "http://api.flickr.com/services/feeds/photos_public.gne?id=$userid&format=atom";
        }
        return $url;
    }

    function get_digest_html ($data) {

        $this->limit = $this->wp_option( 'limit' );

        // Proceed as usual...
        return parent::get_digest_html( $data );
    }

    function get_item_html ($item) {
        $content        = $item->get_content();
        $matches        = array();
        preg_match( '/src=\"(http[^\"]+)\"/', $content, &$matches );
        
        $html = '<a '
              . 'href'          . '="' . $item->get_link()  . '" '
              . 'title'         . '="' . $item->get_title() . '" '
              . 'class'         . '="' . 'thumbnail'        . '" '
              . '>';
              
        if (!empty( $matches[1] )) {
            $thumb_url  = str_replace( '_m.', '_t.', $matches[1]);
            $html       .= '<img '
                        . 'src'           . '="' . $thumb_url         . '" '
                        . 'title'         . '="' . $item->get_title() . '" '
                        . 'alt'           . '="' . $item->get_title() . '" '
                        . 'class'         . '="' . 'thumbnail'        . '" '
                        . '/>';
                        
        } else {
            $html .= $item->get_title();
            
        }
        
        $html .= '</a>';

        return $html;
    }

    function print_options () {
    ?>
    <table class="form-table">
    <?php
        $this->print_options_input( 'User ID', 'userid', '', '', 'In the form <code>00000000@N00</code>', 30 );
        $this->print_options_input( 'Limit', 'limit', '', 'newest items', '', 2, 2 );
    ?>
    </table>
    
    <table class="form-table">
    <?php
        $this->print_options_checkbox( 'Use Flickr Tags In Post', 'import_tags' );
    ?>
    </table>
    <?php
    }

}

?>
