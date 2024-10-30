<?php
/*
Plugin Name:       Magic Post Translate
Description:       Automatic Translate Posts & Pages with Deepl
Version:           1.0.1
Author:            Alexandre Gaboriau
Author URI:        http://www.alexandregaboriau.fr/en/
License:           GPL-2.0+
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain:       mpt
Domain Path:       /languages


Copyright 2020 Magic Post Translate

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Magic_Post_Translate_Backoffice {
    public function __construct() {

        if ( is_admin() ) {
            add_action( 'admin_menu', array( &$this, 'magic_post_translate_menu' ) );
            add_action( 'init', array( &$this, 'magic_post_translate_redirect_menu' ) );
            register_activation_hook( __FILE__, array( &$this, 'magic_post_translate_default_values' ) );
            add_filter(
                'plugin_action_links',
                array( &$this, 'magic_post_translate_add_settings_link' ),
                10,
                2
            );
            load_plugin_textdomain( 'mpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            add_action( 'admin_enqueue_scripts', array( &$this, 'magic_post_translate_admin_enqueues' ) );
            // Plugin hook for adding CSS and JS files required for this plugin
            add_action( 'add_meta_boxes', array( &$this, 'magic_post_translate_add_custom_box' ) );
            add_action( 'save_post', array( &$this, 'magic_post_translate_save_postdata' ) );
	
            add_filter( 'category_row_actions', array( &$this, 'category_row_actions' ), 10, 2 );

            add_filter( 'manage_edit-post_columns', array( &$this, 'posts_add_translated_row' ) ) ;
            add_action( 'manage_post_posts_custom_column', array( &$this, 'posts_add_translated_values' ), 10, 2 );
            add_filter( 'manage_edit-posts_sortable_columns', array( &$this, 'posts_translated_sortable_columns' ) );

            add_action( 'bulk_edit_custom_box', array( &$this, 'magic_post_translate_add_to_bulk_quick_edit_custom_box' ), 10, 2 );
            add_action( 'admin_print_scripts-edit.php', array( &$this, 'magic_post_translate_enqueue_edit_scripts' ) );
            add_action( 'wp_ajax_magic_post_translate_save_bulk_edit', array( &$this, 'magic_post_translate_save_bulk_edit' ) );

            // Ajax calls
            add_action( 'wp_ajax_nopriv_get_users_table_data',array( &$this, 'magic_post_translate_ajax_call' ) );
            add_action( 'wp_ajax_get_users_table_data', array( &$this, 'magic_post_translate_ajax_call' ) );

            /* Bulk Generation link for posts & custom post type */
            $post_type_availables = get_option( 'magic_post_translate_plugin_main_settings' );

            if( empty( $post_type_availables['choosed_post_type'] ) ) {
                return false;
            } else {
                foreach ( $post_type_availables['choosed_post_type'] as $screen ) {
                    add_filter( 'bulk_actions-edit-'. $screen, array( &$this, 'magic_post_translate_add_bulk_actions' ) );	     // Text on dropdown
                    add_action( 'handle_bulk_actions-edit-'.$screen, array( &$this, 'magic_post_translate_bulk_action_handler' ) );  // Redirection
                }
            }
        }

    }

    public function magic_post_translate_bulk_action_handler() {
        $ids = implode( ',', array_map( 'intval', $_REQUEST['post'] ) );
        wp_redirect( 'admin.php?page=magic_post_translate%2Finc%2Fadmin%2Fmain.php&ids=' . $ids );
        exit();
    }

    public function magic_post_translate_add_bulk_actions( $actions ) {
?>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                $('select[name^="action"] option:last-child').before('<option value="bulk_regenerate_thumbnails"><?php echo esc_html__( 'Generate translation with Deepl', 'mpt' ); ?></option>');
            });
        </script>
