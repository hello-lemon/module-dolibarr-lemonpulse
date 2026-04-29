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
 *  Vérifie si une version plus récente du module existe sur GitHub.
 *
 *  Cache 24h en const Dolibarr pour ne pas marteler l'API. Retourne null
 *  silencieusement si l'API est inaccessible — le module reste utilisable.
 *
 *  @param  DoliDB  $db              Handle BDD
 *  @param  string  $currentVersion  Version locale (ex: "0.1.0")
 *  @return array|null               ['version' => 'x.y.z', 'url' => '...'] ou null
 */
function lemonpulse_check_latest_release($db, $currentVersion)
{
	$now = time();
	$cacheRaw = getDolGlobalString('LEMONPULSE_UPDATE_CHECK_CACHE', '');
	$cache = !empty($cacheRaw) ? json_decode($cacheRaw, true) : null;

	$latest = null;
	$htmlUrl = '';
	if (is_array($cache) && isset($cache['ts']) && ($now - (int) $cache['ts']) < 86400) {
		$latest = $cache['version'] ?? null;
		$htmlUrl = $cache['url'] ?? '';
	} else {
		$url = 'https://api.github.com/repos/hello-lemon/module-dolibarr-lemonpulse/releases/latest';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'LemonPulse-UpdateCheck');
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$json = @curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode !== 200 || empty($json)) {
			return null;
		}
		$data = json_decode($json, true);
		if (!is_array($data) || empty($data['tag_name'])) {
			return null;
		}
		$latest = ltrim($data['tag_name'], 'v');
		$htmlUrl = $data['html_url'] ?? '';
		if (!preg_match('#^https://github\.com/hello-lemon/module-dolibarr-lemonpulse/#', $htmlUrl)) {
			$htmlUrl = 'https://github.com/hello-lemon/module-dolibarr-lemonpulse/releases';
		}

		dolibarr_set_const($db, 'LEMONPULSE_UPDATE_CHECK_CACHE', json_encode(array(
			'ts' => $now,
			'version' => $latest,
			'url' => $htmlUrl,
		)), 'chaine', 0, '', 0);
	}

	if (!empty($latest) && version_compare($latest, $currentVersion, '>')) {
		return array('version' => $latest, 'url' => $htmlUrl);
	}
	return null;
}
