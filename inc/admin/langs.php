<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

?>
<div class="wrap">

    <h2 ><?php esc_html_e( 'Magic Post Translate : Settings', 'mpt' ) ?></h2>

    <form method="post" action="options.php" >

            <?php 
                settings_fields( 'magic-post-translate-plugin-langs-settings' );
                $options = get_option( 'magic_post_translate_plugin_langs_settings' );
            ?>

            <table id="langs-options" class="form-table">
                    <tbody>

                        <tr valign="top">
                            <th scope="row">
                                <label for="hseparator">
                                <?php 
                                    esc_html_e( 'Original language', 'mpt' );
                                ?>
                                </label>
                            </th>
                            <td>
                                <select name="magic_post_translate_plugin_langs_settings[original_language]">
                                    <?php 
                                        $selected = $options['original_language'];
                                        $original_languages = array(
                                            esc_html__( 'English', 'mpt' )             => 'EN',
                                            esc_html__( 'German', 'mpt' )              => 'DE',
                                            esc_html__( 'French', 'mpt' )              => 'FR',
                                            esc_html__( 'Spanish', 'mpt' )             => 'ES',
                                            esc_html__( 'Portuguese (mixed)', 'mpt' )  => 'PT',
                                            esc_html__( 'Italian', 'mpt' )             => 'IT',
                                            esc_html__( 'Dutch', 'mpt' )               => 'NL',
                                            esc_html__( 'Polish', 'mpt' )              => 'PL',
                                            esc_html__( 'Russian', 'mpt' )             => 'RU',
                                            esc_html__( 'Japanese', 'mpt' )            => 'JA',
                                            esc_html__( 'Chinese', 'mpt' )             => 'ZH'
                                        );
                                        foreach ( $original_languages as $name_lang => $code_lang ) {
                                            $choose = ( $selected == $code_lang ? 'selected="selected"' : '' );
                                            echo  '<option ' . $choose . ' value="' . $code_lang . '">' . $name_lang . '</option>' ;
                                        }
                                    ?>
                                </select>
                                <br/>
                                <p class="description">
                                    <i><?php esc_html_e( 'Language of the text to be translated.', 'mpt' ) ?></i>
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                    <label for="hseparator">
                                    <?php 
                                        esc_html_e( 'Target language', 'mpt' );
                                    ?>
                                    </label>
                            </th>
                            <td>
                                <select name="magic_post_translate_plugin_langs_settings[target_language]">
                                    <?php 
                                        $selected = $options['target_language'];
                                        $target_languages = array(
                                            esc_html__( 'English', 'mpt' )                 => 'EN',
                                            esc_html__( 'German', 'mpt' )                  => 'DE',
                                            esc_html__( 'French', 'mpt' )                  => 'FR',
                                            esc_html__( 'Spanish', 'mpt' )                 => 'ES',
                                            esc_html__( 'Portuguese', 'mpt' )              => 'PT-PT',
                                            esc_html__( 'Portuguese (Brazilian)', 'mpt' )  => 'PT-BR',
                                            esc_html__( 'Italian', 'mpt' )                 => 'IT',
                                            esc_html__( 'Dutch', 'mpt' )                   => 'NL',
                                            esc_html__( 'Polish', 'mpt' )                  => 'PL',
                                            esc_html__( 'Russian', 'mpt' )                 => 'RU',
                                            esc_html__( 'Japanese', 'mpt' )                => 'JA',
                                            esc_html__( 'Chinese', 'mpt' )                 => 'ZH'
                                        );
                                        foreach ( $target_languages as $name_lang => $code_lang ) {
                                            $choose = ( $selected == $code_lang ? 'selected="selected"' : '' );
                                            echo  '<option ' . $choose . ' value="' . $code_lang . '">' . $name_lang . '</option>' ;
                                        }
                                    ?>
                                </select>
                                <br/>
                                <p class="description">
                                        <i><?php esc_html_e( 'The language into which the text should be translated.', 'mpt' ) ?></i>
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <label for="hseparator"><?php esc_html_e( 'Deepl API key', 'mpt' ) ?></label>
                            </th>
                            <td>
                                <input type="text" name="magic_post_translate_plugin_langs_settings[api_key]" value="<?php echo( isset( $options['api_key'] ) && !empty( $options['api_key'] ) )? $options['api_key']: ''; ?>" >
                            </td>
                        </tr>

                    </tbody>
            </table>

            <?php submit_button(); ?>
    </form>

</div>
<div class="clear"></div>