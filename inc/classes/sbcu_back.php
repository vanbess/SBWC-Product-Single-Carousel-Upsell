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

        // save carousel upsell products
        if (isset($_POST['sbcu_products'])) {
            update_post_meta($post_id, 'sbcu_products', $_POST['sbcu_products']);
        }
    }

    /**
     * Render product data tab
     */
    public function render_product_data_tab()
    {

        global $post;

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

                <!-- label -->
                <p class="form-field">

                    <label for="sbcu_products"><?php _e('Carousel Upsell Products', SBWC_CU_TDOM); ?></label>

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

            </div>

        </div>
<?php }
}

// init
SBCU_Back::get_instance();
