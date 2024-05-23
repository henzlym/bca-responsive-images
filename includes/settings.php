<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
if ( !class_exists('BCA_Responsive_Images_Settings' ) ):
class BCA_Responsive_Images_Settings {

    private $settings_api;

    function __construct() {
        $this->settings_api = new BCA_Responsive_Images_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
		add_options_page( 'Responsive Images', 'Responsive Images', 'delete_posts', 'bca_responsive_images', array($this, 'plugin_page') );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'bca_responsive_images_basics',
                'title' => __( 'Basic Settings', 'wedevs' )
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
			'bca_responsive_images_basics' => array(
				array(
                    'name'    => 'content_types',
                    'label'   => __( 'Content types', 'wedevs' ),
                    'desc'    => __( 'Enable responsive images for content types', 'wedevs' ),
                    'type'    => 'multicheck',
                    'default' => array('post' => 'post'),
                    'options' => array(
                        'post'   => 'post',
                        'page'   => 'page'
                    )
                ),
				array(
                    'name'    => 'in_content',
                    'label'   => __( 'In-content', 'wedevs' ),
                    'desc'    => __( 'Allow in-content responive images', 'wedevs' ),
                    'type'    => 'radio',
					'default' => 0,
                    'options' => array(
                        true => 'Yes',
                        false  => 'No'
                    )
                )
			)
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
new BCA_Responsive_Images_Settings;
endif;
