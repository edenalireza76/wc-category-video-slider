=== WC Category Video Slider ===
Contributors: raykapack
Tags: woocommerce, category, video, slider, elementor
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
WC requires at least: 8.0
WC tested up to: 11.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Video gallery for WooCommerce product categories, shown as a customizable slider above the category description.

== Description ==

* Per-category video gallery: upload to the media library or paste an external link (YouTube, Aparat, Vimeo, direct .mp4/.webm).
* Custom cover image per video (YouTube thumbnails are used automatically when no cover is set).
* Drag & drop ordering, bulk add from the media library, per-category on/off and heading.
* Automatic output right above (or below) the category description:
  - `woocommerce_archive_description` hook – used by the Elementor Pro "Archive Description" widget, WoodMart and most themes.
  - Optional `get_the_archive_description` filter – for the Elementor "Archive Description" dynamic tag.
* Elementor widget "Category Video Slider" with full style controls (responsive slides per view, gap, ratio, height, padding, margin, typography, colors, borders, shadows, play button, arrows, dots).
* Global design settings: WooCommerce → Category Videos.
* Lightweight vanilla-JS slider based on native CSS scroll-snap (no jQuery, no Swiper conflicts), touch-friendly, RTL aware, works even when JS is delayed.
* Lazy: iframes/videos are only loaded when the visitor presses play (great for Core Web Vitals).
* Lightbox or inline playback, keyboard accessible.
* VideoObject structured data (JSON-LD).
* HPOS and Cart/Checkout blocks compatible. Persian (fa_IR) translation included.

== Layouts ==

* `slider` – classic slider.
* `featured` – one large main video, the rest of the gallery as a slider below. Clicking a gallery video plays it in the main box (or in a popup).
* `grid` – responsive grid, no slider.

Set globally, per category, per gallery, in the Elementor widget or in the shortcode.

== Play button ==

Show/hide globally, per category, per gallery, per single video, per widget or per shortcode. 6 built-in icons, custom image icon, Elementor icon library / SVG upload (widget), shape (circle, rounded, square, icon only), position (center or any corner), size, icon size, colors, hover colors, border, pulse animation.

== Standalone galleries & shortcodes ==

Menu "Video galleries" → build any number of galleries and paste the shortcode anywhere.

`[rcvs_gallery id="123"]`
`[rcvs_gallery id="123" layout="grid" columns="3" columns_tablet="2" columns_mobile="1" ratio="9:16" play_icon="no"]`
`[rcvs_video_slider]` – current category (with its category image).
`[rcvs_video_slider id="15" max_width="800" align="center" image="no"]`
`[rcvs_video_slider id="15" layout="featured" heading="Videos of {category}" autoplay="yes" arrows="yes" dots="no" titles="yes" play_mode="inline"]`

== Theme compatibility ==

Hello Elementor / Hello Biz / Hello Commerce + Elementor Pro (Archive Description widget, Theme Builder, flex containers), WoodMart (incl. AJAX shop), Astra, Flatsome, Kadence, Blocksy, OceanWP, Storefront and block themes (Term Description block). Styles are protected against theme and Elementor Kit button styles; the slider never widens flex containers; content loaded by AJAX is initialised automatically; WP Rocket & Perfmatters (Delay JS / Remove unused CSS) exclusions are added automatically.

== Developer hooks ==

* `rcvs_category_videos` (filter) – prepared items of a category.
* `rcvs_items` (filter) – prepared items of any source (category or gallery).
* `rcvs_play_icon_html` (filter) – play icon markup.
* `rcvs_gallery_saved` (action).
* `rcvs_slider_html` (filter) – final HTML.
* `rcvs_inline_css` (filter) – generated CSS.
* `rcvs_schema_graph` (filter) – VideoObject graph.
* `rcvs_archive_hook_priority` (filter) – priority on woocommerce_archive_description (default 9 / 11).
* `rcvs_first_page_only` (filter) – show only on page 1 (default true).
* `rcvs_breakpoint_tablet`, `rcvs_breakpoint_mobile` (filters).
* `rcvs_term_saved` (action).

== Changelog ==

= 1.2.0 =
* New: main video fits each video's shape automatically (vertical / horizontal) with a max height, so vertical videos are no longer huge.
* New: main video max width, max height and alignment; gallery max width and alignment (global, per category, per gallery, Elementor, shortcode max_width / align).
* New: per-video orientation option (auto / 16:9 / 9:16 / 1:1 / 4:3 / 4:5); YouTube Shorts detected as vertical; uploaded video size read automatically.
* New: category image with link, shown above or below the videos (per category), with size, width, alignment, radius, spacing and shadow settings; shortcode image="no".
* Improvement: popup uses each video's own shape.
* Upgrade: old default main-video ratio (16:9) is switched to "auto" once.

= 1.1.0 =
* New: show/hide play button (global, per category, per gallery, per video, widget, shortcode).
* New: "Main video + gallery below" layout and grid layout.
* New: play icon customization – 6 presets, custom image, Elementor icon/SVG, shape, position, colors, border, pulse.
* New: standalone video galleries with [rcvs_gallery] shortcode and copy button; Elementor widget can show a gallery.
* New: shortcode attributes layout, columns, columns_tablet, columns_mobile, ratio, play_icon.
* Compatibility: Hello Elementor + Elementor Kit button styles, Elementor flex containers, block themes, AJAX shop re-init, WP Rocket / Perfmatters exclusions.

= 1.0.0 =
* Initial release.
