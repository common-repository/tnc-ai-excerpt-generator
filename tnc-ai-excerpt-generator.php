<?php
/**
 * Plugin Name: TNC AI Excerpt Generator
 * Plugin URI: https://themencode.com/tnc-ai-excerpt-generator/
 * Description: Automatically generates a excerpt of posts and saves it as an excerpt using OpenAI's GPT-3 language model.
 * Version: 1.0.1
 * Author: ThemeNcode LLC
 * Author URI: https://themencode.com/
 * License: GPL2
 */

define( 'THEMENCODE_AI_EXCERPT_GENERATOR_VERSION', '1.0.1' );
add_action( 'admin_menu', 'themencode_aig_add_ai_excerpt_gen_options_page' );
add_action( 'admin_init', 'themencode_aig_register_ai_excerpt_gen_settings' );

// Define the OpenAI API key
$openai_api_key = get_option('ai_excerpt_gen_api_key');

// Define the OpenAI API key
define('THEMENCODE_OPENAI_API_KEY', $openai_api_key );
define( 'THEMENCODE_OPENAI_MAX_TOKEN', get_option('ai_excerpt_gen_max_token') );

// Register the plugin settings
function themencode_aig_register_ai_excerpt_gen_settings() {
    add_option('ai_excerpt_gen_api_key', '');
    add_option('ai_excerpt_gen_post_types_enabled', '');
    add_option('ai_excerpt_gen_model', 'text-davinci-002');
    add_option('ai_excerpt_gen_max_token', 60);
    register_setting('ai_excerpt_gen_options_group', 'ai_excerpt_gen_api_key');
    register_setting('ai_excerpt_gen_options_group', 'ai_excerpt_gen_post_types_enabled');
    register_setting('ai_excerpt_gen_options_group', 'ai_excerpt_gen_model');
    register_setting('ai_excerpt_gen_options_group', 'ai_excerpt_gen_max_token');
}

// Add the plugin settings menu item
function themencode_aig_add_ai_excerpt_gen_options_page() {
    add_options_page('TNC AI Excerpt Generator Settings', 'TNC AI Excerpt', 'manage_options', 'tnc-ai-excerpt-generator', 'themencode_aig_ai_excerpt_gen_options_page');
}

// Display the plugin settings page
function themencode_aig_ai_excerpt_gen_options_page() {
?>
    <div>
        <h1><?php echo esc_html__( 'Generate Excerpt Settings', 'tnc-ai-excerpt-generator' ) ?></h1>
        <p><?php echo sprintf( esc_html__( 'Developed & Maintained by %1s', 'tnc-ai-excerpt-generator' ), '<a href="https://themencode.com/" target="_blank">ThemeNcode LLC</a>' ); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('ai_excerpt_gen_options_group'); ?>
            <h3><?php echo esc_html__( 'OpenAI API Key', 'tnc-ai-excerpt-generator'); ?></h3>
            <p><input type="password" name="ai_excerpt_gen_api_key" value="<?php echo get_option('ai_excerpt_gen_api_key'); ?>" /> <br><br>
            <small> <?php echo sprintf( esc_html__("Get your API key from %1s", 'tnc-ai-excerpt-generator'), "<a href='https://platform.openai.com/account/api-keys'>this link</a>" ); ?></small></p>

            <h3><?php echo esc_html__( 'Enable on Post Types', 'tnc-ai-excerpt-generator'); ?></h3>
            <p>
                <?php
                $post_types = get_post_types( array( 'public' => true ), 'objects' );
                $post_types_enabled = get_option('ai_excerpt_gen_post_types_enabled');
                foreach ( $post_types as $post_type ) {
                    $post_type_name = $post_type->name;
                    $post_type_label = $post_type->label;
                    $checked = '';
                    if ( is_array($post_types_enabled) && in_array($post_type_name, $post_types_enabled) ) {
                        $checked = 'checked';
                    }
                    echo "<input type='checkbox' name='ai_excerpt_gen_post_types_enabled[]' value='" . esc_attr( $post_type_name ) . "' " . esc_attr( $checked ) . ">" . esc_html ( $post_type_label ) . "<br>";
                }
                ?>
            </p>
            <?php

            $models = array(
                'text-davinci-002', 'text-davinci-003', 'text-curie-001', 'text-babbage-001', 'text-ada-001'
            );

            if( is_array( $models ) ) { ?>
                <h3><?php echo esc_html__( 'Select Model', 'tnc-ai-excerpt-generator' ); ?></h3>

                <?php 
                $get_model_setting = get_option('ai_excerpt_gen_model');

                if( is_array( $models ) ) {
                    echo "<select name='ai_excerpt_gen_model' required>";
                    echo "<option value=''>". esc_html__( 'Select Model', 'tnc-ai-excerpt-generator' ) ."</option>";
                    foreach( $models as $model ) {
                        $model_name = $model;
                        $model_label = $model;
                        $selected = '';
                        if( $model_name == $get_model_setting ) {
                            $selected = 'selected';
                        }
                        echo "<option value='" . esc_attr( $model_name ) . "' " . esc_attr( $selected ) . ">" . esc_html( $model_label ) . "</option>";
                    }
                    echo "</select>";
                }

                ?>
            <?php } ?>

            <h3><?php echo esc_html__( 'Maximum Token', 'tnc-ai-excerpt-generator' ); ?></h3>
            <p><input type="number" name="ai_excerpt_gen_max_token" value="<?php echo get_option('ai_excerpt_gen_max_token'); ?>" /> <br><br>
            <small> <?php echo sprintf( esc_html__( 'Maximum Number of token to use for each excerpt generation. %1s to find how token counting works.', 'tnc-ai-excerpt-generator' ), '<a href="https://platform.openai.com/tokenizer" target="_blank">Click here</a>' ); ?></small></p>

            <?php submit_button(); ?>
        </form>
    </div>
<?php

}

