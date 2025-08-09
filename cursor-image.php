<?php
/**
 * Plugin Name: Cursor Image on Hover
 * Description: Displays custom body content with GSAP scripts and plugin CSS/JS properly enqueued. Includes admin menu settings with drag & drop reorder, icon buttons, font size, and text color option. Shortcode: [wp_cursor_menu]
 * Version: 1.5
 * Author: WP Design Lab
 */

defined('ABSPATH') || exit;

class Custom_Body_Content_Display {
    private $option_name = 'cbdm_menu_items';
    private $settings_option = 'cbdm_font_settings';

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('wp_cursor_menu', [$this, 'render_shortcode']);

        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_assets() {
        wp_enqueue_script('gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', [], '3.12.5', true);
        wp_enqueue_script('gsap-gsdevtools', 'https://unpkg.com/gsap@3/dist/GSDevTools.min.js', ['gsap'], null, true);
        wp_enqueue_script('gsap-customease', 'https://unpkg.com/gsap@3/dist/CustomEase.min.js', ['gsap'], null, true);
        wp_enqueue_script('gsap-custombounce', 'https://unpkg.com/gsap@3/dist/CustomBounce.min.js', ['gsap'], null, true);
        wp_enqueue_script('gsap-customwiggle', 'https://unpkg.com/gsap@3/dist/CustomWiggle.min.js', ['gsap'], null, true);
        wp_enqueue_script('gsap-splittext', 'https://unpkg.com/gsap@3/dist/SplitText.min.js', ['gsap'], null, true);

        $plugin_url = plugin_dir_url(__FILE__);
        wp_enqueue_script('custom-script', $plugin_url . 'script.js', ['gsap'], null, true);
        wp_enqueue_style('style-css', $plugin_url . 'style.css');

        $font_settings = get_option($this->settings_option, ['font_size' => 'inherit', 'text_color' => '#ffff']);
        $font_size = esc_html($font_settings['font_size'] ?? 'inherit');
        $text_color = esc_html($font_settings['text_color'] ?? '#ffff');

        $custom_css = "
            ul.listme li.conten .text h3 {
                font-size: {$font_size};
                color: {$text_color};
            }
            ul.listme li.conten {
                border-bottom: 2px solid {$text_color};
                display: inline-block;
                width: 100%;
                font-family: inherit;
                padding: 2rem 0 2rem 0;
            }
        ";
        wp_add_inline_style('style-css', $custom_css);
    }

    public function render_shortcode() {
        if (is_admin()) {
            return ''; // Disable preview in admin
        }

        $items = get_option($this->option_name, []);

        if (empty($items)) {
            return '<div class="msg">No data found</div>';
        }

        ob_start();
        echo '<ul class="listme" role="list">';
        foreach ($items as $item) {
            $img = esc_url($item['image']);
            $title = esc_html($item['title']);
            $link = esc_url($item['link']);
            ?>
            <li class="conten">
                <?php if ($link): ?>
                    <a href="<?php echo $link; ?>">
                <?php endif; ?>
                <img class="swipeimage" src="<?php echo $img; ?>" alt="<?php echo $title; ?>">
                <div class="text">
                    <h3><?php echo $title; ?></h3>
                </div>
                <?php if ($link): ?>
                    </a>
                <?php endif; ?>
            </li>
            <?php
        }
        echo '</ul>';

        return ob_get_clean();
    }

    public function add_settings_page() {
        add_options_page(
            'Menu Items & Font Settings',
            'Menu Items',
            'manage_options',
            'custom_body_content_menu_items',
            [$this, 'settings_page_html']
        );
    }

    public function register_settings() {
        register_setting('custom_body_content_group', $this->option_name, [$this, 'sanitize_menu_items']);
        register_setting('custom_body_content_group', $this->settings_option, [$this, 'sanitize_font_settings']);
    }

    public function sanitize_menu_items($input) {
        if (!is_array($input)) return [];

        $clean = [];
        foreach ($input as $item) {
            $clean[] = [
                'image' => isset($item['image']) ? esc_url_raw($item['image']) : '',
                'title' => isset($item['title']) ? sanitize_text_field($item['title']) : '',
                'link'  => isset($item['link']) ? esc_url_raw($item['link']) : '',
            ];
        }
        return $clean;
    }

