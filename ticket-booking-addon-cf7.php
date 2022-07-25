<?php

/**
 * Plugin Name: Ticket Booking Addon for Contact Form 7
 * Version: 1.1
 * Author: Otto Uys
 * Author URI: https://github.com/ottouys
 */

/**
 * Create plugin on install
 * Documentation: https://wordpress.stackexchange.com/questions/220935/create-a-table-in-custom-plugin-on-the-activating-it#answer-220939
 * Documentation: https://codex.wordpress.org/Creating_Tables_with_Plugins
 */
function create_plugin_database_table()
{
    global $table_prefix, $wpdb;

    $tblname = 'custom_ticket_booking_cf7';
    $table_name = $table_prefix . "$tblname";

    $charset_collate = $wpdb->get_charset_collate();

    #Check to see if the table exists already, if not, then create it

    if($wpdb->get_var( "show tables like '$table_name'" ) != $table_name) 
    {

        $sql = "CREATE TABLE $table_name (
          ticket_id mediumint(9) NOT NULL AUTO_INCREMENT,          
          ticket_booked tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY  (ticket_id)
        ) $charset_collate;";

        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}

function install_data_populate() {
	global $table_prefix, $wpdb;	
	
	$tblname = 'custom_ticket_booking_cf7';
  $table_name = $table_prefix . "$tblname";

  for ($i=0; $i < 100; $i++) { 
    $wpdb->insert( 
      $table_name, 
      array( 
        'ticket_booked' => '0',         
      ) 
    );
  }
}
register_activation_hook( __FILE__, 'create_plugin_database_table' );
register_activation_hook( __FILE__, 'install_data_populate' );

/**
 * Shortcode to display the checkboxes
 */

// Allow custom shortcodes in CF7 HTML form
add_filter( 'wpcf7_form_elements', 'custom_do_shortcodes_wpcf7_form' );
function custom_do_shortcodes_wpcf7_form( $form ) {
    $form = do_shortcode( $form );
    return $form;
}

// Add new shortcode
add_shortcode('ticket_book_cf7', 'function_ticket_book_cf7', true);
function function_ticket_book_cf7() {
    global $table_prefix, $wpdb;	
	
    $tblname = 'custom_ticket_booking_cf7';
    $table_name = $table_prefix . "$tblname";

    $results = $wpdb->get_results( "SELECT * FROM $table_name");

    foreach ($results as $key => $value) {
      if($value->ticket_booked == 0){
        $html .= '<span class="wpcf7-form-control-wrap ticket_'. $value->ticket_id .'">
                      <span class="wpcf7-form-control wpcf7-checkbox">
                          <span class="wpcf7-list-item first last">
                            <label>
                              <input type="checkbox" name="ticket_' . $value->ticket_id . '" value="Book '. $value->ticket_id .'">
                              <span class="wpcf7-list-item-label">Book ticket no '. $value->ticket_id .'.</span>
                            </label>
                          </span>
                      </span>
                  </span>';
      } else{
        $html .= '<span class="wpcf7-form-control-wrap ticket_'. $value->ticket_id .'">
                    <span class="wpcf7-form-control wpcf7-checkbox">
                        <span class="wpcf7-list-item first last">
                          <label>
                            <input type="checkbox" name="ticket_' . $value->ticket_id . '" value="Book '. $value->ticket_id .'" checked="checked" disabled>
                            <span class="wpcf7-list-item-label">Book ticket no '. $value->ticket_id .'.</span>
                          </label>
                        </span>
                    </span>
                </span>';
      }      
    }   
    
    return $html;
}

/**
 * Save the Post values
 * Documentation: https://stackoverflow.com/questions/29926252/how-to-hook-into-contact-form-7-before-send#answer-29927336
 */
add_action("wpcf7_before_send_mail", "wpcf7_store_ticket_values");  
function wpcf7_store_ticket_values($cf7) {

    // Check if post is set
    if(isset($_POST)){

      global $table_prefix, $wpdb;	

      $tblname = 'custom_ticket_booking_cf7';
      $table_name = $table_prefix . "$tblname";

      $results = $wpdb->get_results( "SELECT * FROM $table_name");

      foreach ($results as $key => $value) {
        if(isset($_POST['ticket_' . $value->ticket_id]) && $_POST['ticket_' . $value->ticket_id] == 'Book ' . $value->ticket_id){
          $wpdb->query( $wpdb->prepare("UPDATE $table_name 
              SET ticket_booked = 1 
             WHERE ticket_id = %s", $value->ticket_id)
          );
        }
      }

      // Debug
      //error_log(print_r($_POST, true));       
    }    

    return $wpcf;
}
