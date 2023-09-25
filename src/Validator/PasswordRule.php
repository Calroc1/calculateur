<?php
namespace App\Validator;

use Symfony\Component\Validator\Constraints\Regex;

/**
 * Règle de validation custom pour mot de passe
 * 
 * @Annotation
 */
class PasswordRule extends Regex
{
    public $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^\w\s])(?=.{8,})/";
    public $message = "Le mot de passe doit contenir un minimum de 8 caractères, et doit contenir au moins une majuscule, une minuscule, un chiffre, et un caractère spécial.";
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions(){
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return Regex::class.'Validator';
    }
}