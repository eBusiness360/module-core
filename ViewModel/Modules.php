<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\ViewModel;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Maxcode\Core\Model\Catalog\Entry;
use Maxcode\Core\Model\Catalog\Reader;
use Maxcode\Core\Model\Docs\Link;
use Maxcode\Core\Model\Module\Status;
use Maxcode\Core\Model\Module\StatusProvider;
use Maxcode\Core\Model\Update\LatestVersionStore;

/**
 * Données de la page « Modules Maxcode » : le catalogue, séparé en gratuits et payants.
 */
class Modules implements ArgumentInterface
{
    /**
     * @param Reader         $catalog        Catalogue embarqué.
     * @param StatusProvider $statusProvider Présence et version des modules.
     * @param Link               $docsLink   Adresse de la documentation embarquée d'un module.
     * @param UrlInterface       $urlBuilder Constructeur d'adresses d'administration.
     * @param LatestVersionStore $store      Résultat de la dernière vérification des mises à jour.
     * @param TimezoneInterface  $timezone   Fuseau et format de date de l'administration.
     */
    public function __construct(
        private readonly Reader $catalog,
        private readonly StatusProvider $statusProvider,
        private readonly Link $docsLink,
        private readonly UrlInterface $urlBuilder,
        private readonly LatestVersionStore $store,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Adresse qui lance la vérification des mises à jour (POST).
     *
     * @return string
     */
    public function getCheckUrl(): string
    {
        // Route avec l'identifiant de ROUTE (maxcode_core), pour que la clé secrète de l'URL soit valide.
        return $this->urlBuilder->getUrl('maxcode_core/updates/check');
    }

    /**
     * Date de la dernière vérification, à l'heure locale de l'administration ; null si elle n'a jamais été faite.
     *
     * @return string|null
     */
    public function getLastCheckLabel(): ?string
    {
        $checkedAt = $this->store->getCheckedAt();
        if ($checkedAt === null) {
            return null;
        }
        try {
            return $this->timezone->formatDateTime(
                new \DateTimeImmutable($checkedAt),
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::SHORT
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Lignes prêtes à afficher, regroupées par type (clés « free » et « paid »).
     *
     * @return array<string, list<array{name:string,description:string,type_label:string,state:string,status_label:string,url:?string,link_label:?string,docs_url:?string,version:?string,latest_version:?string,has_update:bool}>>
     */
    public function getGroups(): array
    {
        $groups = [Entry::TYPE_FREE => [], Entry::TYPE_PAID => []];
        foreach ($this->catalog->getEntries() as $entry) {
            $groups[$entry->type][] = $this->buildRow($entry, $this->statusProvider->getStatus($entry));
        }

        return $groups;
    }

    /**
     * @param Entry  $entry  Entrée du catalogue.
     * @param Status $status État sur cette installation.
     * @return array
     */
    private function buildRow(Entry $entry, Status $status): array
    {
        $url = $entry->url;
        $linkLabel = $url !== null ? (string) __('View details') : null;
        if ($url === null && $entry->isPaid() && !$status->isPresent()) {
            $url = $this->catalog->getContactUrl();
            $linkLabel = (string) __('Contact us');
        }

        return [
            'name' => $entry->name,
            'description' => (string) __($entry->description),
            'type_label' => $entry->isPaid() ? (string) __('Paid extension') : (string) __('Free of charge'),
            'state' => $status->state,
            'status_label' => $this->statusLabel($status),
            'url' => $url,
            'link_label' => $linkLabel,
            'docs_url' => $status->isPresent() ? $this->docsLink->forModule($entry->module) : null,
            'version' => $status->version,
            'latest_version' => $status->latestVersion,
            'has_update' => $status->hasUpdate(),
        ];
    }

    /**
     * Nombre de modules installés pour lesquels une version plus récente est connue.
     *
     * @return int
     */
    public function getUpdateCount(): int
    {
        $count = 0;
        foreach ($this->getGroups() as $rows) {
            foreach ($rows as $row) {
                $count += $row['has_update'] ? 1 : 0;
            }
        }

        return $count;
    }

    /**
     * @param Status $status État du module.
     * @return string
     */
    private function statusLabel(Status $status): string
    {
        return match ($status->state) {
            Status::INSTALLED => (string) __('Installed'),
            Status::DISABLED => (string) __('Installed but disabled'),
            Status::COMING_SOON => (string) __('Coming soon'),
            default => (string) __('Not installed'),
        };
    }
}
