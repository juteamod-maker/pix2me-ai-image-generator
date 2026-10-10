<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Registers the 'AI Image History' Custom Post Type.
 */
function ai_image_generator_register_history_cpt() {

    $labels = array(
        'name'                  => _x( 'Pix2me AI Image Histories', 'Post type general name', 'pix2me-ai-image-generator' ), // Updated
        'singular_name'         => _x( 'Pix2me AI Image History', 'Post type singular name', 'pix2me-ai-image-generator' ), // Updated
        'menu_name'             => _x( 'Pix2me AI History', 'Admin Menu text', 'pix2me-ai-image-generator' ), // Updated
        'name_admin_bar'        => _x( 'AI Image History', 'Add New on Toolbar', 'pix2me-ai-image-generator' ),
        'add_new'               => __( 'Add New', 'pix2me-ai-image-generator' ),
        'add_new_item'          => __( 'Add New History Entry', 'pix2me-ai-image-generator' ),
        'new_item'              => __( 'New History Entry', 'pix2me-ai-image-generator' ),
        'edit_item'             => __( 'Edit History Entry', 'pix2me-ai-image-generator' ),
        'view_item'             => __( 'View History Entry', 'pix2me-ai-image-generator' ),
        'all_items'             => __( 'All History Entries', 'pix2me-ai-image-generator' ),
        'search_items'          => __( 'Search History Entries', 'pix2me-ai-image-generator' ),
        'parent_item_colon'     => __( 'Parent History Entries:', 'pix2me-ai-image-generator' ),
        'not_found'             => __( 'No history entries found.', 'pix2me-ai-image-generator' ), // Updated
        'not_found_in_trash'    => __( 'No history entries found in Trash.', 'pix2me-ai-image-generator' ), // Updated
        'featured_image'        => _x( 'Generated Image', 'Overrides the "Featured Image" phrase for this post type. Added in 4.3', 'pix2me-ai-image-generator' ), // Updated
        'set_featured_image'    => _x( 'Set generated image', 'Overrides the "Set featured image" phrase for this post type. Added in 4.3', 'pix2me-ai-image-generator' ),
        'remove_featured_image' => _x( 'Remove generated image', 'Overrides the "Remove featured image" phrase for this post type. Added in 4.3', 'pix2me-ai-image-generator' ),
        'use_featured_image'    => _x( 'Use as generated image', 'Overrides the "Use as featured image" phrase for this post type. Added in 4.3', 'pix2me-ai-image-generator' ),
        'archives'              => _x( 'History Archives', 'The post type archive label used in nav menus. Default "Post Archives". Added in 4.4', 'pix2me-ai-image-generator' ),
        'insert_into_item'      => _x( 'Insert into history entry', 'Overrides the "Insert into post"/"Insert into page" phrase (used when inserting media into a post). Added in 4.4', 'pix2me-ai-image-generator' ),
        'uploaded_to_this_item' => _x( 'Uploaded to this history entry', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase (used when viewing media attached to a post). Added in 4.4', 'pix2me-ai-image-generator' ),
        'filter_items_list'     => _x( 'Filter history entries list', 'Screen reader text for the filter links heading on the post type listing screen. Default "Filter posts list"/"Filter pages list". Added in 4.4', 'pix2me-ai-image-generator' ),
        'items_list_navigation' => _x( 'History entries list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default "Posts list navigation"/"Pages list navigation". Added in 4.4', 'pix2me-ai-image-generator' ),
        'items_list'            => _x( 'History entries list', 'Screen reader text for the items list heading on the post type listing screen. Default "Posts list"/"Pages list". Added in 4.4', 'pix2me-ai-image-generator' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // Not public on front-end unless needed
        'publicly_queryable' => false,
        'show_ui'            => true, // Show in admin UI
        'show_in_menu'       => true, // Show in admin menu
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-images-alt2', // Choose an appropriate icon
        'supports'           => array( 'title', 'author', 'custom-fields', 'thumbnail' ), // 'thumbnail' for the generated image
        'show_in_rest'       => true, // Enable Gutenberg editor and REST API access if needed
    );

    register_post_type( 'pix2me_ai_history', $args ); // Changed from pix2me_ai_image_history to pix2me_ai_history
}
add_action( 'init', 'ai_image_generator_register_history_cpt' );

/**
 * Adds custom meta boxes for storing generation parameters.
 */
function ai_image_generator_add_history_meta_boxes() {
    add_meta_box(
        'pix2me_ai_image_history_details', // Update meta box ID
        __( 'Generation Details', 'pix2me-ai-image-generator' ), // Update text domain
        'pix2me_ai_image_generator_render_history_meta_box', // Update callback function name
        'pix2me_ai_history', // Update CPT slug
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'ai_image_generator_add_history_meta_boxes' );

/**
 * Renders the content of the meta box.
 *
 * @param WP_Post $post The post object.
 */
function ai_image_generator_render_history_meta_box( $post ) {
    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'ai_image_history_save_meta_box_data', 'ai_image_history_meta_box_nonce' );

    // Use get_post_meta to retrieve an existing value from the database.
    $prompt = get_post_meta( $post->ID, '_ai_prompt', true );
    $parameters = get_post_meta( $post->ID, '_ai_parameters', true ); // Store parameters as serialized array or JSON
    $api_response = get_post_meta( $post->ID, '_ai_api_response', true ); // Store raw API response if needed

    ?>
    <p>
        <label for="ai_prompt"><?php esc_html_e( 'Prompt:', 'pix2me-ai-image-generator' ); ?></label><br />
        <textarea id="ai_prompt" name="ai_prompt" rows="4" style="width:100%;" readonly><?php echo esc_textarea( $prompt ); ?></textarea>
    </p>
    <p>
        <label for="ai_parameters"><?php esc_html_e( 'Parameters:', 'pix2me-ai-image-generator' ); ?></label><br />
        <textarea id="ai_parameters" name="ai_parameters" rows="4" style="width:100%;" readonly><?php echo esc_textarea( is_array($parameters) || is_object($parameters) ? json_encode($parameters, JSON_PRETTY_PRINT) : $parameters ); ?></textarea>
    </p>
     <p>
        <label for="ai_api_response"><?php esc_html_e( 'API Response (Raw):', 'pix2me-ai-image-generator' ); ?></label><br />
        <textarea id="ai_api_response" name="ai_api_response" rows="6" style="width:100%;" readonly><?php echo esc_textarea( is_array($api_response) || is_object($api_response) ? json_encode($api_response, JSON_PRETTY_PRINT) : $api_response ); ?></textarea>
    </p>
    <?php
    // Note: We are making these fields readonly in the meta box as they are historical data.
    // Saving logic is still needed if you decide to make them editable or for initial creation.
}

/**
 * Saves the custom meta box data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function ai_image_generator_save_history_meta_box_data( $post_id ) {

    // Check if our nonce is set.
    if ( ! isset( $_POST['ai_image_history_meta_box_nonce'] ) ) {
        return;
    }

    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $_POST['ai_image_history_meta_box_nonce'], 'ai_image_history_save_meta_box_data' ) ) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check the user's permissions.
    if ( isset( $_POST['post_type'] ) && 'pix2me_ai_history' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
         return; // Not our CPT
    }

    /* OK, it's safe for us to save the data now. */

    // Sanitize user input and update the meta fields in the database.
    // Note: Since we made the fields readonly in the render function, direct saving from POST might not be needed
    // unless you are creating these posts programmatically. If creating programmatically, use update_post_meta().

    /* Example if fields were editable:
    if ( isset( $_POST['ai_prompt'] ) ) {
        update_post_meta( $post_id, '_ai_prompt', sanitize_textarea_field( $_POST['ai_prompt'] ) );
    }
    if ( isset( $_POST['ai_parameters'] ) ) {
        // Consider sanitizing JSON/serialized data appropriately
        update_post_meta( $post_id, '_ai_parameters', sanitize_textarea_field( $_POST['ai_parameters'] ) );
    }
     if ( isset( $_POST['ai_api_response'] ) ) {
        update_post_meta( $post_id, '_ai_api_response', sanitize_textarea_field( $_POST['ai_api_response'] ) );
    }
    */
}
// Temporarily disable saving from meta box as fields are readonly
// add_action( 'save_post', 'ai_image_generator_save_history_meta_box_data' );

/**
 * Adds custom columns to the AI Image History list table.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function ai_image_generator_set_custom_edit_history_columns($columns) {
    // Remove unnecessary columns
    unset($columns['date']);

    $new_columns = array();
    $new_columns['cb'] = $columns['cb']; // Checkbox
    $new_columns['thumbnail'] = __( 'Image', 'pix2me-ai-image-generator' );
    $new_columns['title'] = __( 'Title (ID)', 'pix2me-ai-image-generator' ); // Keep title, maybe rename
    $new_columns['prompt'] = __( 'Prompt', 'pix2me-ai-image-generator' );
    $new_columns['author'] = __( 'User', 'pix2me-ai-image-generator' );
    $new_columns['generation_date'] = __( 'Date', 'pix2me-ai-image-generator' ); // Add back date with custom key

    return array_merge($new_columns, $columns);
}
add_filter( 'manage_pix2me_ai_history_posts_columns', 'ai_image_generator_set_custom_edit_history_columns' );

/**
 * Displays content for custom columns in the AI Image History list table.
 *
 * @param string $column The name of the column to display.
 * @param int    $post_id The ID of the current post.
 */
function ai_image_generator_custom_history_column( $column, $post_id ) {
    switch ( $column ) {
        case 'thumbnail':
            if ( has_post_thumbnail( $post_id ) ) {
                echo '<a href="' . get_edit_post_link( $post_id ) . '">';
                the_post_thumbnail( array( 60, 60 ) ); // Display a small thumbnail
                echo '</a>';
            } else {
                echo '—';
            }
            break;

        case 'prompt':
            $prompt = get_post_meta( $post_id, '_ai_prompt', true );
            echo esc_html( wp_trim_words( $prompt, 15, '...' ) ); // Display truncated prompt
            break;

        case 'generation_date':
             echo get_the_date( '', $post_id ); // Use WordPress date formatting
             echo '<br>';
             echo get_the_time( '', $post_id );
            break;
    }
}
add_action( 'manage_pix2me_ai_history_posts_custom_column', 'ai_image_generator_custom_history_column', 10, 2 );

/**
 * Make custom columns sortable.
 *
 * @param array $columns Existing sortable columns.
 * @return array Modified sortable columns.
 */
function ai_image_generator_make_history_columns_sortable( $columns ) {
    $columns['author'] = 'author';
    $columns['generation_date'] = 'date'; // Sort by the original post date
    return $columns;
}
add_filter( 'manage_edit-pix2me_ai_history_sortable_columns', 'ai_image_generator_make_history_columns_sortable' );

?>