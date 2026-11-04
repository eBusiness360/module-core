<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Update;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Dernières versions relevées lors de la dernière vérification demandée par l'administrateur.
 *
 * Stockées dans la configuration Magento (portée par défaut) : elles survivent à un vidage de cache et se
 * sauvegardent avec la base. Rien n'est écrit tant que l'administrateur n'a pas lancé de vérification.
 */
class LatestVersionStore
{
    public const PATH_LATEST = 'maxcode_core/updates/latest';
    public const PATH_CHECKED_AT = 'maxcode_core/updates/checked_at';

    private const VERSION = '/^\d+\.\d+\.\d+$/';

    /**
     * @param ScopeConfigInterface $scopeConfig Configuration.
     * @param WriterInterface      $writer      Écriture de configuration.
     * @param TypeListInterface    $cacheTypes  Types de cache (pour relire la configuration après écriture).
     * @param Json                 $json        Sérialiseur JSON.
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly WriterInterface $writer,
        private readonly TypeListInterface $cacheTypes,
        private readonly Json $json
    ) {
    }

    /**
     * Versions relevées, indexées par nom de paquet.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $stored = $this->scopeConfig->getValue(self::PATH_LATEST);
        if (!is_string($stored) || $stored === '') {
            return [];
        }
        try {
            $data = $this->json->unserialize($stored);
        } catch (\Throwable) {
            return [];
        }

        return is_array($data)
            ? array_filter(
                $data,
                static fn ($version, $package): bool => is_string($package) && is_string($version)
                    && preg_match(self::VERSION, $version) === 1,
                ARRAY_FILTER_USE_BOTH
            )
            : [];
    }

    /**
     * Date de la dernière vérification (ISO 8601, UTC), ou null si elle n'a jamais été faite.
     *
     * @return string|null
     */
    public function getCheckedAt(): ?string
    {
        $value = $this->scopeConfig->getValue(self::PATH_CHECKED_AT);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Enregistre le résultat d'une vérification.
     *
     * @param array<string, string> $versions Versions « x.y.z » par nom de paquet.
     * @return void
     */
    public function save(array $versions): void
    {
        $this->writer->save(self::PATH_LATEST, $this->json->serialize($versions));
        $this->writer->save(self::PATH_CHECKED_AT, gmdate('c'));
        $this->cacheTypes->cleanType('config');
    }
}