    public function sanitize_font_settings($input) {
        $output = [
            'font_size' => sanitize_text_field($input['font_size'] ?? 'inherit'),
            'text_color' => sanitize_text_field($input['text_color'] ?? '#ffff'),
        ];

        if (!preg_match('/^(\d+(?:\.\d+)?)(px|em|rem|%)$/', $output['font_size'])) {
            if ($output['font_size'] !== 'inherit') {
                $output['font_size'] = 'inherit';
            }
        }

        // Basic color validation (allow named colors or hex or rgb/rgba)
        if (!preg_match('/^(#([a-fA-F0-9]{3}){1,2}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(,\s*(0|1|0?\.\d+))?\s*\)|[a-zA-Z]+)$/', $output['text_color'])) {
            if ($output['text_color'] !== 'inherit') {
                $output['text_color'] = '#ffff';
            }
        }

        return $output;
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_custom_body_content_menu_items') return;

        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('wp-jquery-ui-dialog');

        // Enqueue WP color picker assets
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_add_inline_style('wp-jquery-ui-dialog', $this->get_admin_css());
        wp_add_inline_script('jquery', $this->get_admin_js());

        // Init color picker
        wp_add_inline_script('wp-color-picker', "
            jQuery(document).ready(function($){
                $('#text_color').wpColorPicker();
            });
        ");
    }

    public function settings_page_html() {
        if (!current_user_can('manage_options')) wp_die('Permission denied');

        $items = get_option($this->option_name, []);
        $font_settings = get_option($this->settings_option, ['font_size' => 'inherit', 'text_color' => '#ffff']);
        ?>
        <div class="wrap">
            <h1>Menu Items & Font Settings</h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('custom_body_content_group');
                do_settings_sections('custom_body_content_group');
                ?>

                <h2>Menu Items</h2>
                <table class="form-table" id="menu-items-table">
                    <thead>
                        <tr>
                            <th style="width:50px;"></th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Link</th>
                            <th style="width:70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($items)) : ?>
                        <?php foreach ($items as $index => $item) : ?>
                            <tr>
                                <td class="drag-handle" title="Drag to reorder" style="cursor:move; text-align:center;">&#9776;</td>
                                <td>
                                    <input type="hidden" class="image-url" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][image]" value="<?php echo esc_attr($item['image']); ?>">
                                    <button class="button upload-image">Upload</button><br>
                                    <?php if ($item['image']) : ?>
                                        <img class="image-preview" src="<?php echo esc_url($item['image']); ?>" style="max-width:100px; margin-top:5px; display:block;">
                                    <?php else: ?>
                                        <img class="image-preview" src="" style="max-width:100px; margin-top:5px; display:none;">
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <input type="text" class="widefat" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($item['title']); ?>">
                                </td>
                                <td>
                                    <input type="url" class="widefat" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $index; ?>][link]" value="<?php echo esc_attr($item['link']); ?>">
                                </td>
                                <td>
                                    <button class="button remove-row" title="Remove this item" aria-label="Remove item">
                                        <span style="color:#d63638; font-size:18px;">&#128465;</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="drag-handle" title="Drag to reorder" style="cursor:move; text-align:center;">&#9776;</td>
                            <td>
                                <input type="hidden" class="image-url" name="<?php echo esc_attr($this->option_name); ?>[0][image]" value="">
                                <button class="button upload-image">Upload</button><br>
                                <img class="image-preview" src="" style="max-width:100px; margin-top:5px; display:none;">
                            </td>
                            <td>
                                <input type="text" class="widefat" name="<?php echo esc_attr($this->option_name); ?>[0][title]" value="">
                            </td>
                            <td>
                                <input type="url" class="widefat" name="<?php echo esc_attr($this->option_name); ?>[0][link]" value="">
                            </td>
                            <td>
                                <button class="button remove-row" title="Remove this item" aria-label="Remove item">
                                    <span style="color:#d63638; font-size:18px;">&#128465;</span>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                <p>
                    <button id="add-menu-item" class="button button-primary">Add Menu Item</button>
                </p>

