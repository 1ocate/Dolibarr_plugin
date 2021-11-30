<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur   	<eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  	<marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin          	<regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Andreu Bisquerra Gaya  	<jove@bisquerra.com>
 * Copyright (C) 2012	   David Rodriguez Martinez <davidrm146@gmail.com>
 * Copyright (C) 2012-2018 Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015	   Ferran Marcet			<fmarcet@2byte.es>
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
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/commande/orderstoinvoice.php
 *	\ingroup    commande
 *	\brief      Page to invoice multiple orders
 */

 
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("orders", "deliveries", "companies"));

if (! $user->rights->facture->creer)
	accessforbidden();

$id				= (GETPOST('id')?GETPOST('id','int'):GETPOST("facid","int"));  // For backward compatibility
$ref			= GETPOST('ref','alpha');
$action			= GETPOST('action','alpha');
$confirm		= GETPOST('confirm','alpha');
$sref			= GETPOST('sref');
$sref_client	= GETPOST('sref_client');
$sdetail	    = GETPOST('search_options_ds');
$sall			= trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$socid			= GETPOST('socid','int');
$selected		= GETPOST('merge_po');
$sortfield		= GETPOST("sortfield",'alpha');
$sortorder		= GETPOST("sortorder",'alpha');
$viewstatut		= GETPOST('viewstatut');
$search_status = GETPOST('search_status', 'alpha');

$error = 0;

if (! $sortfield) $sortfield='c.rowid';
if (! $sortorder) $sortorder='DESC';

$now = dol_now();
$date_start = dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
$date_end = dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
$date_starty = dol_mktime(0,0,0,$_REQUEST["date_start_delymonth"],$_REQUEST["date_start_delyday"],$_REQUEST["date_start_delyyear"]);	// Date for local PHP server
$date_endy = dol_mktime(23,59,59,$_REQUEST["date_end_delymonth"],$_REQUEST["date_end_delyday"],$_REQUEST["date_end_delyyear"]);

$object = new Propal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

