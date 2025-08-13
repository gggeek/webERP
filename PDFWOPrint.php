<?php
// PDFWOPrint.php


include('includes/session.php');
include('includes/SQL_CommonFunctions.php');
if (isset($_GET['WO'])) {
	$SelectedWO = $_GET['WO'];
} elseif (isset($_POST['WO'])){
	$SelectedWO = $_POST['WO'];
} else {
	unset($SelectedWO);
}
if (isset($_GET['StockID'])) {
	$StockID = $_GET['StockID'];
} elseif (isset($_POST['StockID'])){
	$StockID = $_POST['StockID'];
} else {
	unset($StockID);
}

if (isset($_GET['PrintLabels'])) {
	$PrintLabels = $_GET['PrintLabels'];
} elseif (isset($_POST['PrintLabels'])){
	$PrintLabels = $_POST['PrintLabels'];
} else {
	unset($PrintLabels);
}

if (isset($_GET['LabelItem'])) {
	$LabelItem = $_GET['LabelItem'];
} elseif (isset($_POST['LabelItem'])){
	$LabelItem = $_POST['LabelItem'];
} else {
	unset($LabelItem);
}
if (isset($_GET['LabelDesc'])) {
	$LabelDesc = $_GET['LabelDesc'];
} elseif (isset($_POST['LabelDesc'])){
	$LabelDesc = $_POST['LabelDesc'];
} else {
	unset($LabelDesc);
}
if (isset($_GET['LabelLot'])) {
	$LabelLot = $_GET['LabelLot'];
} elseif (isset($_POST['LabelLot'])){
	$LabelLot = $_POST['LabelLot'];
} else {
	unset($LabelLot);
}
if (isset($_GET['NoOfBoxes'])) {
	$NoOfBoxes = $_GET['NoOfBoxes'];
} elseif (isset($_POST['NoOfBoxes'])){
	$NoOfBoxes = $_POST['NoOfBoxes'];
} else {
	unset($NoOfBoxes);
}
if (isset($_GET['LabelsPerBox'])) {
	$LabelsPerBox = $_GET['LabelsPerBox'];
} elseif (isset($_POST['LabelsPerBox'])){
	$LabelsPerBox = $_POST['LabelsPerBox'];
} else {
	unset($LabelsPerBox);
}
if (isset($_GET['QtyPerBox'])) {
	$QtyPerBox = $_GET['QtyPerBox'];
} elseif (isset($_POST['QtyPerBox'])){
	$QtyPerBox = $_POST['QtyPerBox'];
} else {
	unset($QtyPerBox);
}
if (isset($_GET['LeftOverQty'])) {
	$LeftOverQty = $_GET['LeftOverQty'];
} elseif (isset($_POST['LeftOverQty'])){
	$LeftOverQty = $_POST['LeftOverQty'];
} else {
	unset($LeftOverQty);
}
if (isset($_GET['PrintLabels'])) {
	$PrintLabels = $_GET['PrintLabels'];
} elseif (isset($_POST['PrintLabels'])){
	$PrintLabels = $_POST['PrintLabels'];
} else {
	$PrintLabels="Yes";
}
if (isset($_GET['ViewingOnly'])) {
	$ViewingOnly = $_GET['ViewingOnly'];
} elseif (isset($_POST['ViewingOnly'])) {
	$ViewingOnly = $_POST['ViewingOnly'];
} else {
	$ViewingOnly = 1;
}
if (isset($_GET['EmailTo'])) {
	$EmailTo = $_GET['EmailTo'];
} elseif (isset($_POST['EmailTo'])) {
	$EmailTo = $_POST['EmailTo'];
} else {
	$EmailTo = '';
}
if (isset($_GET['LabelLot'])) {
	$LabelLot = $_GET['LabelLot'];
} elseif (isset($_POST['LabelLot'])) {
	$LabelLot = $_POST['LabelLot'];
} else {
	$LabelLot = '';
}


