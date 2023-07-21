<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SBCU_Back
{

    // instance of this class
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct()
    {

        // instance
        self::$instance = $this;

        // product edit screen data tab
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'), 10, 1);

        // save product data
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'), 10, 1);

        // render product data tab
        add_action('woocommerce_product_data_panels', array($this, 'render_product_data_tab'), 10, 1);
    }

    /**
     * Get instance of this class
     * @return object
     */
    public static function get_instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add product data tab
     * @param array $tabs
     * @return array
     */
    public function add_product_data_tab($tabs)
    {

        // add new tab
        $tabs['sbcu'] = array(
            'label' => __('Carousel Upsells', SBWC_CU_TDOM),
            'target' => 'sbcu_product_data',
            'class' => array('show_if_simple', 'show_if_variable'),
        );

        // return tabs
        return $tabs;
    }

    /**
     * Save product data
     * @param int $post_id
     */
    public function save_product_data($post_id)
    {

        // save products per slide desktop
        if (isset($_POST['sbcu_per_slide_dt'])) {
            update_post_meta($post_id, 'sbcu_per_slide_dt', $_POST['sbcu_per_slide_dt']);
        }

        // save products per slide tablet
        if (isset($_POST['sbcu_per_slide_tb'])) {
            update_post_meta($post_id, 'sbcu_per_slide_tb', $_POST['sbcu_per_slide_tb']);
        }

        // save products per slide mobile
        if (isset($_POST['sbcu_per_slide_mb'])) {
            update_post_meta($post_id, 'sbcu_per_slide_mb', $_POST['sbcu_per_slide_mb']);
        }

        // save products per slide small mobile
        if (isset($_POST['sbcu_per_slide_smb'])) {
            update_post_meta($post_id, 'sbcu_per_slide_smb', $_POST['sbcu_per_slide_smb']);
        }

        // save carousel upsell products
        if (isset($_POST['sbcu_products'])) {
            update_post_meta($post_id, 'sbcu_products', $_POST['sbcu_products']);
        }

        // save link type
        if (isset($_POST['sbcu_link_type'])) {
            update_post_meta($post_id, 'sbcu_link_type', $_POST['sbcu_link_type']);
        }
    }

    /**
     * Render product data tab
     */
    public function render_product_data_tab()
    {

        global $post;

        // get product per slide
        $per_slide_dt  = get_post_meta($post->ID, 'sbcu_per_slide_dt', true);
        $per_slide_tb  = get_post_meta($post->ID, 'sbcu_per_slide_tb', true);
        $per_slide_mb  = get_post_meta($post->ID, 'sbcu_per_slide_mb', true);
        $per_slide_smb = get_post_meta($post->ID, 'sbcu_per_slide_smb', true);
        $link_type     = get_post_meta($post->ID, 'sbcu_link_type', true);

        // get carousel upsell products
        $product_ids = get_post_meta($post->ID, 'sbcu_products', true);

        // if polylang installed, get product language, then get products ids of same language, else get all product ids
        if (function_exists('pll_get_post_language')) {

            $product_lang = pll_get_post_language($post->ID);

            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'lang'           => $product_lang,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids'
            );
        } else {
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids'
            );
        }

        // get products
        $products = get_posts($args);

        // DEBUG
        // echo '<pre>';
        // print_r($products);
        // echo '</pre>';

        // container 
