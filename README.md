=== Blk Canvas - Responsive Images ===
Contributors:      blkcanvas
Tags:              images, performance, responsive-images, media
Tested up to:      6.5
Stable tag:        0.1.0
Requires at least: 5.8
Requires PHP:      7.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Serve perfectly sized images to every visitor with zero effort. Blk Canvas - Responsive Images replaces featured images and core image blocks with modern <picture> markup, letting WordPress deliver the best source for each device while keeping your pages fast and beautiful.

== Description ==

This plugin automatically transforms featured images and core image blocks into `<picture>` elements that reference your registered thumbnail sizes. By removing default `srcset`/`sizes` attributes and letting the browser choose from tailored sources, pages load faster and look sharper on phones, tablets, and desktops alike.

Use the built-in settings page to choose which post types use the responsive markup and whether to target in-content images. The plugin respects your existing media sizes, so you can keep your workflow while giving visitors bandwidth-friendly assets.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/bca-responsive-images` directory or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → Responsive Images** and choose the post types where the responsive markup should be applied. Enable in-content images if you want core image blocks wrapped automatically.

== Frequently Asked Questions ==

= Does this replace my existing image sizes? =
No. The plugin reuses the sizes already registered by your theme or other plugins, exposing them as sources inside the `<picture>` element.

= Will it affect the editor experience? =
No. The responsive markup is injected on the front end. In the block editor you can keep inserting images as usual.

= Can I disable it for certain content types? =
Yes. The settings page lets you choose which post types use responsive images and whether to wrap in-content blocks at all.

== Screenshots ==

1. Settings page showing content type selection and the in-content toggle.
2. Front-end output of a featured image rendered with `<picture>` sources.

== Changelog ==

= 0.1.0 =
* Initial release with responsive `<picture>` output for featured images and core image blocks.