if (!isset($_GET['WO']) AND !isset($_POST['WO'])) {
	$Title = __('Select a Work Order');
	include('includes/header.php');
	echo '<div class="centre"><br /><br /><br />';
	prnMsg(__('Select a Work Order Number to Print before calling this page'), 'error');
	echo '<br />
				<br />
				<br />
				<table class="table_index">
					<tr><td class="menu_group_item">
						<li><a href="' . $RootPath . '/SelectWorkOrder.php">' . __('Select Work Order') . '</a></li>
						</td>
					</tr></table>
				</div>
				<br />
				<br />
				<br />';
	include('includes/footer.php');
	exit();

	echo '<div class="centre"><br /><br /><br />' . __('This page must be called with a Work order number to print');
	echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a></div>';
	exit();
}
if (isset($_GET['WO'])) {
	$SelectedWO = $_GET['WO'];
}
elseif (isset($_POST['WO'])) {
	$SelectedWO = $_POST['WO'];
}
$Title = __('Print Work Order Number') . ' ' . $SelectedWO;
if (isset($_POST['PrintOrEmail']) AND isset($_POST['EmailTo'])) {
	if ($_POST['PrintOrEmail'] == 'Email' AND !IsEmailAddress($_POST['EmailTo'])) {
		include('includes/header.php');
		prnMsg(__('The email address entered does not appear to be valid. No emails have been sent.'), 'warn');
		include('includes/footer.php');
		exit();
	}
}

/* If we are previewing the order then we dont want to email it */
if ($SelectedWO == 'Preview') { //WO is set to 'Preview' when just looking at the format of the printed order
	$_POST['PrintOrEmail'] = 'Print';
	$MakePDFThenDisplayIt = True;
} //$SelectedWO == 'Preview'

if (isset($_POST['DoIt']) AND ($_POST['PrintOrEmail'] == 'Print' OR $ViewingOnly == 1)) {
	$MakePDFThenDisplayIt = True;
	$MakePDFThenEmailIt = False;
} elseif (isset($_POST['DoIt']) AND $_POST['PrintOrEmail'] == 'Email' AND isset($_POST['EmailTo'])) {
	$MakePDFThenEmailIt = True;
	$MakePDFThenDisplayIt = False;
}

if (isset($SelectedWO) AND $SelectedWO != '' AND $SelectedWO > 0 AND $SelectedWO != 'Preview') {
	/*retrieve the order details from the database to print */
	$ErrMsg = __('There was a problem retrieving the Work order header details for Order Number') . ' ' . $SelectedWO . ' ' . __('from the database');
	$SQL = "SELECT workorders.wo,
							 workorders.loccode,
							 locations.locationname,
							 locations.deladd1,
							 locations.deladd2,
							 locations.deladd3,
							 locations.deladd4,
							 locations.deladd5,
							 locations.deladd6,
							 workorders.requiredby,
							 workorders.startdate,
							 workorders.closed,
							 stockmaster.description,
							 stockmaster.decimalplaces,
							 stockmaster.units,
							 stockmaster.controlled,
							 woitems.stockid,
							 woitems.qtyreqd,
							 woitems.qtyrecd,
							 woitems.comments,
							 woitems.nextlotsnref
						FROM workorders
						INNER JOIN locations
							ON workorders.loccode=locations.loccode
						INNER JOIN woitems
							ON workorders.wo=woitems.wo
						INNER JOIN locationusers
							ON locationusers.loccode=locations.loccode
							AND locationusers.userid='" .  $_SESSION['UserID'] . "'
							AND locationusers.canview=1
						INNER JOIN stockmaster
							ON woitems.stockid=stockmaster.stockid
						WHERE woitems.stockid='" . $StockID . "'
							AND woitems.wo ='" . $SelectedWO . "'";
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result) == 0) {
		/*There is no order header returned */
		$Title = __('Print Work Order Error');
		include('includes/header.php');
		echo '<div class="centre"><br /><br /><br />';
		prnMsg(__('Unable to Locate Work Order Number') . ' : ' . $SelectedWO . ' ', 'error');
		echo '<br />
			<br />
			<br />
			<table class="table_index">
				<tr><td class="menu_group_item">
				<li><a href="' . $RootPath . '/SelectWorkOrder.php">' . __('Select Work Order') . '</a></li>
				</td>
				</tr>
			</table>
			</div><br /><br /><br />';
		include('includes/footer.php');
		exit();
	} elseif (DB_num_rows($Result) == 1) {
		/*There is only one order header returned  (as it should be!)*/
		$WOHeader = DB_fetch_array($Result);
		if ($WOHeader['controlled']==1) {
			$SQL = "SELECT serialno
							FROM woserialnos
							WHERE woserialnos.stockid='" . $StockID . "'
							AND woserialnos.wo ='" . $SelectedWO . "'";
			$Result = DB_query($SQL, $ErrMsg);
			if (DB_num_rows($Result) > 0) {
				$SerialNoArray=DB_fetch_array($Result);
				$SerialNo=$SerialNoArray[0];
			}
			else {
				$SerialNo=$WOHeader['nextlotsnref'];
			}
		} //controlled
		$PackQty=0;
		$SQL = "SELECT value
				FROM stockitemproperties
				INNER JOIN stockcatproperties
				ON stockcatproperties.stkcatpropid=stockitemproperties.stkcatpropid
				WHERE stockid='" . $StockID . "'
				AND label='PackQty'";
		$Result = DB_query($SQL, $ErrMsg);
		$PackQtyArray=DB_fetch_array($Result);
		if (DB_num_rows($Result) == 0) {
			$PackQty = 1;
		} else {
			$PackQty=$PackQtyArray['value'];
			if ($PackQty==0) {
				$PackQty=1;
			}
		}
	} // 1 valid record
} //if there is a valid order number
else if ($SelectedWO == 'Preview') { // We are previewing the order

	/* Fill the order header details with dummy data */
	$WOHeader['comments'] = str_pad('', 1050, 'x');
	$WOHeader['locationname'] = str_pad('', 35, 'y');
	$SerialNo="XXXXXXXXXX";
	$PackQty='999999999';
	$WOHeader['requiredby'] = date('m/d/Y');
	$WOHeader['startdate'] = date('m/d/Y');
	$WOHeader['qtyreqd'] = '999999999';
	$WOHeader['qtyrecd'] = '999999999';
	$WOHeader['deladd1'] = str_pad('', 40, 'x');
	$WOHeader['deladd2'] = str_pad('', 40, 'x');
	$WOHeader['deladd3'] = str_pad('', 40, 'x');
	$WOHeader['deladd4'] = str_pad('', 40, 'x');
	$WOHeader['deladd5'] = str_pad('', 20, 'x');
	$WOHeader['deladd6'] = str_pad('', 15, 'x');
	$WOHeader['stockid'] = str_pad('', 15, 'x');
	$WOHeader['description'] = str_pad('', 50, 'x');
	$WOHeader['wo'] = '99999999';
	$WOHeader['loccode'] = str_pad('',5,'x');

} // end of If we are previewing the order

