<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Update;

use Magento\Framework\HTTP\Client\CurlFactory;

/**
 * Unique point de sortie réseau de Maxcode_Core : un GET vers repo.packagist.org, lancé seulement quand
 * l'administrateur clique sur « Vérifier les mises à jour ».
 *
 * L'hôte est figé : aucune autre adresse n'est acceptée. Rien n'est envoyé sur le site (ni adresse, ni liste de
 * modules, ni identifiant) : la requête ne contient que le nom d'un paquet public.
 */
class Fetcher
{
    public const ALLOWED_PREFIX = 'https://repo.packagist.org/';

    private const TIMEOUT_SECONDS = 8;

    /**
     * @param CurlFactory $curlFactory Fabrique de clients HTTP.
     */
    public function __construct(private readonly CurlFactory $curlFactory)
    {
    }

    /**
     * @param string $url Adresse sous https://repo.packagist.org/.
     * @return array{status:int,body:string}
     * @throws \RuntimeException Adresse refusée ou serveur injoignable.
     */
    public function get(string $url): array
    {
        if (!str_starts_with($url, self::ALLOWED_PREFIX)) {
            throw new \RuntimeException('Adresse refusée : seule repo.packagist.org est interrogée.');
        }
        $curl = $this->curlFactory->create();
        $curl->setTimeout(self::TIMEOUT_SECONDS);
        $curl->addHeader('Accept', 'application/json');
        try {
            $curl->get($url);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Packagist est injoignable.', 0, $exception);
        }
        $status = (int) $curl->getStatus();
        if ($status === 0) {
            throw new \RuntimeException('Packagist est injoignable.');
        }

        return ['status' => $status, 'body' => (string) $curl->getBody()];
    }
}
