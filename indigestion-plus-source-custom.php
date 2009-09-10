<?php

/*
Name:           Custom Feed for Indigestion+
Description:    Generic module for Indigestion+ 
Version:        0.5
Author:         Luís Rodrigues
Author URI:     http://goblindegook.net
*/

require_once('indigestion-plus-super.php');
                                                                           
class IPlusCustom extends IPlusSuper {
    
    function __construct ( $name = null, $prefix = null ) {
        parent::__construct( $name, $prefix );
        
        if (empty( $name ))
            $this->name         = 'Custom Feed';
            
        if (empty( $prefix ))
            $this->prefix       = 'custom';
        
        $this->defaults         = array(
            'name'              => '',
            'class'             => '',
            'tag'               => '',
            'feed_url'          => '',
            'import_tags'       => 0,
        );
    }
    
    function set_defaults () {
        parent::set_defaults();
    }
    
    function get_feed_url () {
        $url = $this->wp_option( 'feed_url' );
        return (empty( $url )) ? false : $url;
    }

    function get_name () {
        return ($this->wp_option( 'name' ))
            ? $this->wp_option( 'name' )
            : $this->name;
    }

    function get_class () {
        return ($this->wp_option( 'class' ))
            ? $this->wp_option( 'class' )
            : $this->class;
    }

    function get_tag () {
        return ($this->wp_option( 'tag' ))
            ? $this->wp_option( 'tag' )
            : $this->tag;
    }

    function print_options () {    
    ?>
    <table class="form-table">
    <?php
        $this->print_options_input( 'Custom Feed Name', 'name', '', '', 'Display name for your feed', 30 );
        $this->print_options_input( 'Custom Feed URL', 'feed_url', '', '', '', 30 );
        $this->print_options_input( 'Custom Feed Class', 'class', '', '', 'HTML property value', 30 );
        $this->print_options_input( 'Custom Feed Tag', 'tag', '', '', 'Digest post tag', 30 );
    ?>
    </table>
    
    <table class="form-table">
    <?php
        $this->print_options_checkbox( 'Attempt To Use Custom Feed Tags In Post', 'import_tags' );
    ?>
    </table>
    <?php
    }

}

?>
