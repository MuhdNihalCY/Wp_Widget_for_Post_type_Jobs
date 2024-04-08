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
    write_log("Create new application: " . $post_id);

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
        update_post_meta($post_id, 'application_id', $post_id);



        // store applicant data
        global $wpdb;
        $table_name = $wpdb->prefix . 'jm_applicants';
        $date = date('Y-m-d H:i:s');
        $job_name = get_the_title($jobId);

        $query_result = $wpdb->query(
            $wpdb->prepare(
                "
        INSERT INTO $table_name (`post_id`,`date`, `name`, `email`, `cover`, `job_id`, `job_name`, `status`)
        VALUES (%s, %s, %s, %s, %s, %d, %s, %s)",
                $post_id,
                $date,
                $applicant_Name,
                $applicant_Email,
                $message,
                $jobId,
                $job_name,
                'pending'
            )
        );


        if ($query_result !== false) {
            echo json_encode(
                array(
                    "status" => "success",
                    "message" => $message,
                    "applicant_Name" => $applicant_Name,
                    "applicant_Email" => $applicant_Email,
                )
            );
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Failed to submit application. Error: ' . $wpdb->last_error));
        }

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
// function hd_add_buttons()
// {
//     global $pagenow;
//     if (is_admin()) {
//         if ($_GET['post_type'] == 'applicants') {
//             echo '<style>.page-title-action{display: none !important;}</style>';
//         }
//     }
// }
// add_action('admin_head', 'hd_add_buttons');


add_action('updated_post_meta', 'update_application');

function update_application($post_id)
{
    write_log("post ID: " . $post_id);

    // $newStatus = $_POST['meta'];

    if (isset($_POST['meta'])) {
        // 'meta' key exists in $_POST
        $meta_data = $_POST['meta'];


        // Define variables to store values
        $applicant_email = '';
        $applicant_name = '';
        $application_cover = '';
        $application_status = '';
        $application_id = '';

        // Iterate over the array and assign values to variables
        foreach ($meta_data as $meta_item) {
            switch ($meta_item['key']) {
                case 'applicant_email':
                    $applicant_email = $meta_item['value'];
                    break;
                case 'applicant_name':
                    $applicant_name = $meta_item['value'];
                    break;
                case 'application_cover':
                    $application_cover = $meta_item['value'];
                    break;
                case 'application_status':
                    $application_status = $meta_item['value'];
                    break;
                case 'application_id':
                    $application_id = $meta_item['value'];
                    break;
            }
        }
        // store applicant data
        global $wpdb;
        $table_name = $wpdb->prefix . 'jm_applicants';
        $update_query = "UPDATE `$table_name` SET `email`='$applicant_email', `name`= '$applicant_name', `cover`='$application_cover', `status`='$application_status' WHERE `post_id` = '$application_id'";
        $wpdb->query($update_query);

    } else {
        // 'meta' key does not exist in $_POST
        echo "No meta data found in the POST request.";
    }
}

add_action('wp_trash_post', 'application_delete');

function application_delete()
{
    if (isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        write_log("Delete Application ID: " . $post_id);
        global $wpdb;
        $table_name = $wpdb->prefix . 'jm_applicants';
        $update_query = "DELETE FROM $table_name WHERE `post_id` = '$post_id'";
        $wpdb->query($update_query);
    }
}