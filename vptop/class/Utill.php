<?php

/**
 * Copyright © 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 *
 * This file is part of Importorderlines.
 *
 * Multismtp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Multismtp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Dolibarr license:
 *
 * You can find a copy of the code at http://github.com/dolibarr/dolibarr
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

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

class Utill extends Propal
{
    
    
    public function createFromClone2(User $user, $socid = 0, $forceentity = null,$id)
	{
		global $conf, $hookmanager, $origin, $originid;
        
        $this->id =$id;

		dol_include_once('/projet/class/project.class.php');

		$error = 0;
		$now = dol_now();

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch2($this->id);

		$objsoc = new Societe($this->db);

		// Change socid if needed
		if (!empty($socid) && $socid != $object->socid)
		{
			if ($objsoc->fetch($socid) > 0)
			{
			    $object->socid = $objsoc->id;
			    $object->cond_reglement_id	= (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
			    $object->mode_reglement_id	= (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
			    $object->fk_delivery_address = '';

				/*if (!empty($conf->projet->enabled))
                {
                    $project = new Project($db);
    				if ($this->fk_project > 0 && $project->fetch($this->fk_project)) {
    					if ($project->socid <= 0) $clonedObj->fk_project = $this->fk_project;
    					else $clonedObj->fk_project = '';
    				} else {
    					$clonedObj->fk_project = '';
    				}
                }*/
			    $object->fk_project = ''; // A cloned proposal is set by default to no project.
			}

			// reset ref_client
			$object->ref_client = '';

			// TODO Change product price if multi-prices
		}
		else
		{
		    $objsoc->fetch2($object->socid);
		}

		$object->id = 0;
		$object->ref = '';
		$object->entity = (! empty($forceentity) ? $forceentity : $object->entity);
		$object->statut = self::STATUS_DRAFT;

		// Clear fields
		$object->user_author = $user->id;
		$object->user_valid = '';
		$object->date = $now;
		$object->datep = $now; // deprecated
		$object->fin_validite = $object->date + ($object->duree_validite * 24 * 3600);
		if (empty($conf->global->MAIN_KEEP_REF_CUSTOMER_ON_CLONING)) $object->ref_client = '';
		if ($conf->global->MAIN_DONT_KEEP_NOTE_ON_CLONING == 1)
		{
			$object->note_private = '';
			$object->note_public = '';
		}

		// Object link
		$element = 'supplier_proposal';
		$subelement = 'supplier_proposal';

		
		$object->origin = $origin;
		$object->origin_id = $originid;

		$object->linked_objects [$object->origin] = $object->origin_id;
		if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects'])) {
			$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);


		if ($result < 0)
		{
		    $this->error = $object->error;
		    $this->errors = array_merge($this->errors, $object->errors);
		    $error++;
		}

		
		/*if (!$error)
		{
			// copy internal contacts
		    if ($object->copy_linked_contact($this, 'internal') < 0)
		    {
				$error++;
		    }
		}

		if (!$error)
		{
			// copy external contacts if same company
			if ($this->socid == $object->socid)
			{
			    if ($object->copy_linked_contact($this, 'external') < 0)
					$error++;
			}
		}*/

		if (!$error)
		{
			// Hook of thirdparty module
			if (is_object($hookmanager))
			{
				$parameters = array('objFrom'=>$this, 'clonedObj'=>$object);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error)
		{
			$this->db->commit();
			return $object->id;
			
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}
    
    function fetch2($rowid,$ref='')
	{
			$sql = "SELECT p.rowid, p.entity, p.ref, p.remise, p.remise_percent, p.remise_absolue, p.fk_soc";
			$sql.= ", p.total, p.tva, p.localtax1, p.localtax2, p.total_ht";
			$sql.= ", p.datec";
			$sql.= ", p.date_valid as datev";
			$sql.= ", p.date_livraison as date_livraison";
			$sql.= ", p.model_pdf, p.extraparams";
			$sql.= ", p.note_private, p.note_public";
			$sql.= ", p.fk_projet, p.fk_statut";
			$sql.= ", p.fk_user_author, p.fk_user_valid, p.fk_user_cloture";
			$sql.= ", p.fk_cond_reglement";
			$sql.= ", p.fk_mode_reglement";
			$sql.= ', p.fk_account';
			$sql.= ", p.fk_shipping_method";
			$sql.= ", p.fk_multicurrency, p.multicurrency_code, p.multicurrency_tx, p.multicurrency_total_ht, p.multicurrency_total_tva, p.multicurrency_total_ttc";
			$sql.= ", c.label as statut_label";
			$sql.= ", cr.code as cond_reglement_code, cr.libelle as cond_reglement, cr.libelle_facture as cond_reglement_libelle_doc";
			$sql.= ", cp.code as mode_reglement_code, cp.libelle as mode_reglement";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_propalst as c, ".MAIN_DB_PREFIX."supplier_proposal as p";
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_mode_reglement = cp.id';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON p.fk_cond_reglement = cr.rowid';
			$sql.= " WHERE p.fk_statut = c.id";
			$sql.= " AND p.entity IN (".getEntity('supplier_proposal').")";
			if ($ref) $sql.= " AND p.ref='".$ref."'";
			else $sql.= " AND p.rowid=".$rowid;

			dol_syslog(get_class($this)."::fetch2", LOG_DEBUG);
        	$resql=$this->db->query($sql);
			$proposal_supplier = 1;
			$table_element = "supplier_proposal";
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id                   = $obj->rowid;
				$this->entity               = $obj->entity;

				$this->ref                  = $obj->ref;
				$this->ref_client           = $obj->ref_client;
				$this->remise               = $obj->remise;
				$this->remise_percent       = $obj->remise_percent;
				$this->remise_absolue       = $obj->remise_absolue;
				$this->total                = $obj->total; // TODO deprecated
				$this->total_ht             = $obj->total_ht;
				$this->total_tva            = $obj->tva;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;
				$this->total_ttc            = $obj->total;
				$this->socid                = $obj->fk_soc;
				$this->fk_project           = $obj->fk_projet;
				$this->modelpdf             = $obj->model_pdf;
				$this->last_main_doc		= $obj->last_main_doc;
				$this->note                 = $obj->note_private; // TODO deprecated
				$this->note_private         = $obj->note_private;
				$this->note_public          = $obj->note_public;
				$this->statut               = (int) $obj->fk_statut;
				$this->statut_libelle       = $obj->statut_label;

				$this->datec                = $this->db->jdate($obj->datec); // TODO deprecated
				$this->datev                = $this->db->jdate($obj->datev); // TODO deprecated
				$this->date_creation		= $this->db->jdate($obj->datec); //Creation date
				$this->date_validation		= $this->db->jdate($obj->datev); //Validation date
				$this->date_modification	= $this->db->jdate($obj->date_modification); // tms
				$this->date                 = $this->db->jdate($obj->dp);	// Proposal date
				$this->datep                = $this->db->jdate($obj->dp);    // deprecated
				$this->fin_validite         = $this->db->jdate($obj->dfv);
				$this->date_livraison       = $this->db->jdate($obj->date_livraison);
				$this->shipping_method_id   = ($obj->fk_shipping_method>0)?$obj->fk_shipping_method:null;
				$this->availability_id      = $obj->fk_availability;
				$this->availability_code    = $obj->availability_code;
				$this->availability         = $obj->availability;
				$this->demand_reason_id     = $obj->fk_input_reason;
				$this->demand_reason_code   = $obj->demand_reason_code;
				$this->demand_reason        = $obj->demand_reason;
				$this->fk_address  			= $obj->fk_delivery_address;

				$this->mode_reglement_id    = $obj->fk_mode_reglement;
				$this->mode_reglement_code  = $obj->mode_reglement_code;
				$this->mode_reglement       = $obj->mode_reglement;
				$this->fk_account           = ($obj->fk_account>0)?$obj->fk_account:null;
				$this->cond_reglement_id    = $obj->fk_cond_reglement;
				$this->cond_reglement_code  = $obj->cond_reglement_code;
				$this->cond_reglement       = $obj->cond_reglement;
				$this->cond_reglement_doc   = $obj->cond_reglement_libelle_doc;

				$this->extraparams			= (array) json_decode($obj->extraparams, true);

				$this->user_author_id = $obj->fk_user_author;
				$this->user_valid_id  = $obj->fk_user_valid;
				$this->user_close_id  = $obj->fk_user_cloture;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code 		= $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				// copy supplier proposal
				if ($proposal_supplier)
				{
					$this->multicurrency_code 		= "IDR";
					$this->multicurrency_tx 		= "1.00000000";
					$this->modelpdf             = "";
				}

				if ($obj->fk_statut == self::STATUS_DRAFT)
				{
					$this->brouillon = 1;
				}

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($resql);

				$this->lines = array();

				/*
                 * Lines
                 */
				$result=$this->fetch_lines2('',$this->socid);
				if ($result < 0)
				{
					return -3;
				}

				return 1;
			}

			$this->error="Record Not Found";
			return 0;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

    /**
	 * Load array lines
	 *
	 * @param		int		$only_product	Return only physical products
	 * @return		int						<0 if KO, >0 if OK
	 */
	function fetch_lines2($only_product=0)
	{
		$this->lines=array();

		


			$sql = "SELECT d.rowid, d.fk_supplier_proposal, d.fk_parent_line, d.label as custom_label, d.description, d.price, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.qty, d.fk_remise_except, d.remise_percent, d.subprice, d.fk_product,";
			$sql.= " d.info_bits, d.total_ht, d.total_tva, d.total_localtax1, d.total_localtax2, d.total_ttc, d.fk_product_fournisseur_price as fk_fournprice, d.buy_price_ht as pa_ht, d.special_code, d.rang, d.product_type,";
            $sql.= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label,';
            $sql.= ' d.ref_fourn as ref_produit_fourn,';
			$sql.= ' d.fk_multicurrency, d.multicurrency_code, d.multicurrency_subprice, d.multicurrency_total_ht, d.multicurrency_total_tva, d.multicurrency_total_ttc, d.fk_unit';
            $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposaldet as d";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON d.fk_product = p.rowid";
            $sql.= " WHERE d.fk_supplier_proposal = ".$this->id;
            $sql.= " ORDER by d.rang";

            dol_syslog(get_class($this)."::fetch_lines2", LOG_DEBUG);
            $result = $this->db->query($sql);
			$proposal_supplier = 1;
			$table_element = "supplier_proposaldet";



		if ($result)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

			$num = $this->db->num_rows($result);

			$i = 0;
			while ($i < $num)
			{
				$objp                   = $this->db->fetch_object($result);
				$line                   = new PropaleLigne2($this->db);

				$line->rowid			= $objp->rowid; //Deprecated
				$line->id				= $objp->rowid;
				$line->fk_propal		= $objp->fk_propal;
				$line->fk_parent_line	= $objp->fk_parent_line;
				$line->product_type     = $objp->product_type;
				$line->label            = $objp->custom_label;
				$line->desc             = $objp->description;  // Description ligne
				$line->qty              = $objp->qty;
				$line->vat_src_code     = $objp->vat_src_code;
				$line->tva_tx           = $objp->tva_tx;
				$line->localtax1_tx		= $objp->localtax1_tx;
				$line->localtax2_tx		= $objp->localtax2_tx;
				$line->localtax1_type	= $objp->localtax1_type;
				$line->localtax2_type	= $objp->localtax2_type;
				$line->subprice         = $objp->subprice;
				$line->fk_remise_except = $objp->fk_remise_except;
				$line->price            = $objp->price;		// TODO deprecated
				$line->remise_percent   = $objp->remise_percent;
				$line->info_bits        = $objp->info_bits;
				$line->total_ht         = $objp->total_ht;
				$line->total_tva        = $objp->total_tva;
				$line->total_localtax1	= $objp->total_localtax1;
				$line->total_localtax2	= $objp->total_localtax2;
				$line->total_ttc        = $objp->total_ttc;
				$line->fk_fournprice 	= $objp->fk_fournprice;
				$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
				$line->pa_ht 			= $marginInfos[0];
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];
				$line->special_code     = $objp->special_code;
				$line->rang             = $objp->rang;

				$line->fk_product       = $objp->fk_product;

				$line->ref				= $objp->product_ref;		// TODO deprecated
				$line->product_ref		= $objp->product_ref;
				$line->libelle			= $objp->product_label;		// TODO deprecated
				$line->product_label	= $objp->product_label;
				$line->product_desc     = $objp->product_desc; 		// Description produit
				$line->fk_product_type  = $objp->fk_product_type;
				$line->fk_unit          = $objp->fk_unit;
				$line->weight = $objp->weight;
				$line->weight_units = $objp->weight_units;
				$line->volume = $objp->volume;
				$line->volume_units = $objp->volume_units;

				$line->date_start  		= $this->db->jdate($objp->date_start);
				$line->date_end  		= $this->db->jdate($objp->date_end);

				// Multicurrency
				$line->fk_multicurrency 		= $objp->fk_multicurrency;
				$line->multicurrency_code 		= $objp->multicurrency_code;
				$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
				$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
				$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
				$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

				//copy supplier proposal
				if ($proposal_supplier)
				{
					$line->remise_percent   = 10;
					$line->pa_ht 			= $objp->total_ht/$objp->qty;
					$line->subprice 		= $objp->total_ht/$objp->qty*1.5;
					//$line->fk_propal		= "";
					

					$table_element = "supplier_proposaldet";

					//$line->fetch_optionals();
					$line->fetch2_optionals('','',$table_element);
                    //$line->array_options ['options_supplier'] ="1";
                    $line->array_options['options_supplier']=$this->socid;
                    $line->array_options['options_sp']=$this->id;
                    $line->array_options['options_spp']=number_format ($objp->multicurrency_subprice, 2);
                    $line->array_options['options_spd']=$objp->remise_percent;
                    //$line->array_options['options_unit']=$objp->fetch_optionals['options_unit'];
                    //$line->fetch_optionals();
					//print $line->array_options['options_unit']; 
					//print_r($line->array_options);
				}
				else{


					$line->fetch_optionals();
				}
				$this->lines[$i]        = $line;
				//dol_syslog("1 ".$line->fk_product);
				//print "xx $i ".$this->lines[$i]->fk_product;
				$i++;
			}

			$this->db->free($result);

			return $num;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -3;
		}
	}
   
}
/**
 *	Class to manage commercial proposal lines
 */
