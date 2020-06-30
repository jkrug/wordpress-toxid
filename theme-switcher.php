<?php

/*
Plugin Name: TOXID Themeswitcher
Plugin URI: http://toxid.org/
Description: Switch theme for TOXID-cURL.
Version: 1.1
Author: Joscha Krug
Author URI: http://www.marmalade.de

mostly copied from Theme-Switcher-Plugin by
Author: Ryan Boren
Author URI: http://ryan.boren.me/

Adapted from Ryan Boren theme switcher.
http://ryan.boren.me/

*/


class ToxidThemeSwitcher {

    /** @var string|false */
    protected $theme_param = null;

    /** @var WP_Theme|false */
    protected $theme = null;

    function __construct()
    {
        add_filter('stylesheet', array(&$this, 'get_stylesheet'));
        add_filter('template', array(&$this, 'get_template'));
        add_filter('preview_page_link', array(&$this, 'add_preview_theme'));
        add_filter('preview_post_link', array(&$this, 'add_preview_theme'));
        add_action('admin_init', array(&$this, 'init_admin'));
    }

    function add_preview_theme($link)
    {
        $theme = urlencode(get_option('toxid_preview_theme'));
        $link .= (strpos($link, '?') === false ? '?' : '&') . 'wptheme=' . $theme;
        return $link;
    }

    function init_admin()
    {
        register_setting('general', 'toxid_preview_theme');
        add_settings_section('toxid-settings', 'TOXID', '__return_false', 'general');
        add_settings_field('toxid_preview_theme', 'Theme used for previews', array(&$this, 'admin_toxid_preview_theme_field'), 'general', 'toxid-settings');
    }

    function admin_toxid_preview_theme_field()
    {
        $themes = array_keys(wp_get_themes());
        $currentTheme = get_option('toxid_preview_theme');
        echo '<select name="toxid_preview_theme">';
        echo '<option>' . __('None') . '</option>';
        foreach ($themes as $theme) {
            printf('<option value="%s" %s>%s</option>', esc_attr($theme), ($theme == $currentTheme ? 'selected' : ''), esc_html($theme));
        }
        echo '</select>';
    }

    function get_stylesheet($stylesheet = '')
    {
        if ($theme = $this->get_theme()) {
            return $theme['Stylesheet'];
        }
        return $stylesheet;
    }

    function get_template($template) {
        if ($theme = $this->get_theme()) {
            return $theme['Template'];
        }
        return $template;
    }

    /**
     * set theme-switch url param
     * default value is false
     *
     * @return string|false
     */
    protected function _get_theme_param()
    {
        if ($this->theme_param === null) {
            $this->theme_param = !empty($_GET['wptheme']) ? $_GET['wptheme'] : false;
        }
        return $this->theme_param;
    }

    /**
     * returns requested WP_Theme object if set
     * default value is false
     *
     * @return WP_Theme|bool
     */
    public function get_theme()
    {
        if ($this->theme === null) {
            $this->theme = false;
            if ($theme_name = $this->_get_theme_param()) {
                /** @var array $themes */
                $themes = wp_get_themes();
                // check if theme is set, but don't let people peek at unpublished themes.
                if (isset($themes[$theme_name]) && !(isset($themes[$theme_name]['Status']) && $themes[$theme_name]['Status'] != 'publish')) {
                    $this->theme = $themes[$theme_name];
                }
            }
        }
        return $this->theme;
    }

}

$theme_switcher = new ToxidThemeSwitcher();
