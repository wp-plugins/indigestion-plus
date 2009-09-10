<?php

/*
Name:           Google Reader for Indigestion+
Description:    Google Reader module for Indigestion+ 
Version:        0.5
Author:         Luís Rodrigues
Author URI:     http://goblindegook.net
*/

require_once('indigestion-plus-super.php');
                                                                           
class IPlusGReader extends IPlusSuper {
    
    var $namespace;
    
    function __construct ( $name = null, $prefix = null ) {
        parent::__construct( $name, $prefix );
        
        if (empty( $name ))
            $this->name         = 'Google Reader';
            
        if (empty( $prefix ))
            $this->prefix       = 'greader';
            
        $this->class            = 'google-reader';
        $this->tag              = 'google reader';
        $this->namespace        = 'http://www.google.com/schemas/reader/atom/';
        
        $this->defaults         = array(
            'userid'            => '',
            'username'          => '',
            'import_tags'       => 0,
            'import_notes'      => 0,
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
        $userid = $this->wp_option( 'userid' );
        if (empty( $userid )) {
            $url = false;
        } else {
            $url = "http://www.google.com/reader/public/atom/user/"
                 . $userid
                 . "/state/com.google/broadcast";
        }
        return $url;
    }

    function get_digest_html ($data) {
        // Set permalink for Google Reader feed homepage:
        $username = $this->wp_option( 'username' );
        $this->permalink = 'http://www.google.com/reader/shared/' . $username;
        // Proceed as usual...
        return parent::get_digest_html( $data );
    }

    function get_item_html ($item) {
        $html = '<a href="';
        $html .= $item->get_link();
        $html .= '" title="';
        $html .= $item->get_title();
        $html .= '">';
        $html .= $item->get_title();
        $html .= '</a>';
        
        if ($this->wp_option( 'import_notes' ) && $item->get_item_tags($this->namespace, 'annotation')) {
            $annotation_tag = $item->get_item_tags($this->namespace, 'annotation');
            $annotation = $annotation_tag[0]['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['content'][0]['data'];
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
        $this->print_options_input( 'User ID', 'userid', '', '', 'Your 20-digit user ID', 30, 20 );
        $this->print_options_input( 'User Name', 'username', '', '', '', 30 );
    ?>
    </table>
    
    <table class="form-table">
    <?php
        $this->print_options_checkbox( 'Include Annotations', 'import_notes' );
        $this->print_options_checkbox( 'Use Google Reader Tags In Post', 'import_tags' );
    ?>
    </table>
    <?php
    }

}

?>
