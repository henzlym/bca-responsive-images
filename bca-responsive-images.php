<?php
/**
 * Plugin Name:       Blank Canvas - Responsive Images
 * Description:       Allow loading of post thumbnail sizes for mobile, tablet, and desktop devices.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bca-responsive-images
 *
 * @package           create-block
 */

 // Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

function bca_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id )
{
    global $post;
    
    if (empty($image_meta) || !is_array($image_meta) || !isset($image_meta['sizes'])) return $sources;

    if (!isset($post->thumbnails)) {
        $post->thumbnails = [];
    }
    
    // $sources = [];
    foreach ($image_meta['sizes'] as $key => $image) {
        // do_action( 'qm/debug', [ '$url' => str_replace( wp_basename( $image_src ), '', $image_src )] );
        $image_baseurl = str_replace( wp_basename( $image_src ), '', $image_src );
        $post->thumbnails[$attachment_id][$key] = array(
            'url'        => $image_baseurl . $image['file'],
            'descriptor' => '',
            'value'      => $key,
        );
    }
    
    return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'bca_calculate_image_srcset', 10, 5);

/**
 * Filter post thumbnail to use the Picture element. 
 * Gives use better control of what image size is used for each browser size
 * @see https://www.youtube.com/watch?v=Rik3gHT24AM | The HTML picture element explained [ Images on the web part 3 ]
 */

function bca_responsive_images($html, $post_id, $post_thumbnail_id, $size, $attr)
{
    global $post;
    
    if ( is_admin() ) return $html;
    if ( get_post_type() !== 'post' ) return $html;
    // is true if either $x or $y are true, but not both.
    if ( !( !is_single() || !is_category() ) ) return $html;
    // do_action( 'qm/debug', [$wp_query,$post] );
    
    $sizes = !isset($attr['sizes']) ? false : $attr['sizes'];
    $sizes = apply_filters( 'bca_responsive_images_sizes', $sizes, $size );
    
    if (!$sizes || !is_string($sizes)) {
        return $html;
    }

    $sizes = explode(',',$sizes);
    
    if (!isset($post->thumbnails) || empty($post->thumbnails) || !isset($post->thumbnails[$post_thumbnail_id]) ) return;
    
    $post_thumbnial = $post->thumbnails[$post_thumbnail_id];
    $sources = [];
    
    foreach ( $sizes as $key => $size ) {
        $size = explode(' ', $size);
        $condition = $size[0];
        $thumbnail_size = $size[1];

        $image_src = isset( $post_thumbnial[$thumbnail_size] ) ? $post_thumbnial[$thumbnail_size]['url'] : false;

        if ($image_src) {
            $sources[] = '<source data-size="'.$thumbnail_size.'" media="'.$condition.'" srcset="' . $image_src . '">';
        }

    }
    
    if (!empty($sources)) {
        $html = '<picture>' . implode("\n",  $sources) . $html . '</picture>';
    }
    
    return $html;
    
}
add_filter( 'post_thumbnail_html', 'bca_responsive_images', 10, 5 );


/**
 * Filters the threshold for how many of the first content media elements to not lazy-load.
 * @link https://developer.wordpress.org/reference/hooks/wp_omit_loading_attr_threshold/
 * 
 * 
 * Article on how lazy loading is implemented.
 * Lazy-loading images in 5.5
 * @link https://make.wordpress.org/core/2020/07/14/lazy-loading-images-in-5-5/
 */
function bca_omit_loading_attr_threshold( $omit_threshold )
{
    if (is_category()) {
        $omit_threshold = 3;
    }

	return $omit_threshold;
}
add_filter('wp_omit_loading_attr_threshold', 'bca_omit_loading_attr_threshold');

// function bca_responsive_images_sizes__($sizes,$size)
// {
//     if (is_single() && ! $sizes && $size == 'medium') {
//         $sizes = $sizes . '(max-width:480px) thumbnail';
//     }
//     return $sizes;
// }
// add_filter( 'bca_responsive_images_sizes', 'bca_responsive_images_sizes__', 10, 2);
