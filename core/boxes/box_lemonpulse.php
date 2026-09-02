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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 *  \file       htdocs/lemonpulse/core/boxes/box_lemonpulse.php
 *  \ingroup    lemonpulse
 *  \brief      Widget tableau de bord — chiffre d'affaires de l'exercice
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 *  Widget LemonPulse : CA exercice en cours, comparaison N-1, résultat indicatif.
 *  Design minimaliste : gros chiffre + pill évolution, sous-éléments discrets.
 */
class box_lemonpulse extends ModeleBoxes
{
	public $boxcode = "lemonpulse";
	public $boximg = "fa-chart-line";
	public $boxlabel;
	public $depends = array("facture");

	public $info_box_head = array();
	public $info_box_contents = array();

	/**
	 *  Constructeur.
	 */
	public function __construct($db, $param = '')
	{
		global $user, $langs;
		$this->db = $db;
		$langs->load("lemonpulse@lemonpulse");
		$this->boxlabel = $langs->transnoentitiesnoconv("BoxLemonPulseTitle");
		$this->hidden = !($user->hasRight('facture', 'lire') || $user->hasRight('lemonpulse', 'lire'));
	}

	/**
	 *  Charge les données et compose le HTML du widget.
	 */
	public function loadBox($max = 5)
	{
		global $conf, $langs;

		$langs->load("lemonpulse@lemonpulse");
		$langs->load("main");

		$mois_start = self::resolveFiscalMonthStart();
		$objectif = (float) getDolGlobalString('LEMONPULSE_OBJECTIF_ANNUEL', '0');

		$today = dol_now();
		$cur_year = (int) dol_print_date($today, '%Y');
		$cur_month = (int) dol_print_date($today, '%m');

		$fy_start_year = $cur_year - ($cur_month >= $mois_start ? 0 : 1);
		$fy_start = dol_mktime(0, 0, 0, $mois_start, 1, $fy_start_year);
		$prev_fy_start = dol_time_plus_duree($fy_start, -1, 'y');
		$prev_fy_end_full = $fy_start - 1;
		$prev_fy_same_period_end = dol_time_plus_duree($today, -1, 'y');

		$ca_courant = $this->sumTotalHT('facture', 'invoice', $fy_start, $today);
		$ca_n1_meme_periode = $this->sumTotalHT('facture', 'invoice', $prev_fy_start, $prev_fy_same_period_end);
		$ca_n1_complet = $this->sumTotalHT('facture', 'invoice', $prev_fy_start, $prev_fy_end_full);

		$achats_courant = $this->sumTotalHT('facture_fourn', 'facture_fourn', $fy_start, $today);
		$achats_n1_meme_periode = $this->sumTotalHT('facture_fourn', 'facture_fourn', $prev_fy_start, $prev_fy_same_period_end);

		$resultat_courant = $ca_courant - $achats_courant;
		$resultat_n1_meme_periode = $ca_n1_meme_periode - $achats_n1_meme_periode;

		$evol_ca = $this->evolution($ca_courant, $ca_n1_meme_periode);
		$evol_achats = $this->evolution($achats_courant, $achats_n1_meme_periode);
		$evol_resultat = $this->evolution($resultat_courant, $resultat_n1_meme_periode);

		$this->info_box_head = array(
			'text' => $langs->trans('BoxLemonPulseTitle'),
			'limit' => dol_strlen($langs->trans('BoxLemonPulseTitle')),
			'subtext' => dol_print_date($fy_start, 'day').' → '.dol_print_date($today, 'day'),
			'subpicto' => '',
		);

		$html = $this->renderHero($ca_courant, $evol_ca, $langs->trans('LemonPulseCAEnCours'), $langs, $conf);
		$html .= $this->renderRefRows(array(
			array($langs->trans('LemonPulseCAN1MemePeriode'), $ca_n1_meme_periode),
			array($langs->trans('LemonPulseCAN1Complet'), $ca_n1_complet),
		), $langs, $conf);

		if ($objectif > 0) {
			$html .= $this->renderObjectif($objectif, $ca_courant, $langs, $conf);
		}

		$html .= $this->renderDivider();
		$html .= $this->renderSecondary($langs->trans('LemonPulseAchatsEnCours'), $achats_courant, $evol_achats, $langs, $conf, true);
		$html .= $this->renderSecondary($langs->trans('LemonPulseResultatIndicatif'), $resultat_courant, $evol_resultat, $langs, $conf, false, true);
		$html .= $this->renderNote($langs->trans('LemonPulseNoteIndicatif'));

		$this->info_box_contents[0][] = array(
			'td' => 'colspan="3" class="nohover" style="padding:0;"',
			'text' => $html,
			'asis' => 1,
		);
	}

