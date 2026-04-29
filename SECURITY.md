# Politique de sécurité — LemonPulse

Ce document décrit le modèle de menace du module LemonPulse, les protections en place, les limitations assumées, et le processus de signalement responsable d'une faille.

## Signaler une vulnérabilité

Merci de **ne pas** ouvrir d'issue publique pour une faille de sécurité. Écrivez à :

**hello@hellolemon.fr**

Précisez :

- Version du module concernée (ou commit SHA)
- Description de la vulnérabilité et impact estimé
- Étapes de reproduction minimales
- Éventuelle preuve de concept

Nous nous engageons à :

- Accuser réception sous 72 heures
- Vous tenir informé de l'avancement de l'analyse
- Mentionner votre contribution (si vous le souhaitez) une fois le correctif publié
- Appliquer un délai de divulgation coordonnée de 90 jours maximum avant publication publique du détail

Merci d'éviter toute action qui pourrait dégrader un service en production, accéder à des données tierces, ou exploiter une faille au-delà du strict nécessaire pour la démontrer.

## Modèle de menace

LemonPulse est un module Dolibarr qui ajoute un widget tableau de bord affichant le chiffre d'affaires de l'exercice en cours, la comparaison N-1 et le résultat indicatif. Il s'exécute **à l'intérieur** d'une instance Dolibarr authentifiée. Le modèle de menace est celui d'une application métier en intranet :

### Rôles

| Rôle | Accès | Confiance |
|---|---|---|
| Administrateur Dolibarr | Configuration du module (mois début exercice, objectif annuel) | **Confiance forte**. Un admin compromis implique de toute façon une compromission totale de Dolibarr. |
| Utilisateur avec `facture.lire` ou `lemonpulse.lire` | Lecture du widget | Confiance interne. Voit les agrégats de CA, achats et résultat sur l'entité courante. |
| Utilisateur anonyme (hors Dolibarr) | Aucun accès | Non concerné : le module n'expose aucun endpoint public. |

### Surface exposée

- **Widget tableau de bord** : `core/boxes/box_lemonpulse.php`, exécuté dans le contexte du tableau de bord Dolibarr (utilisateur authentifié)
- **Page de configuration admin** : `admin/setup.php`, réservée aux admins via `accessforbidden()`
- **Aucun endpoint web exposé publiquement**
- **Aucun trigger, hook, cron, ni API REST**

### Ce qui est **hors** modèle de menace

- Un administrateur Dolibarr malveillant. Un admin peut déjà tout faire dans Dolibarr, y compris lire la base. Aucun mécanisme ne protège contre un admin hostile (et ne le peut pas dans l'architecture Dolibarr).
- Une compromission de l'API publique GitHub utilisée pour le check de mise à jour. La réponse JSON est validée (URL filtrée par regex), aucune désérialisation native (`unserialize`) ni eval n'est effectuée.

## Protections en place

### Injection SQL

Toutes les requêtes SQL du widget utilisent uniquement :

- des timestamps issus de `dol_now()` / `dol_mktime()` / `dol_time_plus_duree()`, transformés via `$this->db->idate()` (échappement Dolibarr standard)
- la fonction `getEntity()` qui retourne une liste d'IDs d'entités contrôlée par Dolibarr

Aucune donnée d'entrée utilisateur n'est concaténée dans le SQL.

### Cross-Site Scripting (XSS)

- Les libellés affichés dans le widget proviennent de `$langs->trans()` (catalogue de traductions du module) et de `price()` (formatage Dolibarr standard pour les montants).
- Les valeurs numériques sont castées en `float` avant insertion dans les attributs HTML.
- Dans `admin/setup.php`, tout output dynamique passe par `dol_escape_htmltag()`.

### Cross-Site Request Forgery (CSRF)

Le formulaire de configuration vérifie le token CSRF avant tout enregistrement :

```php
if (GETPOST('token', 'alpha') !== currentToken()) {
    accessforbidden();
}
```

Le token est régénéré via `newToken()` dans le formulaire. Convention Dolibarr standard.

### Permissions

- L'admin de Dolibarr est requis pour configurer le module (`accessforbidden()` ligne 41 de `setup.php`)
- L'affichage du widget exige le droit `facture.lire` ou `lemonpulse.lire` (vérifié dans le constructeur de la box)

### Path traversal

Aucun include dynamique de fichier dans le code. Les chemins sont littéraux ou construits via `dol_buildpath()` avec un nom de module fixe.

### SSRF (Server-Side Request Forgery) — check de mise à jour

Le module interroge `https://api.github.com/repos/hello-lemon/module-dolibarr-lemonpulse/releases/latest` pour détecter une nouvelle version (cache 24h). Protections :

- URL **codée en dur**, non configurable
- `CURLOPT_TIMEOUT = 5` secondes, `CURLOPT_SSL_VERIFYPEER = true`, `CURLOPT_SSL_VERIFYHOST = 2`
- L'URL `html_url` retournée par GitHub est validée par regex stricte (`^https://github\.com/hello-lemon/module-dolibarr-lemonpulse/`) avant affichage. En cas de non-correspondance, fallback vers une URL fixe.
- Aucune désérialisation : seul `json_decode(..., true)` est utilisé.
- En cas d'échec réseau, le module retourne `null` silencieusement et reste utilisable.

### Secrets

LemonPulse **ne stocke aucun secret**. Les seules constantes utilisées sont :

- `LEMONPULSE_FISCAL_MONTH_START` (entier 0-12)
- `LEMONPULSE_OBJECTIF_ANNUEL` (montant numérique)
- `LEMONPULSE_UPDATE_CHECK_CACHE` (cache JSON du dernier check GitHub)

### Logs

Aucun log custom n'est émis par le module. Les requêtes SQL sont tracées via le mécanisme standard Dolibarr (`dol_syslog` interne au framework).

## Dépendances

- **Dolibarr 18.0+** : la sécurité du module s'appuie sur les primitives Dolibarr (`GETPOST`, `newToken`/`currentToken`, `dol_escape_htmltag`, `accessforbidden`, `getEntity`, permissions). Un Dolibarr non à jour affecte directement la sécurité de tous ses modules, dont celui-ci.
- **Module `facture` activé** (dépendance déclarée dans le descripteur).

## Historique des avis

_Aucune vulnérabilité corrigée n'a été publiée à ce jour._

---

Pour toute question sur la sécurité de ce module : hello@hellolemon.fr
