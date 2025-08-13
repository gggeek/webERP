<?php

/*Now this is not secure so a malicious user could send multiple emails of the report to the intended receipients

The intention is that this script is called from cron at intervals defined with a command like:

/usr/bin/wget http://localhost/web-erp/MailSalesReport.php

The configuration of this script requires the id of the sales analysis report to send
and an array of the receipients and the company database to use*/

/*The Sales report to send */
$ReportID = 4;


/* ----------------------------------------------------------------------------------------------*/

$AllowAnyone = true;
include('includes/session.php');
/*The company database to use */
$DatabaseName = $_SESSION['DatabaseName'];
/*The people to receive the emailed report, This mail list now can be maintained in Mailing List Maintenance of Set Up */

$Recipients = GetMailList('SalesAnalysisReportRecipients');
if (sizeOf($Recipients) == 0) {
	$Title = __('Inventory Valuation') . ' - ' . __('Problem Report');
	include('includes/header.php');
	prnMsg(__('There are no members of the Sales Analysis Report Recipients email group'), 'warn');
	include('includes/footer.php');
	exit();
}
include('includes/ConstructSQLForUserDefinedSalesReport.php');
include('includes/CSVSalesAnalysis.php');

$From = $_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>';
$Subject = __('Sales Analysis') . ' - ' . __('CSV Format');
$Body = __('Please find herewith the comma separated values sales report');
$Attachment = $_SESSION['reports_dir'] . '/SalesAnalysis.csv';

$Result = SendEmailFromWebERP($From, $Recipients, $Subject, $Body, $Attachment, true);
