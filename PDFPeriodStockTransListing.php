<?php

include('includes/SQL_CommonFunctions.php');
include('includes/session.php');
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

$InputError=0;
if (isset($_POST['FromDate']) AND !Is_Date($_POST['FromDate'])){
	$Msg = _('The date must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError=1;
	unset($_POST['FromDate']);
}

if (!isset($_POST['FromDate'])){

	 $Title = _('Stock Transaction Listing');
	 $ViewTopic = 'Inventory';
	 $BookMark = '';
	 include('includes/header.php');

	echo '<div class="centre">
			<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' . $Title . '" alt="" />' . ' '. _('Stock Transaction Listing') . '</p>
		</div>';

	if ($InputError==1){
		prnMsg($Msg,'error');
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<fieldset>
			<legend>', _('Report Criteria'), '</legend>
		<field>
			<label for="FromDate">' . _('Enter the date from which the transactions are to be listed') . ':</label>
			<input required="required" autofocus="autofocus" name="FromDate" maxlength="10" size="11" type="date" value="' . Date('Y-m-d') . '" />
		</field>
		<field>
			<label for="ToDate">' . _('Enter the date to which the transactions are to be listed') . ':</label>
			<input required="required" name="ToDate" maxlength="10" size="11" type="date" value="' . Date('Y-m-d') . '" />
		</field>
		<field>
			<label for="TransType">' . _('Transaction type') . '</label>
			<select name="TransType">
				<option value="10">' . _('Sales Invoice') . '</option>
				<option value="11">' . _('Sales Credit Note') . '</option>
				<option value="16">' . _('Location Transfer') . '</option>
				<option value="17">' . _('Stock Adjustment') . '</option>
				<option value="25">' . _('Purchase Order Delivery') . '</option>
				<option value="26">' . _('Work Order Receipt') . '</option>
				<option value="28">' . _('Work Order Issue') . '</option>
			</select>
		</field>';

	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);

	echo '<field>
			<label for="StockLocation">' . _('For Stock Location') . ':</label>
			<select required="required" name="StockLocation">
				<option value="All">' . _('All') . '</option>';

	while ($MyRow=DB_fetch_array($ResultStkLocs)){
		if (isset($_POST['StockLocation']) AND $_POST['StockLocation']!='All'){
			if ($MyRow['loccode'] == $_POST['StockLocation']){
				echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			} else {
				echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			}
		} elseif ($MyRow['loccode']==$_SESSION['UserStockLocation']){
			echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			$_POST['StockLocation']=$MyRow['loccode'];
		} else {
			echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		}
	}
	echo '</select>
		</field>';

	echo '</fieldset>
			<div class="centre">
				<input type="submit" name="Go" value="' . _('Create PDF') . '" />
			</div>';
	echo '</form>';

	 include('includes/footer.php');
	 exit();
} else {

	include('includes/ConnectDB.php');
}


if ($_POST['StockLocation']=='All') {
	$SQL= "SELECT stockmoves.type,
				stockmoves.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.narrative,
				locations.locationname
			FROM stockmoves
			LEFT JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
			LEFT JOIN locations
			ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(trandate, '%Y-%m-%d')>='".FormatDateForSQL($_POST['FromDate'])."'
			AND date_format(trandate, '%Y-%m-%d')<='".FormatDateForSQL($_POST['ToDate'])."'";
} else {
	$SQL= "SELECT stockmoves.type,
				stockmoves.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.narrative,
				locations.locationname
			FROM stockmoves
			LEFT JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
			LEFT JOIN locations
			ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(trandate, '%Y-%m-%d')>='".FormatDateForSQL($_POST['FromDate'])."'
			AND date_format(trandate, '%Y-%m-%d')<='".FormatDateForSQL($_POST['ToDate'])."'
			AND stockmoves.loccode='" . $_POST['StockLocation'] . "'";
}
$Result=DB_query($SQL,'','',false,false);

if (DB_error_no()!=0){
	$Title = _('Transaction Listing');
	include('includes/header.php');
	prnMsg(_('An error occurred getting the transactions'),'error');
	include('includes/footer.php');
	exit();
} elseif (DB_num_rows($Result) == 0){
	$Title = _('Transaction Listing');
	include('includes/header.php');
	echo '<br />';
	prnMsg (_('There were no transactions found in the database between the dates') . ' ' . $_POST['FromDate'] . ' ' . _('and') . ' '. $_POST['ToDate']  . '<br />' ._('Please try again selecting a different date'), 'info');
	include('includes/footer.php');
  	exit();
}

include('includes/PDFStarter.php');

/*PDFStarter.php has all the variables for page size and width set up depending on the users default preferences for paper size */

$pdf->addInfo('Title',_('Stock Transaction Listing'));
$pdf->addInfo('Subject',_('Stock transaction listing from') . '  ' . $_POST['FromDate'] . ' ' . $_POST['ToDate']);
$LineHeight=12;
$PageNumber = 1;


switch ($_POST['TransType']) {
	case 10:
		$TransType=_('Customer Invoices');
		break;
	case 11:
		$TransType=_('Customer Credit Notes');
		break;
	case 16:
		$TransType=_('Location Transfers');
		break;
	case 17:
		$TransType=_('Stock Adjustments');
		break;
	case 25:
		$TransType=_('Purchase Order Deliveries');
		break;
	case 26:
		$TransType=_('Work Order Receipts');
		break;
	case 28:
		$TransType=_('Work Order Issues');
		break;
}

include('includes/PDFPeriodStockTransListingPageHeader.php');

while ($MyRow=DB_fetch_array($Result)){

	$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,160,$FontSize,$MyRow['description'], 'left');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+162,$YPos,80,$FontSize,$MyRow['transno'], 'left');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+242,$YPos,70,$FontSize,ConvertSQLDate($MyRow['trandate']), 'left');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+312,$YPos,70,$FontSize,locale_number_format($MyRow['qty'],$MyRow['decimalplaces']), 'right');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+382,$YPos,70,$FontSize,$MyRow['locationname'], 'right');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+452,$YPos,70,$FontSize,$MyRow['reference'], 'right');

	$YPos -= ($LineHeight);

	  if ($YPos - (2 *$LineHeight) < $Bottom_Margin){
		  /*Then set up a new page */
			  $PageNumber++;
		  include('includes/PDFPeriodStockTransListingPageHeader.php');
	  } /*end of new page header  */
} /* end of while there are customer receipts in the batch to print */


$YPos-=$LineHeight;

$ReportFileName = $_SESSION['DatabaseName'] . '_StockTransListing_' . date('Y-m-d').'.pdf';
$pdf->OutputD($ReportFileName);
$pdf->__destruct();
