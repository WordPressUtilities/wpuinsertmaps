<?php

/*
Plugin Name: WPU Insert Maps
Description: Insert a Google Map to a page - Requires WPU Options & WPU Post Metas
Version: 0.2.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUInsertMaps {
    public function __construct() {
        add_action('plugins_loaded', array(&$this, 'load_translation'));

        /* Boxes */
        add_filter('wpu_options_tabs', array(&$this, 'set_wpu_options_tabs'), 10, 1);
        add_filter('wpu_options_boxes', array(&$this, 'set_wpu_options_boxes'), 10, 1);
        add_filter('wpu_options_fields', array(&$this, 'set_wputh_options_fields'), 10, 1);
        add_filter('wputh_post_metas_boxes', array(&$this, 'set_wputh_post_metas_boxes'), 10, 1);
        add_filter('wputh_post_metas_fields', array(&$this, 'set_wputh_post_metas_fields'), 10, 1);

        /* Load scripts */
        add_action('wp_enqueue_scripts', array(&$this, 'add_theme_scripts'));
        add_action('wp_footer', array(&$this, 'load_apikey'));

        /* Load content */
        add_filter('the_content', array(&$this, 'load_map'));
    }

    public function load_translation() {
        load_plugin_textdomain('wpuinsertmaps', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    }

    /* ----------------------------------------------------------
      Admin
    ---------------------------------------------------------- */
    /* Options
    -------------------------- */

    public function set_wpu_options_tabs($tabs) {
        $tabs['wpuinsertmaps__tab'] = array(
            'name' => __('WPU Insert map', 'wpuinsertmaps'),
            'sidebar' => true // Load in sidebar
        );
        return $tabs;
    }

    public function set_wpu_options_boxes($boxes) {
        $boxes['wpuinsertmaps__box'] = array(
            'name' => __('Google Maps', 'wpuinsertmaps'),
            'tab' => 'wpuinsertmaps__tab'
        );
        return $boxes;
    }

    public function set_wputh_options_fields($options) {
        $options['wpuinsertmaps__key'] = array(
            'label' => __('Maps API Key', 'wpuinsertmaps'),
            'box' => 'wpuinsertmaps__box',
            'help' => sprintf(__('You can get an <a %s href="%s">API key here</a>'), 'target="_blank"', 'https://console.developers.google.com/apis/library/maps-backend.googleapis.com/?project=')
        );
        return $options;
    }

    /* Post metas
    -------------------------- */

    public function set_wputh_post_metas_boxes($boxes) {
        $boxes['wpuinsertmaps__meta_box'] = array(
            'name' => __('WPU Insert map', 'wpuinsertmaps'),
            'post_type' => array('post', 'page')
        );
        return $boxes;
    }

    public function set_wputh_post_metas_fields($fields) {
        $fields['wpuinsertmaps__load_map'] = array(
            'box' => 'wpuinsertmaps__meta_box',
            'type' => 'checkbox',
            'name' => __('Display map', 'wpuinsertmaps')
        );
        $fields['wputh_post_address'] = array(
            'box' => 'wpuinsertmaps__meta_box',
            'type' => 'table',
            'columns' => array(
                'name' => array('type' => 'text', 'name' => __('Name', 'wpuinsertmaps')),
                'lat' => array('type' => 'text', 'name' => __('Latitude', 'wpuinsertmaps')),
                'lng' => array('type' => 'text', 'name' => __('Longitude', 'wpuinsertmaps')),
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
        if (is_singular()) {
            wp_enqueue_style('wpuinsertmaps-style', plugins_url('assets/style.css', __FILE__));
            wp_enqueue_script('wpuinsertmaps-script', plugins_url('assets/script.js', __FILE__), array('jquery'), 1.1, true);
        }
    }

    /* Load api key
    -------------------------- */

    public function load_apikey() {
        if (is_singular()) {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . get_option('wpuinsertmaps__key') . '&callback=wpuinsertmaps_init"async defer></script>';
        }
    }

    /* Load map in content
    -------------------------- */

    public function load_map($content) {
        if (is_singular() && is_main_query() && get_post_meta(get_the_ID(), 'wpuinsertmaps__load_map', 1)) {
            $wputh_post_address = json_encode(get_post_meta(get_the_ID(), 'wputh_post_address', 1));
            $content .= '<div class="wpuinsertmaps-element" data-map="' . esc_attr($wputh_post_address) . '"></div>';
        }
        return $content;
    }

}

$WPUInsertMaps = new WPUInsertMaps();