<?php
        return $actions;
    }

    public function magic_post_translate_redirect_menu() {
        if( 
            ( isset( $_GET['settings-updated'] ) && ( $_GET['settings-updated'] ==  'true' ) ) && 
            ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'magic_post_translate/inc/admin/main.php' ) 
        ) {
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            // Get cpts OR cats	
            $options = get_option( 'magic_post_translate_plugin_main_settings' );

            $slugs_cats = '';
            $slugs_cpts = '';
            if( !empty( $options['choosed_categories'] ) ) {
                foreach( $options['choosed_categories'] as $chosen ) {
                    $slugs_cats .= $chosen.',';
                }
                $slugs_cats = substr_replace($slugs_cats ,'', -1);
            }

            if( !empty( $options['choosed_post_type'] ) ) {
                foreach( $options['choosed_post_type'] as $chosen ) {
                    $slugs_cpts .= $chosen.',';
                }
                $slugs_cpts = substr_replace($slugs_cpts ,'', -1);
            } 

            wp_redirect( "admin.php?page=magic_post_translate%2Finc%2Fadmin%2Fmain.php&cats=$slugs_cats&cpts=$slugs_cpts" );
            exit;
        }
    }

    public function magic_post_translate_admin_enqueues() {

        if ( (!empty($_POST['mpt']) || !empty($_REQUEST['ids']) || !empty($_REQUEST['cats']) || !empty($_REQUEST['cpts'] )) && (empty($_REQUEST['settings-updated']) || $_REQUEST['settings-updated'] != 'true') ) {

            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-progressbar' );
            wp_enqueue_style( 'style-jquery-ui', plugins_url( 'assets/js/jquery-ui/jquery-ui.css', __FILE__ ) );
            wp_enqueue_script( 'images-generation', plugins_url( 'assets/js/generation.js', __FILE__ ), array( 'jquery-ui-progressbar' ) );

            // JavaScript Variables for nonce, admin-jax.php path and translations
            $js_vars = array(
                'wp_ajax_url'                       => admin_url( 'admin-ajax.php' ),
                'translations' => array(
                    'successful'           => esc_html__( 'Successful generation !!', 'mpt' ),
                    'error_generation'     => esc_html__( 'Error with translations generation', 'mpt' ),
                    'error_translate'      => esc_html__( 'Error with the plugin', 'mpt' ),
                )
            );
            wp_localize_script( 'images-generation', 'translationJsVars', $js_vars );

        }

        wp_enqueue_style( 'style-admin-mpt', plugins_url( 'assets/css/admin-style.css', __FILE__ ) );
    }

    private function magic_post_translate_generate( $contents, $options_langs ) {

        $original_language  = ( !empty($options_langs['original_language']) ? $options_langs['original_language'] : 'EN' );
        $target_language    = ( !empty($options_langs['target_language']) ? $options_langs['target_language'] : 'FR' );
        $api_key            = ( !empty($options_langs['api_key']) ? $options_langs['api_key'] : '' );
        $text               = ( !empty($contents) ? $contents : '' );

        if( empty( $api_key ) ) {
            esc_html_e( 'No API Key', 'mpt' );
        }


        $url_deepl_content = array( 
            'auth_key' 		=> $api_key,
            'source_lang' 	=> $original_language,
            'target_lang' 	=> $target_language,
            'tag_handling'      => 'xml',
            'text'              => $text
        );

        $output = wp_remote_post(
            "https://api.deepl.com/v2/translate", 
            array(
                'method'      => 'POST',
                'headers'     => array(),
                'blocking'    => true,
                'redirection' => 5,
                'httpversion' => '1.0',
                'sslverify'   => false,
                'body'        => $url_deepl_content
            )
        );

        $html = json_decode( $output['body'], true );

        // Problem with API
        if( $html == null ) {
            esc_html_e( 'Problem with Deepl API : Nothing received', 'mpt' );
            echo '<br/>';
            return false;
        }

        $contentsFinal = '';
        foreach( $html['translations'] as $counter => $translatedText ) {
            if( $counter == 0 ) {
                continue;
            }
            $contentsFinal .= $translatedText['text'];
        }
        $result_body = array( $html['translations'][0]['text'], $contentsFinal );
	
        return $result_body;
    }


    public function magic_post_translate_get_content( $content ) {
        $words  = explode( ' ', $content );
        $length = 0;
        $index  = 0;
        $array_content = array();
        foreach ( $words as $word ) {
            $wordLength = strlen($word) + 1;

            if ( ( $length + $wordLength ) <= 30000 ) {
                $array_content[$index] .= $word . ' ';
                $length += $wordLength;
            } else {
                $index += 1;
                $length = $wordLength;
                $array_content[$index] = $word;
            }
        }		
        return  $array_content;	
    }


    /* Launch the translation */
    public function magic_post_translate_create_translation(
        $id,
        $check_value_enable = 1,
        $check_post_type    = 1,
        $check_category     = 1
    )
    {

        //$options = wp_parse_args( get_option( 'magic_post_translate_plugin_main_settings' ), magic_post_translate_default_options_main_settings( TRUE ) );
        $options       = get_option( 'magic_post_translate_plugin_main_settings' );
        $options_langs = get_option( 'magic_post_translate_plugin_langs_settings' );
        $options       = array_merge( $options, $options_langs );
        $search        = get_post_field( 'post_title', $id, 'raw' );


        /* Get the title translation */
        $title    = get_post_field('post_title',   $id);

        $result_title = $this->magic_post_translate_generate(
            $title,
            $options_langs
        );


        /* Get the content translation */
        $contents = get_post_field( 'post_content', $id );
        $contents = $this->magic_post_translate_get_content( $contents );
        
        /* Split the call for very big text */
        foreach( $contents as $content ) {

            $result_content = $this->magic_post_translate_generate(
                $content,
                $options_langs
            );
            $url_results['content'][] = $result_content[0];

        }

        $result_content = implode( ' ', $url_results['content'] );


        /* Update the post with the new content */
        $new_post = array(
            'ID'           => $id,
            'post_title'   => $result_title[0],
            'post_name'	   => sanitize_title( $result_title[0] ),
            'post_content' => $result_content
        );
        $wp_error = '';
        $post_id = wp_update_post( $new_post, $wp_error );

        if( $post_id != 0 ) {
            update_post_meta( $id, '_mpt_value_key', '1' );
            return 1;
        }
    }


    function magic_post_translate_menu() {

        add_menu_page(
            esc_html__( 'Magic Post Translate Options', 'mpt' ),
            'Magic Post Translate',
            'manage_options',
            'magic_post_translate/inc/admin/main.php',
            array( &$this, 'magic_post_translate_options' ),
            'dashicons-translation',
            81
        );
        add_submenu_page(
            'magic_post_translate/inc/admin/main.php',
            esc_html__( 'Settings', 'mpt' ),
            esc_html__( 'Settings', 'mpt' ),
            'manage_options',
            'magic_post_translate/inc/admin/langs.php',
            array( &$this, 'magic_post_translate_langs' )
        );
        add_action( 'admin_head', array( &$this, 'magic_post_translate_admin_register_head' ) );
        require_once dirname( __FILE__ ) . '/inc/default_values.php';
        register_setting( 'magic-post-translate-plugin-main-settings', 'magic_post_translate_plugin_main_settings' );
        register_setting( 'magic-post-translate-plugin-langs-settings', 'magic_post_translate_plugin_langs_settings' );
        /* Bulk Generation link for posts & custom post type */
        $post_type_availables = get_option( 'magic_post_translate_plugin_main_settings' );

    }


    function magic_post_translate_admin_register_head() {

        if ( !empty($_POST['mpt']) || !empty($_REQUEST['ids']) || !empty($_REQUEST['cats']) || !empty($_REQUEST['cpts']) ) {

            // If no CPT filled : Set all CPT
            if( empty( $_REQUEST['cpts'] ) ) {
                $cpts = get_post_types( array( 'public' => true ), 'names' );
                $_REQUEST['cpts'] = $cpts;
            }


            if( isset( $_REQUEST['ids'] ) ) {
                $ids = sanitize_text_field( $_GET['ids'] );
            } elseif( isset( $_REQUEST['cats'] ) && isset( $_REQUEST['cpts'] ) ) {

                if( isset( $_GET['cpts'] ) ) {	
                    $cpts = sanitize_text_field( $_GET['cpts'] );
                    $cpts = explode( ',', $cpts );
                } else {
                    $cpts = '';
                }

                $post_ids = get_posts( array(
                    'numberposts'   => -1, // get all posts.
                    'cat'           => sanitize_text_field( $_GET['cats'] ),
                    'post_type'     => $cpts,
                    'post_status'   => array( 'publish', 'draft', 'pending', 'future', 'private' ),
                    'fields'        => 'ids', // Only get post IDs
                ) );

                $ids = '';
                foreach( $post_ids as $post_id ) {
                    $ids .= $post_id.',';
                }

                $ids = substr_replace($ids ,'', -1);

            } else {
                return false;
            }

            $ids   = array_map( 'intval', explode( ',', trim( $ids, ',' ) ) );
            $count = count( $ids );
            $ids   = json_encode( $ids );
            
            $ajax_nonce = wp_create_nonce( 'ajax_nonce_magic_post_translate' );

            ?>
            <script type="text/javascript">
                sendposts( <?php echo $ids; ?>, 1, <?php echo  $count; ?>, "<?php echo $ajax_nonce; ?>" );
            </script>	
            <?php 
        }
    }


    /* Display magic_post_translate Options */
    public function magic_post_translate_options() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mpt' ) );
        }
        require_once dirname( __FILE__ ) . '/inc/admin/main.php';
    }


    public function magic_post_translate_langs() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mpt' ) );
        }
        require_once dirname( __FILE__ ) . '/inc/admin/langs.php';
    }

    /* Set Default value when activated and never configured */
    public function magic_post_translate_default_values() {
        $main_options  = get_option( 'magic_post_translate_plugin_main_settings' );
        $langs_options = get_option( 'magic_post_translate_plugin_langs_settings' );
        /* Options Never set */

        if ( !$main_options && !$langs_options ) {
            require_once dirname( __FILE__ ) . '/inc/default_values.php';
            $options_main_default = magic_post_translate_default_options_main_settings( TRUE );
            $options_langs_default = magic_post_translate_default_options_langs_settings( TRUE );
            update_option( 'magic_post_translate_plugin_main_settings', $options_main_default );
            update_option( 'magic_post_translate_plugin_langs_settings', $options_langs_default );
        }
    }


    /* Add Settings link to plugins */
    public function magic_post_translate_add_settings_link( $links, $file ) {
        static  $this_plugin ;
        if ( !$this_plugin ) {
            $this_plugin = plugin_basename( __FILE__ );
        }

        if ( $file == $this_plugin ) {
            $settings_link = '<a href="admin.php?page=magic_post_translate%2Finc%2Fadmin%2Fmain.php">' . esc_html__( 'Settings', 'mpt' ) . '</a>';
            array_unshift( $links, $settings_link );
        }

        return $links;
    }


    /* Box on posts edit screens */
    public function magic_post_translate_add_custom_box() {
        $id = get_the_ID();
        $post_type_availables = get_option( 'magic_post_translate_plugin_main_settings' );
        $screens = ( !empty($post_type_availables['choosed_post_type']) ? $post_type_availables['choosed_post_type'] : '' );
        if ( empty($screens) ) {
            return false;
        }
        foreach ( $screens as $screen ) {
            add_meta_box(
                'myplugin_sectionid',
                'Automatic Translate Post',
                array( &$this, 'magic_post_translate_inner_custom_box' ),
                $screen,
                'side'
            );
        }
    }

    /* Box magic_post_translate choice for posts */
    public function magic_post_translate_inner_custom_box( $post ) {
        wp_nonce_field( plugin_basename( __FILE__ ), 'mpt_noncename' );
        $value = get_post_meta( $post->ID, '_mpt_value_key', true );
        $value = ( $value == '1' ? 'checked="checked"' : '' );
        echo  '<label class="selectmpt"> <input value="1" type="checkbox" name="mpt_check" ' . esc_attr( $value ) . '></label> ' ;
        esc_html_e( 'Post translated', 'mpt' );
    }


    /* Save enable/disable choice for a saved post */
    public function magic_post_translate_save_postdata( $post_id ) {

        if ( 'page' == get_post_type( $post_id ) ) {
            if ( !current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( !current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        if ( !isset( $_POST['mpt_noncename'] ) || !wp_verify_nonce( $_POST['mpt_noncename'], plugin_basename( __FILE__ ) ) ) {
            return;
        }
        $post_ID = sanitize_text_field( $_POST['post_ID'] );

        if ( !isset( $_POST['mpt_check'] ) || $_POST['mpt_check'] != 1 ) {
            $mpt_enabled = 0;
        } else {
            $mpt_enabled = 1;
        }

        update_post_meta( $post_ID, '_mpt_value_key', $mpt_enabled );
    }


    public function category_row_actions( $actions, $tag ) {
        $actions['mpt'] = '<a href="admin.php?page=magic_post_translate%2Finc%2Fadmin%2Fmain.php&amp;cats='.$tag->term_id.'" class="aria-button-if-js">'. esc_html__( 'Generate translation', 'mpt' ) .'</a>';
        return $actions;
    }


    public function posts_add_translated_row( $columns ) {

        $columns['cb']         = '&lt;input type="checkbox" />';
        $columns['translated'] = esc_html__( 'Translated', 'mpt' );

        return $columns;
    }


    public function posts_add_translated_values( $column, $post_id ) {
        global $post;

        if( $column == 'translated' ) {

            /* Get the post meta. */
            $translated_post = get_post_meta( $post_id, '_mpt_value_key', true );

            if ( $translated_post == '1' )
                esc_html_e( 'Yes', 'mpt' );

            else
                esc_html_e( 'No', 'mpt' );

        }
    }


    public function posts_translated_sortable_columns( $columns ) {
        $columns['translated'] = 'translated';

        return $columns;
    }


    public function magic_post_translate_add_to_bulk_quick_edit_custom_box( $column_name, $post_type ) {
        switch ( $post_type ) {
            case 'post':
                switch( $column_name ) {
                    case 'translated':
                       ?>
                        <fieldset class="inline-edit-col-right">
                            <div class="inline-edit-group">
                                <label>
                                    <span class="title"><?php esc_html_e( 'Translated', 'mpt' );?> ?</span>
                                    <select name="translated">
                                        <option value="-1">- <?php esc_html_e( 'No change', 'mpt' );?> -</option>
                                        <option value="false"><?php esc_html_e( 'No', 'mpt' );?></option>
                                        <option value="1"><?php esc_html_e( 'Yes', 'mpt' );?></option>
                                    </select>
                                </label>
                            </div>
                       </fieldset><?php
                       break;
                }
                break;
        }
    }


    public function magic_post_translate_save_bulk_edit() {
        
        // get our variables
        $post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? wp_parse_id_list( $_POST[ 'post_ids' ] ) : array();
        $translated = ( isset( $_POST[ 'translated' ] ) && !empty( $_POST[ 'translated' ] ) ) ? sanitize_text_field( $_POST[ 'translated' ] ) : NULL;
        // if everything is in order
        if ( !empty( $post_ids ) && is_array( $post_ids ) && !empty( $translated ) ) {
            foreach( $post_ids as $post_id ) {
                update_post_meta( $post_id, '_mpt_value_key', $translated );
            }
        }
    }


    public function magic_post_translate_enqueue_edit_scripts() {
        wp_enqueue_script( 'translation-admin-edit', plugins_url( 'assets/js/bulk_edit.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), '', true	 );
    }


    public function magic_post_translate_ajax_call() {

        // Security checks & Check if user is admin
        if ( ! current_user_can('administrator') || ! defined( 'DOING_AJAX' ) || false === wp_verify_nonce( $_POST['nonce'], 'ajax_nonce_magic_post_translate' ) ) {
            wp_send_json_error();
        }


        if( !isset( $_POST['ids'] ) )
            return false;

        $post_ids = array_map( 'absint', $_POST['ids'] );
        $count    = count( $post_ids );

        foreach ( $post_ids as $key => $val ) {
            $ids[ $key+1 ] = $val;
        }

        $a  = (int)$_POST['a']; 
        $id = $ids[$a];

        $check_translated = get_post_meta( $id, '_mpt_value_key', true );

        $launch_magic_post_translate = new Magic_Post_Translate_Backoffice();

        if( $check_translated == '1' ) {
            $msg = esc_html__( 'Translation for ', 'mpt' ) . '<a href="'.get_edit_post_link( $id ).'#postimagediv" target="_blank" >' . get_the_title( $id ) . '</a> ' . esc_html__( ' already exists', 'mpt' );
        } else {
            $magic_post_translate_return = $launch_magic_post_translate->magic_post_translate_create_translation( $id, '0', '0', '0' );
            if( $magic_post_translate_return == null )
                $msg = esc_html__( 'Problem with translation for ', 'mpt' ).'<a href="'.get_edit_post_link( $id ).'#postimagediv" target="_blank" > '.get_the_title( $id ).'</a>';
            else
                $msg = '<span class="successful">'. esc_html__( 'Successful translation for ', 'mpt' ).'<a href="'.get_edit_post_link( $id ).'#postimagediv" target="_blank" > '.get_the_title( $id ).'</a></span>';
        }

        $percent = ( 100*$a )/$count;

        $datas['msg']     = $msg;
        $datas['percent'] = $percent;


        // Send data to JavaScript
        if ( ! empty( $datas['msg'] ) && ! empty( $datas['percent'] ) ) {
            wp_send_json_success( $datas );
        } else {
            wp_send_json_error();
        }

    }

}

/* Launch magic_post_translate only for WP backoffice */
if ( is_admin() ) {
    $launch_MPT = new Magic_Post_Translate_Backoffice();
}