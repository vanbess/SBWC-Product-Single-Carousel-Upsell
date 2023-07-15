<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SBCU_Front
{


    // instance of this class
    private static $instance = null;

    /**
     * Constructor
     * @since 1.0.0
     */
    private function __construct()
    {

        // add carousel upsells to single product page after add to cart
        add_action('woocommerce_after_add_to_cart_button', array($this, 'add_carousel_upsells'));

        // add to cart ajax
        add_action('wp_ajax_sbcu_add_to_cart', array($this, 'sbcu_add_to_cart'));
        add_action('wp_ajax_nopriv_sbcu_add_to_cart', array($this, 'sbcu_add_to_cart'));

        // add css and js
        add_action('wp_footer', array($this, 'add_css_js'), PHP_INT_MAX);
    }

    /**
     * Get instance of this class
     * @return object - instance of this class
     * @since 1.0.0
     */
    public static function get_instance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add carousel upsells to single product page
     * 
     * @return void
     * @since 1.0.0
     */
    public function add_carousel_upsells()
    {

        global $post;

        // enqueue jquery
        wp_enqueue_script('jquery');

        // retrieve sbcu upsell ids for this product
        $sbcu_upsell_ids = get_post_meta($post->ID, 'sbcu_products', true);

        // DEBUG
        // echo '<pre>';
        // print_r($sbcu_upsell_ids);
        // echo '</pre>';

        // if there are upsells
        if (!empty($sbcu_upsell_ids)) {

            // get upsell products
            $sbcu_upsell_products = wc_get_products(array(
                'include' => $sbcu_upsell_ids,
                'limit' => -1,
            ));

            // if there are upsell products
            if (!empty($sbcu_upsell_products)) {

                // render carousel
                $this->render_carousel($sbcu_upsell_products);
            }
        }
    }

    /**
     * Render Owl carousel
     * 
     * @param array $products - array of products
     * @return void
     * @since 1.0.0
     */
    private function render_carousel($products)
    {

        global $sbcu_carousel_id;

        // carousel id
        $sbcu_carousel_id = 'sbcu-carousel-' . uniqid();

        // output buffer
        ob_start();

        // carousel
?>

        <h5 id="sbcu-title" class="title mb-2 mt-3"><?php _e('You Might Be Interested In:', SBWC_CU_TDOM); ?></h5>

        <div id="<?php echo $sbcu_carousel_id; ?>" class="sbcu-carousel owl-carousel owl-theme">

            <?php
            foreach ($products as $product) {

                // product id
                $product_id = $product->get_id();

                // product title
                $product_title = $product->get_title();

                // product price
                $product_price = $product->get_price_html();

                // get product img thumbnail from image id
                $product_image = wp_get_attachment_image($product->get_image_id(), 'large');

                // product url
                $product_url = $product->get_permalink();

                // if is variable product render 'Choose Options' button which will trigger a model showing options on click, else render 'Add to Cart' button
                if ($product->is_type('variable')) {

                    // check if variation has linked variations (products linked by variation plugin)
                    $is_linked_variation = $this->is_variation_part_of_linked_products($product_id);

                    if ($is_linked_variation) :
                        // link to product page
                        $product_add_to_cart_button = '<a target="_blank" href="' . $product_url . '" title="' . __('View product', SBWC_CU_TDOM) . '" class="button w-100">' . __('Choose Options', SBWC_CU_TDOM) . '</a>';
                    else :
                        // choose options button
                        $product_add_to_cart_button = '<button title="' . __('View options', SBWC_CU_TDOM) . '" class="button sbcu-variation-modal-trigger w-100" data-product_id="' . $product_id . '">' . __('Choose Options', SBWC_CU_TDOM) . '</button>';
                    endif;
                } else {
                    // add to cart button
                    $product_add_to_cart_button = '<button title="' . __('Add to cart', SBWC_CU_TDOM) . '" data-product_id="' . $product_id . '" class="button sbcu-carousel-item-add-to-cart-button sbcu-add-to-cart w-100">' . __('Add to Cart', SBWC_CU_TDOM) . '</button>';
                }

                // product html
            ?>
                <div class="sbcu-carousel-item">
                    <div class="sbcu-carousel-item-inner">
                        <!-- product image -->
                        <div class="sbcu-carousel-item-image pb-3">
                            <a href="<?php echo $product_url; ?>" title="<?php _e('View product', SBWC_CU_TDOM); ?>" target="_blank">
                                <?php echo $product_image; ?>
                            </a>
                        </div>

                        <!-- title and url -->
                        <div class="sbcu-carousel-item-title has-small-font-size text-center pb-3 font-weight-semi-bold">
                            <a href="<?php echo $product_url; ?>" title="<?php _e('View product', SBWC_CU_TDOM); ?>" target="_blank">
                                <?php echo $product_title; ?>
                            </a>
                        </div>

                        <!-- price -->
                        <div class="sbcu-carousel-item-price has-small-font-size text-center text-grey pb-3 font-weight-semi-bold">
                            <?php echo $product_price; ?>
                        </div>

                        <!-- add to cart/choose options -->
                        <div class="sbcu-carousel-item-add-to-cart">
                            <?php echo $product_add_to_cart_button; ?>
                        </div>

                    </div>
                </div>


            <?php } ?>
        </div>

        <!-- separate loop for product variations -->
        <?php foreach ($products as $product) {

            // product id
            $product_id = $product->get_id();

            // if is variable product
            if ($product->is_type('variable')) {

                // get variations
                $product_variations = $product->get_available_variations();

                // if there are variations
                if (!empty($product_variations)) {

                    // render variation modal
                    $this->render_variation_modal($product, $product_variations);
                }
            }
        } ?>

    <?php


        // output buffer
        $output = ob_get_clean();

        // echo output
        echo $output;
    }

    /**
     * Render variation modal
     * @param object $product - product object
     * @param array $product_variations - product variations
     * @return void
     * 
     * @since 1.0.0
     */
    public function render_variation_modal($product, $product_variations)
    {

        // product id
        $product_id = $product->get_id();

        // product html
    ?>
        <div id="sbcu-variation-modal-<?php echo $product_id; ?>" class="sbcu-variation-modal d-none p-absolute pt-5">
            <div class="sbcu-variation-modal-inner p-relative">

                <!-- close modal button -->
                <span class="sbcu-close-modal p-absolute">x</span>

                <!-- variations -->
                <div class="sbcu-variation-modal-variations d-flex flex-wrap">

                    <?php foreach ($product_variations as $product_variation) {

                        // variation id
                        $variation_id = $product_variation['variation_id'];

                        // get product
                        $variation_product = wc_get_product($variation_id);

                        // variation title
                        $variation_title = get_the_title($variation_id);

                        // variation image
                        $variation_image = wp_get_attachment_image($product_variation['image_id'], 'large');

                        // variation add to cart button if in stock, else disabled with 'Out of Stock' text
                        if ($variation_product->is_in_stock()) {

                            // variation add to cart url
                            $variation_add_to_cart_url = $variation_product->add_to_cart_url();

                            // variation add to cart button
                            $variation_add_to_cart_button = '<button title="' . __('Add to cart', SBWC_CU_TDOM) . '" data-href="' . $variation_add_to_cart_url . '" data-variation_id="' . $variation_id . '" class="button sbcu-carousel-item-add-to-cart-button sbcu-add-to-cart w-100 has-regular-font-size">' . __('Add to Cart', SBWC_CU_TDOM) . '</a>';
                        } else {
                            // variation add to cart button
                            $variation_add_to_cart_button = '<button title="' . __('Unfortunately this product is currently out of stock', SBWC_CU_TDOM) . '" href="#" class="button sbcu-carousel-item-add-to-cart-button sbcu-add-to-cart w-100 has-regular-font-size disabled">' . __('Out of Stock', SBWC_CU_TDOM) . '</a>';
                        }

                        // variation html
                    ?>
                        <div id="sbcu-variation-modal-variation-<?php echo $variation_id; ?>" class="sbcu-variation-modal-variation ps-2 pe-2 pt-2 pb-2 mb-2">
                            <div class="sbcu-variation-modal-variation-inner">

                                <!-- variation image -->
                                <div class="sbcu-variation-modal-variation-image pb-3">
                                    <?php echo $variation_image; ?>
                                </div>

                                <!-- variation title -->
                                <div class="sbcu-variation-modal-variation-title pb-3 has-regular-font-size text-center font-weight-semi-bold">
                                    <?php echo $variation_title; ?>
                                </div>

                                <!-- variation price -->
                                <div class="sbcu-variation-modal-variation-price pb-4 has-regular-font-size text-center text-grey font-weight-semi-bold">
                                    <?php echo $variation_product->get_price_html(); ?>
                                </div>

                                <!-- variation add to cart button -->
                                <div class="sbcu-variation-modal-variation-add-to-cart-button">
                                    <!-- add to cart button, which triggers ajax request on click -->
                                    <?php echo $variation_add_to_cart_button; ?>
                                </div>

                            </div>
                        </div>
                    <?php } ?>
                </div>

            </div>
        </div>

        <!-- modal overlay -->
        <div id="sbcu-variation-modal-overlay-<?php echo $product_id; ?>" class="sbcu-variation-modal-overlay d-none p-fixed"></div>

    <?php
    }

    /**
     * Add css and js to single product page
     * @since 1.0.0
     * @return void
     */
    public function add_css_js()
    {

        // if is product single
        if (is_product()) {

            // css
            wp_enqueue_style('sbcu-front-css', $this->front_css(), [], time(), 'all');

            // js
            wp_enqueue_script('sbcu-front-js', $this->front_js(), array('jquery'), time(), true);
        }
    }

    /**
     * Private function to check of variation is part of products linked by variations
     * 
     * @param int $variation_id - variation id
     * @return bool - true if variation is part of products linked by variations, else false
     * @since 1.0.0
     */
    private function is_variation_part_of_linked_products($variation_id)
    {

        // get products linked by variations option
        $products_linked_by_variations = get_option('plgfymao_all_rulesplgfyplv');

        // debug
        // return $products_linked_by_variations;

        // holds all variations
        $all_variations = [];

        // loop through products linked by variations
        foreach ($products_linked_by_variations as $index => $linked_data) :

            // get applied on ids
            $applied_on_ids = $linked_data['apllied_on_ids'];

            // loop through applied on ids
            foreach ($applied_on_ids as $aid) :
                $all_variations[] = $aid;
            endforeach;

        endforeach;

        return in_array($variation_id, $all_variations);
    }

    /**
     * Front CSS
     * 
     * @since 1.0.0
     * 
     * @return string - css
     */
    public function front_css()
    {

        // css
    ?>
        <style>
            /* modal stuff */
            .sbcu-variation-modal-overlay {
                width: 100vw;
                height: 100vh;
                position: fixed;
                z-index: 10000;
                background: #000000d6;
                top: 0;
                left: 0;
            }

            .sbcu-variation-modal {
                background: white;
                padding: 20px;
                border-radius: 5px;
                left: -22vw;
                top: -23vh;
                z-index: 100000;
                width: 42vw;
            }

            span.sbcu-close-modal.p-absolute {
                right: -34px;
                border: 1px solid #666;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                text-align: center;
                background: #efefef;
                color: #666;
                cursor: pointer;
                top: -37px;
                line-height: 1.8;
            }

            .sbcu-variation-modal-variation {
                flex: 0 0 50%;
            }

            .sbcu-variation-modal-variations.d-flex.flex-wrap {
                align-items: flex-end;
            }

            /* carousel stuff */
            .sbcu-carousel-item {
                background: #fff;
                padding: 10px;
                margin: 10px;
                border: 1px solid #eee;
            }

            .owl-theme .owl-nav .owl-next {
                right: 35px;
                top: 178px;
                background: white;
            }

            .owl-theme .owl-nav .owl-prev {
                left: 35px;
                top: 178px;
                background: white;
            }

            button.owl-prev::before,
            button.owl-next::before {
                content: '';
            }

            .sbcu-carousel {
                right: 10px;
                margin-bottom: 30px;
            }

            .sbcu-carousel-item-price>ins>span {
                color: var(--wp--preset--color--primary);
                font-size: 1.2em;
            }

            .sbcu-variation-modal-variation-price>ins>span {
                color: var(--wp--preset--color--primary);
                font-size: 1.3em;
            }

            .sbcu-add-to-cart>i::before {
                content: "\2713";
                color: white !important;
                margin-right: 5px;
            }

            /* responsiveness */

            /* 1440px */
            @media (max-width: 1440px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 154px;
                }

                .sbcu-variation-modal {
                    left: -28vw;
                    width: 55vw;
                }
            }

            /* 1366px */
            @media (max-width: 1366px) {}

            /* 1280px */
            @media (max-width: 1280px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 140px;
                }

                .sbcu-variation-modal {
                    left: -33vw;
                    width: 64vw;
                }
            }

            /* 1024px */
            @media (max-width: 1024px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 110px;
                }

                .sbcu-carousel-item-title.has-small-font-size.text-center.pb-3.font-weight-semi-bold>a,
                button.button.sbcu-variation-modal-trigger.w-100,
                .sbcu-carousel-item-add-to-cart>a {
                    font-size: 0.9em;
                }

                .sbcu-variation-modal {
                    left: -38vw;
                    width: 74vw;
                }

            }

            /* 768px */
            @media (max-width: 768px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 176px;
                }

                .sbcu-carousel-item-title.has-small-font-size.text-center.pb-3.font-weight-semi-bold>a,
                button.button.sbcu-variation-modal-trigger.w-100,
                .sbcu-carousel-item-add-to-cart>a {
                    font-size: initial;
                }

                h5#sbcu-title {
                    text-align: center;
                }

                .sbcu-variation-modal {
                    left: 4vw;
                    width: 90vw;
                    top: 40vh;
                }
            }

            /* 425px */
            @media (max-width: 425px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 200px;
                }

                .sbcu-carousel {
                    right: 0px;
                }

                .sbcu-variation-modal-variation {
                    flex: 0 0 100%;
                }
            }

            /* 375px */
            @media (max-width: 375px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 178px;
                }

                .sbcu-variation-modal-variation-title.pb-3.has-regular-font-size.text-center.font-weight-semi-bold {
                    font-size: 1.2em;
                }

                span.sbcu-close-modal.p-absolute {
                    right: -12px;
                    top: -18px;
                }

                .sbcu-variation-modal {
                    left: 1vw;
                    width: 98vw;
                }

            }

            /* 320px */
            @media (max-width: 320px) {

                .owl-theme .owl-nav .owl-next,
                .owl-theme .owl-nav .owl-prev {
                    top: 146px;
                }
            }
        </style>
    <?php

    }

    /**
     * Front JS
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function front_js()
    {

        // carousel id
        global $sbcu_carousel_id;

        // js
    ?>
        <script>
            $ = jQuery.noConflict();

            $(document).ready(function() {

                // ---------------
                // setup carousel
                // ---------------
                $('#<?php echo $sbcu_carousel_id; ?>').owlCarousel({
                    loop: true,
                    margin: 10,
                    nav: true,
                    dots: false,
                    autoplay: false,
                    responsive: {
                        0: {
                            items: 1
                        },
                        600: {
                            items: 2
                        },
                        1000: {
                            items: 2
                        }
                    }
                });

                // ------------------------------
                // show variation options modal 
                // and modal overlay on click
                // ------------------------------
                $('.sbcu-variation-modal-trigger').on('click', function(e) {

                    // prevent default
                    e.preventDefault();


                    // if width is more than 768px
                    if ($(window).width() > 768) {
                        $('html, body').animate({
                            scrollTop: 0
                        }, 'fast');
                    }

                    // get product id data attribute
                    var product_id = $(this).data('product_id');

                    console.log('click');

                    // show modal
                    $('#sbcu-variation-modal-' + product_id).removeClass('d-none');

                    // show close button
                    $('#sbcu-variation-modal-' + product_id + ' .sbcu-close-modal').removeClass('d-none');

                    // show modal overlay
                    $('#sbcu-variation-modal-overlay-' + product_id).removeClass('d-none');

                });

                // hide variation options modal and modal overlay on click
                $('.sbcu-variation-modal-overlay').on('click', function(e) {

                    // prevent default
                    e.preventDefault();

                    // hide modal
                    $('.sbcu-variation-modal').addClass('d-none');

                    // hide modal overlay
                    $('.sbcu-variation-modal-overlay').addClass('d-none');

                });

                // hide modal on .sbcu-close-modal click
                $('.sbcu-close-modal').on('click', function(e) {

                    // prevent default
                    e.preventDefault();

                    // hide modal
                    $('.sbcu-variation-modal').addClass('d-none');

                    // hide modal overlay
                    $('.sbcu-variation-modal-overlay').addClass('d-none');

                    // hide this
                    $(this).addClass('d-none');

                });

                // ------------
                // add to cart
                // ------------
                $(document).on('click', 'button.sbcu-carousel-item-add-to-cart-button', function(e) {

                    // prevent default
                    e.preventDefault();

                    // button
                    var button = $(this);

                    // change button text to working...
                    button.text('<?php _e('Working...', SBWC_CU_TDOM); ?>');

                    // add to cart url
                    var add_to_cart_url = $(this).data('href');

                    // ajax url
                    var ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';

                    var data = {
                        'action': 'sbcu_add_to_cart',
                        '_ajax_nonce': '<?php echo wp_create_nonce('sbcu_add_to_cart'); ?>',
                        'atc_url': add_to_cart_url,
                    }

                    $.post(ajax_url, data, function(response) {

                        // if response.success is true, change button text to 'added to cart' and prepend checkmark, else display error message and reload page
                        if (response.success) {

                            // change button text
                            button.text('<?php _e('Added to cart', SBWC_CU_TDOM); ?>');

                            // prepend checkmark
                            button.prepend('<i class="fas fa-check"></i>');

                            // hide popup after 5 seconds
                            setTimeout(() => {
                                $('.sbcu-variation-modal, .sbcu-close-modal, .sbcu-variation-modal-overlay').addClass('d-none');
                            }, 3000);

                        } else {

                            // display error message
                            alert(response.data.message);

                            // reload page
                            location.reload();

                        }

                    });

                });

                // ------------------------------------------------------------------
                // set min height of .sbcu-carousel-item-title based on tallest title
                // ------------------------------------------------------------------
                // if width is more than 425px
                if ($(window).width() > 425) {

                    var tallest_title_height = 0;

                    $('.sbcu-carousel-item-title').each(function() {

                        // get height of this title
                        var this_title_height = $(this).height();

                        // if this title height is greater than tallest_title_height, set tallest_title_height to this_title_height
                        if (this_title_height > tallest_title_height) {
                            tallest_title_height = this_title_height;
                        }

                    });

                    // set tallest_title_height to all .sbcu-carousel-item-title
                    $('.sbcu-carousel-item-title').height(tallest_title_height);

                }

            });
        </script>
<?php
    }

    /**
     * Add to cart
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public static function sbcu_add_to_cart()
    {

        // check nonce
        check_ajax_referer('sbcu_add_to_cart', 'nonce');

        // debug
        // wp_send_json($_POST);

        // get add to cart url
        $add_to_cart_url = $_POST['atc_url'];

        // get url attributes
        $url_attributes = parse_url($add_to_cart_url);

        // holds product cart key
        $product_cart_key = '';

        // get query string
        parse_str($url_attributes['query'], $query_string);

        // if variation id in query string, add variation to cart
        if (isset($query_string['variation_id'])) {

            // add to cart
            $product_cart_key = WC()->cart->add_to_cart($query_string['add-to-cart'], 1,  $query_string['variation_id']);
        }

        // if no variation id in query string, add product to cart
        else {

            // add to cart
            $product_cart_key =  WC()->cart->add_to_cart($query_string['add-to-cart'], 1);
        }

        // if product cart key is not empty, send success, else send error
        if (!empty($product_cart_key)) {

            // send success
            wp_send_json(array(
                'success' => true,
                'product_cart_key' => $product_cart_key,
                'message' => 'Product added to cart.'
            ));
        } else {

            // send error
            wp_send_json(array(
                'success' => false,
                'message' => 'Error adding product to cart.'
            ));
        }
    }
}

// get instance
SBCU_Front::get_instance();


?>