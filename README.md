# LemonPulse — Widget chiffre d'affaires Dolibarr

Module Dolibarr qui ajoute un widget tableau de bord moderne affichant le pouls de votre activité : chiffre d'affaires de l'exercice en cours, comparaison année précédente, achats fournisseurs et résultat indicatif. Design minimaliste inspiré de Stripe et Linear.

[![Dolibarr](https://img.shields.io/badge/Dolibarr-18.0%2B-9bd3ed)](https://www.dolibarr.org/)
[![License](https://img.shields.io/badge/license-GPL--3.0-blue)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.0.0-orange)](https://github.com/hello-lemon/module-dolibarr-lemonpulse/releases)

---

## Aperçu

Le widget affiche d'un coup d'œil :

- **CA de l'exercice en cours** en gros chiffre, avec pill colorée pour l'évolution vs N-1 même période
- **CA N-1 (même période)** et **CA N-1 complet** en lignes de référence discrètes
- **Barre de progression vers l'objectif annuel** (optionnel)
- **Achats fournisseurs** de l'exercice + comparaison N-1
- **Résultat indicatif** (CA − achats) en gras avec couleur signe

Les couleurs des pills suivent une logique métier : **▲ vert** pour une évolution favorable (CA en hausse, achats en baisse, résultat en hausse), **▼ rouge** pour défavorable.

## Fonctionnalités

- **Détection automatique** du mois de début d'exercice fiscal :
  1. Override custom `LEMONPULSE_FISCAL_MONTH_START` si défini
  2. Sinon, config société Dolibarr (`SOCIETE_FISCAL_MONTH_START`)
  3. Sinon, module Comptabilité (`ACCOUNTING_FISCAL_PERIOD_MONTH_START`)
  4. Sinon, janvier par défaut
- **Objectif annuel** paramétrable (0 = désactivé)
- **Multi-entité** : respecte l'entité Dolibarr courante via `getEntity('invoice')` et `getEntity('facture_fourn')`
- **Notification mise à jour** : lit le fichier de version publié par l'éditeur
  (`url_last_version`), en cache 24 h — succès comme échec, pour qu'un serveur
  injoignable ne ralentisse jamais la page de configuration
- **Multi-langue** : FR + EN

## Installation

1. Télécharger la dernière release depuis [GitHub](https://github.com/hello-lemon/module-dolibarr-lemonpulse/releases) ou cloner le repo :
   ```bash
   cd /chemin/vers/dolibarr/htdocs/custom/
   git clone https://github.com/hello-lemon/module-dolibarr-lemonpulse.git lemonpulse
   ```
2. Donner les bonnes permissions (typiquement `www-data:www-data`) :
   ```bash
   chown -R www-data:www-data lemonpulse
   ```
3. Dans Dolibarr : **Accueil > Configuration > Modules**, onglet **Externe — Lemon**, activer **LemonPulse**.

## Configuration

Page **Configuration > Modules > LemonPulse > roue crantée** :

| Paramètre | Description | Défaut |
|---|---|---|
| Mois de début d'exercice fiscal | `0` (auto) ou `1-12` pour forcer un mois | `0` (auto) |
| Objectif annuel de CA HT | Montant en devise de la société, `0` désactive la barre | `0` |

## Activation du widget

Après activation du module, le widget apparaît automatiquement dans la liste des widgets disponibles sur le tableau de bord d'accueil.

Si le widget n'est pas visible, l'ajouter manuellement :
1. Aller sur l'accueil Dolibarr
2. Cliquer sur **« Ajouter le widget au tableau de bord »**
3. Sélectionner **Chiffre d'affaires**

## Permissions

Le widget est visible par tout utilisateur ayant l'un des deux droits suivants :

- `facture.lire` (droit standard Dolibarr — la plupart des comptables/dirigeants l'ont déjà)
- `lemonpulse.lire` (droit dédié au module, à attribuer si vous voulez exposer le widget sans donner accès aux factures elles-mêmes)

## Notes méthodologiques

- Les calculs sont **indicatifs** : sommes brutes du `total_ht` des factures validées, payées ou closes. Hors comptabilité analytique réelle, hors écritures comptables manuelles, hors salaires, charges et amortissements — le « résultat » affiché n'est donc pas un résultat comptable.
- Les **avoirs** (factures de type 2) sont inclus avec leur `total_ht` négatif → ils réduisent le CA, comme attendu.
- Les factures **brouillons** sont exclues : une facture non validée n'existe pas encore.
- Une facture close avec le motif **« remplacée »** est exclue du CA, sa remplaçante étant elle aussi en base : les compter toutes les deux doublerait la vente. C'est la règle appliquée par les statistiques de Dolibarr (`FactureStats`).
- Les autres clôtures (créance abandonnée) restent comptées : le chiffre d'affaires a bien été réalisé, la perte se traite en charge.
- Période : du 1er jour du mois fiscal de l'année courante jusqu'à aujourd'hui (inclus). Comparaison N-1 sur la même fenêtre décalée d'un an.

## Compatibilité

- **Dolibarr** : 18.0 et supérieur (tourne en 23.0.x)
- **PHP** : 7.4 minimum
- **Dépendance** : module `facture` activé (déclaré dans le descripteur)

## Tables utilisées

Lecture seule :

- `llx_facture` (factures clients)
- `llx_facture_fourn` (factures fournisseurs)
- `llx_const` (constantes de configuration et cache du contrôle de mise à jour)

Aucune table custom créée par le module.

## Sécurité

Voir [SECURITY.md](SECURITY.md) pour le modèle de menace, les protections en place et le processus de signalement responsable d'une vulnérabilité.

Contact : **hello@hellolemon.fr**

## Éditeur

Développé et maintenu par [**Lemon**](https://hellolemon.fr) — agence web et communication à Clermont-Ferrand depuis 2012.

Cinq pôles complémentaires :

- **Lemon Digital** — développement web (WordPress, Astro, applications sur mesure)
- **Lemon Strat** — stratégie de communication, branding, conseil
- **Lemon Print** — impression offset/numérique, signalétique, packaging
- **Lemon Maker** — fabrication numérique (impression 3D, découpe laser, prototypage)
- **Lemon TechPro** — conseil IT, infogérance, modules Dolibarr custom

## Licence

[GPL-3.0](LICENSE) — comme Dolibarr.

## Changelog

Le détail des versions est dans [ChangeLog.md](ChangeLog.md).

### 0.2.0 — 2026-04-29
- Refonte design en mode minimaliste (style Stripe/Linear)
- Hero CA + pill évolution colorée
- Lignes de référence discrètes pour N-1
- Barre de progression objectif fine
- Migration ID module 500280 → 210007 (plage officielle réservée Lemon)

### 0.1.0 — 2026-04-27
- Première version : widget tableau de bord chiffre d'affaires + page admin de configuration
- Détection auto du mois fiscal en cascade
- Vérification CSRF, escape XSS, audit sécurité initial
