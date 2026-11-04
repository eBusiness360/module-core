<div align="center">

**English** · [Français](README.fr.md)

# Maxcode Core for Magento 2

**The shared foundation of the Maxcode modules: one admin tab, a module catalogue and common settings, built-in documentation and an upgrade banner. Free, no telemetry, no request unless you click "Check for updates".**

![License: free EULA](https://img.shields.io/badge/license-free%20EULA-1E4FA3)
![Magento 2.4](https://img.shields.io/badge/Magento-2.4.x-F26322)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4)

</div>

## What it does

- **One admin tab.** Every Maxcode module appears under a single **Maxcode** tab in
  *Stores > Configuration*, instead of each module redeclaring it.
- **A "Core" section in that tab** (*Stores > Configuration > Maxcode > Core*), with two groups:
  - **Maxcode Modules**: the whole Maxcode range, free and paid, with a short description, the type, whether it
    is installed (and which version), and links to its page and documentation.
  - **Common settings**: values shared by all Maxcode modules, such as the **Claude API key** (stored encrypted,
    read by the modules that need it through `Maxcode\Core\Model\Settings`; Core itself never uses it).
- **A short "About" block and the full documentation.** At the top of a module's configuration page: what the
  module does, a few key points and a **Full documentation** link. The documentation is the module's own README,
  shipped *inside* its package and rendered in the admin: it works for paid modules whose repository is private.
- **A publisher banner and a "new module" banner, on the Core page only.** The Core section opens with a banner
  presenting eBusiness360 (Maxcode is the developer name; the modules are on sale at eBusiness360.fr), then one
  sober banner for each new module, shown until it is installed. No other module shows promotions.
- **Versions at a glance.** The module list shows the installed and the latest version, highlights in blue the
  modules that can be updated, and a blue counter on the **Maxcode** tab of the configuration menu gives their
  number.
- **No telemetry, no request on its own.** The catalogue ships inside the package (`etc/catalog.json`) and the
  installed versions are read from Magento. The only network request is the **Check for updates** button, which
  an administrator clicks on purpose: it reads the latest versions of the free modules on packagist.org
  (a public address, nothing about your site is sent, paid modules are never queried) and remembers the result.
- **Accessible.** AA contrast, keyboard navigation, focus handling, screen reader labels.
- **Translated** into English and French. **Unit tests** included.

## Installation

```bash
composer require maxcode/module-core
bin/magento module:enable Maxcode_Core
bin/magento setup:upgrade
bin/magento cache:flush
```

The module has no database schema; a single `setup:upgrade` is enough. Requirements: Magento Open Source or
Adobe Commerce 2.4.x, PHP 8.2 or later.

## Usage

- **Catalogue and common settings:** *Stores > Configuration > Maxcode > Core*. Core adds no entry to the admin
  menu.
- **Documentation:** *Full documentation* in the "About" block, or *Documentation* in the module list. Access is
  controlled by the `Maxcode_Core::docs` ACL resource; the section itself by `Maxcode_Core::config`.

## For developers: describing your module on its configuration page

Any Maxcode module, free or paid, can show a short "About" block at the top of its configuration page. Add
one item in its `etc/adminhtml/di.xml`:

```xml
<type name="Maxcode\Core\Model\Intro\IntroPool">
    <arguments>
        <argument name="intros" xsi:type="array">
            <item name="my_module" xsi:type="array">
                <item name="section" xsi:type="string">my_config_section_id</item>
                <item name="module" xsi:type="string">Maxcode_MyModule</item>
                <!-- optional: summary (default: the catalogue description) and up to 6 short points -->
                <item name="points" xsi:type="array">
                    <item name="0" xsi:type="string">A key point.</item>
                </item>
            </item>
        </argument>
    </arguments>
</type>
```

The name, the short description and the **Full documentation** link come from the module's entry in
`etc/catalog.json`: set `docs_url` there to the public GitHub repository, whose README is the full documentation.
Leave it out when no public documentation exists, and never point a paid module to its private repository.
The block is always shown above the upgrade banner; to place it elsewhere, use the
`Maxcode_Core::intro.phtml` block with an `intro_id` argument.

## For developers: promoting new modules

**The rule of the range: new modules are promoted on the Maxcode Core page only.** The other Maxcode modules show
no "Go further" banner on their configuration page: they only declare their "About" block (see above).

Core declares its own promotions in its `etc/adminhtml/di.xml`:

```xml
<type name="Maxcode\Core\Model\Promo\PromotionPool">
    <arguments>
        <argument name="promotions" xsi:type="array">
            <item name="maxcode_core_ai_assistant" xsi:type="array">
                <item name="section" xsi:type="string">maxcode_core</item>
                <item name="paid_module" xsi:type="string">Maxcode_AiAssistant</item>
                <item name="text" xsi:type="string">One sentence that says what the new module does.</item>
                <!-- optional: title (default "Go further with <module>"), url (default: the catalogue page) -->
            </item>
        </argument>
    </arguments>
</type>
```

- `paid_module` must be listed in `etc/catalog.json` and flagged `paid`. A free module cannot be promoted.
- The banner is not dismissible: it disappears once the paid module is installed.
- The publisher banner at the top of the Core section (eBusiness360, seller of the modules) is shown on that
  section only. It has three buttons (the modules page, the website, the Magento 2 tech news) whose addresses
  come from the `publisher` object in `etc/catalog.json`.
- Versions: `latest_version` in `etc/catalog.json` is the latest known version of each module. The module list
  shows installed and latest versions, highlights modules that can be updated in blue, and the **Maxcode** tab of
  the configuration menu shows the count. Without a check, the latest versions are those known when this version
  of Core was published (a new release of Core refreshes them). After an administrator clicks **Check for
  updates**, the most recent of the two wins. The only network code is `Model/Update/Fetcher`, which accepts
  `https://repo.packagist.org/` addresses only.

| Piece | Role |
|---|---|
| `etc/catalog.json` | the embedded catalogue of Maxcode modules |
| `Model/Catalog/Reader` | reads and validates the catalogue (HTTPS links only) |
| `Model/Module/StatusProvider` | installed / disabled / not installed, from Magento's component registrar |
| `Model/Promo/PromotionPool`, `Model/Intro/IntroPool` | the promotions and "About" blocks declared in DI |
| `Model/Docs/DocumentReader`, `MarkdownRenderer`, `Link` | read a module's embedded README and render it safely |
| `ViewModel/PromoBanner`, `ModuleIntro`, `Modules`, `Docs` | data for the banner, the "About" block and the pages |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License and support

Free for any number of Magento installations that you own or operate. You receive the full source code and may
adapt it to your needs; redistribution is not allowed. See [LICENSE](LICENSE) (free-module EULA).

Provided as is, **without support or update commitment**. For questions and paid services:
[ebusiness360.fr](https://www.ebusiness360.fr/contact/).
