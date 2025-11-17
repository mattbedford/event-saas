<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


//Adding correct template page for checkout
function checkout_setup( $template ) {

    if(get_option('ticket_price') === false) return;
    if ( is_page( 'checkout' )) {

        $plugindir = dirname( __FILE__ );
        $template = $plugindir . '/templates/checkout_template.php';
        wp_register_style('checkout-styles', plugins_url('/assets/checkout_styles.css',__FILE__ ));
        wp_register_script( 'checkout-scripts', plugins_url('/assets/checkout_scripts.js',__FILE__ ), "", "", true);
        wp_register_script('slim-select', 'https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.1/slimselect.min.js', "", "", false);
        wp_register_style('slim-styles', 'https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.27.1/slimselect.min.css');
        wp_enqueue_script('checkout-scripts');
        wp_enqueue_style('checkout-styles');
        wp_enqueue_style('slim-styles');
        wp_enqueue_script('slim-select');
    }
    return $template;

}
add_action( 'template_include', 'checkout_setup' );


function success_page_setup( $template ) {

    if(get_option('ticket_price') === false) return;
    if ( is_page( 'success' )) {

        $plugindir = dirname( __FILE__ );
        $template = $plugindir . '/templates/success_template.php';
    }
    return $template;

}
add_action( 'template_include', 'success_page_setup' );



function get_badge_page_setup( $template ) {

    if ( is_page( 'get-badge' )) {

        $plugindir = dirname( __FILE__ );
        $template = $plugindir . '/templates/get_badge_template.php';
    }
    return $template;

}
add_action( 'template_include', 'get_badge_page_setup' );


//Checkout helper functions
//Set new ajax nonce name on checkout along with jquery
function add_coupon_check_nonce() {
    if(is_page('checkout')) {
        ?>
        <script>
            var user_ajax_nonce = '<?php echo wp_create_nonce( "secure_nonce_name" ); ?>';
            var user_admin_url = '<?php echo admin_url( "admin-ajax.php" ); ?>';
        </script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <?php
    }
}
add_action ( 'wp_head', 'add_coupon_check_nonce' );

//Include ajax var for interaction with username checker
add_action( 'wp_ajax_check_submitted_coupon', 'check_submitted_coupon' );
add_action( 'wp_ajax_nopriv_check_submitted_coupon', 'check_submitted_coupon' );

function check_submitted_coupon() {
    check_ajax_referer( 'secure_nonce_name', 'sureandsecret' );
    $coupon_to_check =  htmlspecialchars( stripslashes( trim( $_POST['submitted_coupon'] ) ) );
    require_once(dirname( __DIR__ ) . '/eventer/checkout-scripts/CouponValidator.php');
    $res = new CouponValidator($coupon_to_check);
    echo json_encode($res->coupon_result);
    die();
}

add_action('wp_head','checkout_headers');
function checkout_headers() {
    if(is_page('checkout')) {
        echo '<script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>';
        echo '<script src="https://js.stripe.com/v3/"></script>';
    }
}


// Register checkout ajax hook
add_action('admin_post_nopriv_eventer_checkout', 'eventer_checkout_handler');
add_action('admin_post_eventer_checkout', 'eventer_checkout_handler');

function eventer_checkout_handler() {
    error_log("We hit the checkout handler correctly");
    if (!isset($_POST['eventer_checkout_nonce']) || !wp_verify_nonce($_POST['eventer_checkout_nonce'], 'eventer_checkout_action')) {
        error_log("nonce failed. Dammit.");
        wp_die('Security check failed');
    }
    require_once(dirname( __DIR__ ) . '/eventer/checkout-scripts/checkout_init.php');
    exit;
}