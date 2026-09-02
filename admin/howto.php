<?php
/* Copyright (C) 2026 Lemon <hello@hellolemon.fr> - GPL v3+
 *
 * LemonPulse - Mode d'emploi embarqué.
 *
 * Guide en français pour l'administrateur qui installe le widget : mise en
 * route, réglage de l'exercice, et surtout ce que les chiffres comptent et ne
 * comptent pas — c'est là que naissent les malentendus, parce qu'un écart de
 * quelques euros avec le bilan fait douter de tout le reste.
 * Contenu volontairement en dur, comme les autres modules Lemon : l'audience
 * est l'administrateur francophone.
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

global $langs, $user;

$langs->loadLangs(array('admin', 'lemonpulse@lemonpulse'));

if (!$user->admin) {
	accessforbidden();
}

$setupurl = dol_buildpath('/lemonpulse/admin/setup.php', 1);
$homeurl = DOL_URL_ROOT.'/index.php';

llxHeader('', 'LemonPulse - '.$langs->trans('LemonPulseTabHowto'));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre('LemonPulse - '.$langs->trans('Administration'), $linkback, 'title_setup');

$head = lemonpulseAdminPrepareHead();
print dol_get_fiche_head($head, 'howto', $langs->trans('ModuleLemonPulseName'), -1, 'bill');

// Styles scopés au guide (préfixe lpul-), aucun sélecteur global : le CSS d'un
// module se charge sur toutes les pages de Dolibarr.
print '<style>
.lpul-howto { max-width: 920px; }
.lpul-howto h3 { margin: 26px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #FFD21F; }
.lpul-howto h3:first-child { margin-top: 4px; }
.lpul-howto p, .lpul-howto li { line-height: 1.6; }
.lpul-howto ol, .lpul-howto ul { margin: 8px 0 8px 22px; }
.lpul-howto table.lpul-mini { border-collapse: collapse; margin: 10px 0; width: 100%; }
.lpul-howto table.lpul-mini td, .lpul-howto table.lpul-mini th { border: 1px solid #e0e0e0; padding: 7px 10px; vertical-align: top; text-align: left; }
.lpul-howto table.lpul-mini th { background: #f7f7f8; }
.lpul-howto .lpul-note { background: #fff9e0; padding: 10px 14px; margin: 10px 0; border-radius: 6px; }
</style>';

print '<div class="lpul-howto">';

// ------------------------------------------------------------- En résumé
print '<h3>En résumé</h3>';
print '<p>LemonPulse ajoute <strong>un widget sur la page d\'accueil</strong> de Dolibarr. Il répond à une seule question, celle qu\'on se pose en ouvrant son ERP le matin : <em>où j\'en suis par rapport à l\'an dernier ?</em></p>';
print '<p>Il affiche le chiffre d\'affaires de l\'exercice en cours, l\'écart avec la même période de l\'exercice précédent, les achats fournisseurs, et le résultat indicatif (CA moins achats). Rien d\'autre. Il ne crée aucune donnée, n\'écrit dans aucune table, et ne modifie rien à votre comptabilité : il lit vos factures et il additionne.</p>';

// ------------------------------------------------------------ Mise en route
print '<h3>Mise en route</h3>';
print '<ol>';
print '<li><strong>Le module est activé</strong> — c\'est fait, vous lisez cette page.</li>';
print '<li><strong>Poser le widget sur l\'accueil.</strong> Allez sur <a href="'.dol_escape_htmltag($homeurl).'">l\'accueil</a>, cliquez sur « Ajouter le widget au tableau de bord » (en haut à droite de la zone des widgets), et choisissez <strong>Chiffre d\'affaires</strong>. Le widget n\'apparaît pas tout seul : Dolibarr laisse chaque utilisateur composer son tableau de bord.</li>';
print '<li><strong>Vérifier le mois de début d\'exercice</strong> dans l\'onglet <a href="'.dol_escape_htmltag($setupurl).'">Paramètres</a>. Il est détecté automatiquement ; ne le forcez que s\'il est faux.</li>';
print '<li><strong>Saisir un objectif annuel</strong>, si vous en avez un. Une barre de progression apparaît alors sous le chiffre d\'affaires. Laissez 0 pour ne rien afficher.</li>';
print '</ol>';

// --------------------------------------------------------- Mois d'exercice
print '<h3>Comment le mois d\'exercice est trouvé</h3>';
print '<p>Beaucoup d\'entreprises ne clôturent pas au 31 décembre. LemonPulse cherche donc le mois d\'ouverture dans cet ordre, et s\'arrête au premier trouvé :</p>';
print '<table class="lpul-mini">';
print '<tr><th style="width:40px;">#</th><th>Où il regarde</th><th>Quand ça s\'applique</th></tr>';
print '<tr><td>1</td><td>Le réglage de cette page</td><td>Vous avez forcé un mois. Il gagne sur tout le reste.</td></tr>';
print '<tr><td>2</td><td>Configuration de la société Dolibarr</td><td>Le cas normal si votre société est correctement renseignée.</td></tr>';
print '<tr><td>3</td><td>Module Comptabilité</td><td>Si le module est actif et que son exercice y est réglé.</td></tr>';
print '<tr><td>4</td><td>Janvier</td><td>Dernier recours, quand rien n\'est renseigné nulle part.</td></tr>';
print '</table>';
print '<p>La page Paramètres vous dit laquelle de ces sources est utilisée en ce moment : si le chiffre vous surprend, c\'est la première chose à regarder.</p>';

// -------------------------------------------------------------- Le calcul
print '<h3>Ce qui est compté, et ce qui ne l\'est pas</h3>';
print '<p>C\'est le point à lire avant de comparer le widget à un bilan. Les chiffres sont <strong>indicatifs</strong> : ils viennent de la somme du montant hors taxes de vos factures, pas d\'écritures comptables.</p>';
print '<table class="lpul-mini">';
print '<tr><th>Compté</th><th>Pas compté</th></tr>';
print '<tr>';
print '<td><ul style="margin:0 0 0 18px;">';
print '<li>Factures clients <strong>validées</strong> et payées</li>';
print '<li>Factures fournisseurs validées et payées, côté achats</li>';
print '<li>Les <strong>avoirs</strong>, avec leur montant négatif : ils réduisent le chiffre d\'affaires, comme attendu</li>';
print '</ul></td>';
print '<td><ul style="margin:0 0 0 18px;">';
print '<li>Les <strong>brouillons</strong> : une facture non validée n\'existe pas encore</li>';
print '<li>Les factures <strong>annulées</strong></li>';
print '<li>Les écritures comptables saisies à la main, hors facture</li>';
print '<li>Les salaires, charges sociales, amortissements — le « résultat » affiché n\'est donc pas un résultat comptable</li>';
print '</ul></td>';
print '</tr>';
print '</table>';
print '<p>La période va du premier jour du mois d\'exercice jusqu\'à <strong>aujourd\'hui inclus</strong>. La comparaison N-1 porte sur exactement la même fenêtre, décalée d\'un an : au 3 mars, on compare bien du 1<sup>er</sup> janvier au 3 mars, et non à l\'année N-1 entière. Le CA complet de l\'exercice précédent est affiché à part, en ligne de référence.</p>';
print '<div class="lpul-note"><strong>Les flèches suivent le sens métier, pas le signe.</strong> Une hausse du chiffre d\'affaires est verte, une hausse des achats est rouge. C\'est volontaire : personne ne se réjouit de dépenser plus.</div>';

// ---------------------------------------------------------------- Droits
print '<h3>Qui voit le widget</h3>';
print '<p>Le widget est visible par tout utilisateur qui possède <strong>l\'un des deux</strong> droits suivants :</p>';
print '<table class="lpul-mini">';
print '<tr><th>Droit</th><th>Ce qu\'il implique</th></tr>';
print '<tr><td><strong>Factures : consulter</strong> (droit standard Dolibarr)</td><td>La plupart des dirigeants et comptables l\'ont déjà. Rien à faire.</td></tr>';
print '<tr><td><strong>LemonPulse : voir le widget</strong></td><td>À attribuer si vous voulez montrer les chiffres à quelqu\'un <em>sans</em> lui ouvrir les factures elles-mêmes.</td></tr>';
print '</table>';
print '<div class="lpul-note">Un widget qui affiche le chiffre d\'affaires de l\'entreprise sur la page d\'accueil se voit par-dessus l\'épaule. Regardez qui possède le droit « Factures : consulter » avant de vous en remettre à lui.</div>';
print '<p>En multi-société, chaque entité ne voit que ses propres factures : les totaux sont filtrés sur l\'entité courante.</p>';

// ------------------------------------------------------------ Mises à jour
print '<h3>Mises à jour</h3>';
print '<p>La page Paramètres affiche un bandeau quand une version plus récente est publiée. Elle interroge pour cela un fichier de version chez l\'éditeur, une fois par jour au maximum ; si le serveur est injoignable, elle se tait et réessaie le lendemain — jamais au prix d\'une page qui rame.</p>';
print '<p>Aucune donnée de votre instance n\'est transmise : la requête ne fait que demander un numéro de version.</p>';

// --------------------------------------------------------------- Support
print '<h3>Support</h3>';
print '<p>hello@hellolemon.fr — 06.26.30.63.29</p>';
print '<p>Ce module est livré en installation autonome — vous l\'installez et le configurez vous-même à l\'aide de cette documentation. Un accompagnement à l\'installation est possible sur demande, facturé au ticket.</p>';

print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
