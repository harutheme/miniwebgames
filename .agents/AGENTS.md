# Style Guidelines

- **Material Design 3 (M3)**: All UI designs, CSS modifications, and HTML structure must follow the Material Design 3 guidelines (https://m3.material.io/).
- Do not deviate from this style unless explicitly requested by the user. 
- Mọi chỉnh sửa về giao diện và CSS phải tuân thủ nghiêm ngặt phong cách Material Design 3. Nếu không có chỉ định đặc biệt, hãy mặc định áp dụng các nguyên tắc của M3.


# WordPress Development Guidelines

- **Shortcode HTML Structure (Anti-wpautop)**: When generating HTML output in WordPress shortcodes or block renderers, NEVER place inline HTML elements (`<button>`, `<span>`, `<iframe>`, `<img>`, etc.) as direct children of mixed-content block elements if they sit adjacent to other block elements (like `<div>`). WordPress's native `wpautop` function (which is often forcefully applied by Gutenberg's Block REST API or classic filters) uses aggressive regex that will mistakenly wrap these orphan inline elements in `<p>` tags, corrupting the DOM layout (e.g., generating stray `<p></p>` and `<p><iframe></p>`).
- **Solution**: Always wrap vulnerable inline elements in block-level containers (e.g., `<div class="wg-btn-wrapper">`) or convert them to block/inline-block `<div>` elements natively in the PHP output. This renders the HTML 100% immune to `wpautop` corruption without relying on fragile JavaScript DOM cleanups.
