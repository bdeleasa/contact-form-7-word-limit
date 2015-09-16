<?php

/**
 * Class WPCF7_Word_Limit
 *
 * Integrates with Contact Form 7 to enable maximum and minimum WORD
 * lengths in addition to maximum and minimum CHARACTER lengths.
 */
class WPCF7_Word_Limit {

    /**
     * Holds the current instance of the class
     *
     * @since 1.0.0
     * @access private
     * @var WPCF7_Word_Limit
     */
    private static $instance = null;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct() {

        // Filters
        add_filter( 'wpcf7_validate_textarea', array($this,'textarea_validation_filter'), 10, 2 );
        add_filter( 'wpcf7_validate_textarea*', array($this,'textarea_validation_filter'), 10, 2 );

        // Actions
        add_action( 'wpcf7_init', array($this, 'replace_shortcode_textarea'), 999 );
        add_action( 'wpcf7_init', array($this, 'remove_default_textarea_validation_filter'), 999 );

    }


    /**
     * Initializes the class
     *
     * @param none
     * @return WPCF7_Word_Limit
     *
     * @since 1.0.0
     */
    static function init() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new WPCF7_Word_Limit;
        }
        return self::$instance;
    }


    /**
     * Removes the default textarea validation.  We're handling
     * the validation ourselves in this plugin, so we don't want
     * both of them running.
     *
     * @param none
     * @return none
     */
    function remove_default_textarea_validation_filter() {
        remove_filter( 'wpcf7_validate_textarea', 'wpcf7_textarea_validation_filter', 10 );
        remove_filter( 'wpcf7_validate_textarea*', 'wpcf7_textarea_validation_filter', 10 );
    }


    /**
     * Runs when validating a CF7 field.  We use this to override
     * the default textarea validation and replace it with our
     * custom word count validation
     *
     * @param $result
     * @param $tag
     * @return mixed
     */
    function textarea_validation_filter( $result, $tag ) {

        $tag = new WPCF7_Shortcode( $tag );

        $name = $tag->name;

        $value = isset( $_POST[$name] ) ? (string) $_POST[$name] : '';

        if ( $tag->is_required() && '' == $value ) {
            $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
        }

        if ( ! empty( $value ) ) {
            $maxlengthwords = false;
            $minlengthwords = false;

            $maxlength = $tag->get_maxlength_option();
            $minlength = $tag->get_minlength_option();
            $inputlength = str_word_count( $value );

            $characterlength = strlen( $value );

            if ( $maxlength && $minlength && $maxlength < $minlength ) {
                $maxlength = $minlength = null;
            }

            foreach($tag->options as $key => $option ) {
                if ( stristr($option, 'maxlengthwords:true') ) {
                    $maxlengthwords = true;
                }

                if ( stristr($option, 'minlengthwords:true') ) {
                    $minlengthwords = true;
                }
            }

            if ( $maxlengthwords === true ) {
                if ( $inputlength > intval($maxlength) ) {
                    $result->invalidate( $tag, "Your input is too long (<strong>{$inputlength}</strong>/{$maxlength} maximum words)" );
                }
            }
            else {
                if ( $maxlength && $maxlength < $characterlength ) {
                    $result->invalidate( $tag, wpcf7_get_message( 'invalid_too_long' ) );
                }
            }

            if ( $minlengthwords === true ) {
                if ( intval($inputlength) < intval($minlength) ) {
                    $result->invalidate( $tag, "Your input is too short (<strong>{$inputlength}</strong>/{$minlength} minimum words)" );
                }
            }
            else {
                if ( $minlength && $characterlength < $minlength ) {
                    $result->invalidate( $tag, wpcf7_get_message( 'invalid_too_short' ) );
                }
            }
        }

        return $result;

    }


    /**
     * Replaces the default textarea shortcode with our
     * custom shortcode.
     *
     * @param none
     * @return none
     */
    function replace_shortcode_textarea() {

        wpcf7_remove_shortcode( 'textarea' );
        wpcf7_add_shortcode( array( 'textarea', 'textarea*' ), array($this, 'textarea_shortcode_handler'), true );

    }


    /**
     * Outputs the HTML for the textarea shortcode.  We're replacing the default
     * CF7 textarea shortcode.  If the user wants a word limit instead of a character
     * limit, we need to remove the maxlength attribute from the element.
     *
     * @param $tag
     * @return string
     */
    function textarea_shortcode_handler( $tag ) {

        $tag = new WPCF7_Shortcode( $tag );

        if ( empty( $tag->name ) )
            return '';

        $validation_error = wpcf7_get_validation_error( $tag->name );

        $class = wpcf7_form_controls_class( $tag->type );

        if ( $validation_error )
            $class .= ' wpcf7-not-valid';

        $atts = array();

        $atts['cols'] = $tag->get_cols_option( '40' );
        $atts['rows'] = $tag->get_rows_option( '10' );
        $atts['maxlength'] = $tag->get_maxlength_option();
        $atts['minlength'] = $tag->get_minlength_option();

        if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
            unset( $atts['maxlength'], $atts['minlength'] );
        }
        
        $maxlengthwords = false;

        foreach($tag->options as $key => $option ) {
            if ( stristr($option, 'maxlengthwords:true') ) {
                $maxlengthwords = true;
            }
        }

        if ( $maxlengthwords === true ) {
            unset( $atts['maxlength'], $atts['minlength'] );
        }

        $atts['class'] = $tag->get_class_option( $class );
        $atts['id'] = $tag->get_id_option();
        $atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

        if ( $tag->has_option( 'readonly' ) ) {
            $atts['readonly'] = 'readonly';
        }

        if ( $tag->is_required() ) {
            $atts['aria-required'] = 'true';
        }

        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';

        $value = empty( $tag->content )
            ? (string) reset( $tag->values )
            : $tag->content;

        if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
            $atts['placeholder'] = $value;
            $value = '';
        }

        $value = $tag->get_default_option( $value );

        $value = wpcf7_get_hangover( $tag->name, $value );

        $atts['name'] = $tag->name;

        $atts = wpcf7_format_atts( $atts );

        $html = sprintf(
            '<span class="wpcf7-form-control-wrap %1$s"><textarea %2$s>%3$s</textarea>%4$s</span>',
            sanitize_html_class( $tag->name ), $atts,
            esc_textarea( $value ), $validation_error );

        return $html;

    }

}