	/**
	 *  Hero : chiffre principal très grand + pill évolution + label discret.
	 */
	private function renderHero($value, $evol, $label, $langs, $conf)
	{
		$h = '<div style="padding:22px 24px 18px;">';
		$h .= '<div style="display:flex;align-items:baseline;gap:14px;flex-wrap:wrap;">';
		$h .= '<div style="font-size:32px;font-weight:600;color:#111;letter-spacing:-0.02em;line-height:1;">';
		$h .= price($value, 0, $langs, 1, 0, 0, $conf->currency);
		$h .= '</div>';
		$h .= $this->renderPill($evol);
		$h .= '</div>';
		$h .= '<div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.05em;margin-top:6px;font-weight:500;">'.$label.'</div>';
		$h .= '</div>';
		return $h;
	}

	/**
	 *  Pill colorée pour l'évolution en pourcentage.
	 */
	private function renderPill($evol, $reverse = false)
	{
		if ($evol['pct'] === null) {
			return '<span style="display:inline-block;padding:3px 8px;border-radius:10px;background:#f4f4f4;color:#888;font-size:12px;font-weight:600;">—</span>';
		}
		$pct = $evol['pct'];
		$is_positive = $pct >= 0;
		$is_good = $reverse ? !$is_positive : $is_positive;
		$bg = $is_good ? '#e8f5e9' : '#fdecea';
		$fg = $is_good ? '#2e7d32' : '#c62828';
		$arrow = $is_positive ? '↑' : '↓';
		$sign = $pct > 0 ? '+' : '';
		return '<span style="display:inline-block;padding:3px 8px;border-radius:10px;background:'.$bg.';color:'.$fg.';font-size:12px;font-weight:600;line-height:1.4;">'.$arrow.' '.$sign.$pct.'%</span>';
	}

