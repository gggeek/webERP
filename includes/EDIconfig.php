<?php

/*EDI configuration variables definition */

/*EDI Draft version 01B - Controlling agency is the UN - EAN version control number (EAN Code)
this info is required in the header record of every message sent - prepended with the message type*/

$EDIHeaderMsgId = 'D:01B:UN:EAN010';

/*EDI Reference of the company */

$EDIReference = 'WEBERP';

/* EDI Messages for sending directory */

$EDI_MsgPending = 'EDI_Pending';

/* EDI Messages sent directory */

$EDI_MsgSent = 'EDI_Sent';

//Testing variables
include('includes/session.php');
include('includes/header.php');
$PartnerCode='WALMON';
$MessageType ='INVOIC';

/*end of testing variables / code */

include('includes/SQL_CommonFunctions.php');

$EDITrans = GetNextTransNo(99);

/* Get the message lines for the heading
replace variable names with data
write the output to a file one line at a time

Need code of supplier or customer and the type of message INVOIC or ORDERS*/

$SQL = "SELECT section,
		linetext
	FROM edimessageformat
	WHERE partnercode='" . $PartnerCode . "'
	AND messagetype='" . $MessageType . "'
	ORDER BY sequenceno";

$MessageLinesResult = DB_query($SQL);

$fp = fopen( $EDI_Pending . '/EDI_' . $MessageType . '_' . $EDITrans , 'w');

while ($LineDetails = DB_fetch_array($MessageLinesResult)){

	$PoistionPointer = 0;
	$NewLineText ='';
	/* now get each occurence of [ in the line */
	while (mb_strpos ($LineDetails['linetext'],'[',$PoistionPointer)!=False){
		$LastPositionPointer = $PoistionPointer;
		$PositionPointer = mb_strpos ($LineDetails['linetext'],'[',$PoistionPointer);

		$NewLineText = $NewLineText .  mb_substr($LineDetails['linetext'],$LastPositionPointer,$PoistionPointer-$LastPositionPointer);

		$LastPositionPointer = $PoistionPointer;
		$PositionPointer = mb_strpos ($LineDetails['linetext'],']',$PoistionPointer);

		$VariableName = mb_substr($LineDetails['linetext'],$LastPositionPointer,$PoistionPointer-$LastPositionPointer);

		$NewLineText = $NewLineText . $$VariableName;

	}
	/* now add the text from the last ] to the end of the line */
	$LastPositionPointer = $PoistionPointer;
	$NewLineText = $NewLineText .  mb_substr($LineDetails['linetext'],$LastPositionPointer);

	echo "<BR>$NewLineText";

	fputs($fp, $NewLineText ."\n");
}

fclose($fp);

include('includes/footer.php');
