<?php

namespace Fluid22\Courses\Conditions;

use function Fluid22\Courses\get_courses_post_type;
use function Fluid22\Courses\get_user_courses;

class HasAccessToCourse extends \ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base
{
    public static function get_type() {
        return 'singular';
    }

    public static function get_priority() {
        return 10;
    }

    public function get_name() {
        return 'has_access_to_course';
    }

    public function get_label() {
        return esc_html__( 'Has Access To Course', 'elementor-pro' );
    }

    public function check( $args ) {
        if ( ! is_singular( get_courses_post_type() ) ) {
            return false;
        }

        return apply_filters(
            'fluid22_user_has_access_to_course',
            in_array(
                get_the_ID(),
                get_user_courses()
            )
        );
    }
}