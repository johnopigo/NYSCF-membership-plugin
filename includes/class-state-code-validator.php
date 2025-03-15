<?php
class StateCodeValidator {
    public static function validate_state_code($state_code) {
        $state_code = sanitize_text_field( $state_code );
        // Regex pattern for state code validation
        $pattern = '/^[A-Z]{2}\/(?:1[7-9]|2[0-9])[ABC]\/\d{4,5}$/';
        return preg_match($pattern, $state_code) === 1;
    }

    public static function getUserStatus($state_code) {
        // Extract year from state code
        preg_match('/\/(\d{2})[A-Z]\//', $state_code, $matches);
        $year = intval($matches[1]);

        if ($year <= 22) {
            return 'AJUWAYA';
        } else {
            return 'CORPER SHUN';
        }
    }
}