?>
        <div id="sbcu_product_data" class="panel woocommerce_options_panel">

            <div class="options_group">

                <!-- products per slide desktop -->
                <p class="form-field">
                    <label for="sbcu_per_slide_dt" style="width: 200px;"><b><i><?php _e('Products per slide (desktop)?', SBWC_CU_TDOM); ?></i></b></label>

                    <!-- info -->
                    <span class="woocommerce-help-tip" data-tip="<?php _e('Number of products to show per slide on desktop, i.e. screens >= 1367px.', SBWC_CU_TDOM); ?>"></span>

                    <select name="sbcu_per_slide_dt" id="sbcu_per_slide_dt">
                        <option value="1" <?php echo $per_slide_dt == '1' ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo $per_slide_dt == '2' ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo $per_slide_dt == '3' ? 'selected' : ''; ?>>3</option>
                    </select>
                </p>

                <!-- products per slide tablet -->
                <p class="form-field">
                    <label for="sbcu_per_slide_tb" style="width: 200px;"><b><i><?php _e('Products per slide (tablet)?', SBWC_CU_TDOM); ?></i></b></label>

                    <!-- info -->
                    <span class="woocommerce-help-tip" data-tip="<?php _e('Number of products to show per slide on tablet, i.e. screens >= 768px and <= 1366px.', SBWC_CU_TDOM); ?>"></span>

                    <select name="sbcu_per_slide_tb" id="sbcu_per_slide_tb">
                        <option value="1" <?php echo $per_slide_tb == '1' ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo $per_slide_tb == '2' ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo $per_slide_tb == '3' ? 'selected' : ''; ?>>3</option>
                    </select>
                </p>

                <!-- products per slide mobile -->
                <p class="form-field">

                    <label for="sbcu_per_slide_mb" style="width: 200px;"><b><i><?php _e('Products per slide (mobile)?', SBWC_CU_TDOM); ?></i></b></label>

                    <!-- info -->
                    <span class="woocommerce-help-tip" data-tip="<?php _e('Number of products to show per slide on mobile, i.e. screens >= 480px and < 768px.', SBWC_CU_TDOM); ?>"></span>

                    <select name="sbcu_per_slide_mb" id="sbcu_per_slide_mb">
                        <option value="1" <?php echo $per_slide_mb == '1' ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo $per_slide_mb == '2' ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo $per_slide_mb == '3' ? 'selected' : ''; ?>>3</option>
                    </select>
                </p>

                <!-- products per slide small mobile -->
                <p class="form-field">

                    <label for="sbcu_per_slide_smb" style="width: 200px;"><b><i><?php _e('Products per slide (small mobile)?', SBWC_CU_TDOM); ?></i></b></label>

                    <!-- info -->
                    <span class="woocommerce-help-tip" data-tip="<?php _e('Number of products to show per slide on small mobile, i.e. screens < 480px.', SBWC_CU_TDOM); ?>"></span>

                    <select name="sbcu_per_slide_smb" id="sbcu_per_slide_smb">
                        <option value="1" <?php echo $per_slide_smb == '1' ? 'selected' : ''; ?>>1</option>
                        <option value="2" <?php echo $per_slide_smb == '2' ? 'selected' : ''; ?>>2</option>
                        <option value="3" <?php echo $per_slide_smb == '3' ? 'selected' : ''; ?>>3</option>
                    </select>
                </p>

                <!-- label -->
                <p class="form-field">

                    <label for="sbcu_products" style="width: 200px;"><b><i><?php _e('Carousel Upsell Products', SBWC_CU_TDOM); ?></i></b></label>

                    <!-- select -->
                    <select name="sbcu_products[]" id="sbcu_products" class="" multiple>

                        <?php foreach ($products as $pid) : ?>
                            <!-- option -->
                            <option value="<?php echo $pid; ?>" <?php echo in_array($pid, $product_ids) ? 'selected' : ''; ?>>
                                <?php echo get_the_title($pid); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <!-- select2 -->
                    <script>
                        jQuery(document).ready(function($) {
                            $('#sbcu_products').select2({
                                placeholder: '<?php _e('Type to search', SBWC_CU_TDOM); ?>',
                                allowClear: true,
                                width: '360px',
                            });
                        });
                    </script>

                </p>

                <!-- link to product page or show quickview -->
                <p class="form-field">

                    <label for="sbcu_link_type" style="width: 200px;"><b><i><?php _e('Link to product page or show product quickview on button click?', SBWC_CU_TDOM); ?></i></b></label>

                    <!-- info -->
                    <span class="woocommerce-help-tip" data-tip="<?php _e('Select whether to redirect to product page or show quickview popup on button click.', SBWC_CU_TDOM); ?>"></span>

                    <select name="sbcu_link_type" id="sbcu_link_type">
                        <option value="quickview" <?= $link_type === 'quickview' ? 'selected' : '' ?>><?php _e('Show quickview popup', SBWC_CU_TDOM); ?></option>
                        <option value="prodsingle" <?= $link_type === 'prodsingle' ? 'selected' : '' ?>><?php _e('Redirect to product page', SBWC_CU_TDOM); ?></option>
                    </select>

                </p>

            </div>

        </div>
<?php }
}

// init
SBCU_Back::get_instance();