class PropaleLigne2 extends PropaleLigne
{ 
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to get extra fields of an object into $this->array_options
	 *  This method is in most cases called by method fetch of objects but you can call it separately.
	 *
	 *  @param	int		$rowid			Id of line. Use the id of object if not defined. Deprecated. Function must be called without parameters.
	 *  @param  array	$optionsArray   Array resulting of call of extrafields->fetch_name_optionals_label(). Deprecated. Function must be called without parameters.
	 *  @return	int						<0 if error, 0 if no values of extrafield to find nor found, 1 if an attribute is found and value loaded
	 *  @see fetchValuesForExtraLanguages()
	 */
	public function fetch2_optionals($rowid = null, $optionsArray = null, $table_element = Null)
	{
		
		// phpcs:enable
		global $conf, $extrafields;

		if (empty($rowid)) $rowid = $this->id;
		if (empty($rowid) && isset($this->rowid)) $rowid = $this->rowid; // deprecated

		if (!$table_element)
		$table_element = $this->table_element;

		// To avoid SQL errors. Probably not the better solution though
		if (!$this->table_element) {
			return 0;
		}

		$this->array_options = array();


		if (!is_array($optionsArray))
		{
			// If $extrafields is not a known object, we initialize it. Best practice is to have $extrafields defined into card.php or list.php page.
			if (!isset($extrafields) || !is_object($extrafields))
			{
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields = new ExtraFields($this->db);
			}

			// Load array of extrafields for elementype = $this->table_element
			if (empty($extrafields->attributes[$table_element]['loaded']))
			{
				$extrafields->fetch_name_optionals_label($table_element);
			}
			$optionsArray = (!empty($extrafields->attributes[$table_element]['label']) ? $extrafields->attributes[$table_element]['label'] : null);
			
		} else {
			global $extrafields;
			dol_syslog("Warning: fetch_optionals was called with param optionsArray defined when you should pass null now", LOG_WARNING);
		}

		

		if ($table_element == 'categorie') $table_element = 'categories'; // For compatibility

		// Request to get complementary values
		if (is_array($optionsArray) && count($optionsArray) > 0)
		{
			
			$sql = "SELECT rowid";
			foreach ($optionsArray as $name => $label)
			{
				if (empty($extrafields->attributes[$this->table_element]['type'][$name]) || $extrafields->attributes[$this->table_element]['type'][$name] != 'separate')
				{
					$sql .= ", ".$name;
				}
			}
			$sql .= " FROM ".MAIN_DB_PREFIX.$table_element."_extrafields";
			$sql .= " WHERE fk_object = ".((int) $rowid);

			//dol_syslog(get_class($this)."::fetch_optionals get extrafields data for ".$this->table_element, LOG_DEBUG);		// Too verbose
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$numrows = $this->db->num_rows($resql);
				if ($numrows)
				{
					$tab = $this->db->fetch_array($resql);

					foreach ($tab as $key => $value)
					{
						// Test fetch_array ! is_int($key) because fetch_array result is a mix table with Key as alpha and Key as int (depend db engine)
						if ($key != 'rowid' && $key != 'tms' && $key != 'fk_member' && !is_int($key))
						{
							// we can add this attribute to object
							if (!empty($extrafields) && in_array($extrafields->attributes[$this->table_element]['type'][$key], array('date', 'datetime')))
							{
								//var_dump($extrafields->attributes[$this->table_element]['type'][$key]);
								$this->array_options["options_".$key] = $this->db->jdate($value);
							} else {
								$this->array_options["options_".$key] = $value;
							}

							//var_dump('key '.$key.' '.$value.' type='.$extrafields->attributes[$this->table_element]['type'][$key].' '.$this->array_options["options_".$key]);
						}
					}

					// If field is a computed field, value must become result of compute
					foreach ($tab as $key => $value) {
						if (!empty($extrafields) && !empty($extrafields->attributes[$this->table_element]['computed'][$key]))
						{
							//var_dump($conf->disable_compute);
							if (empty($conf->disable_compute)) {
								$this->array_options["options_".$key] = dol_eval($extrafields->attributes[$this->table_element]['computed'][$key], 1, 0);
							}
						}
					}
				}

				$this->db->free($resql);

				if ($numrows) return $numrows;
				else return 0;
			} else {
				dol_print_error($this->db);
				return -1;
			}
		}
		return 0;
	}
}
class Commande2 extends Commande
{
	public function printOriginLinesList($restrictlist = '', $selectedLines = array())
	{
		global $langs, $hookmanager, $conf, $form;

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td>'.$langs->trans('Vender').'</td>';
		print '<td class="right">'.$langs->trans('VATRate').'</td>';
		print '<td class="right">'.$langs->trans('PriceUHT').'</td>';
		if (!empty($conf->multicurrency->enabled)) print '<td class="right">'.$langs->trans('PriceUHTCurrency').'</td>';
		print '<td class="right">'.$langs->trans('Qty').'</td>';
		if (!empty($conf->global->PRODUCT_USE_UNITS))
		{
			print '<td class="left">'.$langs->trans('Unit').'</td>';
		}
		print '<td class="right">'.$langs->trans('ReductionShort').'</td>';
		print '<td class="center">'.$form->showCheckAddButtons('checkforselect', 1).'</td>';
		print '</tr>';
		$i = 0;

		if (!empty($this->lines))
		{
			foreach ($this->lines as $line)
			{
				if (is_object($hookmanager) && (($line->product_type == 9 && !empty($line->special_code)) || !empty($line->fk_parent_line)))
				{
					if (empty($line->fk_parent_line))
					{
						$parameters = array('line'=>$line, 'i'=>$i);
						$action = '';
						$hookmanager->executeHooks('printOriginObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
					}
				} else {
					$this->printOriginLine($line, '', $restrictlist, '/core/tpl', $selectedLines);
				}

				$i++;
			}
		}
	}

	/**
	 * 	Return HTML with a line of table array of source object lines
	 *  TODO Move this and previous function into output html class file (htmlline.class.php).
	 *  If lines are into a template, title must also be into a template
	 *  But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 * 	@param	CommonObjectLine	$line				Line
	 * 	@param	string				$var				Var
	 *	@param	string				$restrictlist		''=All lines, 'services'=Restrict to services only (strike line if not)
	 *  @param	string				$defaulttpldir		Directory where to find the template
	 *  @param  array       		$selectedLines      Array of lines id for selected lines
	 * 	@return	void
	 */
	public function printOriginLine($line, $var, $restrictlist = '', $defaulttpldir = '/core/tpl', $selectedLines = array())
	{
		global $langs, $conf;

		//var_dump($line);
		if (!empty($line->date_start))
		{
			$date_start = $line->date_start;
		} else {
			$date_start = $line->date_debut_prevue;
			if ($line->date_debut_reel) $date_start = $line->date_debut_reel;
		}
		if (!empty($line->date_end))
		{
			$date_end = $line->date_end;
		} else {
			$date_end = $line->date_fin_prevue;
			if ($line->date_fin_reel) $date_end = $line->date_fin_reel;
		}

		$this->tpl['id'] = $line->id;

		$this->tpl['label'] = '';
		if (!empty($line->fk_parent_line)) $this->tpl['label'] .= img_picto('', 'rightarrow');

		if (($line->info_bits & 2) == 2)  // TODO Not sure this is used for source object
		{
			$discount = new DiscountAbsolute($this->db);
			$discount->fk_soc = $this->socid;
			$this->tpl['label'] .= $discount->getNomUrl(0, 'discount');
		} elseif (!empty($line->fk_product))
		{
			$productstatic = new Product($this->db);
			$productstatic->id = $line->fk_product;
			$productstatic->ref = $line->ref;
			$productstatic->type = $line->fk_product_type;
			if (empty($productstatic->ref)) {
				$line->fetch_product();
				$productstatic = $line->product;
			}

			$this->tpl['label'] .= $productstatic->getNomUrl(1);
			$this->tpl['label'] .= ' - '.(!empty($line->label) ? $line->label : $line->product_label);
			// Dates
			if ($line->product_type == 1 && ($date_start || $date_end))
			{
				$this->tpl['label'] .= get_date_range($date_start, $date_end);
			}
		} else {
			$this->tpl['label'] .= ($line->product_type == -1 ? '&nbsp;' : ($line->product_type == 1 ? img_object($langs->trans(''), 'service') : img_object($langs->trans(''), 'product')));
			if (!empty($line->desc)) {
				$this->tpl['label'] .= $line->desc;
			} else {
				$this->tpl['label'] .= ($line->label ? '&nbsp;'.$line->label : '');
			}

			// Dates
			if ($line->product_type == 1 && ($date_start || $date_end))
			{
				$this->tpl['label'] .= get_date_range($date_start, $date_end);
			}
		}

		if (!empty($line->desc))
		{
			if ($line->desc == '(CREDIT_NOTE)')  // TODO Not sure this is used for source object
			{
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
			} elseif ($line->desc == '(DEPOSIT)')  // TODO Not sure this is used for source object
			{
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
			} elseif ($line->desc == '(EXCESS RECEIVED)')
			{
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
			} elseif ($line->desc == '(EXCESS PAID)')
			{
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
			} else {
				$societe = new Societe($this->db);
				$societe->fetch($line->array_options['options_supplier']);
				$this->tpl['description'] = dol_trunc($societe->getNomUrl(1), 60);
			}
		} else {
			$this->tpl['description'] = '&nbsp;';
		}

		// VAT Rate
		$this->tpl['vat_rate'] = vatrate($line->tva_tx, true);
		$this->tpl['vat_rate'] .= (($line->info_bits & 1) == 1) ? '*' : '';
		if (!empty($line->vat_src_code) && !preg_match('/\(/', $this->tpl['vat_rate'])) $this->tpl['vat_rate'] .= ' ('.$line->vat_src_code.')';

		$this->tpl['price'] = price($line->subprice);
		$this->tpl['multicurrency_price'] = price($line->multicurrency_subprice);
		if (!empty($line->array_options['options_spp'])) {
			$this->tpl['multicurrency_price'] = $line->array_options['options_spp'];
		}

		$this->tpl['qty'] = (($line->info_bits & 2) != 2) ? $line->qty : '&nbsp;';
		if (!empty($conf->global->PRODUCT_USE_UNITS)) $this->tpl['unit'] = $langs->transnoentities($line->getLabelOfUnit('long'));
		
		$this->tpl['remise_percent'] = (($line->info_bits & 2) != 2) ? vatrate($line->array_options['options_spd'], true) : '&nbsp;';

		// Is the line strike or not
		$this->tpl['strike'] = 0;
		if ($restrictlist == 'services' && $line->product_type != Product::TYPE_SERVICE) $this->tpl['strike'] = 1;

		// Output template part (modules that overwrite templates must declare this into descriptor)
		// Use global variables + $dateSelector + $seller and $buyer
		$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
		foreach ($dirtpls as $module => $reldir)
		{
			if (!empty($module))
			{
				$tpl = dol_buildpath($reldir.'/originproductline.tpl.php');
			} else {
				$tpl = DOL_DOCUMENT_ROOT.$reldir.'/originproductline.tpl.php';
			}

			if (empty($conf->file->strict_mode)) {
				$res = @include $tpl;
			} else {
				$res = include $tpl; // for debug
			}
			if ($res) break;
		}
	}
}
class SupplierProposal2 extends SupplierProposal
{
	/**
	 *  Update a proposal line
	 *
	 *  @param      int			$rowid           	Id de la ligne
	 *  @param      double		$pu		     	  	Prix unitaire (HT ou TTC selon price_base_type)
	 *  @param      double		$qty            	Quantity
	 *  @param      double		$remise_percent  	Remise effectuee sur le produit
	 *  @param      double		$txtva	          	Taux de TVA
	 * 	@param	  	double		$txlocaltax1		Local tax 1 rate
	 *  @param	  	double		$txlocaltax2		Local tax 2 rate
	 *  @param      string		$desc            	Description
	 *	@param	  	double		$price_base_type	HT ou TTC
	 *	@param      int			$info_bits        	Miscellaneous informations
	 *	@param		int			$special_code		Special code (also used by externals modules!)
	 * 	@param		int			$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param		int			$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 *  @param		int			$fk_fournprice		Id of origin supplier price
	 *  @param		int			$pa_ht				Price (without tax) of product when it was bought
	 *  @param		string		$label				???
	 *  @param		int			$type				0/1=Product/service
	 *  @param		array		$array_options		extrafields array
	 * 	@param		string		$ref_supplier			Supplier price reference
	 *	@param		int			$fk_unit			Id of the unit to use.
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 *  @return     int     		        		0 if OK, <0 if KO
	 */
	public function updateline2($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $desc = '', $price_base_type = 'HT', $info_bits = 0, $special_code = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = 0, $pa_ht = 0, $label = '', $type = 0, $array_options = 0, $ref_supplier = '', $fk_unit = '', $pu_ht_devise = 0)
	{
		global $conf, $user, $langs, $mysoc;

		dol_syslog(get_class($this)."::updateLine $rowid, $pu_ht, $qty, $remise_percent, $txtva, $desc, $price_base_type, $info_bits");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Clean parameters
		$remise_percent = price2num($remise_percent);
		$qty = price2num($qty);
		$pu = price2num($pu);
		if (!preg_match('/\((.*)\)/', $txtva)) {
			$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
		}
		$txlocaltax1 = price2num($txlocaltax1);
		$txlocaltax2 = price2num($txlocaltax2);
		$pa_ht = price2num($pa_ht);
		if (empty($qty) && empty($special_code)) $special_code = 3; // Set option tag
		if (!empty($qty) && $special_code == 3) $special_code = 0; // Remove option tag

		if ($this->statut == 0)
		{
			$this->db->begin();

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $mysoc, $this->thirdparty);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg))
			{
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];
			if (!empty($conf->multicurrency->enabled) && $pu_ht_devise > 0) {
				$pu = $pu_ht;
			}

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new SupplierProposalLine($this->db);
			$line->fetch($rowid);
			$line->fetch_optionals();

