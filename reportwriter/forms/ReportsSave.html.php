<?php echo '<html lang="' . str_replace('_', '-', substr($Language, 0, 5)) . ">"; ?>
<head></head>
<body>
<h2 align="center"><?php echo RPT_RPTFRM.$Prefs['reportname'].' - '.RPT_PAGESAVE; ?></h2>
<form name="reporthome" method="post" action="ReportMaker.php?action=go">
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
  <input name="ReportID" type="hidden" value="<?php echo $ReportID; ?>">
  <input name="GoBackURL" type="hidden" value="<?php echo $GoBackURL; ?>">
  <table width="400" align="center" border="1" cellspacing="1" cellpadding="1">
	<tr>
		<td colspan="3"><div align="center"><?php echo RPT_RPTENTER; ?></div></td>
	</tr>
	<tr>
		<td colspan="3"><div align="center">
		  <input name="ReportName" type="text" value="<?php echo $Prefs['reportname']; ?>" size="32" maxlength="30">
		  </div></td>
	</tr>
  </table>
  <table width="400" align="center" border="0" cellspacing="1" cellpadding="1">
	<tr>
	  <td><input name="todo" type="submit" id="todo" value="<?php echo RPT_BTN_CANCEL; ?>"></td>
		<?php if ($ShowReplace) { ?>
	  <td><div align="center"><input type="submit" name="todo" id="todo" value="<?php echo RPT_BTN_REPLACE; ?>"></div></td>
		<?php } else echo '<td>&nbsp;</td>'; ?>
	  <td><div align="right"><input type="submit" name="todo" id="todo" value="<?php echo RPT_BTN_SAVE; ?>"></div></td>
    </tr>
  </table>
</form>
</body>
</html>