// Generate a excerpt using OpenAI's GPT-3 language model
function themencode_aig_generate_excerpt( $content ) {

    // Set the OpenAI API endpoint
    $model = get_option('ai_excerpt_gen_model');

    if( empty( $model ) ) {
        $model = 'text-davinci-003';
    }

    $url = 'https://api.openai.com/v1/completions';

    // Set the API request headers and data
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . THEMENCODE_OPENAI_API_KEY,
    );

    $data = array(
        'model' => esc_html( $model ),
        'prompt' => 'Please summarize this article:' . esc_html( $content ),
        'temperature' => 0.5,
        'max_tokens' => intval( THEMENCODE_OPENAI_MAX_TOKEN ),
        'n' => 1,
        'stop' => '\n\n'
    );

    // Send the API request using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = wp_remote_get($ch);
    curl_close($ch);

    //check for curl error
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        return false;
    }

    // Extract the excerpt from the API response
    $json = wp_json_file_decode($response);
    $excerpt = $json->choices[0]->text;

    return $excerpt;
}

// Add metabox for regenerating excerpt
function themencode_aig_add_excerpt_metabox() {

    global $post;
    // get enabled post types
    $post_types_enabled = get_option('ai_excerpt_gen_post_types_enabled');

    // add metabox for each enabled post type
    if( !is_array($post_types_enabled) ) return;

    if( $post->post_status != 'publish' ) return;

    foreach ( $post_types_enabled as $post_type ) {
        add_meta_box(
            'themencode_gen_excerpt_metabox',
            'ThemeNcode AI Excerpt',
            'themencode_aig_excerpt_metabox_callback',
            $post_type,
            'side',
            'high'
        );
    }
}

// Callback function for metabox
function themencode_aig_excerpt_metabox_callback( $post ) {

    if( !is_object($post) ) return;
    if( $post->post_status != 'publish' ) return;

    // Retrieve existing excerpt
    $excerpt = get_post_meta( $post->ID, 'themencode_ai_generated_excerpt', true );
    if( empty($excerpt) ) {
        $excerpt = esc_html__( 'No excerpt generated yet.', 'tnc-ai-excerpt-generator' );
    }
    ?>
    <div>
        <p><strong><?php echo esc_html__( 'Generated Excerpt:', 'tnc-ai-excerpt-generator' ); ?></strong></p>
        <p><?php echo esc_html( $excerpt ); ?></p>
        <button id="themencode-ai-regenerate-excerpt" class="button"><?php echo esc_html__( 'Generate Excerpt', 'tnc-ai-excerpt-generator' ); ?></button>
    </div>
    <script>
        jQuery(document).ready(function($) {
            // Handle regenerate excerpt button click
            $('#themencode-ai-regenerate-excerpt').click(function(e) {
                e.preventDefault();
                var post_id = <?php echo $post->ID; ?>;
                var ajaxurl = '<?php echo admin_url( "admin-ajax.php" ); ?>';
                var data = {
                    action: 'themencode_ai_regenerate_excerpt',
                    post_id: post_id,
                    nonce: '<?php echo wp_create_nonce( 'themencode_ai_regenerate_excerpt' ); ?>'
                };
                $.post(ajaxurl, data, function(response) {
                    // Update excerpt display
                    $('div#themencode_gen_excerpt_metabox p:last-of-type').html(response);
					$('div.editor-post-excerpt textarea').val(response);
                });
            });
        });
    </script>
    <?php
}

// Hook into post editor to add metabox
add_action( 'add_meta_boxes', 'themencode_aig_add_excerpt_metabox' );

// Callback function for regenerate excerpt button
function themencode_aig_regenerate_excerpt_callback() {
    if ( is_numeric( sanitize_text_field ( $_POST['post_id'] ) ) ) {
        $post_id = sanitize_text_field ( $_POST['post_id'] );
    } else {
        wp_die(' Invalid post id');
    }

    // Check nonce
    if ( ! wp_verify_nonce( $_POST['nonce'], 'themencode_ai_regenerate_excerpt' ) ) {
        wp_die( esc_html__( 'Invalid nonce', 'tnc-ai-excerpt-generator' ) );
    }

    $content = get_post_field( 'post_content', $post_id );

    $minumum_word_count = apply_filters( 'themencode_ai_excerpt_gen_minimum_word_count', 50 );

    if( str_word_count( $content ) < $minumum_word_count ) {
        echo esc_html__( 'Content is too short to generate an excerpt. Please ensure there is at least '. $minumum_word_count .' words.', 'tnc-ai-excerpt-generator' );
        wp_die();
    }

    $excerpt = themencode_aig_generate_excerpt( $content );
	
	if( $excerpt ){
		// Save the excerpt as an excerpt
		wp_update_post(array(
			'ID' => $post_id,
			'post_excerpt' => $excerpt,
		));

		update_post_meta( $post_id, 'themencode_ai_generated_excerpt', $excerpt );
		
		echo esc_html( $excerpt );
        wp_die();
	} else {
		echo esc_html__( 'Failed to Generate Excerpt', 'tnc-ai-excerpt-generator');
        wp_die();
	}

    wp_die();
}

// Hook into AJAX action to regenerate excerpt
add_action( 'wp_ajax_themencode_ai_regenerate_excerpt', 'themencode_aig_regenerate_excerpt_callback' );
