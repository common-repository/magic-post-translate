<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Settings Options
if ( !function_exists( "magic_post_translate_default_options_main_settings" ) ) {
    function magic_post_translate_default_options_main_settings( $never_set = FALSE ) {

        if ( $never_set == TRUE ) {
            $post_types_default = get_post_types( '', 'objects' );
            unset( $post_types_default['attachment'], $post_types_default['revision'], $post_types_default['nav_menu_item'] );
            foreach ( $post_types_default as $post_type ) {
                $default_post_types[$post_type->name] = $post_type->name;
            }
            $categories_default = get_terms( array(
                'taxonomy'   => 'category',
                'hide_empty' => false,
            ) );
            foreach ( $categories_default as $category ) {
                $default_categories[$category->slug] = $category->name;
            }
        } else {
            $default_post_types = array();
            $default_categories = array();
        }
        
        $default_options = array(
            'choosed_post_type'  => $default_post_types,
            'choosed_categories' => $default_categories,
        );
        return $default_options;
    }

}

// Settings langs Options
if ( !function_exists( "magic_post_translate_default_options_langs_settings" ) ) {
    function magic_post_translate_default_options_langs_settings( $never_set = FALSE ) {

        $default_options = array(
            'original_language'  => 'EN',
            'target_language'    => 'FR',
            'api_key'            => ''
        );
        return $default_options;
    }

}
