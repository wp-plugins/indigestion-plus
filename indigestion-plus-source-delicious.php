<?php

/*
Name:           Delicious for Indigestion+
Description:    Delicious module for Indigestion+ 
Version:        0.5
Author:         Luís Rodrigues
Author URI:     http://goblindegook.net
*/

require_once('indigestion-plus-super.php');
                                                                           
class IPlusDelicious extends IPlusSuper {
    
    function __construct ( $name = null, $prefix = null ) {
        parent::__construct( $name, $prefix );

        if (empty( $name ))
            $this->name         = 'Delicious';
            
        if (empty( $prefix ))
            $this->prefix       = 'delicious';

        $this->class            = 'delicious';
        $this->tag              = 'delicious';
        $this->defaults         = array(
            'user'              => '',
            'limit'             => 15,
            'import_tags'       => 0,
            'import_comments'   => 0,
        );
    }
    
    function fetch_item_tags ( $item ) {
        $tags = array();
        if ($this->wp_option( 'import_tags' )) {
            if ($item->get_categories()) {
                foreach ($item->get_categories() as $category) {
                    $tags[] = $category->get_term(); 
		        }
	        }
        }
        return $tags;
    }
    
    function get_feed_url () {
        $user   = $this->wp_option( 'user' );
        $count  = $this->wp_option( 'limit' );
        if (empty( $user )) return false;
        $url = "http://feeds.delicious.com/v2/rss/$user";
        if (!empty( $count )) $url .= "?count=$count";
        return $url;
    }
    
    function get_item_html ($item) {
        $annotation = $item->get_description()
                    ? $item->get_description()
                    : '';
        
        $html = '<a href="';
        $html .= $item->get_link();
        $html .= '" title="';
        $html .= (!empty( $annotation )) ? $annotation : $item->get_title();
        $html .= '">';
        $html .= $item->get_title();
        $html .= '</a>';
        
        if ($this->wp_option( 'import_comments' ) && !empty( $annotation )) {
            $html .= ' <span class="annotation">';
            $html .= $annotation;
            $html .= '</span>';
        }
        
        return $html;
    }

    function print_options () {
    ?>
    <table class="form-table">
    
    <?php
        $this->print_options_input( 'User Name', 'user', '', '', '', 30 );
        $this->print_options_input( 'Limit', 'limit', '', 'newest items', '', 2, 2 );
    ?>
    </table>
    
    <table class="form-table">
    <?php
        $this->print_options_checkbox( 'Include Comments', 'import_comments' );
        $this->print_options_checkbox( 'Use Delicious Tags In Post', 'import_tags' );
    ?>
    </table>
    <?php
    }

}

?>
