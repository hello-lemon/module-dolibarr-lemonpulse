<?php
/* Copyright (C) 2026 Lemon <hello@hellolemon.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 *  \file       htdocs/lemonpulse/core/lib/lemonpulse.lib.php
 *  \ingroup    lemonpulse
 *  \brief      Fonctions utilitaires du module LemonPulse.
 */

/**
 *  Une version plus récente est-elle publiée ?
 *
 *  Lit le fichier texte déclaré par `url_last_version` dans le descripteur.
 *  C'est le SEUL mécanisme par lequel une instance apprend qu'une mise à jour
 *  existe : ni Dolibarr ni le DoliStore ne notifient d'eux-mêmes. Le cœur lit
 *  la même URL pour le badge de la liste des modules, mais seulement si
 *  CHECKLASTVERSION_EXTERNALMODULE est activé — d'où ce bandeau, qui la lit
 *  sans condition.
 *
 *  Le résultat est mis en cache 24 h, ÉCHEC COMPRIS : sans ça, un hellolemon.fr
 *  injoignable ferait attendre la page de configuration à chaque ouverture.
 *  C'est le défaut que portait la version précédente, qui interrogeait en plus
 *  l'API GitHub — plafonnée à 60 appels par heure et par IP, donc partagée avec
 *  les autres locataires d'un hébergement mutualisé.
 *
 *  @param  DoliDB  $db              Handle BDD
 *  @param  string  $currentVersion  Version installée (ex: "0.2.0")
 *  @param  string  $versionUrl      URL du fichier de version (descripteur)
 *  @return array|null               ['version' => 'x.y.z', 'url' => '...'] ou null
 */
function lemonpulse_check_latest_release($db, $currentVersion, $versionUrl = '')
{
	// Page produit : c'est là que le client retrouve le ZIP et les notes de version.
	$productUrl = 'https://hellolemon.fr/modules-dolibarr/lemonpulse/';

	if (empty($versionUrl)) {
		return null;
	}

	$now = time();
	$cacheRaw = getDolGlobalString('LEMONPULSE_UPDATE_CHECK_CACHE', '');
	$cache = !empty($cacheRaw) ? json_decode($cacheRaw, true) : null;

	if (is_array($cache) && isset($cache['ts']) && ($now - (int) $cache['ts']) < 86400) {
		$latest = $cache['version'] ?? null;
	} else {
		$latest = null;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
		$res = getURLContent($versionUrl, 'GET', '', 1, array(), array('https'), 0);

		// Le code HTTP est vérifié en plus de l'erreur curl : une page d'erreur
		// est une réponse valide pour curl, et son contenu n'est pas un numéro
		// de version. Le nettoyage ci-dessous l'écarterait aussi par sa
		// longueur, mais mieux vaut ne pas dépendre de ça.
		if (empty($res['curl_error_no']) && (int) ($res['http_code'] ?? 0) === 200 && !empty($res['content'])) {
			// Le fichier ne contient que le numéro de version. Même nettoyage
			// défensif que le cœur (DolibarrModules::checkForUpdate) : l'éditeur
			// d'un module peut être compromis, on ne fait entrer que des
			// caractères de numéro de version, et on borne la longueur.
			$candidate = preg_replace('/[^a-zA-Z0-9_\.\-]+/', '', $res['content']);
			if ($candidate !== '' && strlen($candidate) < 30) {
				$latest = $candidate;
			}
		}

		// Écrit dans tous les cas, y compris quand $latest est resté null.
		dolibarr_set_const($db, 'LEMONPULSE_UPDATE_CHECK_CACHE', json_encode(array(
			'ts'      => $now,
			'version' => $latest,
		)), 'chaine', 0, '', 0);
	}

	if (!empty($latest) && version_compare($latest, $currentVersion, '>')) {
		return array('version' => $latest, 'url' => $productUrl);
	}

	return null;
}

/**
 *  Onglets de l'administration du module.
 *
 *  Le mode d'emploi est embarqué et non renvoyé vers le dépôt : celui qui
 *  installe le module depuis un ZIP n'a ni le README ni l'auteur sous la main.
 *
 *  @return array Tableau des onglets, au format attendu par dol_get_fiche_head()
 */
function lemonpulseAdminPrepareHead()
{
	global $langs;
	$langs->load('lemonpulse@lemonpulse');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/lemonpulse/admin/setup.php', 1);
	$head[$h][1] = $langs->trans('Settings');
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath('/lemonpulse/admin/howto.php', 1);
	$head[$h][1] = $langs->trans('LemonPulseTabHowto');
	$head[$h][2] = 'howto';
	$h++;

	return $head;
}
