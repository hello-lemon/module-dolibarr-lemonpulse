<?php
/* Copyright (C) 2026 Lemon <hello@hellolemon.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Terme additionnel (GPL v3, article 7 b) : voir NOTICE.md.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

/**
 *  \defgroup   lemonpulse   Module LemonPulse
 *  \brief      LemonPulse : widget chiffre d'affaires sur le tableau de bord
 *  \file       htdocs/lemonpulse/core/modules/modLemonPulse.class.php
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Descripteur du module LemonPulse.
 *
 *  Ajoute un widget tableau de bord affichant :
 *    - le chiffre d'affaires de l'exercice en cours (factures clients validées)
 *    - le CA de la même période sur l'exercice précédent + évolution
 *    - le CA de l'exercice précédent complet
 *    - les achats fournisseurs de l'exercice en cours
 *    - le résultat indicatif (CA - achats)
 */
class modLemonPulse extends DolibarrModules
{
	/**
	 *  Constructeur.
	 *
	 *  @param  DoliDB  $db  Handler BDD
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 210007;
		$this->rights_class = 'lemonpulse';
		$this->family = "financial";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Widget tableau de bord chiffre d'affaires (exercice en cours, comparaison N-1, résultat indicatif)";
		$this->descriptionlong = "LemonPulse ajoute un widget sur le tableau de bord Dolibarr affichant le chiffre d'affaires de l'exercice fiscal en cours, la comparaison avec la même période de l'exercice précédent, le CA total de l'exercice précédent, les achats fournisseurs de l'exercice et le résultat indicatif (CA - achats). Le mois de début d'exercice est paramétrable.";
		$this->editor_name = 'Lemon';
		$this->editor_url = 'https://hellolemon.fr';
		// Fichier texte ne contenant que le numéro de version. Lu par le cœur
		// (badge de la liste des modules, si CHECKLASTVERSION_EXTERNALMODULE est
		// activé) ET par le bandeau de notre page de configuration.
		$this->url_last_version = 'https://hellolemon.fr/dolibarr/versions/lemonpulse.txt';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'bill';

		$this->module_parts = array();

		$this->dirs = array();
		$this->config_page_url = array("setup.php@lemonpulse");
		$this->hidden = false;
		$this->depends = array('modFacture');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("lemonpulse@lemonpulse");
		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(18, 0, 0);

		$this->tables = array();

		// Constantes paramétrables (admin/setup.php)
		// Dernier paramètre à 0 : jamais effacées à la désactivation. remove() est
		// appelé à chaque toggle, et une mise à jour passe par désactiver/réactiver :
		// à 1, les réglages du client revenaient à leurs valeurs par défaut.
		$this->const = array(
			0 => array('LEMONPULSE_FISCAL_MONTH_START', 'chaine', '0', 'Mois début exercice fiscal (0=auto, 1-12=override)', 0, 'current', 0),
			1 => array('LEMONPULSE_OBJECTIF_ANNUEL', 'chaine', '0', 'Objectif annuel de CA HT (0 = désactivé)', 0, 'current', 0),
		);

		// Permissions
		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = $this->numero * 100 + 1; // 50028001
		$this->rights[$r][1] = 'Voir le widget chiffre d\'affaires';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';
		$this->rights[$r][5] = '';
		$r++;

		// Widget (box) déclaré pour activation auto
		$this->boxes = array(
			0 => array(
				'file' => 'box_lemonpulse.php@lemonpulse',
				'note' => 'Chiffre d\'affaires exercice en cours',
				'enabledbydefaulton' => 'Home',
			),
		);

		// Cron : aucun
		$this->cronjobs = array();

		$this->menu = array();
	}

	/**
	 *  Activation du module.
	 *
	 *  @param  string  $options  Options
	 *  @return int
	 */
	public function init($options = '')
	{
		$sql = array();
		return $this->_init($sql, $options);
	}

	/**
	 *  Désactivation du module.
	 *
	 *  @param  string  $options  Options
	 *  @return int
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