			// Stock previous line records
			$staticline = clone $line;

			$line->oldline = $staticline;
			$this->line = $line;
			$this->line->context = $this->context;

			// Reorder if fk_parent_line change
			if (!empty($fk_parent_line) && !empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
			{
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			$this->line->id					= $rowid;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty				= $qty;
			$this->line->product_type = $type;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx		= $txlocaltax1;
			$this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type		= empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type		= empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->remise_percent		= $remise_percent;
			$this->line->subprice			= $pu;
			$this->line->info_bits			= $info_bits;
			$this->line->total_ht			= $total_ht;
			$this->line->total_tva			= $total_tva;
			$this->line->total_localtax1	= $total_localtax1;
			$this->line->total_localtax2	= $total_localtax2;
			$this->line->total_ttc			= $total_ttc;
			$this->line->special_code = $special_code;
			$this->line->fk_parent_line		= $fk_parent_line;
			$this->line->skip_update_total = $skip_update_total;
			$this->line->ref_fourn = $ref_supplier;
			$this->line->fk_unit = $fk_unit;

			// infos marge
			if (!empty($fk_product) && empty($fk_fournprice) && empty($pa_ht)) {
				// by external module, take lowest buying price
				include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
				$productFournisseur = new ProductFournisseur($this->db);
				$productFournisseur->find_min_price_product_fournisseur($fk_product);
				$this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
				$this->line->fk_fournprice = $fk_fournprice;
			}
			$this->line->pa_ht = $pa_ht;

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$this->line->array_options[$key] = $array_options[$key];
				}
			}

			// Multicurrency
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht		= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc	= $multicurrency_total_ttc;

			$result = $this->line->update();
			if ($result > 0)
			{
				// Reorder if child line
				if (!empty($fk_parent_line)) $this->line_order(true, 'DESC');

				$this->update_price(1);

				$this->fk_supplier_proposal = $this->id;

				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::updateline Erreur -2 SupplierProposal en mode incompatible pour cette action");
			return -2;
		}
	}
}