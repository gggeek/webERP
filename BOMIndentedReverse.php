<?php


// BOMIndented.php - Reverse Indented Bill of Materials - From lowest level component to top level
// assembly

include('includes/session.php');
use Dompdf\Dompdf;

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	$Result = DB_query("DROP TABLE IF EXISTS tempbom");
	$Result = DB_query("DROP TABLE IF EXISTS passbom");
	$Result = DB_query("DROP TABLE IF EXISTS passbom2");
	$SQL = "CREATE TEMPORARY TABLE passbom (
				part char(20),
				sortpart text) DEFAULT CHARSET=utf8";

	$ErrMsg = _('The SQL to create passbom failed with the message');
	$Result = DB_query($SQL,$ErrMsg);

	$SQL = "CREATE TEMPORARY TABLE tempbom (
				parent char(20),
				component char(20),
				sortpart text,
				level int,
				workcentreadded char(5),
				loccode char(5),
				effectiveafter date,
				effectiveto date,
				quantity double) DEFAULT CHARSET=utf8";
	$Result = DB_query($SQL,_('Create of tempbom failed because'));
	// First, find first level of components below requested assembly
	// Put those first level parts in passbom, use COMPONENT in passbom
	// to link to PARENT in bom to find next lower level and accumulate
	// those parts into tempbom

	// This finds the top level
	$SQL = "INSERT INTO passbom (part, sortpart)
			   SELECT bom.parent AS part,
					  CONCAT(bom.component,bom.parent) AS sortpart
					  FROM bom
			  WHERE bom.component ='" . $_POST['Part'] . "'
			  AND bom.effectiveafter <= '" . date('Y-m-d') . "'
			  AND bom.effectiveto > '" . date('Y-m-d') . "'";
	$Result = DB_query($SQL);

	$LevelCounter = 2;
	// $LevelCounter is the level counter
	$SQL = "INSERT INTO tempbom (
				parent,
				component,
				sortpart,
				level,
				workcentreadded,
				loccode,
				effectiveafter,
				effectiveto,
				quantity)
			  SELECT bom.parent,
					 bom.component,
					 CONCAT(bom.component,bom.parent) AS sortpart,
					 " . $LevelCounter . " AS level,
					 bom.workcentreadded,
					 bom.loccode,
					 bom.effectiveafter,
					 bom.effectiveto,
					 bom.quantity
					 FROM bom
			  WHERE bom.component ='" . $_POST['Part'] . "'
			  AND bom.effectiveafter <= '" . date('Y-m-d') . "'
			  AND bom.effectiveto > '" . date('Y-m-d') . "'";
	$Result = DB_query($SQL);

	// This while routine finds the other levels as long as $ComponentCounter - the
	// component counter finds there are more components that are used as
	// assemblies at lower levels

	$ComponentCounter = 1;
	while ($ComponentCounter > 0) {
		$LevelCounter++;
		$SQL = "INSERT INTO tempbom (parent,
									component,
									sortpart,
									level,
									workcentreadded,
									loccode,
									effectiveafter,
									effectiveto,
									quantity)
				  SELECT bom.parent,
						 bom.component,
						 CONCAT(passbom.sortpart,bom.parent) AS sortpart,
						 " . $LevelCounter . " AS level,
						 bom.workcentreadded,
						 bom.loccode,
						 bom.effectiveafter,
						 bom.effectiveto,
						 bom.quantity
				FROM bom,passbom
				WHERE bom.component = passbom.part
				AND bom.effectiveafter <= '" . date('Y-m-d') . "'
				AND bom.effectiveto > '" . date('Y-m-d') . "'";
		$Result = DB_query($SQL);

		$Result = DB_query("DROP TABLE IF EXISTS passbom2");

		$Result = DB_query("ALTER TABLE passbom RENAME AS passbom2");
		$Result = DB_query("DROP TABLE IF EXISTS passbom");

		$SQL = "CREATE TEMPORARY TABLE passbom (
						part char(20),
						sortpart text) DEFAULT CHARSET=utf8";
		$Result = DB_query($SQL);


		$SQL = "INSERT INTO passbom (part, sortpart)
				   SELECT bom.parent AS part,
						  CONCAT(passbom2.sortpart,bom.parent) AS sortpart
				   FROM bom,passbom2
				   WHERE bom.component = passbom2.part
				   AND bom.effectiveafter <= '" . date('Y-m-d') . "'
				   AND bom.effectiveto > '" . date('Y-m-d') . "'";
		$Result = DB_query($SQL);
		$Result = DB_query("SELECT COUNT(*) FROM bom,passbom WHERE bom.component = passbom.part");

		$MyRow = DB_fetch_row($Result);
		$ComponentCounter = $MyRow[0];

	} // End of while $ComponentCounter > 0

	if (DB_error_no() !=0) {
	  $Title = _('Indented BOM Listing') . ' - ' . _('Problem Report');
	  include('includes/header.php');
	   prnMsg( _('The Indented BOM Listing could not be retrieved by the SQL because') . ' '  . DB_error_msg(),'error');
	   echo '<br />
			<a href="' .$RootPath .'/index.php">' . _('Back to the menu') . '</a>';
	   if ($Debug==1){
		  echo '<br />' . $SQL;
	   }
	   include('includes/footer.php');
	   exit();
	}


	$SQL = "SELECT stockmaster.stockid,
				   stockmaster.description
			  FROM stockmaster
			  WHERE stockid = '" . $_POST['Part'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$Assembly = $_POST['Part'];
	$AssemblyDesc = $MyRow['description'];


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
					' . _('Reverse Indented BOM Listing for Component') . ' ' . mb_strtoupper($_POST['Part']) . '<br />
					' . _('Printed') . ': ' . Date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th class="SortedColumn">' . _('Part Number') . '</th>
							<th class="SortedColumn">' . _('M/B') . '</th>
							<th class="SortedColumn">' . _('Description') . '</th>
							<th class="SortedColumn">' . _('Location') . '</th>
							<th class="SortedColumn">' . _('Work') . '<br />' . _('Centre') . '</th>
							<th class="SortedColumn">' . _('Quantity') . '</th>
							<th class="SortedColumn">' . _('UOM') . '</th>
							<th class="SortedColumn">' . _('From Date') . '</th>
							<th class="SortedColumn">' . _('To Date') . '</th>
						</tr>
					</thead>
					<tbody>';

	$SQL = "SELECT tempbom.*,
				   stockmaster.description,
				   stockmaster.mbflag,
				   stockmaster.units
			  FROM tempbom INNER JOIN stockmaster
			  ON tempbom.parent = stockmaster.stockid
			  INNER JOIN locationusers ON locationusers.loccode=tempbom.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			  ORDER BY sortpart";
	$Result = DB_query($SQL);

	$ListCount = DB_num_rows($Result);

	while ($MyRow = DB_fetch_array($Result)){

		$FormatedEffectiveAfter = ConvertSQLDate($MyRow['effectiveafter']);
		$FormatedEffectiveTo = ConvertSQLDate($MyRow['effectiveto']);

		$HTML .= '<tr class="striped_row">
					<td>' . $MyRow['component'] . '</td>
					<td>' . $MyRow['mbflag'] . '</td>
					<td>' . $MyRow['description'] . '</td>
					<td>' . $MyRow['loccode'] . '</td>
					<td>' . $MyRow['workcentreadded'] . '</td>
					<td class="number">' . locale_number_format($MyRow['quantity'],'Variable') . '</td>
					<td>' . $MyRow['units'] . '</td>
					<td class="date">' . $FormatedEffectiveAfter . '</td>
					<td class="date">' . $FormatedEffectiveTo . '</td>
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
		$dompdf->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$dompdf->render();

		// Output the generated PDF to Browser
		$dompdf->stream($_SESSION['DatabaseName'] . '_BOMIndentedReverse_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = _('Reverse Indented BOM Listing');
		include('includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/maintenance.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';
		echo $HTML;
		include('includes/footer.php');
	}

} else { /*The option to print PDF was not hit so display form */

	$ViewTopic = 'Manufacturing';
	$BookMark = '';

	$Title=_('Reverse Indented BOM Listing');
	include('includes/header.php');

	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' .
		_('Search') . '" alt="" />' . ' ' . $Title . '</p>';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<fieldset>
			<legend>', _('Report Criteria'), '</legend>
		<field>
			<label for="Part">' . _('Part') . ':</label>
			<input type="text" autofocus="autofocus" required="required" title="" name="Part" size="20" />
			<fieldhelp>' ._('Enter the item code required to list the bill of material for') . '</fieldhelp>
		</field>
		</fieldset>
		<div class="centre">
			<input type="submit" name="PrintPDF" title="Produce PDF Report" value="' . _('Print PDF') . '" />
			<input type="submit" name="View" title="View Report" value="' . _('View') . '" />
		</div>
	</form>';

	include('includes/footer.php');

} /*end of else not PrintPDF */
