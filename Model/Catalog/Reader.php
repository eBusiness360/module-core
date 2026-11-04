<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Catalog;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Lit le catalogue des modules Maxcode embarqué dans le paquet (etc/catalog.json).
 *
 * Aucun appel réseau : le fichier est livré avec le module et change avec lui.
 */
class Reader
{
    private const DEFAULT_CONTACT_URL = 'https://www.ebusiness360.fr/contact/';
    private const DEFAULT_PUBLISHER_LINKS = [
        'site' => 'https://www.ebusiness360.fr/',
        'modules' => 'https://www.ebusiness360.fr/modules/',
        'news' => 'https://www.ebusiness360.fr/actus-tech-magento2/',
    ];

    /** @var Entry[]|null */
    private ?array $entries = null;
    private string $contactUrl = self::DEFAULT_CONTACT_URL;
    /** @var array<string, string> */
    private array $publisherLinks = self::DEFAULT_PUBLISHER_LINKS;

    /**
     * @param Json                        $json        Sérialiseur JSON.
     * @param File                        $file        Lecture de fichier.
     * @param ComponentRegistrarInterface $registrar   Registre des composants (chemin du module).
     * @param string                      $catalogFile Chemin du catalogue ; vide = celui du module.
     */
    public function __construct(
        private readonly Json $json,
        private readonly File $file,
        private readonly ComponentRegistrarInterface $registrar,
        private readonly string $catalogFile = ''
    ) {
    }

    /**
     * Toutes les entrées valides, dans l'ordre du fichier.
     *
     * @return Entry[]
     */
    public function getEntries(): array
    {
        $this->load();

        return $this->entries;
    }

    /**
     * L'entrée d'un module, ou null s'il n'est pas au catalogue.
     *
     * @param string $module Nom Magento du module.
     * @return Entry|null
     */
    public function getByModule(string $module): ?Entry
    {
        foreach ($this->getEntries() as $entry) {
            if ($entry->module === $module) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Adresse de contact, utilisée quand un module n'a pas de fiche.
     *
     * @return string
     */
    public function getContactUrl(): string
    {
        $this->load();

        return $this->contactUrl;
    }

    /**
     * Adresses de l'éditeur (eBusiness360), où les modules sont vendus : « modules » (la page des modules),
     * « site » (l'accueil) et « news » (l'actualité technique Magento 2).
     *
     * @return array{site:string,modules:string,news:string}
     */
    public function getPublisherLinks(): array
    {
        $this->load();

        return $this->publisherLinks;
    }

    /**
     * Charge et valide le catalogue une seule fois ; un fichier absent ou illisible donne un catalogue vide.
     *
     * @return void
     */
    private function load(): void
    {
        if ($this->entries !== null) {
            return;
        }
        $this->entries = [];

        $data = $this->readFile();
        if (isset($data['contact_url']) && $this->isHttpsUrl($data['contact_url'])) {
            $this->contactUrl = $data['contact_url'];
        }
        foreach (array_keys(self::DEFAULT_PUBLISHER_LINKS) as $key) {
            $url = $data['publisher'][$key] ?? null;
            if ($this->isHttpsUrl($url)) {
                $this->publisherLinks[$key] = $url;
            }
        }
        $seen = [];
        foreach ((array) ($data['modules'] ?? []) as $row) {
            $entry = is_array($row) ? $this->buildEntry($row) : null;
            if ($entry === null || isset($seen[$entry->module])) {
                continue;
            }
            $seen[$entry->module] = true;
            $this->entries[] = $entry;
        }
    }

    /**
     * Construit une entrée, ou null si une donnée obligatoire manque ou est invalide.
     *
     * @param array $row Ligne brute du fichier.
     * @return Entry|null
     */
    private function buildEntry(array $row): ?Entry
    {
        foreach (['module', 'package', 'type', 'name', 'description'] as $key) {
            if (!isset($row[$key]) || !is_string($row[$key]) || trim($row[$key]) === '') {
                return null;
            }
        }
        if (!in_array($row['type'], [Entry::TYPE_FREE, Entry::TYPE_PAID], true)) {
            return null;
        }
        $availability = $row['availability'] ?? Entry::AVAILABILITY_AVAILABLE;
        if (!in_array($availability, [Entry::AVAILABILITY_AVAILABLE, Entry::AVAILABILITY_SOON], true)) {
            return null;
        }
        $url = $this->isHttpsUrl($row['url'] ?? null) ? $row['url'] : null;

        return new Entry(
            $row['module'],
            $row['package'],
            $row['type'],
            $row['name'],
            $row['description'],
            $url,
            $availability,
            $this->isHttpsUrl($row['docs_url'] ?? null) ? $row['docs_url'] : null,
            $this->isVersion($row['latest_version'] ?? null) ? $row['latest_version'] : null
        );
    }

    /**
     * Seules les adresses HTTPS sont acceptées (jamais de javascript: ni de http:).
     *
     * @param mixed $url Valeur à tester.
     * @return bool
     */
    private function isHttpsUrl(mixed $url): bool
    {
        return is_string($url) && preg_match('#^https://[^\s"<>]+$#i', $url) === 1;
    }

    /**
     * Une version « x.y.z » (rien d'autre : pas de préfixe, pas de suffixe).
     *
     * @param mixed $version Valeur à tester.
     * @return bool
     */
    private function isVersion(mixed $version): bool
    {
        return is_string($version) && preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;
    }

    /**
     * Contenu décodé du fichier de catalogue.
     *
     * @return array
     */
    private function readFile(): array
    {
        $path = $this->catalogFile !== '' ? $this->catalogFile : $this->defaultPath();
        try {
            if ($path === '' || !$this->file->isExists($path)) {
                return [];
            }
            $data = $this->json->unserialize($this->file->fileGetContents($path));
        } catch (\Throwable) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Chemin du catalogue livré avec le module.
     *
     * @return string
     */
    private function defaultPath(): string
    {
        $moduleDir = $this->registrar->getPath(ComponentRegistrar::MODULE, 'Maxcode_Core');

        return $moduleDir === null ? '' : $moduleDir . '/etc/catalog.json';
    }
}
