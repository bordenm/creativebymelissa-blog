<?php
/**
 * Plugin Name: Pixel Theme — Sticky Categories Sidebar
 * Description: Adds a sticky left sidebar listing post categories on every front-end page.
 * Version: 1.0.0
 * Author: Creative by Melissa
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_body_open', function () {
    // Only categories that contain at least one post.
    $cats = get_categories( array(
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => true,
    ) );

    if ( empty( $cats ) ) {
        return;
    }

    // Figure out which category (if any) should be highlighted.
    $current_cat_ids = array();
    if ( is_category() ) {
        $term = get_queried_object();
        if ( $term && isset( $term->term_id ) ) {
            $current_cat_ids[] = (int) $term->term_id;
        }
    } elseif ( is_singular( 'post' ) ) {
        $current_cat_ids = wp_get_post_categories( get_the_ID() );
    }

    echo '<aside class="pixel-sidebar" aria-label="Categories">';
    echo '<h3 class="pixel-sidebar__heading">Categories</h3>';
    echo '<ul class="pixel-sidebar__list">';
    foreach ( $cats as $cat ) {
        $is_current = in_array( (int) $cat->term_id, array_map( 'intval', $current_cat_ids ), true );
        $class      = 'pixel-sidebar__item' . ( $is_current ? ' is-current' : '' );
        printf(
            '<li class="%1$s"><a href="%2$s">%3$s</a></li>',
            esc_attr( $class ),
            esc_url( get_category_link( $cat->term_id ) ),
            esc_html( $cat->name )
        );
    }
    echo '</ul>';
    echo '</aside>';
} );
