<?php

/*
Plugin Name: WPU Insert Maps
Description: Insert a Google Map to a page - Requires WPU Options & WPU Post Metas
Version: 0.6.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUInsertMaps {
    private $version = '0.6.0';
    private $post_types = array('post', 'page');
    private $settings_values = array();

    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
        add_action('init', array(&$this, 'init'));

        /* Boxes */
        add_filter('wputh_post_metas_boxes', array(&$this, 'set_wputh_post_metas_boxes'), 10, 1);
        add_filter('wputh_post_metas_fields', array(&$this, 'set_wputh_post_metas_fields'), 10, 1);

        /* Load scripts */
        add_action('wp_enqueue_scripts', array(&$this, 'add_theme_scripts'));
        add_action('wp_footer', array(&$this, 'load_apikey'));

        /* Load content */
        add_filter('the_content', array(&$this, 'load_map'));
    }

    public function plugins_loaded() {
        /* Translation */
        load_plugin_textdomain('wpuinsertmaps', false, dirname(plugin_basename(__FILE__)) . '/lang/');
        /* Post types */
        $post_types = $this->post_types;
        $this->post_types = apply_filters('wpuinsertmaps__post_types', $this->post_types);
        if (!is_array($this->post_types)) {
            $this->post_types = $post_types;
        }
    }

    public function init() {
        $this->settings_details = array(
            'plugin_id' => 'wpuinsertmaps',
            'option_id' => 'wpuinsertmaps_options',
            'plugin_name' => __('WPU Insert map', 'wpuinsertmaps'),
            'create_page' => true,
            'sections' => array(
                'api' => array(
                    'name' => __('API', 'wpuinsertmaps')
                )
            )
        );
        $this->settings = array(
            'api_key' => array(
                'label' => __('Maps API Key', 'wpuinsertmaps'),
                'help' => sprintf(__('You can get an <a %s href="%s">API key here</a>', 'wpuinsertmaps'), 'target="_blank"', 'https://console.developers.google.com/apis/library/maps-backend.googleapis.com/?project=')
            )
        );

        if (is_admin()) {
            include dirname(__FILE__) . '/inc/WPUBaseMessages/WPUBaseMessages.php';
            $this->messages = new \wpuinsertmaps\WPUBaseMessages($this->settings_details['plugin_id']);

            include dirname(__FILE__) . '/inc/WPUBaseSettings/WPUBaseSettings.php';
            new \wpuinsertmaps\WPUBaseSettings($this->settings_details, $this->settings);
        }

        $this->settings_values = $this->get_settings();
    }

    /* ----------------------------------------------------------
      Admin
    ---------------------------------------------------------- */

    /* Post metas
    -------------------------- */

    public function set_wputh_post_metas_boxes($boxes) {
        $boxes['wpuinsertmaps__meta_box'] = array(
            'name' => __('WPU Insert map', 'wpuinsertmaps'),
            'post_type' => $this->post_types
        );
        return $boxes;
    }

    public function set_wputh_post_metas_fields($fields) {
        $fields['wpuinsertmaps__load_map'] = array(
            'box' => 'wpuinsertmaps__meta_box',
            'type' => 'checkbox',
            'name' => __('Display map', 'wpuinsertmaps')
        );
        $fields['wpuinsertmaps__map_position'] = array(
            'box' => 'wpuinsertmaps__meta_box',
            'type' => 'select',
            'name' => __('Position', 'wpuinsertmaps'),
            'datas' => array(
                'under' => __('Under Content', 'wpuinsertmaps'),
                'over' => __('Over Content', 'wpuinsertmaps')
            )
        );
        $fields['wpuinsertmaps__markers'] = array(
            'box' => 'wpuinsertmaps__meta_box',
            'type' => 'table',
            'table_maxline' => 99,
            'name' => __('Markers', 'wpuinsertmaps'),
            'columns' => array(
                'name' => array('type' => 'text', 'name' => __('Name', 'wpuinsertmaps')),
                'lat' => array('type' => 'text', 'name' => __('Latitude', 'wpuinsertmaps')),
                'lng' => array('type' => 'text', 'name' => __('Longitude', 'wpuinsertmaps')),
                'link' => array('type' => 'url', 'name' => __('URL', 'wpuinsertmaps')),
                'iconType' => array('type' => 'select', 'name' => __('Color', 'wpuinsertmaps'), 'datas' => array(
                    'red' => __('Red', 'wpuinsertmaps'),
                    'yellow' => __('Yellow', 'wpuinsertmaps'),
                    'blue' => __('Blue', 'wpuinsertmaps'),
                    'green' => __('Green', 'wpuinsertmaps'),
                    'ltblue' => __('Light blue', 'wpuinsertmaps'),
                    'orange' => __('Orange', 'wpuinsertmaps'),
                    'pink' => __('Pink', 'wpuinsertmaps'),
                    'purple' => __('Purple', 'wpuinsertmaps')
                ))
            )
        );
        return $fields;
    }

    /* ----------------------------------------------------------
      Content
    ---------------------------------------------------------- */
    /* Load scripts
    -------------------------- */

    public function add_theme_scripts() {
        if (is_singular($this->post_types)) {
            wp_enqueue_style('wpuinsertmaps-style', plugins_url('assets/style.css', __FILE__), array(), $this->version);
            wp_enqueue_script('wpuinsertmaps-script', plugins_url('assets/script.js', __FILE__), array('jquery'), $this->version, true);
        }
    }

    /* Load api key
    -------------------------- */

    public function load_apikey() {
        if (is_singular($this->post_types)) {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . $this->settings_values['api_key'] . '&callback=wpuinsertmaps_init"async defer></script>';
        }
    }

    /* Load map in content
    -------------------------- */

    public function load_map($content) {
        if (is_singular($this->post_types) && is_main_query() && get_post_meta(get_the_ID(), 'wpuinsertmaps__load_map', 1)) {
            $wpuinsertmaps__markers = json_encode(get_post_meta(get_the_ID(), 'wpuinsertmaps__markers', 1));
            $wpuinsertmaps__map_position = get_post_meta(get_the_ID(), 'wpuinsertmaps__map_position', 1);
            $content_map = '<div class="wpuinsertmaps-element" data-map="' . esc_attr($wpuinsertmaps__markers) . '"></div>';
            if ($wpuinsertmaps__map_position == 'over') {
                $content = $content_map . $content;
            } else {
                $content .= $content_map;
            }
        }
        return $content;
    }

    /* Get settings
    -------------------------- */

    public function get_settings() {
        if (!isset($this->settings) || !is_array($this->settings)) {
            return array();
        }
        $settings = get_option($this->settings_details['option_id']);
        if (!is_array($settings)) {
            $settings = array();
        }
        foreach ($this->settings as $key => $setting) {
            if (!isset($settings[$key])) {
                $settings[$key] = false;
            }
        }
        return $settings;
    }

}

$WPUInsertMaps = new WPUInsertMaps();
