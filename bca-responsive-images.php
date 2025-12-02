<?php

/**
 * Plugin Name:       Blk Canvas - Responsive Images
 * Plugin URI:        https://github.com/blkcanvas/bca-responsive-images
 * Description:       Automatically wrap featured images and core image blocks in responsive <picture> markup to serve the right size for every device.
 * Requires at least: 5.8
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Blk Canvas
 * Author URI:        https://blkcanvas.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bca-responsive-images
 *
 * @package bca-responsive-images
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

// Include required files.
require_once __DIR__ . '/includes/settings-api.php';
require_once __DIR__ . '/includes/settings.php';

/**
 * Filters the post thumbnail HTML to wrap it in a responsive picture element.
 *
 * Converts WordPress featured images into responsive <picture> elements with
 * appropriate <source> tags for different viewport sizes.
 *
 * @since 0.1.0
 *
 * @param string       $html              The post thumbnail HTML.
 * @param int          $post_id           The post ID.
 * @param int          $post_thumbnail_id The post thumbnail ID (attachment ID).
 * @param string|int[] $size              The post thumbnail size. Image size or array of width and height.
 * @param string[]     $attr              Array of attribute values for the post thumbnail.
 *
 * @return string Modified post thumbnail HTML with picture element wrapper.
 */
function bca_responsive_images($html, $post_id, $post_thumbnail_id, $size, $attr)
{
	// Skip if in admin context.
	if (is_admin()) {
		return $html;
	}

	// Get plugin options.
	$defaults = array(
		'content_types' => array('post'),
		'in_content'    => 0,
	);
	$options  = get_option('bca_responsive_images_basics', array());
	$options  = array_merge($defaults, is_array($options) ? $options : array());

	// Only process on singular pages of configured content types.
	if (! is_singular($options['content_types'])) {
		return $html;
	}

	// Generate picture element with responsive sources.
	return bca_responsive_get_picture_srcset($html, $post_thumbnail_id, $size);
}
add_filter('post_thumbnail_html', 'bca_responsive_images', 10, 5);

/**
 * Filters core/image blocks to wrap them in responsive picture elements.
 *
 * Processes WordPress core image blocks during rendering to add responsive
 * picture markup with appropriate source elements based on the selected size.
 *
 * @since 0.1.0
 *
 * @param string $block_content The block content about to be rendered.
 * @param array  $block         The full block, including name and attributes.
 *
 * @return string Modified block content with picture element wrapper.
 */
function bca_responsive_images_block($block_content, $block)
{
	// Skip if in admin context.
	if (is_admin()) {
		return $block_content;
	}

	// Get plugin options.
	$defaults = array(
		'content_types' => array('post'),
		'in_content'    => 0,
	);
	$options  = get_option('bca_responsive_images_basics', array());
	$options  = array_merge($defaults, is_array($options) ? $options : array());

	// Skip if in-content processing is disabled.
	if (empty($options['in_content'])) {
		return $block_content;
	}

	// Only process core/image blocks.
	if ('core/image' !== $block['blockName']) {
		return $block_content;
	}

	// Get image attributes.
	$image_id  = isset($block['attrs']['id']) ? absint($block['attrs']['id']) : 0;
	$size_slug = isset($block['attrs']['sizeSlug']) ? sanitize_key($block['attrs']['sizeSlug']) : 'full';

	// Skip if no valid image ID.
	if (! $image_id) {
		return $block_content;
	}

	// Generate picture element with responsive sources.
	return bca_responsive_get_picture_srcset($block_content, $image_id, $size_slug);
}
add_filter('render_block', 'bca_responsive_images_block', 90, 2);

/**
 * Filters the threshold for lazy-loading images on category pages.
 *
 * Increases the number of images that should not be lazy-loaded on category
 * archive pages to improve perceived performance for above-the-fold content.
 *
 * @since 0.1.0
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
 * Removes srcset and sizes attributes from image markup.
 *
 * Since we're using <picture> elements with <source> tags for responsive images,
 * we don't need WordPress's default srcset/sizes attributes on the <img> tag.
 *
 * @since 0.1.0
 *
 * @param string[] $attr       Array of attribute values for the image markup, keyed by attribute name.
 * @param WP_Post  $attachment Image attachment post.
 * @param string   $size       Requested image size name or array of width and height values.
 *
 * @return string[] Modified attributes array without srcset and sizes.
 */
function bca_responsive_images_remove_srcset($attr)
{
	if (is_admin()) {
		return $attr;
	}

	unset($attr['srcset'], $attr['sizes']);

	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'bca_responsive_images_remove_srcset', 10, 3);

/**
 * Sorts an array of image sizes by width in ascending order.
 *
 * @since 0.1.0
 *
 * @param array $array Array of image size data with 'width' keys.
 *
 * @return array Sorted array of image sizes.
 */
function bca_responsive_sort_image_sizes($array)
{
	uasort(
		$array,
		function ($a, $b) {
			return $a['width'] <=> $b['width'];
		}
	);

	return $array;
}

