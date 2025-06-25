<?php

include('includes/session.php');
if (isset($_POST['StartDate'])){$_POST['StartDate'] = ConvertSQLDate($_POST['StartDate']);};
$Title = _('Import Sales Price List');
$ViewTopic = 'SpecialUtilities';
$BookMark = basename(__FILE__, '.php'); ;
include('includes/header.php');
echo '<p class="page_title_text"><img alt="" src="' . $RootPath . '/css/' . $Theme .
		'/images/maintenance.png" title="' .
		_('Import Price List from CSV file') . '" />' . ' ' .
		_('Import Price List from CSV file') . '</p>';

$FieldHeadings = array(
	'StockID',			//  0 'STOCKID',
	'SalesType',		//  1 'Price list id',
	'CurrencyCode',		//  2 'Currency Code',
	'Price'				//  3 'Price'
);

if (isset($_FILES['PriceListFile']) and $_FILES['PriceListFile']['name']) { //start file processing
	//check file info
	$FileName = $_FILES['PriceListFile']['name'];
	$TempName  = $_FILES['PriceListFile']['tmp_name'];
	$FileSize = $_FILES['PriceListFile']['size'];
	$FieldTarget = 4;
	$InputError = 0;

	//get file handle
	$FileHandle = fopen($TempName, 'r');

	//get the header row
	$HeadRow = fgetcsv($FileHandle, 10000, ',');

	//check for correct number of fields
	if ( count($HeadRow) != count($FieldHeadings) ) {
		prnMsg (_('File contains') . ' '. count($HeadRow). ' ' . _('columns, expected') . ' '. count($FieldHeadings). '. ' . _('Download the template to see the expected columns.'),'error');
		fclose($FileHandle);
		include('includes/footer.php');
		exit();
	}

	//test header row field name and sequence
	$HeadingColumnNumber = 0;
	foreach ($HeadRow as $HeadField) {
		if ( trim(mb_strtoupper($HeadField)) != trim(mb_strtoupper($FieldHeadings[$HeadingColumnNumber]))) {
			prnMsg (_('The file to import the price list from contains incorrect column headings') . ' '. mb_strtoupper($HeadField). ' != '. mb_strtoupper($FieldHeadings[$HeadingColumnNumber]). '<br />' . _('The column headings must be') . ' StockID, SalesType, CurrencyCode, Price','error');
			fclose($FileHandle);
			include('includes/footer.php');
			exit();
		}
		$HeadingColumnNumber++;
	}

	//start database transaction
	DB_Txn_Begin();

	//loop through file rows
	$LineNumber = 1;
	while ( ($MyRow = fgetcsv($FileHandle, 10000, ',')) !== FALSE ) {

		//check for correct number of fields
		$FieldCount = count($MyRow);
		if ($FieldCount != $FieldTarget){
			prnMsg ($FieldTarget . ' ' . _('fields required') . ', '. $FieldCount. ' ' . _('fields received'),'error');
			fclose($FileHandle);
			include('includes/footer.php');
			exit();
		}

		// cleanup the data (csv files often import with empty strings and such)
		$StockID = mb_strtoupper($MyRow[0]);
		foreach ($MyRow as &$Value) {
			$Value = trim($Value);
			$Value = str_replace('"', '', $Value);
		}

		//first off check that the item actually exist
		$SQL = "SELECT COUNT(stockid) FROM stockmaster WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL);
		$testrow = DB_fetch_row($Result);
		if ($testrow[0] == 0) {
			$InputError = 1;
			prnMsg (_('Stock item') . ' "'. $MyRow[0]. '" ' . _('does not exist'),'error');
		}
		//Then check that the price list actually exists
		$SQL = "SELECT COUNT(typeabbrev) FROM salestypes WHERE typeabbrev='" . $MyRow[1] . "'";
		$Result = DB_query($SQL);
		$testrow = DB_fetch_row($Result);
		if ($testrow[0] == 0) {
			$InputError = 1;
			prnMsg (_('SalesType/Price List') . ' "' . $MyRow[1]. '" ' . _('does not exist'),'error');
		}

		//Then check that the currency code actually exists
		$SQL = "SELECT COUNT(currabrev) FROM currencies WHERE currabrev='" . $MyRow[2] . "'";
		$Result = DB_query($SQL);
		$testrow = DB_fetch_row($Result);
		if ($testrow[0] == 0) {
			$InputError = 1;
			prnMsg (_('Currency') . ' "' . $MyRow[2] . '" ' . _('does not exist'),'error');
		}

		//Finally force the price to be a double
		$MyRow[3] = (double)$MyRow[3];
		if ($InputError !=1){

			//Firstly close any open prices for this item
			$SQL = "UPDATE prices
						SET enddate='" . FormatDateForSQL($_POST['StartDate']) . "'
						WHERE stockid='" . $StockID . "'
						AND enddate>'" . date('Y-m-d') . "'
						AND typeabbrev='" . $MyRow[1] . "'";
			$Result = DB_query($SQL);

			//Insert the price
			$SQL = "INSERT INTO prices (stockid,
										typeabbrev,
										currabrev,
										price,
										startdate
									) VALUES (
										'" . $MyRow[0] . "',
										'" . $MyRow[1] . "',
										'" . $MyRow[2] . "',
										'" . $MyRow[3] . "',
										'" . FormatDateForSQL($_POST['StartDate']) . "')";

			$ErrMsg =  _('The price could not be added because');
			$DbgMsg = _('The SQL that was used to add the price failed was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
		}

		if ($InputError == 1) { //this row failed so exit loop
			break;
		}
		$LineNumber++;
	}

	if ($InputError == 1) { //exited loop with errors so rollback
		prnMsg(_('Failed on row '. $LineNumber. '. Batch import has been rolled back.'),'error');
		DB_Txn_Rollback();
	} else { //all good so commit data transaction
		DB_Txn_Commit();
		prnMsg( _('Batch Import of') .' ' . $FileName  . ' '. _('has been completed. All transactions committed to the database.'),'success');
	}

	fclose($FileHandle);

} else { //show file upload form

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint" enctype="multipart/form-data">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<div class="page_help_text">' .
			_('This function loads a new sales price list from a comma separated variable (csv) file.') . '<br />' .
			_('The file must contain four columns, and the first row should be the following headers:') . '<br />StockID, SalesType, CurrencyCode, Price<br />' .
			_('followed by rows containing these four fields for each price to be uploaded.') .  '<br />' .
			_('The StockID, SalesType, and CurrencyCode fields must have a corresponding entry in the stockmaster, salestypes, and currencies tables.') . '</div>';

	echo '<fieldset>
			<legend>', _('Import Criteria'), '</legend>
			<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
			<field>
				<label>', _('Prices effective from') . ':</label>
				<input name="StartDate" maxlength="10" size="11" type="date" value="' . date('Y-m-d') . '" />
			</field>
			<field>
				<label>', _('Upload file') . ':</label>
				<input name="PriceListFile" type="file" />
			</field>
			</fieldset>
			<div class="centre">
			<input type="submit" name="submit" value="' . _('Send File') . '" />
		</div>
		</form>';

}

include('includes/footer.php');

?>