if ($action == 'create')
{
	if (! is_array($selected))
	{
		$error++;
		setEventMessages($langs->trans('Error_OrderNotChecked'), null, 'errors');
		setEventMessages('No Proposal selected', null, 'errors');
	}
	else
	{
		$origin = GETPOST('origin');
		$originid = GETPOST('originid');
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('orderstoinvoice'));


/*
 * Actions
 */

if (($action == 'create' || $action == 'add') && !$error)
{
	//require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
	if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

	$langs->load('bills');
	$langs->load('products');
	$langs->load('main');
	if (isset($_GET['merge_po']))
	{
		$orders_id = GETPOST('merge_po','',1);
		$n        = count($orders_id);
		$i        = 0;

		$originid=$orders_id[0];
		$_GET['originid']=$orders_id[0];

	}
	if (isset($_POST['merge_po']))
	{
		$orders_id = GETPOST('merge_po','',2);
		$nn        = count($orders_id);
		$ii        = 0;

		$originid=$orders_id[0];
		$_POST['originid']=$orders_id[0];

	}

	$projectid		= GETPOST('projectid','int')?GETPOST('projectid','int'):0;
	$lineid			= GETPOST('lineid','int');
	$userid			= GETPOST('userid','int');
	$search_ref		= GETPOST('sf_ref')?GETPOST('sf_ref'):GETPOST('search_ref');
	$closeOrders	= GETPOST('autocloseorders') ? true : false;

	// Security check
	$fieldid = GETPOST('ref','alpha')?'facnumber':'rowid';
	if ($user->societe_id) $socid=$user->societe_id;
	$result = restrictedArea($user, 'propal', $id,'','','fk_soc',$fieldid);

	$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;
	//$object=new Facture($db);
	//$object=new Commande($db);
	$object=new Propal($db);

	// Insert new invoice in database
	if ($action == 'add' && $user->rights->propal->creer)
	{
		$object->socid=GETPOST('socid');
		$db->begin();
		$error=0;

		// Standard or deposit or proforma invoice
		if ($_POST['type'] == 0 )
		{
			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($datefacture))
			{
				$datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
			}
			if (! $error)
			{
				// Si facture standard
				$object->socid				= $_POST['socid'];
				$object->type				= $_POST['type'];
				$object->number				= $_POST['facnumber'];
				$object->date				= $datefacture;
				$object->note_public		= trim($_POST['note_public']);
				$object->note				= trim($_POST['note']);
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
				$object->amount				= $_POST['amount'];
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];

				
				//$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
				//if ($ret < 0) $error++;
				$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
				if ($ret < 0) {
					$error++;
					$action = 'create';
				}
				//extrafild
				//$object->array_options=$extrafields->getOptionalsFromPost(null, $object);
				
				if ($_POST['origin'] && $_POST['originid'])
				{
					//print_r($object->array_options=$extrafields->getOptionalsFromPost(null, $object));
					//print_r($object->array_options);
					//exit();
					$object->origin    = $_POST['origin'];
					$object->origin_id = $orders_id[$ii];
					$object->linked_objects = $orders_id;

					$id = $object->create($user);
					$object->fetch_thirdparty();

					


					if ($id>0)
					{
						/*foreach($orders_id as $origin => $origin_id)
						{
							$origin_id = (! empty($origin_id) ? $origin_id : $object->origin_id);
							$db->begin();
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
							$sql.= "fk_source";
							$sql.= ", sourcetype";
							$sql.= ", fk_target";
							$sql.= ", targettype";
							$sql.= ") VALUES (";
							$sql.= $origin_id;
							$sql.= ", '".$object->origin."'";
							$sql.= ", ".$id;
							$sql.= ", '".$object->element."'";
							$sql.= ")";

							if ($db->query($sql))
							{
								$db->commit();
							}
							else
							{
								$db->rollback();
							}
						}*/

						while ($ii < $nn)
						{

                            require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
							$objectsrc = new Propal($db);
							dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
							$result=$objectsrc->fetch($orders_id[$ii]);
							if ($result > 0)
							{
                                
								// link Supplier Proposal
								$objectsrc->fetchObjectLinked();
								if (count($objectsrc->linkedObjectsIds['supplier_proposal']) > 0)
								{
									foreach ($objectsrc->linkedObjectsIds['supplier_proposal'] as $key => $value)
									{
										$object->add_object_linked('supplier_proposal', $value);
									}
								}

								$lines = $objectsrc->lines;
								if (empty($lines) && method_exists($objectsrc, 'fetch_lines'))
								{
									$objectsrc->fetch_lines();
									$lines = $objectsrc->lines;
								}
								$fk_parent_line=0;
								$num=count($lines);
								for ($i=0;$i<$num;$i++)
								{
									$desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
									if ($lines[$i]->subprice < 0)
									{
										// Negative line, we create a discount line
										$discount = new DiscountAbsolute($db);
										$discount->fk_soc=$object->socid;
										$discount->amount_ht=abs($lines[$i]->total_ht);
										$discount->amount_tva=abs($lines[$i]->total_tva);
										$discount->amount_ttc=abs($lines[$i]->total_ttc);
										$discount->tva_tx=$lines[$i]->tva_tx;
										$discount->fk_user=$user->id;
										$discount->description=$desc;
										$discountid=$discount->create($user);
										if ($discountid > 0)
										{
											$result=$object->insert_discount($discountid);
											//$result=$discount->link_to_invoice($lineid,$id);
										}
										else
										{
											setEventMessages($discount->error, $discount->errors, 'errors');
											$error++;
											break;
										}
									}
									else
									{
										// Positive line
										$product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);
										// Date start
										/*$date_start=false;
										if ($lines[$i]->date_debut_prevue) $date_start=$lines[$i]->date_debut_prevue;
										if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
										if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
										//Date end
										$date_end=false;
										if ($lines[$i]->date_fin_prevue) $date_end=$lines[$i]->date_fin_prevue;
										if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
										if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;
										// Reset fk_parent_line for no child products and special product
										if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9)
										{
											$fk_parent_line = 0;
										}*/

										// Extrafields
										if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
											$lines[$i]->fetch_optionals($lines[$i]->rowid);
											$array_options = $lines[$i]->array_options;
										}

										/*$result = $object->addline(
                                            $desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product,
                                            $lines[$i]->remise_percent, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $date_start, $date_end, $product_type,
                                            $lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options,
                                            $lines[$i]->fk_unit, $object->origin, $lines[$i]->rowid

                                        );*/
                                        $result = $object->addline(
                                            $desc, $lines[$i]->subprice, $lines[$i]->qty, $tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->fk_product, 
                                            $lines[$i]->remise_percent, 'HT', 0, $lines[$i]->info_bits, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $fk_parent_line, 
                                            $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $date_start, $date_end, $array_options, $lines[$i]->fk_unit
                                        );
										if ($result > 0)
										{
											$lineid=$result;
										}
										else
										{
											$lineid=0;
											$error++;
											break;
										}
										// Defined the new fk_parent_line
										if ($result > 0 && $lines[$i]->product_type == 9)
										{
											$fk_parent_line = $result;
										}
									}
								}
								/*if ($closeOrders)
								{
									//$objectsrc->classifyBilled($user);
									$objectsrc->delete();
								}*/
							}
							else
							{
								setEventMessages($objectsrc->error, $objectsrc->errors, 'errors');
								$error++;
							}
							$ii++;
						}
					}
					else
					{
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					}
				}
			}
		}

		// End of object creation, we show it
		if ($id > 0 && ! $error)
		{
			$db->commit();
			// Delete Origin
			if ($closeOrders)
			{
				$db->begin();
				
				foreach ($orders_id as $key => $value) {
					$object->fetch($value);
					$object->delete();
				}

				$db->commit();
			}
			header('Location: '.DOL_URL_ROOT.'/comm/propal/card.php?id='.$id);
			exit;
		}
		else
		{
			$db->rollback();
			$action='create';
			$_GET["origin"]=$_POST["origin"];
			$_GET["originid"]=$_POST["originid"];
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}
}