                <h2>Font Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="font_size">Font Size</label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($this->settings_option); ?>[font_size]" id="font_size" value="<?php echo esc_attr($font_settings['font_size']); ?>" class="regular-text" placeholder="e.g. 28px, 1.5rem, inherit">
                            <p class="description">Enter font size with units (px, em, rem, %) or "inherit". Font family will use your theme default.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="text_color">Text Color</label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($this->settings_option); ?>[text_color]" id="text_color" value="<?php echo esc_attr($font_settings['text_color']); ?>" class="regular-text wp-color-picker-field" placeholder="#ffff or red">
                            <p class="description">Enter a CSS color value for the title text. E.g., #000000, red, rgb(255,0,0)</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        $this->print_admin_js_template();
    }

    private function get_admin_css() {
        return '
            #menu-items-table tbody tr:hover {
                background: #f9f9f9;
            }
            #menu-items-table tbody tr.dragging {
                background: #e9e9e9;
                opacity: 0.7;
            }
            #menu-items-table tbody tr td {
                vertical-align: middle;
            }
            #menu-items-table tbody tr td.drag-handle {
                font-size: 18px;
                user-select: none;
            }
            button.upload-image {
                margin-top: 4px;
            }
            button.remove-row span {
                pointer-events: none;
            }
        ';
    }

    private function get_admin_js() {
        return <<<JS
jQuery(document).ready(function($){
    // Media uploader
    function mediaUploader(button) {
        button.on('click', function(e){
            e.preventDefault();

            var btn = $(this);
            var file_frame = wp.media({
                title: 'Select or Upload Image',
                button: { text: 'Use this image' },
                multiple: false
            });

            file_frame.on('select', function(){
                var attachment = file_frame.state().get('selection').first().toJSON();
                btn.siblings('input.image-url').val(attachment.url);
                btn.siblings('img.image-preview').attr('src', attachment.url).show();
            });

            file_frame.open();
        });
    }

    mediaUploader($('.upload-image'));

    // Add new row
    $('#add-menu-item').on('click', function(e){
        e.preventDefault();
        var table = $('#menu-items-table tbody');
        var index = table.find('tr').length;
        var row = $('#menu-items-row-template').html();
        row = row.replace(/__index__/g, index);
        table.append(row);

        mediaUploader(table.find('tr:last .upload-image'));
    });

    // Remove row
    $('#menu-items-table').on('click', '.remove-row', function(e){
        e.preventDefault();
        if(confirm('Remove this menu item?')){
            $(this).closest('tr').remove();
        }
    });

    // Make table rows sortable
    $('#menu-items-table tbody').sortable({
        handle: '.drag-handle',
        helper: function(e, tr) {
            var originals = tr.children();
            var helper = tr.clone();
            helper.children().each(function(index){
                $(this).width(originals.eq(index).width());
            });
            return helper;
        },
        start: function(e, ui){
            ui.item.addClass('dragging');
        },
        stop: function(e, ui){
            ui.item.removeClass('dragging');
        }
    }).disableSelection();
});
JS;
    }

    private function print_admin_js_template() {
        ?>
        <script type="text/template" id="menu-items-row-template">
            <tr>
                <td class="drag-handle" title="Drag to reorder" style="cursor:move; text-align:center;">&#9776;</td>
                <td>
                    <input type="hidden" class="image-url" name="<?php echo esc_attr($this->option_name); ?>[__index__][image]" value="">
                    <button class="button upload-image">Upload</button><br>
                    <img class="image-preview" src="" style="max-width:100px; margin-top:5px; display:none;">
                </td>
                <td>
                    <input type="text" class="widefat" name="<?php echo esc_attr($this->option_name); ?>[__index__][title]" value="">
                </td>
                <td>
                    <input type="url" class="widefat" name="<?php echo esc_attr($this->option_name); ?>[__index__][link]" value="">
                </td>
                <td>
                    <button class="button remove-row" title="Remove this item" aria-label="Remove item">
                        <span style="color:#d63638; font-size:18px;">&#128465;</span>
                    </button>
                </td>
            </tr>
        </script>
        <?php
    }
}

new Custom_Body_Content_Display();
