<?php

class IPlusSuper {

    var $plugin_prefix          = 'indigestion_plus';   # Plugin options prefix
    
    var $name                   = '';                   # Feed options friendly name
    var $prefix                 = '';                   # Feed options prefix
    
    var $class                  = '';                   # Optional feed HTML class
    var $tag                    = '';                   # Optional feed post tag
    var $limit                  = '';                   # Optional feed item limit
    
    # Used internally, do not redefine:
    var $group                  = '';                   # Feed options group
    var $permalink              = '';                   # Feed permalink
    var $title                  = '';                   # Feed title
    var $new_items              = '';                   # New item count
    var $all_tags               = array();              # All feed tags
    
    var $defaults               = array();              # Default WP user options
    var $internal               = array();              # Internal WP options
    var $schedules              = array();              # Allowed WP schedules
    
    function __construct ( $name = null, $prefix = null ) {
        $this->internal         = array(
        	'last'              => 0,
        );
        $this->schedules        = wp_get_schedules();
        
        if (!empty( $name ))
            $this->name         = $name;
        if (!empty( $prefix ))
            $this->prefix       = $prefix;
    }
    
    function __destruct () {
    }
    
    function set_defaults () {
        foreach ($this->defaults as $option => $value) {
            add_option( $this->wp_option_name( $option ) , $value );
        }
        foreach ($this->internal as $option => $value) {
            add_option( $this->wp_option_name( $option ) , $value );
        }
    }
    
    function admin_init ($do_not_register = array()) {
        $this->group = $this->plugin_prefix . '_' . $this->prefix . '_settings';
        foreach (array_keys( $this->defaults ) as $option) {
            register_setting( $this->group, $this->wp_option_name( $option ) );
        }
    }

    function fetch_feed ( $preview = false ) {
        $results = array();
        $last    = $this->wp_option('last');
        
        if ($preview || empty( $last ) || !$last) {
            $last = time() - $this->schedules['daily']['interval'];
        }

        if ($uri = $this->get_feed_url()) {
            $data = fetch_feed( $uri );
            
            $this->permalink        = $data->get_permalink();
            $this->title            = $data->get_title();
            $this->all_tags         = array();
            
            foreach ($data->get_items() as $item) {
                if ($preview || empty($last) || $item->get_date('U') > $last) {
                    $results[] = $item;
                    $this->all_tags = array_merge(
                        $this->all_tags,
                        $this->fetch_item_tags( $item )
                    );
                }
            }
            if (!$preview)
                $this->wp_option( 'last', time() );
                
        } else {
            $this->wp_option( 'last', 0 );
            
        }
        
        $this->new_items = count( $results );
        
        return $results;
    }
    
