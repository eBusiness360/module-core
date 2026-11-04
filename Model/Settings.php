<?php
/**
 * @author    eBusiness360 – Maxime LESGUILLIER aka Maxcode
 * @copyright Copyright © 2026 eBusiness360 – Maxime LESGUILLIER aka Maxcode. Tous droits réservés.
 * @license   Licence propriétaire — voir le fichier LICENSE
 * @package   Maxcode_Core
 */
declare(strict_types=1);

namespace Maxcode\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Paramètres communs des modules Maxcode (section Maxcode > Core > Paramètres communs).
 *
 * Core ne fait que les stocker : il n'appelle aucun service. Ce sont les modules qui en ont besoin (par exemple un
 * module qui interroge l'API Claude) qui les lisent ici, plutôt que de redemander la même clé chacun de leur côté.
 */
class Settings
{
    public const XML_PATH_CLAUDE_API_KEY = 'maxcode_core/settings/claude_api_key';

    /**
     * @param ScopeConfigInterface $scopeConfig Configuration.
     * @param EncryptorInterface   $encryptor   Chiffrement des valeurs sensibles.
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * La clé API Claude en clair, ou null si elle n'est pas renseignée.
     *
     * @return string|null
     */
    public function getClaudeApiKey(): ?string
    {
        $stored = $this->scopeConfig->getValue(self::XML_PATH_CLAUDE_API_KEY);
        if (!is_string($stored) || trim($stored) === '') {
            return null;
        }
        $key = trim($this->encryptor->decrypt($stored));

        return $key !== '' ? $key : null;
    }

    /**
     * Une clé API Claude est-elle renseignée ?
     *
     * @return bool
     */
    public function hasClaudeApiKey(): bool
    {
        return $this->getClaudeApiKey() !== null;
    }
}
