# Theme Guideline (Webgames FSE)

## Overview
This document serves as the standard guideline for modifying the `webgames` theme (based on Twenty Twenty-Five FSE block theme) and specifically outlines the integration of the Game Player layout.

## Single Game Template (`single-game.html`)
The single game template utilizes a 70/30 split layout using WordPress core blocks. 

### Block Structure Reference:
```html
<!-- Header -->
<!-- wp:template-part {"slug":"header"} /-->

<!-- Main Container -->
<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">
    
    <!-- Title -->
    <!-- wp:post-title {"level":1,"align":"wide"} /-->

    <!-- Columns (70 / 30) -->
    <!-- wp:columns {"align":"wide"} -->
    <div class="wp-block-columns alignwide">
        
        <!-- Left Column: Main Content (70%) -->
        <!-- wp:column {"width":"70%"} -->
        <div class="wp-block-column" style="flex-basis:70%">
            <!-- wp:shortcode -->[webgames_player]<!-- /wp:shortcode -->
            <!-- wp:shortcode -->[webgames_under_player_ad]<!-- /wp:shortcode -->
            <!-- wp:post-content /-->
            <!-- wp:post-terms {"term":"game-tag"} /-->
            <!-- wp:pattern {"slug":"webgames/comments"} /-->
        </div>
        <!-- /wp:column -->

        <!-- Right Column: Sidebar (30%) -->
        <!-- wp:column {"width":"30%"} -->
        <div class="wp-block-column" style="flex-basis:30%">
            <!-- wp:shortcode -->[webgames_sidebar_list]<!-- /wp:shortcode -->
        </div>
        <!-- /wp:column -->

    </div>
    <!-- /wp:columns -->
    
</main>
<!-- /wp:group -->

<!-- Footer -->
<!-- wp:template-part {"slug":"footer"} /-->
```

## CSS Standards
- Do NOT use inline styles or inline `<style>` tags.
- Rely on WP Core CSS Variables (e.g., `var(--wp--preset--spacing--60)`).
- Plugin-specific styles (like player overlays) are handled via `wp_enqueue_style` in the `webgames` plugin.

## Design Architecture: Dashboard Layout (CrazyGames Style)
- **Global Structure**: All templates MUST follow the Dashboard Flexbox structure:
  1. `header` (Top Navbar, full width)
  2. `.wg-body-container` (Flex row)
     - `sidebar-vertical` (Left Sidebar)
     - `.wg-main-column` (Main scrolling content)
        - Page Content Wrapper
        - `footer` (Must be placed inside the main column at the very bottom)
- **AI UPDATE RULE**: When creating or modifying a new FSE template (like `archive.html`, `page.html`), you MUST ensure the `footer` template part is included at the end of the `.wg-main-column`. Do not forget to include both `header` and `footer`.
- **Dark Mode**: The theme operates natively in a 100% Dark Mode.
- **Global UI Elements (`elements.css`)**: 
  - All shared components like labels (HOT/NEW), primary buttons, and form inputs are located in `wp-content/plugins/webgames/assets/css/elements.css`.
  - **AI UPDATE RULE**: Whenever a new type of generic component (e.g., a standard modal layout, a new badge color) is designed, it MUST be added to `elements.css` rather than defined locally in inline styles or single-use CSS files.
  - To use the labels, assign the class `.wg-label` along with `.wg-label-hot` or `.wg-label-new`.
  - **CRITICAL ASSET RULE**: All global layout CSS (`elements.css`) and global interactive JS MUST be enqueued globally using `wp_enqueue_scripts` in the main plugin class (`class-shortcodes.php`), NOT restricted to `is_singular('game')`, otherwise the layout will break on the Homepage and Archive pages.
