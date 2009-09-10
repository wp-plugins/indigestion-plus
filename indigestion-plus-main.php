<?php

require_once('indigestion-plus-super.php');

class IPlusMain extends IPlusSuper {
    
    var $feeds;
    
    function __construct () {
        parent::__construct();

        $this->class_loader();
        
        // Instantiate duplicate services below.  For example:
        // $this->feeds[]       = new IPlusDelicious( 'Second Delicious Account', 'delicious-2' );

        $this->name             = 'Main';
        $this->prefix           = 'main';
        $this->class            = '';
                                // Attn: update_option() trims whitespace     
        $this->defaults         = array(
        	'post_title'        => 'Daily link digest',
        	'post_author'       => 1, // admin
        	'post_category'     => get_option( 'default_category' ),
        	'post_tags'         => 'links',
        	'post_before'       => '<p>New items for %date%:</p><ul>',
        	'post_after'        => '</ul>',        
            'feed_tags'         => 1,
            'feed_before'       => '<li class="%class%"><strong>%link%</strong>:&nbsp;',
            'feed_after'        => '</li>',
            'item_separator'    => ',&nbsp;',
            //'feed_before'       => '<li class="%class%"><strong>%link%</strong>:<ul class="%class%"><li class="first">',
            //'feed_after'        => '</li></ul></li>',
            //'item_separator'    => '</li><li>',
        	'fetch_time'        => '00:05',
        );
        
        $this->internal['recurrence'] = 'daily';

        ksort( $this->feeds );

    }
     
    function class_loader () {
        $this->feeds    = array();
        $dirname        = dirname( __FILE__ );
        
        if ($handle = opendir( $dirname )) {
            while (false !== ($file = readdir( $handle ))) {
                if (preg_match( '/^indigestion-plus-source-.+\.php$/', $file )) {
                    $contents = implode( file( $dirname . '/' . $file ) );
                    $match = array();
                    preg_match( '/class\s+(\w+)\s+extends\s+IPlusSuper/', $contents, &$match );
                    if (!empty( $match[1] )) {
                        $class = $match[1];
                        eval("\$this->feeds\[\$class\] = new \$class;");
                    }
                }
            }
            closedir( $handle );
        }
    }
     
    function activate () {
        $this->set_defaults();
        $this->refresh_schedule();
    }

    function deactivate () {
    	remove_action( 'indigestion_plus_run', array( &$this, 'run' ) );
	    wp_clear_scheduled_hook( 'indigestion_plus_run' );
	    delete_option( $this->wp_option_name( 'last' ) );
    }
        
    function refresh_schedule () {
	    wp_clear_scheduled_hook( 'indigestion_plus_run' );
	    $fetch_time = explode(':',  $this->wp_option( 'fetch_time' ));
	    wp_schedule_event( mktime( $fetch_time[0], $fetch_time[1] ), 'daily', 'indigestion_plus_run' );
    }
    
    function set_defaults () {
        parent::set_defaults();
        // Set defaults for each feed:
        foreach ($this->feeds as $feed) {
            $feed->set_defaults();
        }
    }
    
    function admin_init () {
        parent::admin_init();
        // Register options for each feed:
        foreach ($this->feeds as $feed) {
            $feed->admin_init();
        }
    }
    
    function admin_menu () {
        add_options_page('Indigestion+ Settings', 'Indigestion+', 8, 'indigestion_plus', array(&$this, 'print_options') );
    }

    function get_post_replacements () {
        $time       = time();
        $time_fmt   = get_option('time_format');
        $date_fmt   = get_option('date_format');
        
        return array(
            '%date%'    => date($date_fmt, $time ),
            '%time%'    => date($time_fmt, $time ),
        );
    }

