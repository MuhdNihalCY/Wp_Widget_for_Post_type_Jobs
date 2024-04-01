<?php
/*
Plugin Name: Jobs Post_types
Description: Create a custom job listing post type.
Version: 1.0
Author: Nihal
*/

// Register custom post type
function post_type_jobs() {
    $supports = array(
        'title',
        'editor',
        'author',
        'thumbnail',
        'excerpt',
    );
    $labels = array(
        'name' => _x('Jobs', 'plural'),
        'singular_name' => _x('Job', 'singular'),
        'menu_name' => _x('Jobs', 'admin menu'),
        'name_admin_bar' => _x('Jobs', 'admin bar'),
        'add_new' => _x('Add New Job', 'add new'),
        'add_new_item' => __('Add New Jobs'),
        'new_item' => __('New Jobs'),
        'edit_item' => __('Edit Jobs'),
        'view_item' => __('View Jobs'),
        'all_items' => __('All Jobs'),
        'search_items' => __('Search Jobs'),
        'not_found' => __('No Jobs found.'),
    );
    $args = array(
        'supports' => $supports,
        'labels' => $labels,
        'public' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'jobs'),
        'has_archive' => true,
        'hierarchical' => false,
    );
    register_post_type('jobs', $args);
}
add_action('init', 'post_type_jobs');

// Define a widget class
class Latest_Jobs_Widget extends WP_Widget {
    // Constructor
    public function __construct() {
        parent::__construct(
            'latest_jobs_widget', // Base ID
            'Latest Jobs Widget',  // Name
            array('description' => 'A widget to display the latest jobs.') // Description
        );
    }
    // Widget content output
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . 'Latest Jobs' . $args['after_title'];

        $jobs_query = new WP_Query(
            array(
                'post_type' => 'jobs',
                'posts_per_page' => 5, // Number of jobs to display
                'orderby' => 'date',
                'order' => 'DESC',
            )
        );

        if ($jobs_query->have_posts()):
            echo '<ul>';
            while ($jobs_query->have_posts()):
                $jobs_query->the_post();
                echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            endwhile;
            echo '</ul>';
            wp_reset_postdata();
        else:
            echo 'No jobs found.';
        endif;

        echo $args['after_widget'];
    }
}

function register_latest_jobs_widget() {
    register_widget('Latest_Jobs_Widget');
}
add_action('widgets_init', 'register_latest_jobs_widget');

function job_application_form($content) {
    global $post;
    if ($post->post_type === 'jobs') {
        $job_id = $post->ID;
        $form_html = '
        <div class="job_application_form">
            <form id="job_application_form_' . $job_id . '" class="job-application-form" data-job-id="' . $job_id . '">
                <label for="applicant_name_' . $job_id . '">Name:</label>
                <input type="text" name="applicant_name" id="applicant_name_' . $job_id . '" value=""><br>

                <label for="applicant_email_' . $job_id . '">Email:</label>
                <input type="email" name="applicant_email" id="applicant_email_' . $job_id . '" value=""><br>

                <label for="message_' . $job_id . '">Message:</label><br>
                <textarea name="message" id="message_' . $job_id . '" cols="30" rows="5"></textarea><br>

                <input type="hidden" name="job_id" value="' . $job_id . '">
                <input type="hidden" name="action" value="submit_job_application">
                <input type="submit" value="Submit Application">
            </form>
            <div id="application_preview">
                <h2>Application Preview</h2>
            </div>
        </div>
        ';
        $content = $content . $form_html;
    }
    return $content;
}
add_filter('the_content', 'job_application_form');

function styles_enqueuer() {
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('style', $plugin_url . "/css/style.css");
}

add_action('init', 'styles_enqueuer');

function script_enqueuer() {
    // Register the JS file with a unique handle, file location, and an array of dependencies
    wp_register_script("job-application", plugin_dir_url(__FILE__) . '/js/job-application.js', array('jquery'));

    // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
    wp_localize_script('job-application', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

    // enqueue jQuery library and the script you registered above   
    wp_enqueue_script('jquery');
    wp_enqueue_script('job-application');
}
add_action('init', 'script_enqueuer');
function submit_job_application() {
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

    // Store the application data
    $job_applications = get_post_meta($jobId, 'job_applications', true);
    $job_applications = array(
        "applicant_Name" => $applicant_Name,
        "applicant_Email" => $applicant_Email,
        "message" => $message
    );

    update_post_meta($jobId, "job_applications", $job_applications);

    // ob_start();
    // display_job_applications($jobId);
    // $output = ob_get_contents();
    // ob_end_clean();

    // return success response
    echo json_encode(
        array(
            "status" => "success",
            "message" => $message,
            "applicant_Name" => $applicant_Name,
            "applicant_Email" => $applicant_Email
        )
    );
    wp_die();
}

add_action('wp_ajax_submit_job_application', 'submit_job_application'); // This is for authenticated users
add_action('wp_ajax_noprivsubmit_job_application', 'submit_job_application'); // This is for unauthenticated users.