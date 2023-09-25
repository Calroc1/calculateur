<?php

namespace App\Twig;

use App\Entity\Emission\Rate;
use App\Entity\Support\Support;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;
use Twig\TwigFilter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Serializer\SerializerInterface;

use App\Service\EmissionService;

/**
 * Extension twig qui ajoute des filtres, des tests et des fonctions customs
 */
class AppExtension extends AbstractExtension
{
    protected $twig;
    protected $serializer;
    protected $emissionService;
    protected $em;

    public function __construct(Environment $twig, SerializerInterface $serializer, EmissionService $emissionService, EntityManagerInterface $em)
    {
        $this->twig = $twig;
        $this->serializer = $serializer;
        $this->emissionService = $emissionService;
        $this->em = $em;
    }

    public function getFilters(){
        return [
            new TwigFilter('serialize', [$this, 'serialize'])            
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('includeSVG', [$this, 'includeSVG']),
            new TwigFunction('showNotification', [$this, 'showNotification']),
            new TwigFunction('getClassName', [$this, 'getClassName']),
            new TwigFunction('getTotal', [$this, 'getTotal']),
            new TwigFunction('toTons', [$this, 'toTons']),
            new TwigFunction('toGrams', [$this, 'toGrams']),
            new TwigFunction('toKmVoiture', [$this, 'toKmVoiture']),
            new TwigFunction('toAnneesEmissionFr', [$this, 'toAnneesEmissionFr']),
            new TwigFunction('getPercent', [$this, 'getPercent']),
            new TwigFunction('getCampaignStatuses', [$this, 'getCampaignStatuses']),
            new TwigFunction('getShortcodes', [$this, 'getShortcodes']),
            new TwigFunction('str_replace', 'str_replace'),
            new TwigFunction('getEmissionRate', [$this, 'getEmissionRate']),
            new TwigFunction('getNestedTotal', [$this, 'getNestedTotal']),
            new TwigFunction('toFloat', [$this, 'toFloat']),
            new TwigFunction('getEnabledSupports', [$this, 'getEnabledSupports']),
            new TwigFunction('getSupportByName', [$this, 'getSupportByName']),
        ];
    }

    /**
     * Permet de récupérer un support de diffusion en fonction de son nom
     * 
     * @param string $name
     * 
     * @return Support|null
     */
    public function getSupportByName(string $name): ?Support
    {
        return $this->em->getRepository(Support::class)->findOneByName($name);
    }

    /**
     * Permet de récupérer le tableau des supports de diffusion actifs
     * 
     * @return array
     */
    public function getEnabledSupports(): array
    {
        return $this->em->getRepository(Support::class)->findEnabledSupports();
    }

    /**
     * Convertir une string en float
     * 
     * @param string $var
     * 
     * @return float
     */
    public function toFloat(string $var): float
    {
        return floatval(str_replace(',', '.', $var));
    }

    /**
     * Récupérer un facteur d'émission via son chemin
     *
     * @param array $names
     * 
     * @return Rate|null
     */
    public function getEmissionRate(array $names): ?Rate
    {
        return $this->emissionService->getRate(...$names);
    }

    /**
     * Serialiser un objet
     * 
     * @param mixed $object
     * @param string $group
     * @param string $type
     * 
     * @return mixed
     */
    public function serialize($object, string $group, string $type = 'json')
    {
        return $this->serializer->serialize($object, $type, [ 'groups' => $group ]);            
    }

    /**
     * Renvoyer le contenu d'un fichier svg
     * 
     * @param string $filepath
     * 
     * @return void
     */
    public function includeSVG(string $filepath): void
    {
        echo file_get_contents('media/pictos/' . $filepath);
    }

    /**
     * Affichage d'une notification
     * 
     * @param string $message
     * @param string $type
     * @param string $class
     * 
     * @return string
     */
    public function showNotification(string $message, string $type = 'success', string $class = ''): void
    {
        echo $this->twig->render('front/parts/_notification.html.twig', [
            'message' => $message,
            'type' => $type,
            'class' => $class
        ]);
    }

    /**
     * Récupérer la classe d'un objet
     * 
     * @param mixed $object
     * 
     * @return string
     */
    public function getClassName(Object $object): string
    {
        return strtolower((new \ReflectionClass($object))->getShortName());
    }

    /**
     * Récupérer la somme des éléments d'un tableau
     * 
     * @param array $array
     * 
     * @return float
     */
    public function getTotal(array $array): float
    {
        return array_sum($array);
    }

    /**
     * Récupérer la somme des éléments des sous-tableaux d'un tableau
     * 
     * @param array $array
     * 
     * @return float
     */
    public function getNestedTotal(array $array): float
    {
        $result = array();
        foreach ($array as $key => $value)
            $result[$key] = array_sum($value);
        return array_sum($result);
    }

    /**
     * Converti une donnée en kg vers une donnée en tonnes
     * 
     * @param float $kgValue
     * 
     * @return string
     */
    public function toTons(string $kgValue): string
    {
        return \App\Utils\Utils::toTons($kgValue);
    }

    /**
     * Converti une donnée en kg vers une donnée en grammes
     * 
     * @param float $kgValue
     * 
     * @return string
     */
    public function toGrams(string $kgValue): string
    {
        return \App\Utils\Utils::toGrams($kgValue);
    }

    /**
     * Permet de convertir une valeur en tonne vers une valeur exprimée en kilométrage de voiture
     * 
     * @param float $tonValue
     * 
     * @return float
     */
    public function toKmVoiture(float $tonValue): float
    {
        return round($tonValue * 1000000 / 160);
    }

    /**
     * Permet de convertir une valeur en tonne vers une valeur exprimée en années d’émissions d’un français
     * 
     * @param float $tonValue
     * 
     * @return float
     */
    public function toAnneesEmissionFr(float $tonValue): float
    {
        return round($tonValue / 9, 1);
    }

    /**
     * Permet de calculer le pourcentage d'une valeur par rapport au total
     * 
     * @param float $value
     * @param float $total
     * 
     * @return int
     */
    public function getPercent(float $value, float $total): int
    {
        if(!$value)
            return 0;
        return round(($value * 100) / $total);
    }

    /**
     * Permet de récupérer le tableau des status des campagnes
     * 
     * @return array
     */
    public function getCampaignStatuses(): array
    { 
        return \App\Utils\LabelHelpers::getCampaignStatuses();
    }

    /**
     * Permet de récupérer le tableau des shortcodes d'une chaîne de caractères
     * 
     * @param string $string
     * 
     * @return array
     */
    public function getShortcodes(string $string): array
    { 
        $return = [];
        foreach(\App\Service\ShortcodeService::SHORTCODES as $shortcode){
            $regex = str_replace('{{SHORTCODE}}', $shortcode, \App\Service\ShortcodeService::REGEX);
            preg_match_all($regex, $string, $matches);
            foreach($matches[0] as $i => $match){
                $type = $matches[1][$i];
                $data = null;
                switch($type){
                    case 'FIELD' :
                        $data = $matches[2][$i];
                        break;
                    case 'EMISSION':
                        $data = explode('.', $matches[2][$i]);
                        break;
                    case 'VAR':
                        $data = $matches[2][$i];
                        break;
                    case 'FCT':
                        $data = [
                            $matches[2][$i],
                            ($matches[4][$i] ?? null)
                        ];
                        break;
                }
                $return[$match] = [
                    'type' => $matches[1][$i],
                    'data' => $data 
                ];
            }
        }
        return $return;
    }
}
