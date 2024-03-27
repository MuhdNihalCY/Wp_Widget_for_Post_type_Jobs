<?php
/*
Plugin Name: Jobs Post_types
Description: Create a custom job listing post type.
Version: 1.0
Author: Nihal
*/

// Register custom post type
function post_type_jobs(){
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
            array( 'description' => 'A widget to display the latest jobs.' ) // Description
        );
    }
    // Widget content output
    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        echo $args['before_title'] . 'Latest Jobs' . $args['after_title'];
        
        $jobs_query = new WP_Query( array(
            'post_type' => 'jobs',
            'posts_per_page' => 5, // Number of jobs to display
            'orderby' => 'date',
            'order' => 'DESC',
        ) );
        
        if ( $jobs_query->have_posts() ) :
            echo '<ul>';
            while ( $jobs_query->have_posts() ) : $jobs_query->the_post();
                echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            endwhile;
            echo '</ul>';
            wp_reset_postdata();
        else :
            echo 'No jobs found.';
        endif;

        echo $args['after_widget'];
    }
}

// Register the widget
function register_latest_jobs_widget() {
    register_widget( 'Latest_Jobs_Widget' );
}
add_action( 'widgets_init', 'register_latest_jobs_widget' );