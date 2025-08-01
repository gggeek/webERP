<?php
if ($PageNumber>1){
	$pdf->newPage();
}

$YPos = $Page_Height - $Top_Margin - 50;

$pdf->addJpegFromFile($_SESSION['LogoFile'],$Left_Margin,$YPos,0,50);

$FontSize=15;

Switch ($_POST['TransType']) {
	case 10:
		$TransType=_('Customer Invoices');
		break;
	case 11:
		$TransType=_('Customer Credit Notes');
		break;
	case 12:
		$TransType=_('Customer Payments');
}

$XPos = $Left_Margin;
$YPos -= 40;
$pdf->addText($XPos, $YPos,$FontSize, $_SESSION['CompanyRecord']['coyname']);
$FontSize=12;
$pdf->addText($XPos, $YPos-20,$FontSize, $TransType . ' ' ._('input on') . ' ' . $_POST['Date']);

$XPos = $Page_Width-$Right_Margin-50;
$YPos -=30;
$pdf->addText($XPos, $YPos,$FontSize, _('Page') . ': ' . $PageNumber);

/*Now print out the company name and address */
$XPos = $Left_Margin;
$YPos -= $LineHeight;

/*draw a square grid for entering line items */
$pdf->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);
$pdf->line($Page_Width-$Right_Margin, $YPos,$Page_Width-$Right_Margin, $Bottom_Margin);
$pdf->line($Page_Width-$Right_Margin, $Bottom_Margin,$XPos, $Bottom_Margin);
$pdf->line($XPos, $Bottom_Margin,$XPos, $YPos);

$YPos -= $LineHeight;
/*Set up headings */
$FontSize=8;

$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,160,$FontSize,_('Customer'), 'centre');
$LeftOvers = $pdf->addTextWrap($Left_Margin+162,$YPos,80,$FontSize,_('Reference'), 'centre');
$LeftOvers = $pdf->addTextWrap($Left_Margin+242,$YPos,70,$FontSize,_('Trans Date'), 'centre');
$LeftOvers = $pdf->addTextWrap($Left_Margin+312,$YPos,70,$FontSize,_('Net Amount'), 'right');
$LeftOvers = $pdf->addTextWrap($Left_Margin+382,$YPos,70,$FontSize,_('Tax Amount'), 'right');
$LeftOvers = $pdf->addTextWrap($Left_Margin+452,$YPos,70,$FontSize,_('Total Amount'), 'right');
$YPos-=$LineHeight;

/*draw a line */
$pdf->line($XPos, $YPos,$Page_Width-$Right_Margin, $YPos);

$YPos -= ($LineHeight);
