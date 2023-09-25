<?php

namespace App\Utils;

/**
 * Fonctions diverses
 */
class Utils
{
    /**
     * Génération d'un token aléatoire
     * 
     * @return string
     */
    public static function generateToken() :string{
        $token = openssl_random_pseudo_bytes(16);
        $token = bin2hex($token);
        return $token;
    }

    /**
     * Convertir une chaîne de caractère en nom de fichier
     * 
     * @param string $string
     * 
     * @return string
     */
    public static function sanitizeForFilename(string $string):string{
        return preg_replace( '/[^a-z0-9]+/', '-', strtolower($string));
    }

    /**
     * Convertir une donnée en kg vers une donnée en tonnes
     * 
     * @param float $kgValue
     * 
     * @return string
     */
    public static function toTons(float $kgValue) :float{
        $tons = $kgValue/1000;
        return $tons > 1 ? round($tons) : round($tons, 2);
    }

    /**
     * Convertir une donnée en kg vers une donnée en grammes
     * 
     * @param float $kgValue
     * 
     * @return string
     */
    public static function toGrams(float $kgValue) :float{
        $grams = $kgValue*1000;
        return $grams > 1 ? round($grams) : round($grams, 2);
    }

    /**
     * Renvoie un tableau avec {$count} nuances de la couleur {color}
     * 
     * @param string $color
     * @param int $count
     * 
     * @return array
     */
    public static function getColorsFromMain(string $color, int $count) :array{
        if($count <= 0)
            return [];
        $percent = 100 / $count;
        $return = [];
        for($i=0;$i<$count;$i++){           
           $return[] = $color;
           $color = \App\Utils\Utils::adjustBrightness($color, $percent);
        }
        return $return;
    }

    /**
     * Permet de récupérer une nuance de la couleur fournie, en fonction d'un pourcentage
     * 
     * @param string $hex
     * @param int $steps
     * 
     * @return string
     */
    public static function adjustBrightness(string $hex, int $steps = 20) :string {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));    
        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
        }    
        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';    
        foreach ($color_parts as $color) {
            $color   = hexdec($color); // Convert to decimal
            $color   = max(0,min(255,$color + $steps)); // Adjust color
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
        }    
        return $return;
    }

    /**
     * Permet de convertir une couleur héxadécimale au format RVB
     * 
     * @param string $hex
     * 
     * @return string
     */
    public static function hexaToRgb(string $hex) :string{
        list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
        return "$r, $g, $b";
    }
}