/*
 * View
 */

$html = new Form($db);
$htmlother = new FormOther($db);
$formpropal = new FormPropal($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);


// Mode creation
if ($action == 'create' && !$error)
{
	//$facturestatic=new Facture($db);

	llxHeader();
	//print load_fiche_titre($langs->trans('NewBill'));
	print load_fiche_titre($langs->trans('Merge Proposal'),'','title_commercial.png');	

	$soc = new Societe($db);
	if ($socid) $res=$soc->fetch($socid);
	if ($res)
	{
		$cond_reglement_id 	= $soc->cond_reglement_id;
		$mode_reglement_id 	= $soc->mode_reglement_id;
		$remise_percent 	= $soc->remise_percent;
	}
	$remise_absolue 	= 0;
	$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:'';

	$absolute_discount=$soc->getAvailableDiscounts();
	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="'.$ref_client.'">';
	print '<input name="ref_int" type="hidden" value="'.$ref_int.'">';
	print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
	print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';
	print '<input type="hidden" name="autocloseorders" value="'.GETPOST('autocloseorders').'">';

    

	dol_fiche_head();

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';


	// Third party
	print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td>';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>'."\n";

    // Date
	print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td>';
	$form->select_date('', 're', '', '', '', "crea_commande", 1, 1);			// Always autofill date with current date
	print '</td></tr>';

	// Type
	/*print '<tr><td class="tdtop fieldrequired">'.$langs->trans('Type').'</td><td>';
	print '<table class="nobordernopadding">'."\n";

	// Standard invoice
	print '<tr height="18"><td width="16px" valign="middle">';
	print '<input type="radio" name="type" value="0"'.(GETPOST('type')==0?' checked':'').'>';
	print '</td><td valign="middle">';
	$desc=$html->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
	print $desc;
	print '</td></tr>'."\n";
	print '</table>'; 
	// Date invoice
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
	$html->select_date('','','','','',"add",1,1);
	print '</td></tr>';
	// Payment term
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
	$html->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';
	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
	$html->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';
    */

	// Project
	if (! empty($conf->projet->enabled))
	{
		$formproject=new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td>';
		$formproject->select_projects($soc->id, $projectid, 'projectid');
		print '</td></tr>';
	}

    include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	$objectsrc = new Propal($db);
	$listoforders = array();
	foreach ($selected as $sel)
	{
		$result=$objectsrc->fetch($sel);
		if ($result > 0)
		{
			$listoforders[] = $objectsrc->ref;
		}
	}


    

	// Modele PDF
	// Template to use by default
	/*print '<tr><td>' . $langs->trans('DefaultModel') . '</td>';
	print '<td>';
	include_once DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php';
	$liste = ModelePDFPropales::liste_modeles($db);
	print $form->selectarray('model', $liste, $conf->global->COMMANDE_ADDON_PDF);
	print "</td></tr>";
    */

    // Other attributes
    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
	$parameters = array('objectsrc' => $objectsrc, 'socid'=>$socid);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		//print $object->showOptionals($extrafields, 'edit');
		print $object->showOptionals($extrafields, 'edit', $parameters);
	}

	// Public note
	print '<tr>';
	print '<td class="" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top">';
	print '<textarea name="note_public" class="quatrevingtpercent" rows="'.ROWS_3.'">';

	print implode(', ', $listoforders);

	print '</textarea></td></tr>';
	// Private note
	if (empty($user->societe_id))
	{
		print '<tr>';
		print '<td class="" valign="top">'.$langs->trans('NotePrivate').'</td>';
		print '<td valign="top">';
		print '<textarea name="note" class="quatrevingtpercent" rows="'.ROWS_3.'">';

		print '</textarea></td></tr>';
	}

	print '</table>';

	while ($i < $n)
	{
		print '<input type="hidden" name="merge_po[]" value="'.$orders_id[$i].'">';

		$i++;
	}

	dol_fiche_end();

	// Button "Create Draft"
	print '<div class="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'" /></div>';
	print "</form>\n";

	print '</td></tr>';
	print "</table>\n";


}

