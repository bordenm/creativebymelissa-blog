<?php
/**
 * Plugin Name: Pixel Theme — Sticky Categories Sidebar
 * Description: Adds a sticky left sidebar listing post categories and a monthly post-activity calendar (with prev/next month nav) on every front-end page.
 * Version: 1.2.0
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

    echo '<aside class="pixel-sidebar" aria-label="Sidebar">';

    // ------- Categories -------
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

    // ------- Activity Calendar -------
    pixel_sidebar_render_calendar();

    echo '</aside>';
} );

/**
 * Render a monthly post-activity calendar.
 *
 * The visible month/year is read from the query string (cal_year &
 * cal_month), defaulting to the current month. Each day is a cell in
 * a 7-column grid; days that have one or more published posts become
 * links to that day's archive and are styled with the eggplant accent.
 * Days with 2+ posts get a small gold count badge in the corner. The
 * actual today gets a thin outline (only when the visible month is
 * the actual current month).
 *
 * Prev/next arrows navigate to neighbouring months by setting
 * cal_year & cal_month query params on the current page URL. The
 * "next" arrow is disabled when viewing the current month or beyond,
 * since there can't be future posts to look at.
 */
function pixel_sidebar_render_calendar() {
    $today_year  = (int) current_time( 'Y' );
    $today_month = (int) current_time( 'n' );
    $today_day   = (int) current_time( 'j' );

    // Read year/month from the query string with sanity bounds.
    $year  = isset( $_GET['cal_year'] )  ? (int) $_GET['cal_year']  : $today_year;
    $month = isset( $_GET['cal_month'] ) ? (int) $_GET['cal_month'] : $today_month;
    if ( $year < 2000 || $year > 2100 ) {
        $year = $today_year;
    }
    if ( $month < 1 || $month > 12 ) {
        $month = $today_month;
    }

    $first_day_ts  = mktime( 0, 0, 0, $month, 1, $year );
    $days_in_month = (int) date( 't', $first_day_ts );
    $first_weekday = (int) date( 'w', $first_day_ts ); // 0 = Sun
    $month_label   = date_i18n( 'M Y', $first_day_ts );

    // Only highlight "today" when the visible month is the real current month.
    $is_current_view = ( $year === $today_year && $month === $today_month );

    // Compute prev/next month for the nav arrows.
    $prev_ts    = mktime( 0, 0, 0, $month - 1, 1, $year );
    $next_ts    = mktime( 0, 0, 0, $month + 1, 1, $year );
    $prev_year  = (int) date( 'Y', $prev_ts );
    $prev_month = (int) date( 'n', $prev_ts );
    $next_year  = (int) date( 'Y', $next_ts );
    $next_month = (int) date( 'n', $next_ts );

    // Disable "next" if we're already at the current month or beyond.
    $next_disabled = ( $year > $today_year ) || ( $year === $today_year && $month >= $today_month );

    // Build prev/next URLs from the current request URI, preserving any
    // other existing query params (e.g. ?paged=2 on a category archive).
    $prev_url = add_query_arg(
        array(
            'cal_year'  => $prev_year,
            'cal_month' => $prev_month,
        )
    );
    $next_url = $next_disabled ? '' : add_query_arg(
        array(
            'cal_year'  => $next_year,
            'cal_month' => $next_month,
        )
    );

    // Query post counts per day for the visible month.
    global $wpdb;
    $start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
    $end   = date( 'Y-m-t 23:59:59', $first_day_ts );
    $rows  = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DAY(post_date) AS day, COUNT(*) AS cnt
             FROM {$wpdb->posts}
             WHERE post_type = 'post'
               AND post_status = 'publish'
               AND post_date BETWEEN %s AND %s
             GROUP BY DAY(post_date)",
            $start,
            $end
        )
    );
    $counts = array();
    foreach ( $rows as $row ) {
        $counts[ (int) $row->day ] = (int) $row->cnt;
    }

    echo '<h3 class="pixel-sidebar__heading pixel-sidebar__heading--calendar">Activity</h3>';
    echo '<div class="pixel-sidebar__calendar">';

    // Month label row with prev/next arrows.
    echo '<div class="pixel-sidebar__calendar-month">';
    printf(
        '<a class="pixel-sidebar__calendar-nav" href="%s" aria-label="Previous month" rel="nofollow">&lsaquo;</a>',
        esc_url( $prev_url )
    );
    echo '<span class="pixel-sidebar__calendar-month-label">' . esc_html( strtoupper( $month_label ) ) . '</span>';
    if ( $next_disabled ) {
        echo '<span class="pixel-sidebar__calendar-nav pixel-sidebar__calendar-nav--disabled" aria-hidden="true">&rsaquo;</span>';
    } else {
        printf(
            '<a class="pixel-sidebar__calendar-nav" href="%s" aria-label="Next month" rel="nofollow">&rsaquo;</a>',
            esc_url( $next_url )
        );
    }
    echo '</div>';

    echo '<div class="pixel-sidebar__calendar-grid" role="grid">';

    // Weekday header row (Sun – Sat).
    $weekdays = array( 'S', 'M', 'T', 'W', 'T', 'F', 'S' );
    foreach ( $weekdays as $wd ) {
        echo '<div class="pixel-sidebar__calendar-weekday" aria-hidden="true">' . esc_html( $wd ) . '</div>';
    }

    // Empty pad cells before the first of the month.
    for ( $i = 0; $i < $first_weekday; $i++ ) {
        echo '<div class="pixel-sidebar__calendar-day pixel-sidebar__calendar-day--pad" aria-hidden="true"></div>';
    }

    // Day cells.
    for ( $d = 1; $d <= $days_in_month; $d++ ) {
        $count    = isset( $counts[ $d ] ) ? $counts[ $d ] : 0;
        $is_today = ( $is_current_view && $d === $today_day );

        $classes = array( 'pixel-sidebar__calendar-day' );
        if ( $count > 0 ) {
            $classes[] = 'pixel-sidebar__calendar-day--has-posts';
        }
        if ( $is_today ) {
            $classes[] = 'pixel-sidebar__calendar-day--today';
        }
        $class_attr = esc_attr( implode( ' ', $classes ) );

        if ( $count > 0 ) {
            $day_url = get_day_link( $year, $month, $d );
            $title   = sprintf(
                _n( '%d post on %s', '%d posts on %s', $count, 'twentytwentyfive-pixel' ),
                $count,
                date_i18n( 'M j', mktime( 0, 0, 0, $month, $d, $year ) )
            );
            echo '<a class="' . $class_attr . '" href="' . esc_url( $day_url ) . '" title="' . esc_attr( $title ) . '">';
            echo '<span class="pixel-sidebar__calendar-day-num">' . esc_html( $d ) . '</span>';
            if ( $count > 1 ) {
                echo '<span class="pixel-sidebar__calendar-count">' . esc_html( $count ) . '</span>';
            }
            echo '</a>';
        } else {
            echo '<div class="' . $class_attr . '"><span class="pixel-sidebar__calendar-day-num">' . esc_html( $d ) . '</span></div>';
        }
    }

    echo '</div>'; // grid
    echo '</div>'; // calendar
}
