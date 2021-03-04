<?php

namespace LumenMicroservice\Classes;

// Slightly stolen from wordpress
class Sanitizer
{
    /**
     * Create a new sanitizer instance.
     * @return void
     */
    public function __construct() {}

    public static function utf8_uri_encode( $utf8_string, $length = 0 ) {
        $unicode = '';
        $values = array();
        $num_octets = 1;
        $unicode_length = 0;

        $string_length = strlen( $utf8_string );
        for ($i = 0; $i < $string_length; $i++ ) {

            $value = ord( $utf8_string[ $i ] );

            if ( $value < 128 ) {
                if ( $length && ( $unicode_length >= $length ) )
                    break;
                $unicode .= chr($value);
                $unicode_length++;
            } else {
                if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

                $values[] = $value;

                if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                    break;
                if ( count( $values ) == $num_octets ) {
                    if ($num_octets == 3) {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                        $unicode_length += 9;
                    } else {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                        $unicode_length += 6;
                    }

                    $values = array();
                    $num_octets = 1;
                }
            }
        }

        return $unicode;
    }

    private function seems_utf8($str) {
        $length = strlen($str);
        for ($i=0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) $n = 0; # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                    return false;
            }
        }
        return true;
    }

    public static function slugify($text) {
        $text = strip_tags($text);
        // Preserve escaped octets.
        $text = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $text);
        // Remove percent signs that are not part of an octet.
        $text = str_replace('%', '', $text);
        // Restore octets.
        $text = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $text);

        if (seems_utf8($text)) {
            if (function_exists('mb_strtolower')) {
                $text = mb_strtolower($text, 'UTF-8');
            }
            $text = utf8_uri_encode($text, 200);
        }

        $text = strtolower($text);
        $text = preg_replace('/&.+?;/', '', $text); // kill entities
        $text = str_replace('.', '-', $text);
        $text = preg_replace('/[^%a-z0-9 _-]/', '', $text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('|-+|', '-', $text);
        $text = trim($text, '-');

        return $text;
    }
}