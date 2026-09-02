<?php
/* Copyright (C) 2026 Lemon <hello@hellolemon.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

/**
 *  \file       htdocs/lemonpulse/admin/setup.php
 *  \ingroup    lemonpulse
 *  \brief      Page de configuration du module LemonPulse
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dol_buildpath('/lemonpulse/core/lib/lemonpulse.lib.php');

global $langs, $user, $conf, $db;

$langs->loadLangs(array('admin', 'lemonpulse@lemonpulse'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$selfUrl = DOL_URL_ROOT.'/custom/lemonpulse/admin/setup.php';

if ($action == 'save') {
	if (GETPOST('token', 'alpha') !== currentToken()) {
		accessforbidden();
	}

	$mois = (int) GETPOST('LEMONPULSE_FISCAL_MONTH_START', 'int');
	if ($mois < 0 || $mois > 12) {
		$mois = 0;
	}
	$objectif = (float) GETPOST('LEMONPULSE_OBJECTIF_ANNUEL', 'alpha');
	if ($objectif < 0) {
		$objectif = 0;
	}

	dolibarr_set_const($db, 'LEMONPULSE_FISCAL_MONTH_START', (string) $mois, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'LEMONPULSE_OBJECTIF_ANNUEL', (string) $objectif, 'chaine', 0, '', $conf->entity);

	setEventMessages($langs->trans('LemonPulseSaved'), null, 'mesgs');
	header('Location: '.$selfUrl);
	exit;
}

llxHeader('', $langs->trans('LemonPulseSetup'));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('LemonPulseSetup'), $linkback, 'title_setup');

$head = lemonpulseAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans('ModuleLemonPulseName'), -1, 'bill');

// Bandeau "Nouvelle version disponible". L'URL vient du descripteur : le cœur
// et nous lisons ainsi le même fichier, il n'y a qu'une version de référence.
require_once dirname(__DIR__).'/core/modules/modLemonPulse.class.php';
$modDesc = new modLemonPulse($db);
$updateInfo = lemonpulse_check_latest_release($db, $modDesc->version, $modDesc->url_last_version);
if ($updateInfo !== null) {
	print '<div class="warning" style="margin:8px 0;padding:10px;border-left:4px solid #e67e22;background:#fff3e0;">';
	print '<strong>'.$langs->trans("LemonPulseUpdateAvailable").'</strong> : ';
	print $langs->trans("LemonPulseUpdateAvailableMsg", dol_escape_htmltag($updateInfo['version']), dol_escape_htmltag($modDesc->version));
	print ' <a href="'.dol_escape_htmltag($updateInfo['url']).'" target="_blank" rel="noopener">'.$langs->trans("LemonPulseUpdateSeeRelease").'</a>';
	print '</div>';
}

print '<form method="POST" action="'.dol_escape_htmltag($selfUrl).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Parameter').'</td>';
print '<td>'.$langs->trans('Value').'</td>';
print '<td>'.$langs->trans('Description').'</td>';
print '</tr>';

// Source auto-détectée (config société Dolibarr ou module Comptabilité)
$societe_val = (int) getDolGlobalString('SOCIETE_FISCAL_MONTH_START', '0');
$compta_val = (int) getDolGlobalString('ACCOUNTING_FISCAL_PERIOD_MONTH_START', '0');
if ($societe_val >= 1 && $societe_val <= 12) {
	$auto_value = $societe_val;
	$auto_source = $langs->transnoentities('LemonPulseAutoSourceSociete');
} elseif ($compta_val >= 1 && $compta_val <= 12) {
	$auto_value = $compta_val;
	$auto_source = $langs->transnoentities('LemonPulseAutoSourceCompta');
} else {
	$auto_value = 1;
	$auto_source = $langs->transnoentities('LemonPulseAutoSourceDefault');
}

$mois_labels = array(
	1 => $langs->transnoentities('Month01'), 2 => $langs->transnoentities('Month02'), 3 => $langs->transnoentities('Month03'),
	4 => $langs->transnoentities('Month04'), 5 => $langs->transnoentities('Month05'), 6 => $langs->transnoentities('Month06'),
	7 => $langs->transnoentities('Month07'), 8 => $langs->transnoentities('Month08'), 9 => $langs->transnoentities('Month09'),
	10 => $langs->transnoentities('Month10'), 11 => $langs->transnoentities('Month11'), 12 => $langs->transnoentities('Month12'),
);

print '<tr class="oddeven">';
print '<td><label for="LEMONPULSE_FISCAL_MONTH_START">'.$langs->trans('LemonPulseFiscalMonthStart').'</label></td>';
print '<td>';
print '<select name="LEMONPULSE_FISCAL_MONTH_START" id="LEMONPULSE_FISCAL_MONTH_START" class="flat">';
$mois_actuel = (int) getDolGlobalString('LEMONPULSE_FISCAL_MONTH_START', '0');
$auto_label = $langs->transnoentities('LemonPulseAutoLabel', $mois_labels[$auto_value], $auto_source);
print '<option value="0"'.($mois_actuel == 0 ? ' selected' : '').'>'.dol_escape_htmltag($auto_label).'</option>';
foreach ($mois_labels as $num => $label) {
	$selected = $num == $mois_actuel ? ' selected' : '';
	print '<option value="'.$num.'"'.$selected.'>'.$num.' — '.dol_escape_htmltag($label).'</option>';
}
print '</select>';
print '</td>';
print '<td><span class="opacitymedium">'.$langs->trans('LemonPulseFiscalMonthStartHelp').'</span></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td><label for="LEMONPULSE_OBJECTIF_ANNUEL">'.$langs->trans('LemonPulseObjectifAnnuel').'</label></td>';
print '<td><input type="number" step="0.01" min="0" name="LEMONPULSE_OBJECTIF_ANNUEL" id="LEMONPULSE_OBJECTIF_ANNUEL" value="'.dol_escape_htmltag(getDolGlobalString('LEMONPULSE_OBJECTIF_ANNUEL', '0')).'" class="flat width150"> '.$conf->currency.'</td>';
print '<td><span class="opacitymedium">'.$langs->trans('LemonPulseObjectifAnnuelHelp').'</span></td>';
print '</tr>';

print '</table>';

print '<div class="center" style="margin-top:15px;">';
print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

// Bloc "À propos de Lemon" — vitrine éditeur
print '<div style="margin:30px 0;padding:20px 25px;border:1px solid #e0e0e0;border-left:4px solid #FFD21F;border-radius:6px;background:linear-gradient(135deg,#fffef7 0%,#fafafa 100%);">';
print '<h3 style="margin:0 0 10px 0;color:#333;">'.$langs->trans("LemonPulseAboutTitle").'</h3>';
print '<p style="margin:0 0 12px 0;color:#555;">'.$langs->trans("LemonPulseAboutIntro").'</p>';
print '<ul style="margin:0 0 15px 20px;color:#555;">';
print '<li><strong>'.$langs->trans("LemonPulseAboutSvc1Title").'</strong> : '.$langs->trans("LemonPulseAboutSvc1Desc").'</li>';
print '<li><strong>'.$langs->trans("LemonPulseAboutSvc2Title").'</strong> : '.$langs->trans("LemonPulseAboutSvc2Desc").'</li>';
print '<li><strong>'.$langs->trans("LemonPulseAboutSvc3Title").'</strong> : '.$langs->trans("LemonPulseAboutSvc3Desc").'</li>';
print '<li><strong>'.$langs->trans("LemonPulseAboutSvc4Title").'</strong> : '.$langs->trans("LemonPulseAboutSvc4Desc").'</li>';
print '<li><strong>'.$langs->trans("LemonPulseAboutSvc5Title").'</strong> : '.$langs->trans("LemonPulseAboutSvc5Desc").'</li>';
print '</ul>';
print '<p style="margin:0;">';
print '<a href="https://hellolemon.fr" target="_blank" rel="noopener" class="butAction" style="text-decoration:none;">'.$langs->trans("LemonPulseAboutCTA").'</a>';
print ' <span style="color:#999;margin-left:15px;">'.$langs->trans("LemonPulseAboutLocation").'</span>';
print '</p>';
print '</div>';

llxFooter();
$db->close();