	/**
	 *  Lignes de référence discrètes (label gauche, valeur droite, gris).
	 */
	private function renderRefRows($rows, $langs, $conf)
	{
		$h = '<div style="padding:0 24px 14px;display:flex;flex-direction:column;gap:5px;">';
		foreach ($rows as $row) {
			list($label, $value) = $row;
			$h .= '<div style="display:flex;justify-content:space-between;font-size:12px;color:#999;">';
			$h .= '<span>'.$label.'</span>';
			$h .= '<span style="font-variant-numeric:tabular-nums;">'.price($value, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
			$h .= '</div>';
		}
		$h .= '</div>';
		return $h;
	}

	/**
	 *  Barre de progression objectif annuel — fine et discrète.
	 */
	private function renderObjectif($objectif, $ca, $langs, $conf)
	{
		$pct = $ca > 0 ? round(($ca / $objectif) * 100, 1) : 0;
		$bar_width = min(100, max(0, $pct));
		$color = $this->objectifColor($pct);

		$h = '<div style="padding:6px 24px 14px;">';
		$h .= '<div style="display:flex;justify-content:space-between;font-size:11px;color:#666;margin-bottom:6px;">';
		$h .= '<span style="text-transform:uppercase;letter-spacing:0.05em;font-weight:500;">'.$langs->trans('LemonPulseObjectif').' · '.price($objectif, 0, $langs, 0, 0, 0, $conf->currency).'</span>';
		$h .= '<span style="font-weight:600;color:#333;">'.$pct.'%</span>';
		$h .= '</div>';
		$h .= '<div style="background:#f0f0f0;border-radius:99px;height:4px;overflow:hidden;">';
		$h .= '<div style="background:'.$color.';height:100%;width:'.$bar_width.'%;border-radius:99px;transition:width 0.3s;"></div>';
		$h .= '</div>';
		$h .= '</div>';
		return $h;
	}

	/**
	 *  Couleur de la barre objectif selon le pourcentage atteint.
	 */
	private function objectifColor($pct)
	{
		if ($pct >= 100) {
			return '#2e7d32';
		}
		if ($pct >= 75) {
			return '#0288d1';
		}
		if ($pct >= 50) {
			return '#f57c00';
		}
		return '#c62828';
	}

	/**
	 *  Séparateur fin entre sections.
	 */
	private function renderDivider()
	{
		return '<div style="border-top:1px solid #eee;margin:0 24px;"></div>';
	}

	/**
	 *  Ligne secondaire : label + valeur moyenne + pill évolution.
	 *  Le résultat (last=true) est en gras pour le démarquer.
	 */
	private function renderSecondary($label, $value, $evol, $langs, $conf, $reverse = false, $emphasize = false)
	{
		$value_color = $emphasize ? ($value >= 0 ? '#2e7d32' : '#c62828') : '#333';
		$value_weight = $emphasize ? '600' : '500';
		$label_weight = $emphasize ? '600' : '400';

		$h = '<div style="padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;">';
		$h .= '<div style="font-size:13px;color:#444;font-weight:'.$label_weight.';">'.$label.'</div>';
		$h .= '<div style="display:flex;align-items:center;gap:10px;">';
		$h .= '<span style="font-size:14px;font-weight:'.$value_weight.';color:'.$value_color.';font-variant-numeric:tabular-nums;">'.price($value, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
		$h .= $this->renderPill($evol, $reverse);
		$h .= '</div>';
		$h .= '</div>';
		return $h;
	}

	/**
	 *  Note indicative en bas du widget.
	 */
	private function renderNote($text)
	{
		return '<div style="padding:10px 24px 16px;font-size:10px;color:#aaa;border-top:1px solid #f5f5f5;margin-top:4px;">'.$text.'</div>';
	}

	/**
	 *  Résout le mois de début d'exercice fiscal en cascade.
	 *
	 *  @return int  Mois 1-12
	 */
	public static function resolveFiscalMonthStart()
	{
		$override = (int) getDolGlobalString('LEMONPULSE_FISCAL_MONTH_START', '0');
		if ($override >= 1 && $override <= 12) {
			return $override;
		}
		$societe = (int) getDolGlobalString('SOCIETE_FISCAL_MONTH_START', '0');
		if ($societe >= 1 && $societe <= 12) {
			return $societe;
		}
		$compta = (int) getDolGlobalString('ACCOUNTING_FISCAL_PERIOD_MONTH_START', '0');
		if ($compta >= 1 && $compta <= 12) {
			return $compta;
		}
		return 1;
	}

	/**
	 *  Calcule la somme du total HT d'une table facture sur une période.
	 */
	private function sumTotalHT($table, $entity_key, $date_start, $date_end)
	{
		$sql = "SELECT SUM(f.total_ht) as total";
		$sql .= " FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE f.fk_statut > 0";

		// Une facture REMPLACÉE reste en base à côté de celle qui la remplace :
		// les compter toutes les deux gonfle le chiffre d'affaires du montant de
		// la première, sans que rien ne le signale. Le cœur écarte exactement ce
		// cas dans FactureStats ; on reprend sa clause, côté client seulement.
		//
		// Les AUTRES clôtures en statut 3 restent comptées, volontairement : une
		// créance abandonnée a bien été facturée, elle appartient au chiffre
		// d'affaires de la période (sa perte se traite en charge, pas en moins-CA).
		//
		// COALESCE parce que `close_code` est NULLable : sans lui, une facture
		// abandonnée sans motif renseigné rendrait la condition NULL, donc fausse,
		// et sortirait du total — le cœur a cette fragilité, pas nous.
		if ($table === 'facture') {
			$sql .= " AND (f.fk_statut <> 3 OR COALESCE(f.close_code, '') <> 'replaced')";
		}

		$sql .= " AND f.entity IN (".getEntity($entity_key).")";
		$sql .= " AND f.datef >= '".$this->db->idate($date_start)."'";
		$sql .= " AND f.datef <= '".$this->db->idate($date_end)."'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			// Sans cette trace, une requête cassée s'affiche comme un chiffre
			// d'affaires à zéro — indiscernable d'une période sans vente.
			dol_syslog('box_lemonpulse::sumTotalHT '.$this->db->lasterror(), LOG_ERR);
			return 0.0;
		}
		$obj = $this->db->fetch_object($resql);
		return (float) ($obj->total ?? 0);
	}

	/**
	 *  Calcule l'évolution en pourcentage entre deux valeurs.
	 *
	 *  @return array{pct: float|null, abs: float}
	 */
	private function evolution($current, $previous)
	{
		if (abs($previous) < 0.01) {
			return array('pct' => null, 'abs' => $current);
		}
		return array(
			'pct' => round((($current - $previous) / abs($previous)) * 100, 1),
			'abs' => $current - $previous,
		);
	}

	/**
	 *  Affiche le widget.
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
