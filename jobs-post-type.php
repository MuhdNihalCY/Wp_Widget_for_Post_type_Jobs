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
            <div id="application_preview"> </div>
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
    wp_localize_script('job-application', 'ajax_url', array('ajaxurl' => admin_url('admin-ajax.php')));

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

    // get all job applications
    $job_applications = get_post_meta($jobId, 'job_applications', true);
    // If $job_applications is not an array or is empty, initialize it as an empty array
    if (!is_array($job_applications)) {
        $job_applications = array();
    }

    // Create a new job application array
    $new_job_application = array(
        "applicant_Name" => $applicant_Name,
        "applicant_Email" => $applicant_Email,
        "message" => $message,
        "ID" => $jobId
    );

    $job_applications[] = $new_job_application;
    update_post_meta($jobId, "job_applications", $job_applications);

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
add_action('wp_ajax_submit_job_application', 'submit_job_application');
add_action('wp_ajax_nopriv_submit_job_application', 'submit_job_application');

function add_job_application_meta_box() {
    add_meta_box(
        'job_application_meta_box',
        'Job Applications',
        'render_job_application_meta_box',
        'jobs',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_job_application_meta_box');

function render_job_application_meta_box($post) {
    // Retrieve job applications
    $job_applications = get_post_meta($post->ID, 'job_applications', true);
    ?>
    <div class="job-applications-container">
        <?php if (!empty($job_applications)): ?>
            <ul>
                <?php foreach ($job_applications as $index => $application): ?>
                    <li>
                        <strong>Name:</strong>
                        <?php echo esc_html($application['applicant_Name']); ?><br>
                        <strong>Email:</strong>
                        <?php echo esc_html($application['applicant_Email']); ?><br>
                        <strong>Message:</strong>
                        <?php echo esc_html($application['message']); ?><br>
                        <input type="button" class="application_del_btn" data-job-id="<?php echo esc_html($post->ID); ?>"
                            data-application-index="<?php echo esc_html($index); ?>"
                            data-applicant-name="<?php echo esc_html($application['applicant_Name']); ?>"
                            data-applicant-email="<?php echo esc_html($application['applicant_Email']); ?>" value="Delete">
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No applications submitted for this job yet.</p>
        <?php endif; ?>
    </div>
    <?php
}

function delete_job_application() {
    // Retrieve form data
    $applicant_Name = isset($_POST['applicant_name']) ? sanitize_text_field($_POST['applicant_name']) : '';
    $applicant_Email = isset($_POST['applicant_email']) ? sanitize_email($_POST['applicant_email']) : '';
    $jobId = isset($_POST['job_id']) ? absint($_POST['job_id']) : 0;
    $application_index = isset($_POST['application_index']) ? sanitize_text_field($_POST['application_index']) : '';

    // Validate form data
    if (empty($applicant_Name) || empty($applicant_Email) || empty($jobId)) {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid data'));
        wp_die();
    }

    // get all job applications
    $job_applications = get_post_meta($jobId, 'job_applications', true);
    // Filter out applications with matching applicant name and email
    $job_applications = array_filter($job_applications, function ($application) use ($applicant_Name, $applicant_Email) {
        return $application['applicant_Name'] !== $applicant_Name || $application['applicant_Email'] !== $applicant_Email;
    });

    update_post_meta($jobId, "job_applications", $job_applications);

    // return success response
    echo json_encode(
        array(
            "status" => "success",
            "applicant_Name" => $applicant_Name,
            "applicant_Email" => $applicant_Email
        )
    );
    wp_die();
}
add_action('wp_ajax_delete_job_application', 'delete_job_application');
add_action('wp_ajax_nopriv_delete_job_application', 'delete_job_application');