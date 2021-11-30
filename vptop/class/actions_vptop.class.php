<?php
/* Copyright (C) 2021 Yeo Jay <purchasewgi@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    vptop/class/actions_vptop.class.php
 * \ingroup vptop
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsVPtoP
 */



class ActionsVPtoP
{
/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * @param   array         $parameters     Hook metadatas (context, etc...)
	 * @param   Commande    $object        The object to process
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons(array $parameters,  $object, &$action, HookManager $hookmanager)
	{
		global $langs, $user;

		
		echo '<script>';
		//supplier hide
		if (!$user->rights->fournisseur->lire){
			echo 'jQuery("tr>.propaldet_extras_supplier").parent().css("display","none");';
			echo 'jQuery("tr>.propaldet_extras_sp").parent().css("display","none");';
			echo 'jQuery("tr>.commandedet_extras_supplier").parent().css("display","none");';
			echo 'jQuery("tr>.commandedet_extras_sp").parent().css("display","none");';
			echo 'jQuery("tr>.propaldet_extras_spp").parent().css("display","none");';
			echo 'jQuery("tr>.propaldet_extras_spd").parent().css("display","none");';
		}
		//echo 'jQuery("tr>.commandedet_extras_spp").parent().css("display","none");';
		//echo 'jQuery("tr>.commandedet_extras_spd").parent().css("display","none");';
		echo 'var buying_price = $("input[name=buying_price]:first");';
		echo '$("#buying_price").val(price2numjs(buying_price.val().replace(",","")));';
		echo '</script>';
		
		//print '<div class="inline-block divButAction"><a class="butActionDelete" href="/custom/vptop/reception_card.php?action=create&origin=supplierorder&origin_id=' . $object->id . '&amp;socid=">' . $object->element . '</a></div>';

		if ($object->element=='supplier_proposal') {

			if ($object->statut > 0)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=new_customer_proposal&amp;object=' . $object->element . '">' . $langs->trans("Customer Proposal") . '</a></div>';

				return 0;
			}
		}
		if ($object->element=='propal') {

			if ($object->statut > 0)
			{

				print '<div class="inline-block divButAction"><a class="butActionDelete" href="/custom/vptop/merge_proposal.php?socid=' . $object->socid . '&amp;sref_client='. $object->ref_client . '">' . $langs->trans("Merge") . '</a></div>';
				//print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=new_sporder&amp;object=' . $object->element . '">' . $langs->trans("Supplier Order") . '</a></div>';

				
				return 0;
			}
		}
		if ($object->element=='commande') {

			if ($object->statut > 0)
			{

				print '<div class="inline-block divButAction"><a class="butActionDelete" href="/custom/vptop/merge.php?socid=' . $object->socid . '&amp;sref_client='. $object->ref_client . '">' . $langs->trans("Merge") . '</a></div>';
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;socid=' . $object->socid . '&amp;action=new_sporder&amp;object=' . $object->element . '">' . $langs->trans("Supplier Order") . '</a></div>';

				
				return 0;
			}
		}
		if ($object->element=='order_supplier') {

			if ($object->statut > 0)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="/custom/vptop/reception_card.php?action=create&origin=supplierorder&origin_id=' . $object->id . '&amp;socid=">' . $langs->trans("Reception") . '</a></div>';
			
				return 0;
			}
		}
		if ($object->element=='reception') {
			//header("Location: '.DOL_URL_ROOT.'/custom/vptop/reception_card.php?id='.$object->id");
			$arrayofmassactions = array(
				'presend'=>$langs->trans("SendByMail"),
			);
		}
		
	}
	/**
	 * @param   array         $parameters     Hook metadatas (context, etc...)
	 * @param   Commande    $object        The object to process
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formAddObjectLine(array $parameters,  $object, &$action, HookManager $hookmanager)
	{
		global $langs, $user;

		//$object->formAddObjectLine(1, $societe, $mysoc);
		echo '<script>';
		echo '$("#prod_entry_mode_free").prop("checked", true);';
		echo '$("#select_type option:eq(1)").prop("selected", true);';
		echo '$("#tva_tx option:eq(0)").prop("selected", true);';
		echo 'jQuery("#dp_desc").focus();';
		
		//echo 'jQuery("tr>.commandedet_extras_spp").parent().css("display","none");';
		//echo 'jQuery("tr>.commandedet_extras_spd").parent().css("display","none");';

		//for no right supplier mangment 
		if (!$user->rights->fournisseur->lire){
			echo 'jQuery("tr>.propaldet_extras_spp").parent().css("display","none");';
			echo 'jQuery("tr>.propaldet_extras_spd").parent().css("display","none");';
			echo 'jQuery("tr>.propaldet_extras_supplier").parent().css("display","none");';
			echo 'jQuery("tr>.propaldet_extras_sp").parent().css("display","none");';
			echo 'jQuery("tr>.commandedet_extras_supplier").parent().css("display","none");';
			echo 'jQuery("tr>.commandedet_extras_sp").parent().css("display","none");';
		}
		echo '</script>';
	}

	/**
	 * @param   array         $parameters     Hook metadatas (context, etc...)
	 * @param   SupplierProposal    $object        The object to process
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formConfirm(array $parameters,  $object, &$action, HookManager $hookmanager)
	{
		global $langs, $form, $conf, $object, $db;
		if ($object->element=='supplier_proposal') {
			// New Customer Proposal
			if ($action == 'new_customer_proposal') {
				// Create an array for form
				$formquestion = array(
									// 'text' => $langs->trans("ConfirmClone"),
									// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
									// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' =>
									// 1),
									array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '(s.client=1 OR s.client=2 OR s.client=3)')));
				if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY) && !empty($object->date_livraison)) {
					$formquestion[] = array('type' => 'date','name' => 'date_delivery','label' => $langs->trans("DeliveryDate"),'value' => $object->date_livraison);
				}
				// Paiement incomplet. On demande si motif = escompte ou autre
				$this->resprints = $form->formconfirm('/comm/propal/card.php?origin=supplier_proposal&originid=' . $object->id.'&proposal_supplier=1','', $langs->trans('ConfirmClonePropal', $object->ref), 'new_customer_proposal', $formquestion, 'yes', 1);
				//$this->resprints = $form->formconfirm('/custom/vptop/card.php?origin=supplier_proposal&originid=' . $object->id, $langs->trans('ClonePropal'), $langs->trans('ConfirmClonePropal', $object->ref), 'create', $formquestion, 'yes', 1);
			}
		}	
		if ($object->element=='commande') {
			// New Customer Proposal
			if ($action == 'new_sporder') {

				$object->fetchObjectLinked('','',$object->id);
				$tosocid ="";
				if (count($object->linkedObjectsIds['supplier_proposal']) > 0)
				{
					$tosocid ="";
					foreach ($object->linkedObjectsIds['supplier_proposal'] as $key => $value)
					{
						$supplier_proposal = new SupplierProposal($db);
						$supplier_proposal->fetch($value);
						$tosocid = $supplier_proposal->socid;
					}

				}

				// Create an array for form
				$formquestion = array(
									// 'text' => $langs->trans("ConfirmClone"),
									// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
									// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' =>
									// 1),
									//array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company(GETPOST('socid', 'int'), 'socid','s.fournisseur=1')));
									array('type' => 'other','name' => 'socid','label' => $langs->trans("SelectThirdParty"),'value' => $form->select_company($tosocid, 'socid','s.fournisseur=1')));
				if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY) && !empty($object->date_livraison)) {
					$formquestion[] = array('type' => 'date','name' => 'date_delivery','label' => $langs->trans("DeliveryDate"),'value' => $object->date_livraison);
				}
				// Paiement incomplet. On demande si motif = escompte ou autre
				$this->resprints = $form->formconfirm('/custom/vptop/sporder.php?origin=commande&originid=' . $object->id, $langs->trans('ClonePropal'), $langs->trans('ConfirmClonePropal', $object->ref), 'create', $formquestion, 'yes', 1);
			}
		}	
		
		
	}
	/**
	 * @param   array         $parameters     Hook metadatas (context, etc...)
	 * @param   SupplierProposal    $object        The object to process
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function createFrom(array $parameters, $object, &$action, HookManager $hookmanager)
	{
		global $langs, $user, $srcobject, $object, $objFrom;

		if ($object->element=='commande') {

			if(!empty($srcobject)){
				$srcobject->fetchObjectLinked();
				if (count($srcobject->linkedObjectsIds['supplier_proposal']) > 0)
				{
					foreach ($srcobject->linkedObjectsIds['supplier_proposal'] as $key => $value)
					{
						$object->add_object_linked('supplier_proposal', $value);
					}
				}
			}
		
		}
		if ($object->element=='propal') {

			$object->fetchObjectLinked();
			if (count($object->linkedObjectsIds['supplier_proposal']) > 0)
			{
				foreach ($object->linkedObjectsIds['supplier_proposal'] as $key => $value)
				{
					$parameters['clonedObj']->add_object_linked("supplier_proposal", $value);
					
				}
			}
		}
	}
	/**
	 * @param   array         $parameters     Hook metadatas (context, etc...)
	 * @param   SupplierProposal    $object        The object to process
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions(array $parameters, &$object, &$action, HookManager $hookmanager){

		global $langs, $form, $conf, $ref, $rowid, $user, $socid, $forceentity, $db, $contextpage, $massactionbutton, $sortfield, $sortorder, $search_status, $massaction;
		//if ($object->element=='') {
		if ($contextpage=='supplierorderlist') {
		
			if ($massaction=="createbills") 
			{
				$massaction = GETPOST('massaction', 'alpha');
				$toselect = GETPOST('toselect', 'array');
				
				
				
				print '	
				<form id="input_form" method="POST" action="/custom/vptop/sporder_list.php">
					<input type="hidden" name="massaction" value='.$massaction.'>';
					foreach ($toselect as $key => $value) {
						print '<input type="hidden" name="toselect[]" value='.$value.'>';
					}

				print '	
					
					<input type="hidden" name="confirmmassaction" value="Confirm">
					//<input type="hidden" name="sortfield" value="cf.date_creation">
				</form>';
				print '
 
				<script type="text/javascript">
				this.document.getElementById("input_form").submit();

				</script>';
				
				//print_r($toselect);

			}
			/*if (in_array('supplierorderlist', explode(':', $parameters['context']))) 
			{
				if ($sortfield && $sortorder && $search_status){
					//echo $search_status;
					header("Location: /custom/vptop/sporder_list.php?sortfield=$sortfield&sortorder=$sortorder&search_status=$search_status");
				}
				else{
					header("Location: /custom/vptop/sporder_list.php");
				}
			}
			if($action == 'classifyclose')
			{			
				
				//setEventMessage($langs->trans("done"));
				//header("Location: /custom/vptop/expedition_list.php");
			}*/
		}
		if ($contextpage=='orderlist1') {
			//if (in_array('createbills', explode(':', $parameters['massaction']))) 
			/*if ($massaction=="createbills") 
			{
				if ($sortfield && $sortorder && $search_status){
					//echo $search_status;
					header("Location: /custom/vptop/sporder_list.php?sortfield=$sortfield&sortorder=$sortorder&search_status=$search_status");
				}
				else{
					header("Location: /custom/vptop/sporder_list.php");
				}
			}*/
			if ($massaction=="createbills") 
			{
				/*$postdata = http_build_query(
					array(
						'massaction' => GETPOST('massaction', 'alpha'),
						//'toselect' => GETPOST('toselect', 'array'),
						'sortfield' => GETPOST('sortfield', 'alpha'),
						'sortorder' => GETPOST('sortorder', 'alpha'),
						'confirmmassaction' => GETPOST('confirmmassaction', 'alpha')
					)
				);

				//print_r($postdata);
				$opts = array('http' =>
					array(
						'method' => 'POST',
						'header' => 'Content-type: application/x-www-form-urlencoded',
						'content' => $postdata
					)
				);
				$context = stream_context_create($opts);
				$result = file_get_contents('/var/www/html/custom/vptop/sporder_list.php', false, $context);
				echo $result;
				exit;*/
				$_SESSION[massaction] = GETPOST('massaction', 'alpha');
				$_SESSION[toselect] = GETPOST('toselect', 'array');
				$_SESSION[sortfield] = GETPOST('sortfield', 'alpha');
				$_SESSION[sortorder] = GETPOST('sortorder', 'alpha');
				$_SESSION[confirmmassaction] = GETPOST('confirmmassaction', 'alpha');
				header("Location: /custom/vptop/cmorder_list.php");


			/*	
				
				print '	
				<form id="input_form" method="POST" action="/custom/vptop/sporder_list.php">
					<input type="hidden" name="massaction" value='.$massaction.'>';
					foreach ($toselect as $key => $value) {
						print '<input type="hidden" name="toselect[]" value='.$value.'>';
					}

				print '	
					
					<input type="hidden" name="confirmmassaction" value="cf.date_creation">
					<input type="hidden" name="sortfield" value="Confirm">
				</form>';
				print '
 
				<script type="text/javascript">
				//this.document.getElementById("input_form").submit();

				</script>';
				
				print_r($toselect);
			*/

			}

			/*if ($massaction=="confirm_createsupplierbills") 
			{
				print($mg_ref_supplier);
				print("test");
				exit;
			}*/
			if($action == 'classifyclose')
			{			
				
				//setEventMessage($langs->trans("done"));
				//header("Location: /custom/vptop/expedition_list.php");
			}
		}
		if ($contextpage=='orderlist_new') {
			/*$_POST[massaction]=$_SESSION[massaction];
			$massaction=$_SESSION[massaction];*/
			if ($_SESSION[massaction]){
				$massaction = $_SESSION[massaction];
			}
				print $massaction;

			if ($massaction=="createbills") 
			{

				unset($_SESSION[massaction]);
				$_POST[toselect]=$_SESSION[toselect];
				$_POST[sortfield]=$_SESSION[sortfield];
				$_POST[sortorder]=$_SESSION[sortorder];
				$_POST[confirmmassaction]=$_SESSION[confirmmassaction];
				$toselect=$_SESSION[toselect];
				$orders=$_SESSION[toselect];
				$sortfield=$_SESSION[sortfield];
				
				$confirmmassaction=$_SESSION[confirmmassaction];
				//$massaction = GETPOST('massaction', 'alpha');
			}
			if ($massaction=="confirm_createsupplierbills") 
			{
				print($mg_ref_supplier);
				print("test");
			//	exit;
			}
		
			
			print_r($toselect);
		}
		if ($contextpage=='shipmentlist') {
			
			if (in_array('shipmentlist', explode(':', $parameters['context']))) 
			{
				if ($sortfield && $sortorder && $search_status){
					//echo $search_status;
					header("Location: /custom/vptop/expedition_list.php?sortfield=$sortfield&sortorder=$sortorder&search_status=$search_status");
				}
				else{
					header("Location: /custom/vptop/expedition_list.php");
				}
			}
			if($action == 'classifyclose')
			{			
				
				//setEventMessage($langs->trans("done"));
				//header("Location: /custom/vptop/expedition_list.php");
			}
		}
		if ($contextpage=='receptionlist') {
			$arrayofmassactions = array(
				'presend'=>$langs->trans("SendByMail"),
			   );

		}	
		if ($object->element=='supplier_proposal') {
			//print $parameters['context'];
			
			if (in_array('supplier_proposalcard', explode(':', $parameters['context'])) && empty($contextpage)) 
			{
				if(!empty($object->id))
				{
					header("Location: /custom/vptop/sproposal.php?id=$object->id");
				} elseif ($action == 'create')
				{

					header("Location: /custom/vptop/sproposal.php?action=create");
				}
			}
		}
		if ($object->element=='reception')  {
			//header("Location: /");
			//header("Location: '.DOL_URL_ROOT.'/custom/vptop/reception_card.php?id='.$object->id");
			//`print $object->id;
			if (in_array('receptioncard', explode(':', $parameters['context']))) 
			{
				header("Location: /custom/vptop/reception_card.php?id=$object->id");
			}
		//	if ($contextpage == '')

		}

		if ($object->element=='propal') {
			// Add new proposal
			if ($action == 'create2')
			{
				$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
				llxHeader('', $langs->trans('Proposal'), $help_url);
				$form = new Form($db);
				$formother = new FormOther($db);
				$formfile = new FormFile($db);
				$formpropal = new FormPropal($db);
				$formmargin = new FormMargin($db);
				$companystatic = new Societe($db);
				if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

				

				$now = dol_now();
				$currency_code = $conf->currency;

				print load_fiche_titre($langs->trans("NewProp"), '', 'propal');

				$soc = new Societe($db);
				if ($socid > 0)
					$res = $soc->fetch($socid);

				// Load objectsrc
				if (!empty($origin) && !empty($originid))
				{
					// Parse element/subelement (ex: project_task)
					$element = $subelement = $origin;
					$regs = array();
					if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
						$element = $regs[1];
						$subelement = $regs[2];
					}

					if ($element == 'project') {
						$projectid = $originid;
					} else {
						// For compatibility
						if ($element == 'order' || $element == 'commande') {
							$element = $subelement = 'commande';
						}
						if ($element == 'propal') {
							$element = 'comm/propal';
							$subelement = 'propal';
						}
						if ($element == 'contract') {
							$element = $subelement = 'contrat';
						}
						if ($element == 'shipping') {
							$element = $subelement = 'expedition';
						}
						if ($element == 'supplier') {
							$element = $subelement = 'supplier_proposal';
						}

						dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

						if ($element == 'supplier_proposal') {
							$classname = 'SupplierProposal';
						}
						else{
							$classname = ucfirst($subelement);
						}
						$objectsrc = new $classname($db);
						$objectsrc->fetch($originid);
						if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
						{
							$objectsrc->fetch_lines();
						}
						$objectsrc->fetch_thirdparty();

						$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : 0);
						$ref_client = (!empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

						$soc = $objectsrc->thirdparty;

						$cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id) ? $objectsrc->cond_reglement_id : (!empty($soc->cond_reglement_id) ? $soc->cond_reglement_id : 0)); // TODO maybe add default value option
						$mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id) ? $objectsrc->mode_reglement_id : (!empty($soc->mode_reglement_id) ? $soc->mode_reglement_id : 0));
						$remise_percent 	= (!empty($objectsrc->remise_percent) ? $objectsrc->remise_percent : (!empty($soc->remise_percent) ? $soc->remise_percent : 0));
						$remise_absolue 	= (!empty($objectsrc->remise_absolue) ? $objectsrc->remise_absolue : (!empty($soc->remise_absolue) ? $soc->remise_absolue : 0));
						$dateinvoice = (empty($dateinvoice) ? (empty($conf->global->MAIN_AUTOFILL_DATE) ?-1 : '') : $dateinvoice);

						// Replicate extrafields
						$objectsrc->fetch_optionals();
						$object->array_options = $objectsrc->array_options;

						if (!empty($conf->multicurrency->enabled))
						{
							if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
							if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
						}
					}
				} else {
					if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;
				}

				$object = new Propal($db);

				print '<form name="addprop" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="add">';
				if ($origin != 'project' && $originid) {
					print '<input type="hidden" name="origin" value="'.$origin.'">';
					print '<input type="hidden" name="originid" value="'.$originid.'">';
				} elseif ($origin == 'project' && !empty($projectid)) {
					print '<input type="hidden" name="projectid" value="'.$projectid.'">';
				}

				print dol_get_fiche_head();

				print '<table class="border centpercent">';

				// Reference
				print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';

				// Ref customer
				print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
				print '<input type="text" name="ref_client" value="'.GETPOST('ref_client').'"></td>';
				print '</tr>';

				// Third party
				print '<tr>';
				print '<td class="fieldrequired">'.$langs->trans('Customer').'</td>';
				if ($socid > 0) {
					print '<td>';
					print $soc->getNomUrl(1);
					print '<input type="hidden" name="socid" value="'.$soc->id.'">';
					print '</td>';
				} else {
					print '<td>';
					print img_picto('', 'company').$form->select_company('', 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300 maxwidth500');
					// reload page to retrieve customer informations
					if (empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE_DISABLED))
					{
						print '<script type="text/javascript">
						$(document).ready(function() {
							$("#socid").change(function() {
								console.log("We have changed the company - Reload page");
								var socid = $(this).val();
								// reload page
								window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&ref_client="+$("input[name=ref_client]").val();
							});
						});
						</script>';
					}
					print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span></a>';
					print '</td>';
				}
				print '</tr>'."\n";

				if ($socid > 0)
				{
					// Contacts (ask contact only if thirdparty already defined).
					print "<tr><td>".$langs->trans("DefaultContact").'</td><td>';
					$form->select_contacts($soc->id, $contactid, 'contactid', 1, $srccontactslist);
					print '</td></tr>';

					// Third party discounts info line
					print '<tr><td>'.$langs->trans('Discounts').'</td><td>';

					$absolute_discount = $soc->getAvailableDiscounts();

					$thirdparty = $soc;
					$discount_type = 0;
					$backtopage = urlencode($_SERVER["PHP_SELF"].'?socid='.$thirdparty->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid'));
					include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
					print '</td></tr>';
				}

				// Date
				print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
				print $form->selectDate('', '', '', '', '', "addprop", 1, 1);
				print '</td></tr>';

				// Validaty duration
				print '<tr><td class="fieldrequired">'.$langs->trans("ValidityDuration").'</td><td><input name="duree_validite" class="width50" value="'.(GETPOSTISSET('duree_validite') ? GETPOST('duree_validite', 'alphanohtml') : $conf->global->PROPALE_VALIDITY_DURATION).'"> '.$langs->trans("days").'</td></tr>';

				// Terms of payment
				print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
				$form->select_conditions_paiements((GETPOSTISSET('cond_reglement_id') ? GETPOST('cond_reglement_id', 'int') : $soc->cond_reglement_id), 'cond_reglement_id', -1, 1);
				print '</td></tr>';

				// Mode of payment
				print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
				$form->select_types_paiements((GETPOSTISSET('mode_reglement_id') ? GETPOST('mode_reglement_id', 'int') : $soc->mode_reglement_id), 'mode_reglement_id');
				print '</td></tr>';

				// Bank Account
				if (!empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL) && !empty($conf->banque->enabled)) {
					print '<tr><td>'.$langs->trans('BankAccount').'</td><td>';
					$form->select_comptes($soc->fk_account, 'fk_account', 0, '', 1);
					print '</td></tr>';
				}

				// What trigger creation
				print '<tr><td>'.$langs->trans('Source').'</td><td>';
				$form->selectInputReason('', 'demand_reason_id', "SRC_PROP", 1);
				print '</td></tr>';

				// Delivery delay
				print '<tr class="fielddeliverydelay"><td>'.$langs->trans('AvailabilityPeriod');
				if (!empty($conf->commande->enabled))
					print ' ('.$langs->trans('AfterOrder').')';
				print '</td><td>';
				$form->selectAvailabilityDelay('', 'availability_id', '', 1);
				print '</td></tr>';

				// Shipping Method
				if (!empty($conf->expedition->enabled)) {
					if (!empty($conf->global->SOCIETE_ASK_FOR_SHIPPING_METHOD) && !empty($soc->shipping_method_id)) {
						$shipping_method_id = $soc->shipping_method_id;
					}
					print '<tr><td>'.$langs->trans('SendingMethod').'</td><td>';
					print $form->selectShippingMethod($shipping_method_id, 'shipping_method_id', '', 1);
					print '</td></tr>';
				}

				// Delivery date (or manufacturing)
				print '<tr><td>'.$langs->trans("DeliveryDate").'</td>';
				print '<td>';
				if ($conf->global->DATE_LIVRAISON_WEEK_DELAY != "") {
					$tmpdte = time() + ((7 * $conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
					$syear = date("Y", $tmpdte);
					$smonth = date("m", $tmpdte);
					$sday = date("d", $tmpdte);
					print $form->selectDate($syear."-".$smonth."-".$sday, 'date_livraison', '', '', '', "addprop");
				} else {
					print $form->selectDate(-1, 'date_livraison', '', '', '', "addprop", 1, 1);
				}
				print '</td></tr>';

				// Project
				if (!empty($conf->projet->enabled))
				{
					$langs->load("projects");
					print '<tr>';
					print '<td>'.$langs->trans("Project").'</td><td>';
					print img_picto('', 'project').$formproject->select_projects(($soc->id > 0 ? $soc->id : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
					print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
					print '</td>';
					print '</tr>';
				}

				// Incoterms
				if (!empty($conf->incoterm->enabled))
				{
					print '<tr>';
					print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $soc->label_incoterms, 1).'</label></td>';
					print '<td class="maxwidthonsmartphone">';
					print $form->select_incoterms((!empty($soc->fk_incoterms) ? $soc->fk_incoterms : ''), (!empty($soc->location_incoterms) ? $soc->location_incoterms : ''));
					print '</td></tr>';
				}

				// Template to use by default
				print '<tr>';
				print '<td>'.$langs->trans("DefaultModel").'</td>';
				print '<td>';
				$liste = ModelePDFPropales::liste_modeles($db);
				$preselected = ($conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT ? $conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT : $conf->global->PROPALE_ADDON_PDF);
				print $form->selectarray('model', $liste, $preselected, 0, 0, 0, '', 0, 0, 0, '', '', 1);
				print "</td></tr>";

				// Multicurrency
				if (!empty($conf->multicurrency->enabled))
				{
					print '<tr>';
					print '<td>'.$form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0).'</td>';
					print '<td class="maxwidthonsmartphone">';
					print $form->selectMultiCurrency($currency_code, 'multicurrency_code', 0);
					print '</td></tr>';
				}

				// Public note
				print '<tr>';
				print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
				print '<td valign="top">';
				$note_public = $object->getDefaultCreateValueFor('note_public', (is_object($objectsrc) ? $objectsrc->note_public : null));
				$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
				print $doleditor->Create(1);

				// Private note
				if (empty($user->socid))
				{
					print '<tr>';
					print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
					print '<td valign="top">';
					$note_private = $object->getDefaultCreateValueFor('note_private', ((!empty($origin) && !empty($originid) && is_object($objectsrc)) ? $objectsrc->note_private : null));
					$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
					print $doleditor->Create(1);
					// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
					print '</td></tr>';
				}

				// Other attributes
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

				// Lines from source
				if (!empty($origin) && !empty($originid) && is_object($objectsrc))
				{
					// TODO for compatibility
					if ($origin == 'contrat') {
						// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
						$objectsrc->remise_absolue = $remise_absolue;
						$objectsrc->remise_percent = $remise_percent;
						$objectsrc->update_price(1, - 1, 1);
					}

					print "\n<!-- ".$classname." info -->";
					print "\n";
					print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
					print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
					print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
					print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
					print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

					$newclassname = $classname;
					if ($newclassname == 'Propal')
						$newclassname = 'CommercialProposal';
					elseif ($newclassname == 'Commande')
						$newclassname = 'Order';
					elseif ($newclassname == 'Expedition')
						$newclassname = 'Sending';
					elseif ($newclassname == 'Fichinter')
						$newclassname = 'Intervention';

					print '<tr><td>'.$langs->trans($newclassname).'</td><td>'.$objectsrc->getNomUrl(1).'</td></tr>';
					print '<tr><td>'.$langs->trans('AmountHT').'</td><td>'.price($objectsrc->total_ht, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';
					print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($objectsrc->total_tva, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
					if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1
					{
						print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax1, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
					}

					if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2
					{
						print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td><td>'.price($objectsrc->total_localtax2, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";
					}
					print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($objectsrc->total_ttc, 0, $langs, 1, -1, -1, $conf->currency)."</td></tr>";

					if (!empty($conf->multicurrency->enabled))
					{
						print '<tr><td>'.$langs->trans('MulticurrencyAmountHT').'</td><td>'.price($objectsrc->multicurrency_total_ht).'</td></tr>';
						print '<tr><td>'.$langs->trans('MulticurrencyAmountVAT').'</td><td>'.price($objectsrc->multicurrency_total_tva)."</td></tr>";
						print '<tr><td>'.$langs->trans('MulticurrencyAmountTTC').'</td><td>'.price($objectsrc->multicurrency_total_ttc)."</td></tr>";
					}
				}

				print "</table>\n";


				/*
				* Combobox for copy function
				*/

				if (empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE)) print '<input type="hidden" name="createmode" value="empty">';

				if (!empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
				{
					print '<br><table>';

					// For backward compatibility
					print '<tr>';
					print '<td><input type="radio" name="createmode" value="copy"></td>';
					print '<td>'.$langs->trans("CopyPropalFrom").' </td>';
					print '<td>';
					$liste_propal = array();
					$liste_propal [0] = '';

					$sql = "SELECT p.rowid as id, p.ref, s.nom";
					$sql .= " FROM ".MAIN_DB_PREFIX."propal p";
					$sql .= ", ".MAIN_DB_PREFIX."societe s";
					$sql .= " WHERE s.rowid = p.fk_soc";
					$sql .= " AND p.entity IN (".getEntity('propal').")";
					$sql .= " AND p.fk_statut <> 0";
					$sql .= " ORDER BY Id";

					$resql = $db->query($sql);
					if ($resql) {
						$num = $db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$row = $db->fetch_row($resql);
							$propalRefAndSocName = $row [1]." - ".$row [2];
							$liste_propal [$row [0]] = $propalRefAndSocName;
							$i++;
						}
						print $form->selectarray("copie_propal", $liste_propal, 0);
					} else {
						dol_print_error($db);
					}
					print '</td></tr>';

					print '<tr><td class="tdtop"><input type="radio" name="createmode" value="empty" checked></td>';
					print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyPropal").'</td></tr>';
					print '</table>';
				}

				print dol_get_fiche_end();

				$langs->load("bills");
				print '<div class="center">';
				print '<input type="submit" class="button" value="'.$langs->trans("CreateDraft").'">';
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
				print '</div>';

				print "</form>";


				// Show origin lines
				if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
					print '<br>';

					$title = $langs->trans('ProductsAndServices');
					print load_fiche_titre($title);

					print '<table class="noborder centpercent">';

					$objectsrc->printOriginLinesList();

					print '</table>';
				}
			}

			if ($action == 'new_customer_proposal') {
				require __DIR__.'/Utill.php';
				//$result = Utill::test( $socid);
				//echo $result;
				$object->id=GETPOST('originid');
				if (!GETPOST('socid', 3))
				{
					setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
				} else {
					if ($object->id > 0) {

						if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY)) {
							//Get difference between old and new delivery date and change lines according to difference
							$date_delivery = dol_mktime(12, 0, 0,
								GETPOST('date_deliverymonth', 'int'),
								GETPOST('date_deliveryday', 'int'),
								GETPOST('date_deliveryyear', 'int')
							);
							$date_delivery_old = (empty($object->delivery_date) ? $object->date_livraison : $object->delivery_date);
							if (!empty($date_delivery_old) && !empty($date_delivery))
							{
								//Attempt to get the date without possible hour rounding errors
								$old_date_delivery = dol_mktime(12, 0, 0,
									dol_print_date($date_delivery_old, '%m'),
									dol_print_date($date_delivery_old, '%d'),
									dol_print_date($date_delivery_old, '%Y')
								);
								//Calculate the difference and apply if necessary
								$difference = $date_delivery - $old_date_delivery;
								if ($difference != 0)
								{
									$object->date_livraison = $date_delivery;
									$object->delivery_date = $date_delivery;
									foreach ($object->lines as $line)
									{
										if (isset($line->date_start)) $line->date_start = $line->date_start + $difference;
										if (isset($line->date_end)) $line->date_end = $line->date_end + $difference;
									}
								}
							}
						}
						$result = Utill::createFromClone2($user, $socid, (GETPOSTISSET('entity') ? GETPOST('entity', 'int') : null),$object->id);
						if ($result > 0) {
							header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
							exit();
						} else {
							if (count($object->errors) > 0) setEventMessages($object->error, $object->errors, 'errors');
							$action = '';
						}
					}
				}
				 

			}
			
			
			
		}
		/*if ($object->element=='invoice_supplier') {

			if ($action == "create" && GETPOST('origin') == "order_supplier" && GETPOST('originid')  && empty($cancel) && $id > 0){
				
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$srcobject =  new CommandeFournisseur($db);
				$srcobject->fetch(GETPOST('originid'));
				$srcobject->fetchObjectLinked();
				if (count($srcobject->linkedObjectsIds['supplier_proposal']) > 0)
				{
					foreach ($srcobject->linkedObjectsIds['supplier_proposal'] as $key => $value)
					{
						$object->add_object_linked('supplier_proposal', $value);
					}
				}
			}
		}*/
	}

	/**
	 * @param   array         $parameters     Hook metadatas (context, etc...)
	 * @param   SupplierProposal    $object        The object to process
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldPreListTitle(array $parameters, &$object, &$action, HookManager $hookmanager){
		global $langs, $form, $conf, $ref, $rowid, $user, $socid, $forceentity, $db, $contextpage;
	

		if ($contextpage=='receptionlist') {
			$arrayofmassactions = array(
				'presend'=>$langs->trans("SendByMail"),
			);
	//		$this->resprints = $form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail")));
			$massactionbutton = $form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail")));
			$this->resprints = $massactionbutton;

		}
	}
	
}
