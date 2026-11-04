# Changelog — Maxcode_Core

Toutes les évolutions notables du module sont consignées ici.
Format : [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/), versionnage [SemVer](https://semver.org/lang/fr/).

## [1.0.0] — 2026-10-29 — Première version

### Ajouté
- Onglet d'administration **Maxcode** unique, déclaré par le module (Stores > Configuration), sur lequel les
  autres modules Maxcode viendront se brancher.
- Section **Stores > Configuration > Maxcode > Core**, sans aucune entrée dans le menu d'administration. Groupe
  **Modules Maxcode** : catalogue des modules Maxcode, gratuits puis payants (nom,
  description courte, type, statut « installé » avec version, « désactivé », « non installé » ou « bientôt
  disponible », lien vers la fiche). Le catalogue est embarqué dans le paquet (`etc/catalog.json`) : aucun appel
  réseau de lui-même, aucune télémétrie. Le statut se lit dans le registre des composants Magento, quel que soit le canal
  d'installation du module.
- **Mini-documentation** réutilisable en haut de la page de configuration d'un module, gratuit ou payant : un
  résumé et quelques points clés, plus un lien « Documentation complète ». Un module la déclare en DI (section,
  module, résumé et points facultatifs) ; le nom et la description viennent du catalogue. Toujours visible,
  affichée au-dessus du bandeau d'évolution.
- **Documentation embarquée** : « Documentation complète » ouvre une page d'administration qui affiche le
  `README.md` / `README.fr.md` livré DANS le paquet du module (langue de l'administrateur, repli sur l'anglais).
  Elle fonctionne donc aussi pour un module payant dont le dépôt est privé. Le HTML brut du README est supprimé,
  seuls les liens HTTPS sont conservés, les images sont retirées ; le chemin lu vient du registre des composants
  (jamais d'une saisie) et se limite aux modules Maxcode installés. Rendu par `league/commonmark` (nouvelle
  dépendance Composer) ; sans elle, le texte est affiché tel quel, échappé.
- Lien secondaire « Voir sur GitHub » pour les modules gratuits dont le dépôt est public (champ `docs_url` du
  catalogue, HTTPS uniquement ; jamais renseigné pour un module payant).
- **Bandeau « Allez plus loin avec … »** réutilisable : un module gratuit déclare en DI le module payant qu'il promeut
  (section de configuration, texte, titre et lien facultatifs) et le bandeau s'affiche en haut de sa page de
  configuration. Un seul bandeau, sobre, accessible (contraste AA, clavier, lecteur d'écran). Il n'est pas
  fermable : il disparaît dès que le module payant est présent.
- Groupe **Paramètres communs** : la **clé API Claude** (chiffrée), lue par les modules qui en ont besoin via
  `Maxcode\Core\Model\Settings`. Core la stocke seulement : il n'appelle aucun service.
- **Bandeau de l'éditeur** en tête de la section Core : présente eBusiness360, explique que Maxcode est le nom de
  développeur et que les modules sont en vente sur eBusiness360.fr. Trois boutons : la page des modules, « Visiter
  le site » et « Suivre l'actu tech Magento 2 » (adresses dans l'objet `publisher` du catalogue).
- **Versions et mises à jour** : le catalogue embarque la dernière version connue de chaque module
  (`latest_version`). La liste affiche la version installée et la dernière, met en surbrillance bleue (bleu
  eBusiness360) les modules à mettre à jour, et l'onglet **Maxcode** du menu de configuration affiche leur nombre
  (comme le compteur Swissup). Sans vérification, les dernières versions sont celles connues à la publication de
  Core.
- **Bouton « Vérifier les mises à jour »** dans la liste des modules : requête POST lancée seulement par
  l'administrateur, qui lit les dernières versions stables des modules GRATUITS sur repo.packagist.org (hôte figé,
  rien sur le site n'est envoyé, modules payants jamais interrogés). Résultat mémorisé dans la configuration
  (`maxcode_core/updates/*`), avec la date de la dernière vérification ; la version la plus récente entre le
  catalogue et la vérification l'emporte. Seul code réseau du module : `Model/Update/Fetcher`.
- Règle de la gamme : les nouveaux modules ne sont promus que sur la page de Core (promotion Search Insights
  déclarée par Core). Les autres modules ne déclarent que leur bloc « À propos ».
- Aucun réglage pour masquer ou désactiver les bandeaux, par choix.
- Ressources ACL `Maxcode_Core::config` et `Maxcode_Core::docs`.
- Traduction française embarquée.
- Tests unitaires (catalogue, versions, statuts, promotions, introductions, documentation, ViewModels).
- Licence : CLUF Maxcode des modules gratuits (fichier `LICENSE`).
- Documentation : `README.md` (anglais) et `README.fr.md`.
