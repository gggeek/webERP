<?php


// InventoryQuantities.php - Report of parts with quantity. Sorts by part and shows
// all locations where there are quantities of the part

include('includes/session.php');
use Dompdf\Dompdf;

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . _('Inventory Quantities Report') . '<br />
					' . _('Category') . ' ' . $_POST['StockCat'] . ' ' . $CatDescription . '<br />
					' . _('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th>' . _('Part Number') . '</th>
							<th>' . _('Description') . '</th>
							<th>' . _('Location') . '</th>
							<th>' . _('Quantity') . '</th>
							<th>' . _('Reorder') . '<br />' . _('Level') . '</th>
						</tr>
					</thead>
					<tbody>';

	$WhereCategory = ' ';
	$CatDescription = ' ';
	if ($_POST['StockCat'] != 'All') {
		$WhereCategory = " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
		$SQL= "SELECT categoryid,
					categorydescription
				FROM stockcategory
				WHERE categoryid='" . $_POST['StockCat'] . "' ";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		$CatDescription = $MyRow[1];
	}

	if ($_POST['Selection'] == 'All') {
		$SQL = "SELECT locstock.stockid,
					stockmaster.description,
					locstock.loccode,
					locations.locationname,
					locstock.quantity,
					locstock.reorderlevel,
					stockmaster.decimalplaces,
					stockmaster.serialised,
					stockmaster.controlled
				FROM locstock INNER JOIN stockmaster
				ON locstock.stockid=stockmaster.stockid
				INNER JOIN locations
				ON locstock.loccode=locations.loccode
				WHERE locstock.quantity <> 0
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') " .
				$WhereCategory . "
				ORDER BY locstock.stockid,
						locstock.loccode";
	} else {
		// sql to only select parts in more than one location
		// The SELECT statement at the beginning of the WHERE clause limits the selection to
		// parts with quantity in more than one location
		$SQL = "SELECT locstock.stockid,
					stockmaster.description,
					locstock.loccode,
					locations.locationname,
					locstock.quantity,
					locstock.reorderlevel,
					stockmaster.decimalplaces,
					stockmaster.serialised,
					stockmaster.controlled
				FROM locstock INNER JOIN stockmaster
				ON locstock.stockid=stockmaster.stockid
				INNER JOIN locations
				ON locstock.loccode=locations.loccode
				WHERE (SELECT count(*)
					  FROM locstock
					  WHERE stockmaster.stockid = locstock.stockid
					  AND locstock.quantity <> 0
					  GROUP BY locstock.stockid) > 1
				AND locstock.quantity <> 0
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') " .
				$WhereCategory . "
				ORDER BY locstock.stockid,
						locstock.loccode";
	}


	$Result = DB_query($SQL,'','',false,true);

	if (DB_error_no() !=0) {
	  $Title = _('Inventory Quantities') . ' - ' . _('Problem Report');
	  include('includes/header.php');
	   prnMsg( _('The Inventory Quantity report could not be retrieved by the SQL because') . ' '  . DB_error_msg(),'error');
	   echo '<br /><a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   if ($Debug==1){
		  echo '<br />' . $SQL;
	   }
	   include('includes/footer.php');
	   exit();
	}
	if (DB_num_rows($Result)==0){
			$Title = _('Print Inventory Quantities Report');
			include('includes/header.php');
			prnMsg(_('There were no items with inventory quantities'),'error');
			echo '<br /><a href="'.$RootPath.'/index.php">' . _('Back to the menu') . '</a>';
			include('includes/footer.php');
			exit();
	}

	$HoldPart = " ";
	while ($MyRow = DB_fetch_array($Result)){

		if ($MyRow['stockid'] != $HoldPart) {
			$HoldPart = $MyRow['stockid'];
			$HTML .= '<tr class="total_row">
						<td colspan="5"> </td>
					</tr>';
		}

		$HTML .= '<tr class="striped_row">
					<td>' . $MyRow['stockid'] . '</td>
					<td>' . $MyRow['description'] . '</td>
					<td>' . $MyRow['loccode'] . '</td>
					<td class="number">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . '</td>
					<td class="number">' . locale_number_format($MyRow['reorderlevel'], $MyRow['decimalplaces']) . '</td>
				</tr>';

	} /*end while loop */


	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
		$HTML .= '</tbody>
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . _('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	if (isset($_POST['PrintPDF'])) {
		$dompdf = new Dompdf(['chroot' => __DIR__]);
		$dompdf->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$dompdf->setPaper($_SESSION['PageSize'], 'portrait');

		// Render the HTML as PDF
		$dompdf->render();

		// Output the generated PDF to Browser
		$dompdf->stream($_SESSION['DatabaseName'] . '_InventoryQuantities_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = _('Inventory Quantities');
		include('includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';
		echo $HTML;
		include('includes/footer.php');
	}

} else { /*The option to print PDF was not hit so display form */

	$Title=_('Inventory Quantities Reporting');
	$ViewTopic = 'Inventory';
	$BookMark = '';
	include('includes/header.php');
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . _('Inventory') . '" alt="" />' . ' ' . _('Inventory Quantities Report') . '</p>';
	echo '<div class="page_help_text">' . _('Use this report to display the quantity of Inventory items in different categories.') . '</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<fieldset>
			<legend>', _('Report Criteria'), '</legend>';

	echo '<field>
			<label for="Selection">' . _('Selection') . ':</label>
			<select name="Selection">
				<option selected="selected" value="All">' . _('All') . '</option>
				<option value="Multiple">' . _('Only Parts With Multiple Locations') . '</option>
			</select>
		</field>';

	$SQL="SELECT categoryid,
				categorydescription
			FROM stockcategory
			ORDER BY categorydescription";
	$Result1 = DB_query($SQL);
	if (DB_num_rows($Result1)==0){
		echo '</table>
			<p />';
		prnMsg(_('There are no stock categories currently defined please use the link below to set them up'),'warn');
		echo '<br /><a href="' . $RootPath . '/StockCategories.php">' . _('Define Stock Categories') . '</a>';
		include('includes/footer.php');
		exit();
	}

	echo '<field>
			<label for="StockCat">' . _('In Stock Category') . ':</label>
			<select name="StockCat">';
	if (!isset($_POST['StockCat'])){
		$_POST['StockCat']='All';
	}
	if ($_POST['StockCat']=='All'){
		echo '<option selected="selected" value="All">' . _('All') . '</option>';
	} else {
		echo '<option value="All">' . _('All') . '</option>';
	}
	while ($MyRow1 = DB_fetch_array($Result1)) {
		if ($MyRow1['categoryid']==$_POST['StockCat']){
			echo '<option selected="selected" value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
		} else {
			echo '<option value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
		}
	}
	echo '</select>
		</field>
		</fieldset>
		<div class="centre">
			<input type="submit" name="PrintPDF" title="Produce PDF Report" value="' . _('Print PDF') . '" />
			<input type="submit" name="View" title="View Report" value="' . _('View') . '" />
		</div>';

	echo '</form>';
	include('includes/footer.php');

} /*end of else not PrintPDF */
