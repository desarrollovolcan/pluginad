<?php

namespace MAD\Utils;

class Validators {
    public static function validate_address( array $address ): void {
        $required = array( 'first_name', 'last_name', 'phone', 'country', 'city', 'address_1', 'postcode' );
        foreach ( $required as $field ) {
            if ( empty( $address[ $field ] ) ) {
                throw new \InvalidArgumentException( sprintf( 'Missing address field: %s', $field ) );
            }
        }

        foreach ( $address as $key => $value ) {
            if ( is_string( $value ) && strlen( $value ) > 255 ) {
                throw new \InvalidArgumentException( sprintf( 'Address field too long: %s', $key ) );
            }
        }
    }
}
