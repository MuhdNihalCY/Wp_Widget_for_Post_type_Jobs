<?php

function child_post_type_applicants()
{
    $supports = array(
        //   'title',
        // 'editor',
        // 'author',
        // 'thumbnail',
        // 'excerpt',
        // 'page-attributes',
    );
    $labels = array(
        'name' => _x('Applicants', 'plural'),
        'singular_name' => _x('Applicant', 'singular'),
        'menu_name' => _x('Applicants', 'admin menu'),
        'name_admin_bar' => _x('Applicants', 'admin bar'),
        'add_new' => _x('Add New Applicant', 'add new'),
        'add_new_item' => __('Add New Applicants'),
        'new_item' => __('New Applicants'),
        'edit_item' => __('Edit Applicants'),
        'view_item' => __('View Applicants'),
        'all_items' => __('All Applicants'),
        'search_items' => __('Search Applicants'),
        'not_found' => __('No Applicants found.'),
    );
    $args = array(
        'supports' => $supports,
        'labels' => $labels,
        'public' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'applicants'),
        'has_archive' => true,
        'hierarchical' => false,
        'show_in_menu' => 'edit.php?post_type=jobs',
        // 'capabilities' => array(
        //     'create_posts' => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
        // ),
    );
    register_post_type('applicants', $args);
}
add_action('init', 'child_post_type_applicants');

function submit_job_application()
{
    // Retrieve form data
    $applicant_Name = isset($_POST['applicant_name']) ? sanitize_text_field($_POST['applicant_name']) : '';
    $applicant_Email = isset($_POST['applicant_email']) ? sanitize_email($_POST['applicant_email']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
    $jobId = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;

    // Validate form data
    if (empty($applicant_Name) || empty($applicant_Email) || empty($message) || empty($jobId)) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid data'));
        wp_die();
    }

    // here store the submited data as post as each posts for the this post type
    // Prepare post data
    $post_data = array(
        'post_title' => $applicant_Name,
        'post_content' => $message,
        'post_status' => 'publish',
        'post_type' => 'applicants',
        'post_author' => 1,
        'applicant_name' => $applicant_Name,
        'applicant_email' => $applicant_Email,
        'applicant_cover' => $message,
        'child_post_type' => 'applicants',
        'parent_post_type' => 'jobs',
        'job_id' => $jobId,
        // You can add any other custom fields here
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);  

    // Handle post insertion result
    if ($post_id) {
        // Add meta data for applicant's email and job ID
        update_post_meta($post_id, 'job_id', $jobId);
        update_post_meta($post_id, 'applicant_name', $applicant_Name);
        update_post_meta($post_id, 'applicant_email', $applicant_Email);
        update_post_meta($post_id, 'job_name', get_the_title($jobId));
        update_post_meta($post_id, 'application_status', 'pending');
        update_post_meta($post_id, 'application_cover', $message);
        update_post_meta($post_id, 'post_type', 'jobs');

        echo json_encode(
            array(
                "status" => "success",
                "message" => $message,
                "applicant_Name" => $applicant_Name,
                "applicant_Email" => $applicant_Email,
            )
        );
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Failed to submit application'));
    }

    wp_die();
}
add_action('wp_ajax_submit_job_application', 'submit_job_application');
add_action('wp_ajax_nopriv_submit_job_application', 'submit_job_application');

add_filter('manage_applicants_posts_columns', 'filter_cpt_columns');

function filter_cpt_columns($columns)
{
    // this will add the column to the end of the array
    $columns['job_name'] = __('Applied Job');
    $columns['email'] = __('Email');
    $columns['job_name'] = __('Applied Job');
    $columns['application_status'] = __('Application status');

    return $columns;
}


add_action('manage_posts_custom_column', 'action_custom_columns_content', 10, 2);
function action_custom_columns_content($column_id, $post_id)
{
    //run a switch statement for all of the custom columns created
    switch ($column_id) {
        case 'email':
            echo ($value = get_post_meta($post_id, 'applicant_email', true)) ? $value : '';
            break;
        case 'job_name':
            echo ($value = get_post_meta($post_id, 'job_name', true)) ? $value : '';
            break;
        case 'application_status':
            echo ($value = get_post_meta($post_id, 'application_status', true)) ? $value : '';
            break;
        default:
            echo '';
    }
}

// hide add new applicants
function hd_add_buttons()
{
    global $pagenow;
    if (is_admin()) {
        if ($_GET['post_type'] == 'applicants') {
            echo '<style>.page-title-action{display: none !important;}</style>';
        }
    }
}
add_action('admin_head', 'hd_add_buttons');

// function application_status_metabox()
// {
//     add_meta_box(
//         'application_status',
//         'Application Status',
//         'render_application_status_metabox',
//         'applicants',
//         'side',
//         'high'
//     );
// }
// add_action('add_meta_boxes', 'application_status_metabox');

/*
function render_application_status_metabox($post)
{

    $input_data = get_post_meta($post->ID, 'application_status', true);
     $checkbox_data = get_post_meta($post->ID, 'checkbox_value', true);

    // nonce field for security
    wp_nonce_field('custom_metabox_nonce', 'custom_metabox_nonce');

    // display metabox content
    ?>
    <label>Status</label>
    <select name="application_status">

        <?php
        foreach ($input_data as $key => $value) {
            ?>
            <option value="<?php echo esc_attr($key) ?>" <?php selected($key, $input_data) ?>>
                <?php echo esc_html($value) ?>
            </option>

            <?php
        }
}*/