<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Validates input data.
 * Example function - expand based on specific needs.
 *
 * @param mixed $input The input data to validate.
 * @param string $type The expected type or validation rule (e.g., 'prompt', 'integer').
 * @return bool True if valid, false otherwise.
 */
function ai_validate_input( $input, $type ) {
    switch ( $type ) {
        case 'prompt':
            // Example: Check if prompt is a non-empty string
            return is_string( $input ) && ! empty( trim( $input ) );
        case 'integer':
            return filter_var( $input, FILTER_VALIDATE_INT ) !== false;
        // Add more validation cases as needed
        default:
            return false;
    }
}

?>