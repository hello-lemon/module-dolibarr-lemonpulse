# Journal des versions — LemonPulse

Ce fichier alimente l'onglet « Fichier ChangeLog » de la fiche du module dans
Dolibarr (Configuration → Modules → picto information).

## 1.0.0 — 2 septembre 2026

Première version publique. Le module est diffusé gratuitement.

### Corrigé

- **Les factures remplacées étaient comptées deux fois.** Une facture close avec
  le motif « remplacée » reste en base à côté de celle qui la remplace : le
  chiffre d'affaires était gonflé du montant de la première, en silence. Le
  total suit désormais la même règle que les statistiques de Dolibarr.
- Une requête en échec affichait un chiffre d'affaires à zéro, impossible à
  distinguer d'une période sans vente. L'erreur est maintenant tracée dans le
  journal.

### Modifié

- **Contrôle de mise à jour** : le module lit le fichier de version publié par
  l'éditeur (`url_last_version`) au lieu d'interroger l'API GitHub. Le résultat
  est mis en cache 24 h, échec compris — un serveur injoignable ne ralentit plus
  la page de configuration. Aucune donnée de l'instance n'est transmise.

### Ajouté

- **Onglet « Mode d'emploi »** dans l'administration du module : mise en route,
  détection du mois d'exercice, et le détail de ce que les chiffres comptent —
  ou ne comptent pas.

## 0.2.0 — 29 avril 2026

- Refonte de l'affichage : chiffre d'affaires en hero, pastille d'évolution
  colorée selon le sens métier, lignes de référence N-1 discrètes, barre
  d'objectif fine.
- Migration de l'identifiant de module 500280 vers 210007.

## 0.1.0 — 27 avril 2026

- Première version : widget de tableau de bord (chiffre d'affaires de l'exercice,
  comparaison avec l'exercice précédent, achats, résultat indicatif) et page de
  configuration.
- Détection en cascade du mois de début d'exercice fiscal.
