<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

?>
<div class="wrap">

	<h2 ><?php _e( 'Magic Post Translate : Generate', 'mpt' ) ?></h2>

	<?php 
            if ( (!empty($_POST['mpt']) || !empty($_REQUEST['ids']) || !empty($_REQUEST['cats']) || !empty($_REQUEST['cpts'])) && (empty($_REQUEST['settings-updated']) || $_REQUEST['settings-updated'] != 'true') ) {
        ?>
            <div id="ids" style="display:none;">
                <?php 
                    echo sanitize_text_field( $_REQUEST['ids'] );
                ?>
            </div>
            <div id="hide-before-import" style="display:none">
                <div id="progressbar"></div>
                <div id="results" ></div>
            </div>
        <?php 
            }
        ?>

	<form method="post" action="options.php" >

            <?php 
                settings_fields( 'magic-post-translate-plugin-main-settings' );
                $options = wp_parse_args( get_option( 'magic_post_translate_plugin_main_settings' ), magic_post_translate_default_options_main_settings( TRUE ) );
            ?>

            <table id="general-options" class="form-table">
                <tbody>
                    <tr valign="top">
                        <th scope="row">
                            <?php 
                                _e( 'Relevant post type', 'mpt' );
                            ?>
                        </th>
                        <td>
                            <?php 
                                $post_types_default = get_post_types( '', 'objects' );
                                unset( $post_types_default['attachment'], $post_types_default['revision'], $post_types_default['nav_menu_item'] );
                                foreach ( $post_types_default as $post_type ) {

                                    if ( post_type_supports( $post_type->name, 'thumbnail' ) == 'true' ) {
                                        $checked = ( isset( $options['choosed_post_type'][$post_type->name] ) ? 'checked' : '' );
                                        echo  '<label>
                                                <input ' . $checked . ' name="magic_post_translate_plugin_main_settings[choosed_post_type][' . $post_type->name . ']" type="checkbox" value="' . $post_type->name . '"> ' . $post_type->labels->name . '
                                        </label><br/>' ;
                                    }

                                }
                            ?>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                                <?php 
                                    _e( 'Relevant categories', 'mpt' );
                                ?>
                        </th>
                        <td>
                                <?php 
                                    $categories_default = get_terms( array(
                                        'taxonomy'   => 'category',
                                        'hide_empty' => false,
                                    ) );
                                    foreach ( $categories_default as $category ) {
                                        $checked = ( isset( $options['choosed_categories'][$category->slug] ) ? 'checked' : '' );
                                        echo  '<label>
                                                <input ' . $checked . ' name="magic_post_translate_plugin_main_settings[choosed_categories][' . $category->slug . ']" type="checkbox" value="' . $category->term_taxonomy_id . '"> ' . $category->name . ' ( ' . $category->count . ' )
                                        </label><br/>' ;
                                    }
                                ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php 
                submit_button( __( 'Save & Generate', 'mpt' ) );
            ?>

	</form>
</div>
<div class="clear"></div>