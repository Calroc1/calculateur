<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\Extension\Core\Type\FileType;

/**
 * FormType pour upload de fichier
 */
class UploadType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => 0,
            'fileType' => 'default',
            'maxSize' => '2M'
        ]);                
        $resolver->setNormalizer('constraints', function (Options $options, $constraints){
            if($options['fileType']){
                if($options['fileType'] == 'image'){
                    $mimeTypes = [
                        "image/*"
                    ];
                    $mimeTypesMessage = 'Les types de fichiers autorisés sont: .jpg, .jpeg, .png';
                }
                else if($options['fileType'] == 'yaml'){
                    $mimeTypes = [
                        "application/x-yaml",
                        "text/yaml",
                        "application/vnd.yaml",
                        "text/vnd.yaml",
                        "application/yaml",
                        "text/plain",
                    ];
                    $mimeTypesMessage = 'Les types de fichiers autorisés sont: .yml';
                }
                else{
                    $mimeTypes = [
                        "application/pdf",
                        "image/*",
                        "application/msword",
                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    ];
                    $mimeTypesMessage = 'Les types de fichiers autorisés sont: .pdf, .jpg, .jpeg, .png, .doc, .docx';
                }
                $constraints[] = new Assert\File([
                    'mimeTypes' => $mimeTypes,
                    'mimeTypesMessage' => $mimeTypesMessage,
                    'maxSize' => $options['maxSize']                
                ]);
            }            
            return $constraints;
        });
    }

    public function getParent()
    {
        return FileType::class;
    }
}