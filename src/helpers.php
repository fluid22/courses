<?php

namespace Fluid22\Courses;

/**
 * Get the post type key
 *
 * @return string
 */
function get_courses_post_type() {
    return apply_filters( 'fluid22_courses_post_type', 'courses' );
}

/**
 * Get the user meta key for courses
 *
 * @return string
 */
function get_courses_user_meta_key() {
    return apply_filters( 'fluid22_courses_user_meta_key', 'courses' );
}

/**
 * Get the list of course ids that the user has purchased
 *
 * @param $user_id
 * @return false|int[]
 */
function get_user_courses( $user_id = 0 ) {
    if ( 0 === $user_id ) {
        if ( is_user_logged_in() ) {
            $user_id === get_current_user_id();
        } else {
            return array();
        }
    }

    $key = get_courses_user_meta_key();
    $arr = get_user_meta( $user_id, $key, true );

    return ( is_array( $arr ) ) ? $arr : array();
}