/**
 * Parses a srcset string into an array of image URLs and widths.
 *
 * Converts WordPress srcset format (URL 300w, URL 768w, etc.) into a structured
 * array sorted by width for easier processing.
 *
 * @since 0.1.0
 *
 * @param string $input Srcset string from wp_get_attachment_image_srcset().
 *
 * @return array Array of associative arrays with 'url' and 'width' keys, sorted by width.
 */
function bca_responsive_images_parse_urls($input)
{
	$pairs  = explode(',', $input);
	$result = array();

	foreach ($pairs as $pair) {
		$pair = trim($pair);

		if (preg_match('/(https?:\/\/\S+)\s(\d+)w/', $pair, $matches)) {
			$result[] = array(
				'url'   => $matches[1],
				'width' => (int) $matches[2],
			);
		}
	}

	return bca_responsive_sort_image_sizes($result);
}

/**
 * Gets the width of a specific image size.
 *
 * Determines the width of an image in the following order:
 * 1. Extract from HTML width attribute (featured images)
 * 2. Check image metadata for intermediate size
 * 3. Check registered image sizes (theme/plugin custom sizes)
 * 4. Check WordPress default sizes (thumbnail, medium, large)
 *
 * @since 0.1.0
 *
 * @param int    $attachment_id The attachment ID.
 * @param string $size_slug     The size slug (e.g., 'thumbnail', 'medium', 'large', 'full').
 * @param string $html          Optional. HTML content to extract width from. Default empty string.
 *
 * @return int|null The width in pixels, or null if not found.
 */
function bca_responsive_get_image_width($attachment_id, $size_slug, $html = '')
{
	// Try to extract from HTML first (works for featured images with width attribute).
	if (! empty($html) && preg_match('/width="(\d+)"/', $html, $matches)) {
		return (int) $matches[1];
	}

	// Get attachment metadata.
	$image_meta = wp_get_attachment_metadata($attachment_id);
	if (! $image_meta) {
		return null;
	}

	// Full size image.
	if ('full' === $size_slug && isset($image_meta['width'])) {
		return $image_meta['width'];
	}

	// Intermediate size from metadata.
	if (isset($image_meta['sizes'][$size_slug]['width'])) {
		return $image_meta['sizes'][$size_slug]['width'];
	}

	// Try registered image sizes (custom sizes added by themes/plugins).
	global $_wp_additional_image_sizes;
	if (isset($_wp_additional_image_sizes[$size_slug]['width'])) {
		return $_wp_additional_image_sizes[$size_slug]['width'];
	}

	// WordPress default sizes.
	$default_sizes = array(
		'thumbnail'    => get_option('thumbnail_size_w', 150),
		'medium'       => get_option('medium_size_w', 300),
		'medium_large' => get_option('medium_large_size_w', 768),
		'large'        => get_option('large_size_w', 1024),
	);

	if (isset($default_sizes[$size_slug])) {
		return (int) $default_sizes[$size_slug];
	}

	return null;
}

/**
 * Generates responsive picture element markup from image HTML.
 *
 * Takes image HTML and wraps it in a <picture> element with appropriate <source>
 * elements for different viewport sizes. Only includes image sizes up to and
 * including the selected size to avoid serving unnecessarily large images.
 *
 * @since 0.1.0
 *
 * @param string       $html          The image HTML to wrap.
 * @param int          $attachment_id The attachment ID.
 * @param string|int[] $selected_size The selected image size slug or array. Default 'full'.
 *
 * @return string Modified HTML with picture element, or original HTML if processing fails.
 */
function bca_responsive_get_picture_srcset($html, $attachment_id, $selected_size = 'full')
{
	// Normalize size to string for internal processing.
	$size_slug = is_array($selected_size) ? 'full' : $selected_size;

	// Get the width of the selected size.
	$selected_width = bca_responsive_get_image_width($attachment_id, $size_slug, $html);
	if (! $selected_width) {
		return $html;
	}

	// Get available image sizes from WordPress.
	$srcset = wp_get_attachment_image_srcset($attachment_id);
	if (empty($srcset)) {
		return $html;
	}

	// Parse srcset into structured array.
	$sizes = bca_responsive_images_parse_urls($srcset);
	if (empty($sizes)) {
		return $html;
	}

	// Filter to only include sizes up to and including the selected size.
	$filtered_sizes = array_filter(
		$sizes,
		function ($size) use ($selected_width) {
			return $size['width'] <= $selected_width;
		}
	);

	if (empty($filtered_sizes)) {
		return $html;
	}

	// Generate <source> elements with media queries.
	$sources = array_map(
		function ($size) {
			$media_query = '(max-width:' . ($size['width'] + 100) . 'px)';
			return sprintf(
				'<source media="%s" srcset="%s">',
				esc_attr($media_query),
				esc_url($size['url'])
			);
		},
		$filtered_sizes
	);

	// Wrap <img> in <picture> element.
	$pattern     = '/(<img[^>]*\bsrcset\b[^>]*>|<img[^>]*>)/i';
	$replacement = '<picture>' . implode("\n", $sources) . '$1' . '</picture>';
	$html        = preg_replace($pattern, $replacement, $html);

	return $html;
}
