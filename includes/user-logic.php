<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Checks if myCred plugin is active and available.
 *
 * @return bool True if myCred is active, false otherwise.
 */
function ai_is_mycred_active() {
    return function_exists('mycred_get_users_balance');
}

/**
 * Checks if the current user has enough credits and permission.
 *
 * @param int $required_credits The amount of credits needed for the action.
 * @return bool True if the user has permission and enough credits, false otherwise.
 */
function ai_check_user_permission( $required_credits = 1 ) {
    if ( ! is_user_logged_in() ) {
        return false;
    }

    if ( ! function_exists( 'mycred_get_users_balance' ) ) {
        return false;
    }

    $user_id = get_current_user_id();
    $balance = mycred_get_users_balance( $user_id );

    if ( !is_numeric($balance) || $balance < $required_credits ) {
        return false;
    }

    // Add any other permission checks if needed

    return true;
}

/**
 * Deducts credits from the current user's balance.
 *
 * @param int    $amount The amount of credits to deduct.
 * @param string $log_entry Log entry template.
 * @param int    $ref_id Reference ID (optional).
 * @param mixed  $data Additional data for the log entry (optional).
 * @return bool True on success, false on failure.
 */
function ai_deduct_credits( $amount, $log_entry = 'Image generation cost', $ref_id = 0, $data = null ) {
    if ( ! is_user_logged_in() || ! function_exists( 'mycred_subtract' ) ) {
        return false;
    }

    $user_id = get_current_user_id();

    // First, double-check the balance before attempting deduction
    if (!ai_check_user_permission($amount)) {
        return false;
    }

    $result = mycred_subtract(
        'image_generation', // Reference hook ID
        $user_id,
        -$amount, // myCred expects a negative value for subtraction
        $log_entry, // Log entry template
        $ref_id, // Reference ID (e.g., post ID of the generated image)
        $data // Additional data
    );

    // 如果结果为false或0，表示扣除失败
    if ($result === false || $result === 0) {
        return false;
    }

    return true; // Deduction successful
}

/**
 * Gets the current user's myCred balance.
 *
 * @return int The user's balance or 0 on failure.
 */
function ai_get_mycred_balance() {
    if ( ! is_user_logged_in() ) {
        return 0;
    }

    if ( ! function_exists( 'mycred_get_users_balance' ) ) {
        return 0;
    }

    $user_id = get_current_user_id();
    $balance = mycred_get_users_balance( $user_id );
    
    return is_numeric($balance) ? intval($balance) : 0;
}

?>