// Mode liste
if (($action != 'create' && $action != 'add') || ($action == 'create' && $error))
{
	llxHeader();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
		jQuery("#checkall").click(function() {
			jQuery(".checkformerge").prop('checked', true);
		});
		jQuery("#checknone").click(function() {
			jQuery(".checkformerge").prop('checked', false);
		});
	});
	</script>
	<?php

	$generic_commande = new Propal($db);

	$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
	//$sql.= ' c.date_valid, commande, c.date_livraison, c.fk_statut, c.facture as billed';
	$sql.= ' c.date_valid, c.fin_validite, c.fk_statut';
        foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) 
        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
	$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
	$sql.= ', '.MAIN_DB_PREFIX.'propal as c';
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (c.rowid = ef.fk_object)";
	$sql.= ' WHERE c.entity IN ('.getEntity('propal').')';
	$sql.= ' AND c.fk_soc = s.rowid';

	// Show orders with status validated, shipping started and delivered (well any order we can bill)
	//$sql.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 ))";

	if ($socid)	$sql.= ' AND s.rowid = '.$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($sref)
	{
		$sql.= " AND c.ref LIKE '%".$db->escape($sref)."%'";
	}
    
	if ($sall)
	{
		$sql.= " AND (c.ref LIKE '%".$db->escape($sall)."%' OR c.note LIKE '%".$db->escape($sall)."%')";
	}

	//Date filter
	if ($date_start && $date_end) $sql.= " AND c.date_valid >= '".$db->idate($date_start)."' AND c.date_valid <= '".$db->idate($date_end)."'";
	if ($date_starty && $date_endy) $sql.= " AND c.date_livraison >= '".$db->idate($date_starty)."' AND c.date_livraison <= '".$db->idate($date_endy)."'";

    

	if (!empty($sref_client))
	{
		$sql.= ' AND c.ref_client LIKE \'%'.$db->escape($sref_client).'%\'';
	}
    //Status
    if ($search_status != '' && $search_status != '-1')
    {
	    $sql .= ' AND c.fk_statut IN ('.$db->sanitize($db->escape($search_status)).')';
    }
    //Extra Fild
    if (!empty($sdetail))
	{
		$sql.= ' AND .ef.ds LIKE \'%'.$db->escape($sdetail).'%\'';
	}
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
	$resql = $db->query($sql);

	if ($resql)
	{
		if ($socid)
		{
			$soc = new Societe($db);
			$soc->fetch($socid);
		}
		$title = $langs->trans('ListOfOrders');
		$title.=' - '.$langs->trans('StatusOrderValidated').', '.$langs->trans("StatusOrderSent").', '.$langs->trans('StatusOrderToBill');
		$num = $db->num_rows($resql);
		print load_fiche_titre($title);
		$i = 0;
		$period=$html->select_date($date_start,'date_start',0,0,1,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,1,'',1,0,1);
		$periodely=$html->select_date($date_starty,'date_start_dely',0,0,1,'',1,0,1).' - '.$html->select_date($date_endy,'date_end_dely',0,0,1,'',1,0,1);

		if (! empty($socid))
		{
			// Company
			$companystatic->id=$socid;
			$companystatic->name=$soc->name;
			print '<h3>'.$companystatic->getNomUrl(1,'customer').'</h3>';
		}

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre('Ref',$_SERVER["PHP_SELF"],'c.ref','','&amp;socid='.$socid,'',$sortfield,$sortorder);
		print_liste_field_titre('RefCustomerOrder',$_SERVER["PHP_SELF"],'c.ref_client','','&amp;socid='.$socid,'',$sortfield,$sortorder);
		print_liste_field_titre('OrderDate',$_SERVER["PHP_SELF"],'c.date_valid','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre('DeliveryDate',$_SERVER["PHP_SELF"],'c.fin_validite','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre('Detail',$_SERVER["PHP_SELF"],'ef.ds','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre('Status',$_SERVER["PHP_SELF"],'c.fk_statut','','&amp;search_status='.$search_status,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre('','','','','','align="center"');
		print '</tr>';

		// Lignes des champs de filtre
		print '<form method="get" action="merge_proposal.php">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		//REF
		print '<input class="flat" size="10" type="text" name="sref" value="'.$sref.'">';
		print '</td>';

		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="10" name="sref_client" value="'.$sref_client.'">';
        print '</td>';

		//DATE ORDER
		print '<td class="liste_titre" align="center">';
		print $period;
		print '</td>';

		//DATE DELIVERY
		print '<td class="liste_titre" align="center">';
		print $periodely;
		print '</td>';

        //Detail
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" type="text" name="search_options_ds" value="'.$sdetail.'">';
        print '</td>';

        // Status
		print '<td class="liste_titre maxwidthonsmartphone right">';
		$formpropal->selectProposalStatus($search_status, 1, 0, 1, 'customer', 'search_status');
		print '</td>';

		//SEARCH BUTTON
		/*print '<td align="right" class="liste_titre">';
		print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '</td>';*/

		//ALL/NONE
		print '<td align="center" class="liste_titre">';
        print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
		print '</td>';

		print '</tr>';
		print '</form>';

		print '<form name="orders2invoice" action="merge_proposal.php" method="GET">';
		
        


		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td class="nowrap">';

			$generic_commande->id=$objp->rowid;
			$generic_commande->ref=$objp->ref;
			$generic_commande->statut = $objp->fk_statut;
			$generic_commande->date_commande = $db->jdate($objp->date_valid);
			$generic_commande->date_livraison = $db->jdate($objp->date_livraison);
            //Extra Fild
            $extralabels=$extrafields->fetch_name_optionals_label($generic_commande->table_element);
            $generic_commande->fetch_optionals($objp->rowid,$extralabels);

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowrap">';
			print $generic_commande->getNomUrl(1,0);
			print '</td>';

			print '<td width="20" class="nobordernopadding nowrap">';
			/*if ($generic_commande->hasDelay()) {
				print img_picto($langs->trans("Late"),"warning");
			}*/
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
			print '</td></tr></table>';
			print '</td>';

			print '<td>'.$objp->ref_client.'</td>';

			// Order date
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($objp->date_valid),'day');
			print '</td>';

			//Delivery date
			print '<td align="center" class="nowrap">';
			print dol_print_date($db->jdate($objp->fin_validite),'day');
			print '</td>';
            
            //Detail
			print '<td align="center" class="nowrap">';
			print $generic_commande->array_options['options_ds'];
			print '</td>';

			// Statut
            print '<td class="nowrap right">'.$generic_commande->getLibStatut(5).'</td>';
			//print '<td align="right" class="nowrap">'.$generic_commande->LibStatut($objp->fk_statut,$objp->billed,5).'</td>';

			// Checkbox
			print '<td align="center">';
			print '<input class="flat checkformerge" type="checkbox" name="merge_po[]" value="'.$objp->rowid.'">';
			print '</td>' ;

			print '</tr>';

			$total = $total + $objp->price;
			$subtotal = $subtotal + $objp->price;
			$i++;
		}
		print '</table>';

		/*
		 * Boutons actions
		*/
		print '<br><div class="center"><input type="checkbox" '.(empty($conf->global->INVOICE_CLOSE_ORDERS_OFF_BY_DEFAULT_FORMASSINVOICE)?' checked="checked"':'').' name="autocloseorders"> '.$langs->trans("Deleate last Proposal");
		print '<div align="right">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" name="origin" value="propal"><br>';
		//print '<a class="butAction" href="index.php">'.$langs->trans("GoBack").'</a>';
		print '<input type="submit" class="butAction" value="'.$langs->trans("Proposal merge").'">';
		print '</div>';
		print '</div>';
		print '</form>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

}

llxFooter();
$db->close();