<?php

function custom_list_page()
{

    if (!class_exists('Link_List_Table')) {

        require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

    }
    class Link_List_Table extends WP_List_Table
    {



        /**
         * Constructor, we override the parent to pass our own arguments
         * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
         */

        function __construct()
        {

            parent::__construct(
                array(

                    'singular' => 'applicant',     //singular name of the listed records

                    'plural' => 'applicants',    //plural name of the listed records

                    'ajax' => false

                )
            );

        }

        /**
         * Add extra markup in the toolbars before or after the list
         * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
         */
        function extra_tablenav($which)
        {
            if ($which == "top") {
                //The code that goes before the table is here
                echo "Hello, I'm before the table";
            }
            if ($which == "bottom") {
                //The code that goes after the table is there
                echo "Hi, I'm after the table";
            }
        }

        /**
         * Define the columns that are going to be used in the table
         * @return array $columns, the array of columns to use with the table
         */
        function get_columns()
        {
            return $columns = array(
                'col_link_id' => __('ID'),
                'col_link_name' => __('Name'),
                'col_link_url' => __('Url'),
                'col_link_description' => __('Description'),
                'col_link_visible' => __('Visible')
            );
        }

        /**
 * Decide which columns to activate the sorting functionality on
 * @return array $sortable, the array of columns that can be sorted by the user
 */
public function get_sortable_columns() {
    return $sortable = array(
       'col_link_id'=>'link_id',
       'col_link_name'=>'link_name',
       'col_link_visible'=>'link_visible'
    );
 }

    }


    $wp_list_table = new Link_List_Table();

    if (isset($_GET['s'])) {

        $wp_list_table->prepare_items($_GET['s']);

    } else {

        $wp_list_table->prepare_items();

    }

}


