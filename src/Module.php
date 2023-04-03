<?php

namespace Fluid22\Courses;

use Fluid22\Courses\Conditions\HasAccessToCourse;
use function Fluid22\Module\container;

class Module extends \Fluid22\Module\Module
{

    /**
     * @inheritDoc
     */
    public function setup() {
        container()->add( HasAccessToCourse::class );

        add_filter( 'product_type_selector', array( $this, 'product_types' ) );
        add_filter( 'woocommerce_product_class', array( $this, 'product_class' ), 10, 2 );

        add_action( 'admin_footer', array( $this, 'admin_course_product_js' ) );

        add_action( 'woocommerce_course_add_to_cart', 'woocommerce_simple_add_to_cart' );
        add_action( 'woocommerce_add_to_cart_handler_course', array( $this, 'add_course_to_cart' ) );

        add_filter( 'woocommerce_add_cart_item', array( $this, 'add_course_cart_item' ) );
        add_filter( 'woocommerce_get_cart_contents', array( $this, 'add_course_cart_items' ) );
        add_action( 'woocommerce_cart_product_cannot_add_another_message', array( $this, 'disable_course_cannot_add_another_message' ), 10, 2 );

        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'handle_create_order_line_item' ), 10, 4 );
        add_action( 'woocommerce_payment_complete', array( $this, 'handle_payment_complete' ) );

        add_filter( 'woocommerce_get_product_from_item', array( $this, 'get_product_from_line_item' ), 10, 3 );

        add_action( 'elementor/theme/register_conditions', array( $this, 'register_conditions' ) );
    }

    /**
     * Add our new product types to WooCommerce
     *
     * @param   array $types
     * @return  array
     */
    public function product_types( $types ) {
        $types['course'] = __( 'Course' );

        return $types;
    }

    /**
     * Match type to the correct product class
     *
     * @param   string $class
     * @param   string $type
     * @return  string
     */
    public function product_class( $class, $type ) {
        if ( 'course' === $type ) {
            return CourseProduct::class;
        }

        return $class;
    }

    /**
     * Add single course to cart
     *
     * @return bool
     * @throws \Exception
     */
    public function add_course_to_cart() {
        $product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( wp_unslash( $_REQUEST['add-to-cart'] ) ) );
        $course_id = absint( wp_unslash( $_REQUEST['course'] ) );

        if ( 0 === $course_id ) {
            wc_add_notice( 'No course selected', 'error' );
            return false;
        }

        $quantity = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( wp_unslash( $_REQUEST['quantity'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );

        if ( $passed_validation && false !== WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), array( 'course_id' => $course_id ) ) ) {
            wc_add_to_cart_message( array( $product_id => $quantity ), true );
            return true;
        }

        return false;
    }

    /**
     * Set up course products in cart
     *
     * @param array $cart_item_data
     * @return array
     */
    public function add_course_cart_item( $cart_item_data ) {
        if ( 'course' === $cart_item_data['data']->get_type() ) {
            $cart_item_data['data']->set_course_id( $cart_item_data['course_id'] );
            $cart_item_data['data']->set_name( get_the_title( $cart_item_data['course_id'] ) );
        }

        return $cart_item_data;
    }

    /**
     * Add course id to products
     *
     * @param $cart
     * @return void
     */
    public function add_course_cart_items( $cart_contents ) {
        foreach ( $cart_contents as $key => $item ) {
            if ( 'course' === $item['data']->get_type() ) {
                $cart_contents[$key]['data']->set_course_id( $item['course_id'] );
                $cart_contents[$key]['data']->set_name( get_the_title( $item['course_id'] ) );
            }
        }

        return $cart_contents;
    }

    /**
     * Disable error on course
     *
     * @param $message
     * @param $product
     * @return void
     * @throws \Exception
     */
    public function disable_course_cannot_add_another_message( $message, $product ) {
        if ( 'course' === $product->get_type() ) {
            throw new \Exception();
        }
    }

    /**
     * Save course ID to order line item
     *
     * @param \WC_Order_Item_Product $item
     * @param string $cart_item_key
     * @param array $values
     * @param \WC_Order $order
     */
    public function handle_create_order_line_item( $item, $cart_item_key, $values, $order ) {
        if ( 'course' === $values['data']->get_type() ) {
            $item->set_name( get_the_title( $values['data']->get_course_id() ) );

            $item->add_meta_data( '_course_id', $values['data']->get_course_id() );
        }
    }

    /**
     * Get product from order line item
     *
     * @param \WC_Product $product
     * @param \WC_Order_Item_Product $line_item
     * @param \WC_Order $order
     * @return \WC_Product
     */
    public function get_product_from_line_item( $product, $line_item, $order ) {
        // safety check
        if ( ! is_a( $product, \WC_Product::class ) ) {
            return $product;
        }

        if ( false !== ( $course_id = $line_item->get_meta( '_course_id' ) ) ) {
            $product = new CourseProduct( $product->get_id() );

            $product->set_course_id( $course_id );
            $product->set_name( get_the_title( $course_id ) );
        }

        return $product;
    }

    /**
     * After the payment has cleared, add the course id to the user's meta
     *
     * @param $order_id
     * @return void
     */
    public function handle_payment_complete( $order_id ) {
        $order = wc_get_order( $order_id );
        $user_course_ids = (array) get_user_courses( $order->get_user_id() );

        foreach ( $order->get_items() as $item ) {
            if ( $item->get_meta( '_course_id' ) ) {
                $user_course_ids[] = (int) $item->get_meta( '_course_id' );
            }
        }

        update_user_meta(
            $order->get_user_id(),
            get_courses_user_meta_key(),
            array_unique( $user_course_ids )
        );
    }

    /**
     * Add admin js to products
     *
     * @return void
     */
    public function admin_course_product_js() {
        if ( 'product' != get_post_type() ) {
            return;
        }

        ?>
        <script type='text/javascript'>
            jQuery( '.options_group.pricing' )
                .addClass( 'show_if_course' );
        </script>
        <?php
    }

    /**
     * Add our custom render conditions for Elementor
     *
     * @param $conditions_manager
     */
    public function register_conditions( $conditions_manager ) {
        $conditions_manager->get_condition( 'singular' )->register_sub_condition( container()->get( HasAccessToCourse::class ) );
    }
}