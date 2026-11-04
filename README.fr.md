<div align="center">

[English](README.md) · **Français**

# Maxcode Core pour Magento 2

**Le socle commun des modules Maxcode : un onglet admin, un catalogue de modules et des paramètres communs, une documentation intégrée et un bandeau d'évolution. Gratuit, aucune télémétrie, aucune requête sauf si vous cliquez sur « Vérifier les mises à jour ».**

![Licence : CLUF gratuit](https://img.shields.io/badge/licence-CLUF%20gratuit-1E4FA3)
![Magento 2.4](https://img.shields.io/badge/Magento-2.4.x-F26322)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4)

</div>

## Ce que fait le module

- **Un onglet d'administration unique.** Tous les modules Maxcode apparaissent sous un seul onglet
  **Maxcode** dans *Stores > Configuration*, au lieu que chacun le redéclare.
- **Une section « Core » dans cet onglet** (*Stores > Configuration > Maxcode > Core*), avec deux groupes :
  - **Modules Maxcode** : toute la gamme Maxcode, gratuite et payante, avec une courte description, le type,
    l'état d'installation (et la version) et des liens vers la fiche et la documentation.
  - **Paramètres communs** : les valeurs partagées par tous les modules Maxcode, comme la **clé API Claude**
    (stockée chiffrée, lue par les modules qui en ont besoin via `Maxcode\Core\Model\Settings` ; Core ne s'en
    sert pas lui-même).
- **Un bloc « À propos » et la documentation complète.** En haut de la page de configuration d'un module :
  ce que fait le module, quelques points clés et un lien **Documentation complète**. La documentation est le
  README du module, livré *dans* son paquet et affiché dans l'administration : elle fonctionne aussi pour un
  module payant dont le dépôt est privé.
- **Un bandeau éditeur et un bandeau « nouveau module », sur la page Core uniquement.** La section Core
  s'ouvre sur un bandeau qui présente eBusiness360 (Maxcode est le nom de développeur ; les modules sont en vente
  sur eBusiness360.fr), puis un bandeau sobre par nouveau module, affiché tant qu'il n'est pas installé. Aucun
  autre module n'affiche de promotion.
- **Les versions d'un coup d'œil.** La liste des modules affiche la version installée et la dernière, met en bleu
  les modules à mettre à jour, et un compteur bleu sur l'onglet **Maxcode** du menu de configuration en donne le
  nombre.
- **Aucune télémétrie, aucune requête de lui-même.** Le catalogue est livré dans le paquet (`etc/catalog.json`) et
  les versions installées sont lues dans Magento. La seule requête réseau est le bouton **Vérifier les mises à
  jour**, sur lequel un administrateur clique volontairement : il lit les dernières versions des modules gratuits
  sur packagist.org (adresse publique, rien sur votre site n'est envoyé, les modules payants ne sont jamais
  interrogés) et en garde le résultat.
- **Accessible.** Contraste AA, navigation au clavier, gestion du focus, libellés pour lecteurs d'écran.
- **Traduit** en anglais et en français. **Tests unitaires** inclus.

## Installation

```bash
composer require maxcode/module-core
bin/magento module:enable Maxcode_Core
bin/magento setup:upgrade
bin/magento cache:flush
```

Le module n'a pas de schéma de base de données : un seul `setup:upgrade` suffit. Prérequis : Magento Open
Source ou Adobe Commerce 2.4.x, PHP 8.2 ou supérieur.

## Utilisation

- **Catalogue et paramètres communs :** *Stores > Configuration > Maxcode > Core*. Core n'ajoute aucune entrée
  au menu d'administration.
- **Documentation :** *Documentation complète* dans le bloc « À propos », ou *Documentation* dans la liste des
  modules. L'accès dépend de la ressource ACL `Maxcode_Core::docs` ; la section, de `Maxcode_Core::config`.

## Pour les développeurs : décrire votre module sur sa page de configuration

Tout module Maxcode, gratuit ou payant, peut afficher un court bloc « À propos » en haut de sa page de
configuration. Ajoutez un item dans son `etc/adminhtml/di.xml` :

```xml
<type name="Maxcode\Core\Model\Intro\IntroPool">
    <arguments>
        <argument name="intros" xsi:type="array">
            <item name="mon_module" xsi:type="array">
                <item name="section" xsi:type="string">id_de_ma_section_de_config</item>
                <item name="module" xsi:type="string">Maxcode_MonModule</item>
                <!-- facultatif : summary (défaut : la description du catalogue) et jusqu'à 6 points courts -->
                <item name="points" xsi:type="array">
                    <item name="0" xsi:type="string">Un point clé.</item>
                </item>
            </item>
        </argument>
    </arguments>
</type>
```

Le nom, la description courte et le lien **Documentation complète** viennent de l'entrée du module dans
`etc/catalog.json` : renseignez-y `docs_url` avec le dépôt GitHub public, dont le README est la documentation
complète. Omettez-le s'il n'existe pas de documentation publique, et ne pointez jamais un module payant vers son
dépôt privé. Le bloc s'affiche toujours au-dessus du bandeau d'évolution ; pour le placer ailleurs, utilisez le
bloc `Maxcode_Core::intro.phtml` avec un argument `intro_id`.

## Pour les développeurs : promouvoir les nouveaux modules

**La règle de la gamme : les nouveaux modules ne sont promus que sur la page de Maxcode Core.** Les autres modules
Maxcode n'affichent aucun bandeau « Allez plus loin » sur leur page de configuration : ils déclarent seulement leur
bloc « À propos » (voir plus haut).

Core déclare ses propres promotions dans son `etc/adminhtml/di.xml` :

```xml
<type name="Maxcode\Core\Model\Promo\PromotionPool">
    <arguments>
        <argument name="promotions" xsi:type="array">
            <item name="maxcode_core_ai_assistant" xsi:type="array">
                <item name="section" xsi:type="string">maxcode_core</item>
                <item name="paid_module" xsi:type="string">Maxcode_AiAssistant</item>
                <item name="text" xsi:type="string">Une phrase qui dit ce que fait le nouveau module.</item>
                <!-- facultatif : title (défaut « Allez plus loin avec <module> »), url (défaut : fiche du catalogue) -->
            </item>
        </argument>
    </arguments>
</type>
```

- `paid_module` doit figurer dans `etc/catalog.json` avec le type `paid`. Un module gratuit ne peut pas être
  promu.
- Le bandeau n'est pas fermable : il disparaît une fois le module payant installé.
- Le bandeau de l'éditeur en tête de la section Core (eBusiness360, vendeur des modules) n'apparaît que sur cette
  section. Il a trois boutons (la page des modules, le site, l'actu tech Magento 2) dont les adresses viennent de
  l'objet `publisher` de `etc/catalog.json`.
- Versions : `latest_version` dans `etc/catalog.json` est la dernière version connue de chaque module. La liste
  des modules affiche versions installée et dernière, met en bleu les modules à mettre à jour, et l'onglet
  **Maxcode** du menu de configuration affiche le compteur. Sans vérification, les dernières versions sont celles
  connues à la publication de cette version de Core (une nouvelle version de Core les rafraîchit). Une fois qu'un
  administrateur a cliqué sur **Vérifier les mises à jour**, la plus récente des deux l'emporte. Le seul code
  réseau est `Model/Update/Fetcher`, qui n'accepte que les adresses `https://repo.packagist.org/`.

| Élément | Rôle |
|---|---|
| `etc/catalog.json` | le catalogue embarqué des modules Maxcode |
| `Model/Catalog/Reader` | lit et valide le catalogue (liens HTTPS uniquement) |
| `Model/Module/StatusProvider` | installé / désactivé / non installé, d'après le registre de composants Magento |
| `Model/Promo/PromotionPool`, `Model/Intro/IntroPool` | les promotions et blocs « À propos » déclarés en DI |
| `Model/Docs/DocumentReader`, `MarkdownRenderer`, `Link` | lisent le README embarqué d'un module et l'affichent en toute sécurité |
| `ViewModel/PromoBanner`, `ModuleIntro`, `Modules`, `Docs` | données du bandeau, du bloc « À propos » et des pages |

## Journal des modifications

Voir [CHANGELOG.md](CHANGELOG.md).

## Licence et support

Gratuit pour un nombre illimité d'installations Magento que vous possédez ou exploitez. Vous recevez le code
source complet et pouvez l'adapter à vos besoins ; la redistribution est interdite. Voir [LICENSE](LICENSE)
(CLUF des modules gratuits).

Fourni en l'état, **sans engagement de support ni de mise à jour**. Pour toute question et les services payants :
[ebusiness360.fr](https://www.ebusiness360.fr/contact/).
