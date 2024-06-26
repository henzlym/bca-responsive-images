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

include __DIR__  . '/includes/settings-api.php';
include __DIR__  . '/includes/settings.php';

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

	if (is_admin()) return $html;

	$defaults = ['content_types' => array('post'), 'in_content' => 0];
	$options = get_option('bca_responsive_images_basics');
	$options = array_merge($defaults, !is_array($options) ? array() : $options);

	if (!is_singular($options['content_types'])) return $html;

	$html = bca_responsive_get_picture_srcset($html, $post_thumbnail_id);

	return $html;
}
add_filter('post_thumbnail_html', 'bca_responsive_images', 10, 5);


function bca_responsive_images_block($block_content, $block)
{
	if (is_admin()) return $block_content;

	$defaults = ['content_types' => array('post'), 'in_content' => 0];
	$options = get_option('bca_responsive_images_basics');
	$options = array_merge($defaults, !is_array($options) ? array() : $options);

	if ($options['in_content'] == 0) return $block_content;

	if ($block['blockName'] === 'core/image') {

		$block_content = bca_responsive_get_picture_srcset($block_content, $block['attrs']['id']);
	}

	return $block_content;
}

add_filter('render_block', 'bca_responsive_images_block', 10, 2);

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
 * Filters the list of attachment image attributes.
 *
 * @since 2.8.0
 *
 * @param string[]     $attr       Array of attribute values for the image markup, keyed by attribute name.
 *                                 See wp_get_attachment_image().
 * @param WP_Post      $attachment Image attachment post.
 * @param string|int[] $size       Requested image size. Can be any registered image size name, or
 *                                 an array of width and height values in pixels (in that order).
 */
function bca_responsive_images_remove_srcset($attr): array
{
	if (is_admin()) return $attr;

	unset($attr['srcset']);
	unset($attr['sizes']);
	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'bca_responsive_images_remove_srcset', 10, 3);


function bca_responsive_sort_image_sizes($array)
{
	uasort($array, function ($a, $b) {
		return $a['width'] <=> $b['width'];
	});
	return $array;
}

function bca_responsive_wrap_picture_tag($content)
{
	$pattern = '/(<img[^>]*\bsrcset\b[^>]*>|<img[^>]*>)/i';
	$replacement = '$1';
	return preg_replace($pattern, $replacement, $content);
}

function bca_responsive_images_parse_urls($input)
{
	// Split the input string by commas to get individual image URL and width pairs
	$pairs = explode(',', $input);

	$result = [];

	// Loop through each pair
	foreach ($pairs as $pair) {
		// Trim any extra whitespace
		$pair = trim($pair);

		// Use a regular expression to extract the URL and width
		if (preg_match('/(https?:\/\/\S+)\s(\d+)w/', $pair, $matches)) {
			$url = $matches[1];
			$width = (int) $matches[2];

			// Append the result array with an associative array containing the URL and width
			$result[] = ['url' => $url, 'width' => $width];
		}
	}

	$result = bca_responsive_sort_image_sizes($result);

	return $result;
}

function bca_responsive_get_picture_srcset($html, $attachment_id)
{
	$sizes = wp_get_attachment_image_srcset($attachment_id);
	$sizes = bca_responsive_images_parse_urls($sizes);

	foreach ($sizes as $key => $size) {
		$width = $size['width'];
		$url = $size['url'];
		$condition = '(max-width:' . ($width + 100) . 'px)';
		$sources[] = '<source media="' . $condition . '" srcset="' . $url . '">';
	}

	if (!empty($sources)) {
		$pattern = '/(<img[^>]*\bsrcset\b[^>]*>|<img[^>]*>)/i';
		$replacement = '<picture>' . implode("\n", $sources) . '$1' . '</picture>';
		$html = preg_replace($pattern, $replacement, $html);
	}

	return $html;
}
