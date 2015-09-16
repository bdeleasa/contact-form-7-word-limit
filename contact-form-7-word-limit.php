<?php
/*
Plugin Name: Contact Form 7 Word Limit
Description: Allows you to specify a word limit instead of character limit for textareas
Version: 1.0.0
Author: Brianna Deleasa
Author URI: http://www.briannadeleasa.com
License: GPL v3
*/


// Include our classes
require_once 'classes/class-wpcfy-word-limit.php';


add_action( 'plugins_loaded', 'wpcf7wl_init' );
/**
 * Starts up our plugin
 *
 * @param none
 * @return none
 */
function wpcf7wl_init() {

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    // Get out now if Contact Form 7 isn't active
    if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
        return;
    }

    WPCF7_Word_Limit::init();

}