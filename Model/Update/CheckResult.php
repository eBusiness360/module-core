<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model\Update;

/**
 * Bilan d'une vérification des mises à jour.
 */
final class CheckResult
{
    /**
     * @param int $queried Nombre de paquets publics interrogés.
     * @param int $found   Nombre de paquets dont une version a été trouvée.
     * @param int $failed  Nombre de paquets pour lesquels Packagist n'a pas répondu.
     */
    public function __construct(
        public readonly int $queried,
        public readonly int $found,
        public readonly int $failed
    ) {
    }
}
