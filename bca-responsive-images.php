<?php

/**
 * Plugin Name:       Blk Canvas - Responsive Images
 * Description:       Enhance your website's performance and user experience with responsive images! This plugin dynamically serves the most appropriate image sizes for mobile, tablet, and desktop devices, ensuring faster load times and optimized visual quality across all screen sizes.
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

/**
 * Filters the image sources for responsive images.
 *
 * @param array  $sources       One or more arrays of source data to include in the 'srcset' attribute.
 * @param array  $size_array    Array of width and height values in pixels (in that order).
 * @param string $image_src     The 'src' of the image.
 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 * @param int    $attachment_id Image attachment ID.
 *
 * @return array Modified array of sources.
 */
function bca_calculate_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
{
	global $post;

	if (empty($image_meta) || !is_array($image_meta) || !isset($image_meta['sizes'])) return $sources;

	if (!isset($post->thumbnails)) {
		$post->thumbnails = [];
	}

	foreach ($image_meta['sizes'] as $key => $image) {
		$image_baseurl = str_replace(wp_basename($image_src), '', $image_src);
		$post->thumbnails[$attachment_id][$key] = array(
			'url'        => $image_baseurl . $image['file'],
			'descriptor' => '',
			'value'      => $key,
		);
	}

	return $sources;
}
add_filter('wp_calculate_image_srcset', 'bca_calculate_image_srcset', 10, 5);

/**
 * Filters the post thumbnail HTML to use the Picture element.
 *
 * @param string $html              The post thumbnail HTML.
 * @param int    $post_id           The post ID.
 * @param int    $post_thumbnail_id The post thumbnail ID.
 * @param string $size              The post thumbnail size.
 * @param array  $attr              Array of attribute values for the post thumbnail.
 *
 * @return string Modified post thumbnail HTML.
 */
function bca_responsive_images($html, $post_id, $post_thumbnail_id, $size, $attr)
{
	global $post;

	if (is_admin()) return $html;
	if (get_post_type() !== 'post') return $html;
	if (!(!is_single() || !is_category())) return $html;

	$sizes = !isset($attr['sizes']) ? false : $attr['sizes'];
	$sizes = apply_filters('bca_responsive_images_sizes', $sizes, $size);

	if (!$sizes || !is_string($sizes)) {
		return $html;
	}

	$sizes = explode(',', $sizes);

	if (!isset($post->thumbnails) || empty($post->thumbnails) || !isset($post->thumbnails[$post_thumbnail_id])) return $html;

	$post_thumbnail = $post->thumbnails[$post_thumbnail_id];
	$sources = [];

	foreach ($sizes as $key => $size) {
		$size = explode(' ', $size);
		$condition = $size[0];
		$thumbnail_size = $size[1];

		$image_src = isset($post_thumbnail[$thumbnail_size]) ? $post_thumbnail[$thumbnail_size]['url'] : false;

		if ($image_src) {
			$sources[] = '<source data-size="' . $thumbnail_size . '" media="' . $condition . '" srcset="' . $image_src . '">';
		}
	}

	if (!empty($sources)) {
		$html = '<picture>' . implode("\n", $sources) . $html . '</picture>';
	}

	return $html;
}
add_filter('post_thumbnail_html', 'bca_responsive_images', 10, 5);

/**
 * Filters the threshold for how many of the first content media elements to not lazy-load.
 *
 * @param int $omit_threshold The number of content media elements to not lazy-load.
 *
 * @return int Modified threshold value.
 */
function bca_omit_loading_attr_threshold($omit_threshold)
{
	if (is_category()) {
		$omit_threshold = 3;
	}

	return $omit_threshold;
}
add_filter('wp_omit_loading_attr_threshold', 'bca_omit_loading_attr_threshold');

/**
 * Filters the responsive image sizes attribute.
 *
 * @param string $sizes Image sizes attribute.
 * @param string $size  The post thumbnail size.
 *
 * @return string Modified image sizes attribute.
 */
function bca_responsive_set_images_sizes($sizes, $size)
{
	if (is_single() && !$sizes && $size == 'medium') {
		$sizes = $sizes . '(max-width:480px) thumbnail';
	}
	return $sizes;
}
add_filter('bca_responsive_images_sizes', 'bca_responsive_set_images_sizes', 10, 2);