    function get_name           () { return $this->name;                    }
    function get_prefix         () { return $this->prefix;                  }
    function get_class          () { return $this->class;                   }
    function get_tag            () { return $this->tag;                     }
    function get_group          () { return $this->group;                   }
    function get_permalink      () { return $this->permalink;               }
    function get_description    () { return $this->description;             }
    function get_all_tags       () { return $this->all_tags;                }
    
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
        // REDEFINE THIS METHOD AS NEEDED
        return false;
    }
    
    function get_replacements () {
        $feed_url = $this->get_feed_url();
        
        return array(
            '%class%'    => $this->get_class(),
            '%count%'    => $this->new_items,
            '%feedurl%'  => $feed_url,
            '%link%'     => $this->get_link_html(),
            '%name%'     => $this->get_name(),
            '%title%'    => $this->title,
            '%url%'      => $this->permalink,
        );
    }
    

    function string_replace ($input = '') {
        foreach ($this->get_replacements() as $search => $replace) {
            $input = str_replace( $search, $replace, $input );
        }
        return $input;
    }
    
    
    function get_item_separator () {
        return get_option( $this->plugin_prefix . '_item_separator' );
    }
    
    
    function get_link_html () {
        $output = '<a';
        $output .= ' href="'  . $this->permalink . '"';
        $output .= ' title="' . $this->title     . '"';
        $output .= '>' . $this->get_name() . '</a>';
        
        return $output;
    }


    function get_item_html ($item) {
        // REDEFINE THIS METHOD AS NEEDED
        $output = '<a ';
        $output .= 'href="'  . $item->get_link()  . '" ';
        $output .= 'title="' . $item->get_title() . '" ';
        $output .= '>';
        $output .= $item->get_title();
        $output .= '</a>';
        return $output;
    }


    function get_feed_more ( $remaining = 0) {
        if ( $remaining > 0 ) {
        $html = '<a href="'                 . $this->permalink
              . '" title="'                 . $remaining . ' more...'
              . '">'                        . 'and ' . $remaining . ' more...'
              . '</a>';
        }
        return $html;
    }


    function get_digest_html ($data) {

        if ($this->limit < 1 )
            $this->limit = $this->new_items;
        
        $remaining = $this->new_items - $this->limit;
        
        $output = '';
        
        if ($this->new_items > 0) {
            $output .= $this->string_replace(
                get_option( $this->plugin_prefix . '_feed_before' )
            );
            
            foreach( $data as $item ) {
                if ( $this->limit-- > 0 ) {
                    $item_html = $this->get_item_html( $item );
                    $output .= $item_html;
                    if (!empty( $item_html ) && $this->limit > 0 && next($data) !== false) {
                        $output .= $this->get_item_separator();
                    }
                }
            }
            
            if ($remaining) {
                $output .= $this->get_item_separator();
                $output .= $this->get_feed_more( $remaining );
            }
            
            $output .= $this->string_replace(
                get_option( $this->plugin_prefix . '_feed_after' )
            );
        }
        return $output;
    }


    function wp_option ($option, $new_value = null) {
        $wp_option = $this->wp_option_name( $option );
                
        return (isset($new_value))
            ? update_option( $wp_option, $new_value )
            : get_option( $wp_option )
            ;
    }


    function wp_form_option ($option) {
        return form_option( $this->wp_option_name( $option ) );
    }


    function wp_option_name( $option ) {
        return $this->plugin_prefix . '_' . $this->prefix . '_' . $option;
    }


    function print_options_header () {
        echo '<h3>' . $this->name . '</h3>';
    }


    function print_options_input (
        $label, $name,
        $before = '', $after = '', $description = '',
        $size = 20, $maxlength = 255
    ) {
        $option_name  = $this->wp_option_name( $name );
        $option_value = $this->wp_option($name);
    
        echo '<tr valign="top">';
        echo '<th scope="row">';
        echo '<label for="' . $option_name . '_option">' . $label . '</label>';
        echo '</th>';
        
        echo '<td>';
        echo (!empty($before))
            ? ' ' . $before
            : ''
            ;
            
        echo '<input type="text';
        echo '" name="'      . $option_name;
        echo '" id="'        . $option_name . '_option';
        echo '" value="';
        $this->wp_form_option($name);
        echo '" size="'      . $size;
        echo '" maxlength="' . $maxlength;
        echo '" />';
        
        echo (!empty($after))
            ? ' ' . $after
            : ''
            ;
        echo (!empty($description))
            ? ' <span class="description">' . $description . '</span>'
            : ''
            ;
    }
    
    
    function print_options_checkbox ( $label, $name ) {
        $option_name  = $this->wp_option_name( $name );
        $option_value = $this->wp_option($name);
    
        echo '<tr valign="top">';
        echo '<th scope="row" class="th-full">';
        echo '<input type="checkbox';
        echo '" name="'      . $option_name;
        echo '" id="'        . $option_name . '_option';
        echo '" ';
        echo ($option_value) ? ' checked="checked"' : "";
        echo '/>';
        echo ' <label for="' . $option_name . '_option">' . $label . '</label>';
        echo '</td></tr>';
    }

    
    function print_options_select (
        $type, $label, $name,
        $before = '', $after = '', $description = '',
        $options = array()
    ) {
        $option_name  = $this->wp_option_name( $name );
        $option_value = $this->wp_option($name);
    
        echo '<tr valign="top">';
        echo '<th scope="row">';
        echo '<label for="' . $option_name . '_option">' . $label . '</label>';
        echo '</th>';
        
        echo '<td>';
        echo (!empty($before))
            ? ' ' . $before
            : ''
            ;
            
        if ($type == 'users') {
            wp_dropdown_users( "name=$option_name&selected=$option_value" );
            
        } elseif ($type == 'categories') {
            wp_dropdown_categories( "name=$option_name&hide_empty=0&hierarchical=1&selected=$option_value" );
            
        } else {
            // CUSTOM SELECT FIELD
            
        }
        
        echo (!empty($after))
            ? ' ' . $after
            : ''
            ;
        echo (!empty($description))
            ? ' <span class="description">' . $description . '</span>'
            : ''
            ;
        echo '</td></tr>';
    }


    function print_options () {
        // REDEFINE THIS METHOD AS NEEDED
        echo "<p>No options for this feed.</p>";
        return;
    }

}

?>
