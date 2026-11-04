<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Module;

use Maxcode\Core\Model\Catalog\Reader;

/**
 * Nombre de modules Maxcode installés pour lesquels une version plus récente est connue.
 *
 * « Connue » veut dire : inscrite dans le catalogue livré avec Core. Aucune requête n'est faite : pour savoir
 * qu'une version est sortie, il faut mettre Core à jour (ou le catalogue qu'il embarque).
 */
class UpdateCounter
{
    /**
     * @param Reader         $catalog        Catalogue embarqué.
     * @param StatusProvider $statusProvider Présence et version des modules.
     */
    public function __construct(
        private readonly Reader $catalog,
        private readonly StatusProvider $statusProvider
    ) {
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->catalog->getEntries() as $entry) {
            if ($this->statusProvider->getStatus($entry)->hasUpdate()) {
                $count++;
            }
        }

        return $count;
    }
}