/* Load the relevant xml file */
if (isset($MakePDFThenDisplayIt) or isset($MakePDFThenEmailIt)) {
	if ($SelectedWO == 'Preview') {
		$FormDesign = simplexml_load_file(sys_get_temp_dir() . '/WOPaperwork.xml');
	} else {
		$FormDesign = simplexml_load_file($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/WOPaperwork.xml');
	}
	// Set the paper size/orintation
	$PaperSize = $FormDesign->PaperSize;
	include('includes/PDFStarter.php');
	$pdf->addInfo('Title', __('Work Order'));
	$pdf->addInfo('Subject', __('Work Order Number') . ' ' . $SelectedWO);
	$LineHeight = $FormDesign->LineHeight;
	$PageNumber = 1;
	$FooterPrintedInPage = 0;
	if ($SelectedWO != 'Preview') { // It is a real order
		$IssuedAlreadyRow = array();
		$ErrMsg = __('There was a problem retrieving the line details for order number') . ' ' . $SelectedWO . ' ' . __('from the database');
		$RequirmentsResult = DB_query("SELECT worequirements.stockid,
										stockmaster.description,
										stockmaster.decimalplaces,
										autoissue,
										qtypu,
										controlled,
										units
									FROM worequirements INNER JOIN stockmaster
									ON worequirements.stockid=stockmaster.stockid
									WHERE wo='" . $SelectedWO . "'
									AND worequirements.parentstockid='" . $StockID . "'");
		$IssuedAlreadyResult = DB_query("SELECT stockid,
											SUM(-qty) AS total
										FROM stockmoves
										WHERE stockmoves.type=28
										AND reference='".$SelectedWO."'
										GROUP BY stockid");
		while ($IssuedRow = DB_fetch_array($IssuedAlreadyResult)){
			$IssuedAlreadyRow[$IssuedRow['stockid']] = $IssuedRow['total'];
		}
		$i=0;
		$WOLine=array();
		while ($RequirementsRow = DB_fetch_array($RequirmentsResult)){
			if ($RequirementsRow['autoissue']==0){
				$WOLine[$i]['action']='Manual Issue';
			} else {
				$WOLine[$i]['action']='Auto Issue';
			}
			if (isset($IssuedAlreadyRow[$RequirementsRow['stockid']])){
				$Issued = $IssuedAlreadyRow[$RequirementsRow['stockid']];
				unset($IssuedAlreadyRow[$RequirementsRow['stockid']]);
			}else{
				$Issued = 0;
			}
			$WOLine[$i]['item'] = $RequirementsRow['stockid'];
			$WOLine[$i]['description'] = $RequirementsRow['description'];
			$WOLine[$i]['controlled'] = $RequirementsRow['controlled'];
			$WOLine[$i]['qtyreqd'] = $WOHeader['qtyreqd']*$RequirementsRow['qtypu'];
			$WOLine[$i]['issued'] = $Issued  ;
			$WOLine[$i]['decimalplaces'] = $RequirementsRow['decimalplaces'];
			$WOLine[$i]['units'] = $RequirementsRow['units'];
			$i+=1;
		}
		/* Now do any additional issues of items not in the BOM */
		if(count($IssuedAlreadyRow)>0){
			$AdditionalStocks = implode("','",array_keys($IssuedAlreadyRow));
			$RequirementsSQL = "SELECT stockid,
							description,
							decimalplaces,
							controlled,
							units
					FROM stockmaster WHERE stockid IN ('".$AdditionalStocks."')";
			$RequirementsResult = DB_query($RequirementsSQL);
			$AdditionalStocks = array();
			while($MyRow = DB_fetch_array($RequirementsResult)){
				$WOLine[$i]['action']='Additional Issue';
				$WOLine[$i]['item'] =  $MyRow['stockid'];
				$WOLine[$i]['description'] = $MyRow['description'];
				$WOLine[$i]['controlled'] = $MyRow['controlled'];
				$WOLine[$i]['qtyreqd'] = 0;
				$WOLine[$i]['issued'] = $IssuedAlreadyRow[$MyRow['stockid']];
				$WOLine[$i]['decimalplaces'] = $RequirementsRow['decimalplaces'];
				$WOLine[$i]['units'] = $RequirementsRow['units'];
				$i+=1;
			}
		}

	}
	if ($SelectedWO == 'Preview' or $i > 0) {
		/*Yes there are line items to start the ball rolling with a page header */
		include('includes/PDFWOPageHeader.php');
		$YPos = $Page_Height - $FormDesign->Data->y;
		$i=0;
		while ((isset($SelectedWO) AND $SelectedWO == 'Preview') OR (count($WOLine) > $i )) {
			if ($SelectedWO == 'Preview') {
				$WOLine[$i]['action'] = str_pad('', 20, 'x');
				$WOLine[$i]['item'] = str_pad('', 10, 'x');
				$WOLine[$i]['description'] = str_pad('', 50, 'x');
				$WOLine[$i]['qtyreqd'] = 9999999.99;
				$WOLine[$i]['issued'] = 9999999.99;
				$WOLine[$i]['decimalplaces'] = 2;
				$WOLine[$i]['units'] = 'ea';
			}
			if ($WOLine[$i]['decimalplaces'] != NULL) {
				$DecimalPlaces = $WOLine[$i]['decimalplaces'];
			}
			else {
				$DecimalPlaces = 2;
			}
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column1->x, $YPos, $FormDesign->Data->Column1->Length, $FormDesign->Data->Column1->FontSize, $WOLine[$i]['action'], 'left');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column2->x, $YPos, $FormDesign->Data->Column2->Length, $FormDesign->Data->Column2->FontSize, $WOLine[$i]['item'], 'left');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column3->x, $YPos, $FormDesign->Data->Column3->Length, $FormDesign->Data->Column3->FontSize, $WOLine[$i]['description'], 'left');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column4->x, $YPos, $FormDesign->Data->Column4->Length, $FormDesign->Data->Column4->FontSize, locale_number_format($WOLine[$i]['qtyreqd'],$WOLine[$i]['decimalplaces']), 'right');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column5->x, $YPos, $FormDesign->Data->Column5->Length, $FormDesign->Data->Column5->FontSize, locale_number_format($WOLine[$i]['issued'],$WOLine[$i]['decimalplaces']), 'right');
			$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column6->x, $YPos, $FormDesign->Data->Column6->Length, $FormDesign->Data->Column6->FontSize, $WOLine[$i]['units'], 'left');

			$YPos -= $LineHeight;
			if ($YPos - (2*$LineHeight) <= $Page_Height - $FormDesign->Comments->y) {
				$PageNumber++;
				$YPos = $Page_Height - $FormDesign->Data->y;
				include('includes/PDFWOPageHeader.php');
			}

			/*display already issued and available qty and lots where applicable*/

			$IssuedAlreadyDetail = DB_query("SELECT stockmoves.stockid,
													SUM(qty) as qty,
													stockserialmoves.serialno,
													sum(stockserialmoves.moveqty) as moveqty,
													locations.locationname
													FROM stockmoves LEFT OUTER JOIN stockserialmoves
													ON stockmoves.stkmoveno= stockserialmoves.stockmoveno
													INNER JOIN locations
													ON stockmoves.loccode=locations.loccode
													WHERE stockmoves.type=28
													AND stockmoves.stockid = '".$WOLine[$i]['item']."'
													AND reference='".$SelectedWO."'
													GROUP BY stockserialmoves.serialno");
			while ($IssuedRow = DB_fetch_array($IssuedAlreadyDetail)){
				if ($WOLine[$i]['controlled']) {
					$CurLot=$IssuedRow['serialno'];
					$CurQty=-$IssuedRow['moveqty'];
				}
				else {
					$CurLot=$IssuedRow['locationname'];
					$CurQty=-$IssuedRow['qty'];
				}
				$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column3->x, $YPos, $FormDesign->Data->Column3->Length, $FormDesign->Data->Column3->FontSize, $CurLot, 'left');
				$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column5->x, $YPos, $FormDesign->Data->Column5->Length, $FormDesign->Data->Column5->FontSize, $CurQty, 'right');
				$YPos -= $LineHeight;
				if ($YPos - (2*$LineHeight) <= $Page_Height - $FormDesign->Comments->y) {
					$PageNumber++;
					$YPos = $Page_Height - $FormDesign->Data->y;
					include('includes/PDFWOPageHeader.php');
				}
			}

			if ($WOLine[$i]['issued'] <= $WOLine[$i]['qtyreqd']) {
				$AvailQty = DB_query("SELECT locstock.loccode,
											locstock.bin,
											locstock.quantity,
											serialno,
											stockserialitems.quantity as qty
											FROM locstock LEFT OUTER JOIN stockserialitems
											ON locstock.loccode=stockserialitems.loccode AND locstock.stockid = stockserialitems.stockid
											WHERE locstock.loccode='".$WOHeader['loccode']."'
											AND locstock.stockid='".$WOLine[$i]['item']."'
											ORDER BY createdate, quantity");
				while ($ToIssue = DB_fetch_array($AvailQty)){
					if ($WOLine[$i]['controlled']) {
						$CurLot=$ToIssue['serialno'];
						$CurQty=locale_number_format($ToIssue['qty'],$DecimalPlaces);
					}
					else {
						$CurLot=substr($WOHeader['locationname'] . ' ' . $ToIssue['bin'],0,34);
						$CurQty=locale_number_format($ToIssue['quantity'],$DecimalPlaces);
					}
					//remove display of very small number raised due to rounding error
					$MinalQtyAllowed = 1/pow(10,$DecimalPlaces)/10;
					if ($CurQty > $MinalQtyAllowed) {
						$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column3->x, $YPos, $FormDesign->Data->Column3->Length, $FormDesign->Data->Column3->FontSize, $CurLot, 'left');
						$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column3->x, $YPos, $FormDesign->Data->Column3->Length, $FormDesign->Data->Column3->FontSize, $CurQty, 'right');
						$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column5->x, $YPos, $FormDesign->Data->Column5->Length, $FormDesign->Data->Column5->FontSize, '________', 'right');
						$YPos -= $LineHeight;
						if ($YPos - (2*$LineHeight) <= $Page_Height - $FormDesign->Comments->y) {
							$PageNumber++;
							$YPos = $Page_Height - $FormDesign->Data->y;
							include('includes/PDFWOPageHeader.php');
						}

					}
				}
			} //not all issued
			if ($SelectedWO == 'Preview') {
				$SelectedWO = 'Preview_WorkOrder';
			} //$SelectedWO == 'Preview'
			$i+=1;
			$YPos -= $LineHeight; /*extra line*/
			if ($YPos - (2*$LineHeight) <= $Page_Height - $FormDesign->Comments->y) {
				$PageNumber++;
				$YPos = $Page_Height - $FormDesign->Data->y;
				include('includes/PDFWOPageHeader.php');
			}
		} //end while there are line items to print out

		if ($YPos - (2*$LineHeight) <= $Page_Height - $FormDesign->Comments->y) { // need to ensure space for totals
			$PageNumber++;
			include('includes/PDFWOPageHeader.php');
		} //end if need a new page headed up
	} /*end if there are order details to show on the order - or its a preview*/
	if($FooterPrintedInPage == 0){
			$Http = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
			$BaseURL = $Http . $_SERVER['HTTP_HOST'] . $RootPath;
			$pdf->write2DBarcode($BaseURL.'/WorkOrderIssue.php?WO='.$SelectedWO.'&StockID='.$StockID,'QRCODE,H',60,650,100,100,[''],'N');
			$pdf->write2DBarcode($StockID,'QRCODE,H',260,650,100,100,[''],'N');
			$pdf->write2DBarcode($BaseURL.'/WorkOrderReceive.php?WO='.$SelectedWO.'&StockID='.$StockID,'QRCODE,H',440,650,100,100,[''],'N');
			$LeftOvers = $pdf->addText($FormDesign->SignedDate->x,$Page_Height-$FormDesign->SignedDate->y,$FormDesign->SignedDate->FontSize, __('Date') . ' : ______________');
			$LeftOvers = $pdf->addText($FormDesign->SignedBy->x,$Page_Height-$FormDesign->SignedBy->y,$FormDesign->SignedBy->FontSize, __('Signed for') . ': ____________________________________');

			$FooterPrintedInPage= 1;
	}

	$PrintingComments=true;
	$LeftOvers = $pdf->addTextWrap($FormDesign->Comments->x, $Page_Height - $FormDesign->Comments->y,$FormDesign->Comments->Length,$FormDesign->Comments->FontSize, $WOHeader['comments'], 'left');
	$YPos=$Page_Height - $FormDesign->Comments->y;
	while (mb_strlen($LeftOvers) > 1) {
		$YPos -= $LineHeight;
		if ($YPos - $LineHeight <= $Bottom_Margin)  {
			$PageNumber++;
			$YPos = $Page_Height - $FormDesign->Headings->Column1->y;
			include('includes/PDFWOPageHeader.php');
		}
		$LeftOvers = $pdf->addTextWrap($FormDesign->Comments->x, $YPos,$FormDesign->Comments->Length,$FormDesign->Comments->FontSize, $LeftOvers, 'left');
	}

	$Success = 1; //assume the best and email goes - has to be set to 1 to allow update status
	if ($MakePDFThenDisplayIt) {
		$pdf->OutputD($_SESSION['DatabaseName'] . '_WorkOrder_' . $SelectedWO . '_' . date('Y-m-d') . '.pdf');
		$pdf->__destruct();
	} else {
		$PdfFileName = $_SESSION['DatabaseName'] . '_WorkOrder_' . $SelectedWO . '_' . date('Y-m-d') . '.pdf';
		$pdf->Output($_SESSION['reports_dir'] . '/' . $PdfFileName, 'F');
		$pdf->__destruct();

		$Success = SendEmailFromWebERP($_SESSION['CompanyRecord']['email'],
								array($_POST['EmailTo'] => ''),
								__('Work Order Number') . ' ' . $SelectedWO,
								('Please Process this Work order number') . ' ' . $SelectedWO,
								$_SESSION['reports_dir'] . '/' . $PdfFileName);

		if ($Success == 1) {
			$Title = __('Email a Work Order');
			include('includes/header.php');
			prnMsg(__('Work Order') . ' ' . $SelectedWO . ' ' . __('has been emailed to') . ' ' . $_POST['EmailTo'] . ' ' . __('as directed'), 'success');

		} else { //email failed
			$Title = __('Email a Work Order');
			include('includes/header.php');
			prnMsg(__('Emailing Work order') . ' ' . $SelectedWO . ' ' . __('to') . ' ' . $_POST['EmailTo'] . ' ' . __('failed'), 'error');
		}
	}
	include('includes/footer.php');
} //isset($MakePDFThenDisplayIt) OR isset($MakePDFThenEmailIt)

