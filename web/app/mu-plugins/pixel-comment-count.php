<?php
/**
 * Plugin Name: Pixel Comment Count
 * Description: Adds comment counts to the core/latest-posts block output for the homepage Latest Posts list.
 * Author: Creative by Melissa
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('render_block', function ($block_content, $block) {
    if (empty($block['blockName']) || $block['blockName'] !== 'core/latest-posts') {
        return $block_content;
    }

    if (empty($block_content)) {
        return $block_content;
    }

    return preg_replace_callback(
        '/<li([^>]*)>(.*?)<\/li>/s',
        function ($m) {
            $attrs = $m[1];
            $inner = $m[2];

            // Extract post URL from the title link to resolve the post ID.
            if (preg_match('/<a class="wp-block-latest-posts__post-title"[^>]*href="([^"]+)"/', $inner, $href_m)) {
                $url = html_entity_decode($href_m[1]);
                $post_id = url_to_postid($url);
                if ($post_id) {
                    $count = (int) get_comments_number($post_id);
                    // Only render if comments exist or are open for new replies.
                    if ($count > 0 || comments_open($post_id)) {
                        $label = $count === 1 ? '1 comment' : $count . ' comments';
                        $href = get_permalink($post_id) . '#comments';
                        $inner .= sprintf(
                            '<a class="pixel-comment-count" href="%s">%s</a>',
                            esc_url($href),
                            esc_html($label)
                        );
                    }
                }
            }

            return '<li' . $attrs . '>' . $inner . '</li>';
        },
        $block_content
    );
}, 10, 2);