    function run ( $preview = false, $run_feed = '' ) {
        $feed_total = 0;
        $feed_items = array();
        $post_tags  = explode( ',', $this->wp_option( 'post_tags' ) );
                
        foreach ($this->feeds as $feed_class => $feed) {
            if (!empty( $run_feed ) && $run_feed != $feed_class)
                continue;
            $prefix = $feed->get_prefix();
            $data   = $feed->fetch_feed( $preview );

            if (count( $data )) {
                $feed_total += count( $data );
                $feed_items[$prefix] = $data;
                if ($this->wp_option( 'feed_tags' ))
                    $post_tags[] = $feed->get_tag();
                $post_tags = array_merge( $post_tags, $feed->get_all_tags() );
            }
        }

        $post_tags = array_map( 'trim', $post_tags );
        $post_tags = array_unique( $post_tags );
        
        if ($feed_total > 0) {
            $digest                     = array();
            $digest['post_title']       = $this->wp_option( 'post_title' );
            $digest['post_author']      = $this->wp_option( 'post_author' );
            $digest['post_status']      = 'publish';
            $digest['post_category']    = array( $this->wp_option( 'post_category' ) );
            $digest['post_content']     = $this->get_digest_html($feed_items);
            $digest['tags_input']       = $post_tags;

            foreach ($this->get_post_replacements() as $search => $replace) {
                $digest['post_title']   = str_replace( $search, $replace, $digest['post_title'] );
                $digest['post_content'] = str_replace( $search, $replace, $digest['post_content'] );
            }
            
            if ($preview) {
                $this->print_preview( $digest );
            } else {
                wp_insert_post( $digest );
            }
        }
		
        if (!$preview)
            $this->wp_option( 'last' , time() );
    }


    function print_preview ($digest) {
        echo '<div id="indigestion-plus-widgets" class="metabox-holder">';
	    echo '<div class="postbox-container" style="width:100%;">';
        echo '<div id="indigestion-plus-preview" class="postbox">';
        
        echo '<h3 class="hndle"><span>Post Preview: ' . $digest['post_title'] . '</span></h3>';
        echo '<div class="handlediv" title="Click to toggle"><br /></div>';
        
        echo '<div class="inside" style="padding: 10px;">';
        echo $digest['post_content'];
        echo "<p><strong>Tags:</strong> ";
        echo implode( ', ', $digest['tags_input']);
        echo "</p>";
        echo '</div>';
        
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }


    function get_digest_html ($data) {
        $content = '';
        $content .= $this->wp_option( 'post_before' );
        
        foreach ($this->feeds as $feed) {
            $content .= $feed->get_digest_html( $data[$feed->get_prefix()] );
        }
        
        $content .= $this->wp_option( 'post_after' );
        
        return $content;
    }

    function wp_option_name( $option ) {
        return $this->plugin_prefix . '_' . $option;
    }
    
