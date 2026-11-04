<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Update;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Dernière version stable d'un paquet Maxcode public, lue sur Packagist.
 */
class PackagistClient
{
    private const PACKAGE_PATTERN = '#^maxcode/module-[a-z0-9-]+$#';
    private const STABLE_VERSION = '/^v?(\d+\.\d+\.\d+)$/';

    /**
     * @param Fetcher $fetcher Accès réseau (repo.packagist.org seulement).
     * @param Json    $json    Sérialiseur JSON.
     */
    public function __construct(
        private readonly Fetcher $fetcher,
        private readonly Json $json
    ) {
    }

    /**
     * @param string $package Nom du paquet Composer (maxcode/module-xxx).
     * @return string|null Version « x.y.z », ou null si le paquet n'est pas publié ou n'a aucune version stable.
     * @throws \RuntimeException Packagist injoignable ou en erreur.
     */
    public function latestStableVersion(string $package): ?string
    {
        if (preg_match(self::PACKAGE_PATTERN, $package) !== 1) {
            return null;
        }
        $response = $this->fetcher->get(Fetcher::ALLOWED_PREFIX . 'p2/' . $package . '.json');
        if ($response['status'] === 404) {
            return null;
        }
        if ($response['status'] !== 200) {
            throw new \RuntimeException('Packagist a répondu ' . $response['status'] . '.');
        }
        try {
            $data = $this->json->unserialize($response['body']);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Réponse de Packagist illisible.', 0, $exception);
        }

        $best = null;
        foreach ((array) ($data['packages'][$package] ?? []) as $release) {
            $version = is_array($release) ? ($release['version'] ?? null) : null;
            if (!is_string($version) || preg_match(self::STABLE_VERSION, $version, $match) !== 1) {
                continue;
            }
            if ($best === null || version_compare($match[1], $best, '>')) {
                $best = $match[1];
            }
        }

        return $best;
    }
}
