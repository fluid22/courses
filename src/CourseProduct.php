<?php

namespace Fluid22\Courses;

class CourseProduct extends \WC_Product_Simple
{
    protected $extra_data = array(
        'course_id' => 0,
    );

    /**
     * Get internal type.
     *
     * @return string
     */
    public function get_type() {
        return 'course';
    }

    /*
     |-----------------------------
     | Getters
     |-----------------------------
     */

    public function get_course_id( $context = 'view' ) {
        return $this->get_prop( 'course_id', $context );
    }

    /*
     |-----------------------------
     | Setters
     |-----------------------------
     */

    public function set_course_id( $id ) {
        $this->set_prop( 'course_id', absint( $id ) );
    }

    /*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

    /**
     * Get the product's title. For products this is the product name.
     *
     * @return string
     */
    public function get_title() {
        $name = ( $this->get_course_id() !== 0 ) ? get_the_title( $this->get_course_id() ) : $this->get_name();

        return apply_filters( 'woocommerce_product_title', $name, $this );
    }

    /**
     * Product permalink.
     *
     * @return string
     */
    public function get_permalink() {
        return get_permalink( $this->get_course_id() );
    }

    /**
     * Returns the main product image.
     *
     * @param  string $size (default: 'woocommerce_thumbnail').
     * @param  array  $attr Image attributes.
     * @param  bool   $placeholder True to return $placeholder if no image is found, or false to return an empty string.
     * @return string
     */
    public function get_image( $size = 'woocommerce_thumbnail', $attr = array(), $placeholder = true ) {
        $image = '';

        if ( $this->get_course_id() ) {
            $image = wp_get_attachment_image( get_post_thumbnail_id( $this->get_course_id() ), $size, false, $attr );
        } elseif ( $this->get_image_id() ) {
            $image = wp_get_attachment_image( $this->get_image_id(), $size, false, $attr );
        } elseif ( $this->get_parent_id() ) {
            $parent_product = wc_get_product( $this->get_parent_id() );
            if ( $parent_product ) {
                $image = $parent_product->get_image( $size, $attr, $placeholder );
            }
        }

        if ( ! $image && $placeholder ) {
            $image = wc_placeholder_img( $size, $attr );
        }

        return apply_filters( 'woocommerce_product_get_image', $image, $this, $size, $attr, $placeholder, $image );
    }

    /**
     * @return bool
     */
    public function is_sold_individually() {
        return true;
    }
}