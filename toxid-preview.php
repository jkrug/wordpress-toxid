<?php
/**
 * Plugin Name: TOXID Preview
 * Plugin URI: http://toxid.org/
 * Description: Preview for logged in Admin Users via query param.
 * Version: 1.0
 * Author: Joscha Krug
 * Author URI: http://www.marmalade.de
 *
 * Plugin mostly copied from
 * Public Post Preview
 * Original-Author: Dominik Schilling
 * URI: http://wphelper.de/
 * Original Plugin URI: https://dominikschilling.de/wp-plugins/public-post-preview/en/
 *
 * License: GPLv2 or later
 *
 * Previously (2009-2011) maintained by Jonathan Dingman and Matt Martz.
 *
 *	Copyright (C) 2012-2016 Dominik Schilling
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Don't call this file directly.
 * TODO is this really needed?
 */
if ( ! class_exists( 'WP' ) ) {
    die();
}

/**
 * The class which controls the plugin.
 *
 * Inits at 'plugins_loaded' hook.
 */
class TOXID_Preview {

    /**
     * Registers actions and filters.
     *
     * @since 1.0.0
     */
    function init() {

        add_filter( 'pre_get_posts', [ __CLASS__, 'show_public_preview' ] );
        add_filter( 'query_vars', [ __CLASS__, 'add_query_var' ] );
        // Add the query var to WordPress SEO by Yoast whitelist.
        /* TODO is this really needed? */
        add_filter( 'wpseo_whitelist_permalink_vars', [__CLASS__, 'add_query_var' ] );
        add_action('admin_init', [__CLASS__, 'init_admin' ]);
    }

    /**
     * Registers the input field for the preview password
     */
    function init_admin()
    {
        add_settings_section('toxid-settings', 'TOXID', '__return_false', 'general');
        register_setting('general', 'toxid_preview_password');
        add_settings_field('toxid_preview_password', ' Password for previews', [__CLASS__, 'admin_toxid_preview_password_field'], 'general', 'toxid-settings');

    }

    /**
     * The input field for the preview password
     */
    function admin_toxid_preview_password_field()
    {
        $pw = get_option('toxid_preview_password');
        echo "<input name='toxid_preview_password' '.$pw ? value=$pw '.>";
    }

    /**
     * Registers the new query var `toxid-preview`.
     *
     * @since 2.1.0
     *
     * @param  array $qv Existing list of query variables.
     * @return array List of query variables.
     */
    public static function add_query_var( $qv ) {
        $qv[] = 'toxid-preview';

        return $qv;
    }

    /**
     * Registers the filter to handle a public preview.
     *
     * Filter will be set if it's the main query, a preview, a singular page
     * and the query var `_ppp` exists.
     *
     * @since 2.0.0
     *
     * @param object $query The WP_Query object.
     * @return object The WP_Query object, unchanged.
     */
    public static function show_public_preview( $query ) {
        if (
            $query->is_main_query() &&
            $query->is_preview() &&
            $query->is_singular() &&
            $query->get( 'toxid-preview' )
        ) {
            add_filter( 'posts_results', [ __CLASS__, 'set_post_to_publish' ], 10, 2 );
        }

        return $query;
    }

    /**
     * Checks if a public preview is available and allowed.
     * Verifies the nonce and if the post id is registered for a public preview.
     *
     * @since 2.0.0
     *
     * @param int $post_id The post id.
     * @return bool True if a public preview is allowed, false on a failure.
     */
    private static function is_public_preview_available( $post_id ) {

        if ( empty( $post_id ) ) {
            return false;
        }

        if ( ! self::verify_nonce( get_query_var( 'toxid-preview' )) ) {
            wp_die( __( 'Your preview settings are incorrect. Please verify the password in OXID and Wordpress!', 'public-post-preview' ) );
        }

        return true;
    }

    /**
     * Sets the post status of the first post to publish, so we don't have to do anything
     * *too* hacky to get it to load the preview.
     *
     * @since 2.0.0
     *
     * @param  array $posts The post to preview.
     * @return array The post that is being previewed.
     */
    public static function set_post_to_publish( $posts ) {
        // Remove the filter again, otherwise it will be applied to other queries too.
        remove_filter( 'posts_results', [ __CLASS__, 'set_post_to_publish' ], 10 );

        if ( empty( $posts ) ) {

            return;
        }

        $post_id = $posts[0]->ID;

        if ( self::is_public_preview_available( $post_id ) ) {
            // Set post status to publish so that it's visible.
            $posts[0]->post_status = 'publish';

            // Disable comments and pings for this post.
            add_filter( 'comments_open', '__return_false' );
            add_filter( 'pings_open', '__return_false' );
            add_filter( 'wp_link_pages_link', [ __CLASS__, 'filter_wp_link_pages_link' ], 10, 2 );
        }

        return $posts;
    }

    /**
     * Verifies that correct nonce was used with time limit. Without an UID.
     *
     * @since 1.0.0
     *
     * @param string $nonce Nonce that was used in the form to verify.
     * @return bool Whether the nonce check passed or failed.
     * @internal param int|string $action Should give context to what is taking place and be the same when nonce was created.
     */
    private static function verify_nonce( $nonce ) {

        $previewPW = get_option('toxid_preview_password');

        if ( $nonce === $previewPW ) {
            return 1;
        }

        return false;
    }
}

add_action( 'plugins_loaded', ['TOXID_Preview', 'init'] );