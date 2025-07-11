<?php
/* Definition of the Offer class to hold all the information for a supplier offer
*/


Class Offer {

	var $LineItems; /*array of objects of class LineDetails using the product id as the pointer */
	var $TenderID;
	var $CurrCode;
	var $Location;
	var $SupplierID;
	var $SupplierName;
	var $EmailAddress;
	var $LinesOnOffer;
	var $Version;
	var $OfferMailText;

	function __construct($Supplier){
	/*Constructor function initialises a new purchase offer object */
		$this->LineItems = array();
		$this->total = 0;
		$this->LinesOnOffer = 0;
		$this->SupplierID = $Supplier;
		$SQL = "SELECT suppname,
					email,
					currcode
				FROM suppliers
				WHERE supplierid='" . $this->SupplierID . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$this->SupplierName = $MyRow['suppname'];
		$this->EmailAddress = $MyRow['email'];
		$this->CurrCode = $MyRow['currcode'];
	}
	function Offer($Supplier) {
		self::__construct($Supplier);
	}

	function add_to_offer($LineNo,
							$StockID,
							$Qty,
							$ItemDescr,
							$Price,
							$UOM,
							$DecimalPlaces,
							$ExpiryDate){

		if (isset($Qty) and $Qty != 0){

			$this->LineItems[$LineNo] = new LineDetails($LineNo,
														$StockID,
														$Qty,
														$ItemDescr,
														$Price,
														$UOM,
														$DecimalPlaces,
														$ExpiryDate);
			$this->LinesOnOffer++;
			return 1;
		}
		return 0;
	}

	function GetSupplierName() {
		return $this->SupplierName;
	}

	function GetSupplierEmail() {
		return $this->EmailAddress;
	}

	function Save($Update = '') {
		if ($Update == '') {
			foreach ($this->LineItems as $LineItems) {
				if ($LineItems->Deleted == False) {
					$SQL = "INSERT INTO offers (	supplierid,
												tenderid,
												stockid,
												quantity,
												uom,
												price,
												expirydate,
												currcode)
						VALUES ('" . $this->SupplierID . "',
								'" . $this->TenderID . "',
								'" . $LineItems->StockID . "',
								'" . $LineItems->Quantity . "',
								'" . $LineItems->Units . "',
								'" . $LineItems->Price . "',
								'" . FormatDateForSQL($LineItems->ExpiryDate) . "',
								'" . $this->CurrCode . "')";
					$ErrMsg = _('The suppliers offer could not be inserted into the database because');
					$DbgMsg = _('The SQL statement used to insert the suppliers offer record and failed was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
					if (DB_error_no() == 0) {
						prnMsg(_('The offer for') . ' ' . $LineItems->StockID . ' ' . _('has been inserted into the database'), 'success');
						$this->OfferMailText .= $LineItems->Quantity . ' ' . $LineItems->Units . ' ' . _('of') . ' ' .
							$LineItems->StockID . ' ' . _('at a price of') . ' ' . $this->CurrCode .
							number_format($LineItems->Price, 2) . "\n";
					} else {
						prnMsg(_('The offer for') . ' ' . $LineItems->StockID . ' ' . _('could not be inserted into the database'), 'error');
						include('includes/footer.php');
						exit();
					}
				}
			}
		} else {
			foreach ($this->LineItems as $LineItem) {
				if ($LineItem->Deleted == false){ //Update only the LineItems which is not flagged as deleted
					$SQL = "UPDATE offers SET
							quantity='" . $LineItem->Quantity . "',
							price='" . $LineItem->Price . "',
							expirydate='" . FormatDateForSQL($LineItem->ExpiryDate) . "'
						WHERE offerid='" . $LineItem->LineNo . "'";
					$ErrMsg = _('The suppliers offer could not be updated on the database because');
					$DbgMsg = _('The SQL statement used to update the suppliers offer record and failed was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
					if (DB_error_no() == 0) {
						prnMsg(_('The offer for') . ' ' . $LineItem->StockID . ' ' . _('has been updated in the database'), 'success');
						$this->OfferMailText .= $LineItem->Quantity . ' ' . $LineItem->Units . ' ' . _('of') . ' ' .
							$LineItem->StockID . ' ' . _('at a price of') . ' ' . $this->CurrCode . $LineItem->Price . "\n";
					} else {
						prnMsg(_('The offer for') . ' ' . $LineItem->StockID . ' ' . _('could not be updated in the database'), 'error');
						include('includes/footer.php');
						exit();
					}
				} else { // the LineItem is Deleted flag is true so delete it
					$SQL = "DELETE from offers WHERE offerid='" . $LineItem->LineNo . "'";
					$ErrMsg = _('The supplier offer could not be deleted on the database because');
					$DbgMsg = _('The SQL statement used to delete the suppliers offer record are failed was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
					if (DB_error_no() == 0) {
						prnMsg(_('The offer for') . ' ' . $LineItem->StockID . ' ' . _('has been deleted in the database'), 'info');
						$this->OfferMailText .= $LineItem->Quantity . ' ' . $LineItem->Units . ' ' . _('of') . ' ' .
							$LineItem->StockID . ' ' . _('at a price of') . ' ' . $this->CurrCode .
							$LineItem->Price . ' ' . _('has been deleted') . "\n";
					}
				}
			}
		}
	}

	function EmailOffer() {
		$Subject = (_('Offer received from') . ' ' . $this->GetSupplierName());
		$Message = (_('This email is automatically generated by webERP') . "\n" .
			_('You have received the following offer from') . ' ' . $this->GetSupplierName() . "\n\n" . $this->OfferMailText);

		$Result = SendEmailFromWebERP($this->GetSupplierEmail(),
									array($this->EmailAddress, $_SESSION['PurchasingManagerEmail']),
									$Subject,
									$Message,
									'',
									false);

		return $Result;
	}

	function update_offer_item($LineNo,
								$Qty,
								$Price,
								$ExpiryDate){

			$this->LineItems[$LineNo]->Quantity = $Qty;
			$this->LineItems[$LineNo]->Price = $Price;
			$this->LineItems[$LineNo]->ExpiryDate = $ExpiryDate;
	}

	function remove_from_offer(&$LineNo){
		$this->LineItems[$LineNo]->Deleted = True;
	}


	function Offer_Value() {
		$TotalValue = 0;
		foreach ($this->LineItems as $OrderedItems) {
			$TotalValue += ($OrderedItems->Price) * ($OrderedItems->Quantity);
		}
		return $TotalValue;
	}
} /* end of class defintion */

Class LineDetails {
/* PurchOrderDetails */
	var $LineNo;
	var $StockID;
	var $ItemDescription;
	var $Quantity;
	var $Price;
	var $Units;
	var $DecimalPlaces;
	var $Deleted;
	var $ExpiryDate;

	function __construct($LineNo,
							$StockItem,
							$Qty,
							$ItemDescr,
							$Price,
							$UOM,
							$DecimalPlaces,
							$ExpiryDate) {

	/* Constructor function to add a new LineDetail object with passed params */
		$this->LineNo = $LineNo;
		$this->StockID = $StockItem;
		$this->ItemDescription = $ItemDescr;
		$this->Quantity = $Qty;
		$this->Price = $Price;
		$this->Units = $UOM;
		$this->DecimalPlaces = $DecimalPlaces;
		$this->ExpiryDate = $ExpiryDate;
		$this->Deleted = False;
	}
	function LineDetails($LineNo,
							$StockItem,
							$Qty,
							$ItemDescr,
							$Price,
							$UOM,
							$DecimalPlaces,
							$ExpiryDate) {
		self::__construct($LineNo,
							$StockItem,
							$Qty,
							$ItemDescr,
							$Price,
							$UOM,
							$DecimalPlaces,
							$ExpiryDate);
	}
}