    function print_options () {
    
        $schedule     = $this->schedules[wp_get_schedule('indigestion_plus_run')]['display'];
        $last_fetched = $this->wp_option( 'last' );
        $gmt_offset   = get_option( 'gmt_offset' ) * 3600;
        $options_href = 'options-general.php?page=indigestion_plus';
        
    ?>
        
    <div class="wrap">
    <div class="icon32" id="icon-options-general"><br/></div>
    <h2>Indigestion+ Settings</h2>
    
    <?php
        $tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : $this->group;

        echo '<ul class="subsubsub">';
	    echo '<li><a href="' . admin_url( $options_href ) . '"';
	    if ($tab == $this->group) echo ' class="current"';
	    echo '>';
	    _e( 'General' );
	    echo '</a></li>';
	
		foreach ($this->feeds as $feed) {
			echo '<li> | <a href="';
			echo admin_url( $options_href . '&tab=' . $feed->get_group() );
			echo '"';
			if ($tab == $feed->get_group()) echo ' class="current"';
			echo '>';
			echo $feed->get_name();
			echo '</a></li>';
		}
		
		echo '</ul>';
	?>
	
	<div class="clear"></div>
    <form method="post" action="options.php">
    <input type="hidden" name="action" value="update" />
    
    <?php if (($tab == $this->group)) { ?>
    
        <h3><?php _e( 'General' ); ?></h3>
        <?php settings_fields($this->group); ?>
        <table class="form-table">
        
        <tr valign="top">
        <th scope="row">Last Run</th>
        <td><?php echo ($last_fetched)
                ? '<span class="description">' . gmdate( 'Y-m-d H:i:s', $last_fetched + $gmt_offset ) . '</span>'
                : 'Never'
                ;
        ?></td>
        </tr>
        
        <?php
            $this->print_options_input( 'Digest Post Title', 'post_title', '', '', '*', 50 );
            $this->print_options_input( 'Digest Post Time',  'fetch_time',
                $schedule . ' at ', '', 'Expects a <code>hh:mm</code> (24h) format.', 5, 5 );
                
            $this->print_options_select( 'users',      'Digest Post Author',   'post_author' );
            $this->print_options_select( 'categories', 'Digest Post Category', 'post_category' );
            
            $this->print_options_input( 'Digest Post Tags',     'post_tags',   '', '', 'Separate tags by commas.', 30 );
            $this->print_options_input( 'Digest Post Preamble', 'post_before', '', '', '*',                        50 );
            $this->print_options_input( 'Digest Post Epilogue', 'post_after',  '', '', '*',                        50 );
        ?>
        
        <tr valign="top">
        <th scope="row"></th>
        <td><?php echo '<span class="description">* String replacement options: <code>'
            . implode('</code>, <code>', array_keys( $this->get_post_replacements() ))
            . '</code>.</span>'; ?></td>
        </tr>
        
        </table>

        <h3><?php _e( 'Feed Options' ); ?></h3>
        <table class="form-table">
        <?php
            $this->print_options_input( 'Feed Before',    'feed_before',    '', '', '*', 50 );
            $this->print_options_input( 'Feed After',     'feed_after',     '', '', '*', 50 );
            $this->print_options_input( 'Item Separator', 'item_separator', '', '', '',  10 );
        ?>
        
        <tr valign="top">
        <th scope="row"></th>
        <td><?php echo '<span class="description">* String replacement options: <code>'
            . implode('</code>, <code>', array_keys( $this->get_replacements() ))
            . '</code>.</span>'; ?></td>
        </tr>
        
        </table>

        <table class="form-table">
        <?php
            $this->print_options_checkbox( 'Add Relevant Feed Tags', 'feed_tags' );
        ?>
        </table>
    
    <?php } else {
    
        // FEED OPTIONS
    
        foreach ($this->feeds as $feed_class => $feed) {
            if ($tab != $feed->get_group()) continue;
            $feed->print_options_header();
            
            settings_fields($feed->get_group());
            $feed->print_options();

            /*
            echo '<h3>Debug</h3>';
            echo '<table class="form-table"><tr valign="top"><th scope="row">Last Run</th><td>';
            echo ($feed->wp_option( 'last' ))
                ? '<span class="description">'
                    . gmdate( 'Y-m-d H:i:s', $feed->wp_option( 'last' )
                        + get_option( 'gmt_offset' ) * 3600 )
                    . '</span>'
                : 'Never'
                ;
            echo '</td></tr><tr valign="top"><th scope="row">Feed URL</th><td>';
            echo $feed->get_feed_url() ? '<code>' . $feed->get_feed_url() . '</code>' : 'None';
            echo '</td></tr></table>';
            
            $this->run( $preview = true, $feed_class );
            */
        }
    
    } ?>
    
    <p class="submit">
        <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" />
    </p>
    </form>
    </div>
    
    <?php
    }
    
}

// CLASS IMPORTER

if ($handle = opendir( dirname( __FILE__ ) )) {
    while (false !== ($file = readdir( $handle ))) {
        if (preg_match( '/^indigestion-plus-source-.+\.php$/', $file )) {
            include_once( $file );
        }
    }
    closedir( $handle );
}

?>