/* There was enough info to either print or email the Work order */
else {
	/**
	/*the user has just gone into the page need to ask the question whether to print the order or email it */
	include('includes/header.php');

	if (!isset($LabelItem)) {
		$SQL = "SELECT workorders.wo,
						stockmaster.description,
						stockmaster.decimalplaces,
						stockmaster.units,
						stockmaster.controlled,
						woitems.stockid,
						woitems.qtyreqd,
						woitems.nextlotsnref
						FROM workorders INNER JOIN woitems
						ON workorders.wo=woitems.wo
						INNER JOIN stockmaster
						ON woitems.stockid=stockmaster.stockid
						WHERE woitems.stockid='" . $StockID . "'
                        AND woitems.wo ='" . $SelectedWO . "'";

		$Result = DB_query($SQL, $ErrMsg);
		$Labels = DB_fetch_array($Result);
		$LabelItem=$Labels['stockid'];
		$LabelDesc=$Labels['description'];
		$QtyPerBox=0;
		$SQL = "SELECT value
				FROM stockitemproperties
				INNER JOIN stockcatproperties
				ON stockcatproperties.stkcatpropid=stockitemproperties.stkcatpropid
				WHERE stockid='" . $StockID . "'
				AND label='PackQty'";
		$Result = DB_query($SQL, $ErrMsg);
		$PackQtyArray=DB_fetch_array($Result);
		if (DB_num_rows($Result) == 0) {
			$QtyPerBox = 1;
		} else {
			$QtyPerBox=$PackQtyArray['value'];
			if ($QtyPerBox==0) {
				$QtyPerBox=1;
			}
		}
		$NoOfBoxes=(int)($Labels['qtyreqd'] / $QtyPerBox);
		$LeftOverQty=$Labels['qtyreqd'] % $QtyPerBox;
		$LabelsPerBox=1;
		$QtyPerBox=locale_number_format($QtyPerBox, $Labels['decimalplaces']);
		$LeftOverQty=locale_number_format($LeftOverQty, $Labels['decimalplaces']);
		if ($Labels['controlled']==1) {
			$SQL = "SELECT serialno
							FROM woserialnos
							WHERE woserialnos.stockid='" . $StockID . "'
							AND woserialnos.wo ='" . $SelectedWO . "'";
			$Result = DB_query($SQL, $ErrMsg);
			if (DB_num_rows($Result) > 0) {
				$SerialNoArray=DB_fetch_array($Result);
				$LabelLot=$SerialNoArray[0];
			}
			else {
				$LabelLot=$WOHeader['nextlotsnref'];
			}
		} //controlled
	} //not set yet
	echo '<p class="page_title_text" ><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/printer.png" title="' . __('Print') . '" alt="" />' . ' ' . $Title . '</p>';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	if ($ViewingOnly == 1) {
		echo '<input type="hidden" name="ViewingOnly" value="1" />';
	} //$ViewingOnly == 1
	echo '<input type="hidden" name="WO" value="' . $SelectedWO . '" />';
	echo '<input type="hidden" name="StockID" value="' . $StockID . '" />';
	echo '<Fieldset>
			<legend>', __('Order Printiing Options'), '</legend>
			<field>
				<label for="PrintOrEmail">' . __('Print or Email the Order') . '</label>
				<select name="PrintOrEmail">';

	if (!isset($_POST['PrintOrEmail'])) {
		$_POST['PrintOrEmail'] = 'Print';
	}
	if ($ViewingOnly != 0) {
		echo '<option selected="selected" value="Print">' . __('Print') . '</option>';
	}
	else {
		if ($_POST['PrintOrEmail'] == 'Print') {
			echo '<option selected="selected" value="Print">' . __('Print') . '</option>';
			echo '<option value="Email">' . __('Email') . '</option>';
		} else {
			echo '<option value="Print">' . __('Print') . '</option>';
			echo '<option selected="selected" value="Email">' . __('Email') . '</option>';
		}
	}
	echo '</select>
		</field>';
	echo '<field>
			<label for="PrintLabels">' . __('Print Labels') . ':</label>
			<select name="PrintLabels" >';
	if ($PrintLabels=="Yes") {
		echo '<option value="Yes" selected>' . __('Yes') . '</option>';
		echo '<option value="No">' . __('No') . '</option>';
	}
	else {
		echo '<option value="Yes" >' . __('Yes') . '</option>';
		echo '<option value="No" selected>' . __('No') . '</option>';
	}
	echo '</select>';

	if ($_POST['PrintOrEmail'] == 'Email') {
		$ErrMsg = __('There was a problem retrieving the contact details for the location');

		$SQL = "SELECT workorders.wo,
						workorders.loccode,
						locations.email
						FROM workorders INNER JOIN locations
						ON workorders.loccode=locations.loccode
						INNER JOIN woitems
						ON workorders.wo=woitems.wo
						WHERE woitems.stockid='" . $StockID . "'
						AND woitems.wo ='" . $SelectedWO . "'";
		$ContactsResult = DB_query($SQL, $ErrMsg);
		if (DB_num_rows($ContactsResult) > 0) {
			echo '<field><td>' . __('Email to') . ':</td><td><input name="EmailTo" value="';
			while ($ContactDetails = DB_fetch_array($ContactsResult)) {
				if (mb_strlen($ContactDetails['email']) > 2 AND mb_strpos($ContactDetails['email'], '@') > 0) {
					echo $ContactDetails['email'];
				}
			}
			echo '"/></field></fieldset>';
		}

	} else {
		echo '</fieldset>';
	}
	echo '<div class="centre">
			<input type="submit" name="DoIt" value="' . __('Paperwork') . '" />
		</div>';

	if ($PrintLabels=="Yes") {
		echo '<form action="PDFFGLabel.php" method="post">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		if ($ViewingOnly == 1) {
			echo '<input type="hidden" name="ViewingOnly" value="1" />';
		} //$ViewingOnly == 1
		echo '<input type="hidden" name="WO" value="' . $SelectedWO . '" />';
		echo '<input type="hidden" name="StockID" value="' . $StockID . '" />';
		echo '<input type="hidden" name="EmailTo" value="' . $EmailTo . '" />';
		echo '<input type="hidden" name="PrintOrEmail" value="' . $_POST['PrintOrEmail'] . '" />';
		echo '<fieldset>
				<legend>', __('Label Print Options'), '</legend>
				<field>
					<label for="LabelItem">' . __('Label Item') . ':</label>
					<input name="LabelItem" value="' .$LabelItem.'"/>
				</field>
				<field>
					<label for="LabelDesc">' . __('Label Description') . ':</label>
					<input name="LabelDesc" value="' .$LabelDesc.'"/>
				</field>
				<field>
					<label for="LabelLot">' . __('Label Lot') . ':</label>
					<input name="LabelLot" value="' .$LabelLot.'"/>
				</field>
				<field>
					<label for="NoOfBoxes">' . __('No of Full Packages') . ':</label>
					<input name="NoOfBoxes" class="integer" value="' .$NoOfBoxes.'"/>
				</field>
				<field>
					<label for="LabelsPerBox">' . __('Labels/Package') . ':</label>
					<input name="LabelsPerBox" class="integer" value="' .$LabelsPerBox.'"/>
				</field>
				<field>
					<label for="QtyPerBox">' . __('Weight/Package') . ':</label>
					<input name="QtyPerBox" class="number" value="' .$QtyPerBox. '"/>
				</field>
				<field>
					<label for="LeftOverQty">' . __('LeftOver Qty') . ':</label>
					<input name="LeftOverQty" class="number" value="' .$LeftOverQty.'"/>
				</field>
				<field>
					<label for="PrintOrEmail">' . __('Print or Email the Order') . '</label>
					<select name="PrintOrEmail">';

		if (!isset($_POST['PrintOrEmail'])) {
			$_POST['PrintOrEmail'] = 'Print';
		}
		if ($ViewingOnly != 0) {
			echo '<option selected="selected" value="Print">' . __('Print') . '</option>';
		}
		else {
			if ($_POST['PrintOrEmail'] == 'Print') {
				echo '<option selected="selected" value="Print">' . __('Print') . '</option>';
				echo '<option value="Email">' . __('Email') . '</option>';
			} else {
				echo '<option value="Print">' . __('Print') . '</option>';
				echo '<option selected="selected" value="Email">' . __('Email') . '</option>';
			}
		}
		echo '</select>
			</field>';
		$SQL = "SELECT workorders.wo,
						workorders.loccode,
						locations.email
						FROM workorders INNER JOIN locations
						ON workorders.loccode=locations.loccode
						INNER JOIN woitems
						ON workorders.wo=woitems.wo
						WHERE woitems.stockid='" . $StockID . "'
						AND woitems.wo ='" . $SelectedWO . "'";
		$ContactsResult = DB_query($SQL, $ErrMsg);
		if (DB_num_rows($ContactsResult) > 0) {
			echo '<field><label for="EmailTo">' . __('Email to') . ':</label><input name="EmailTo" value="';
			while ($ContactDetails = DB_fetch_array($ContactsResult)) {
				if (mb_strlen($ContactDetails['email']) > 2 AND mb_strpos($ContactDetails['email'], '@') > 0) {
					echo $ContactDetails['email'];
				}
			}
			echo '"/></field></fieldset>';
		}
		else {
			echo '</fieldset>';
		}
		echo '<div class="centre">
				<input type="submit" name="DoIt" value="' . __('Labels') . '" />
			</div>
			</form>';
	}
	include('includes/footer.php');
}
