<?php

include('includes/session.php');
$Title = __('Maintain General Ledger Tags');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLTags';

include('includes/header.php');

if (isset($_GET['SelectedTag'])) {
	if ($_GET['Action'] == 'delete') {
		//first off test there are no transactions created with this tag
		$Result = DB_query("SELECT counterindex
							FROM gltrans
							WHERE tag='" . $_GET['SelectedTag'] . "'");
		if (DB_num_rows($Result) > 0) {
			prnMsg(__('This tag cannot be deleted since there are already general ledger transactions created using it.') , 'error');
		}
		else {
			$Result = DB_query("DELETE FROM tags WHERE tagref='" . $_GET['SelectedTag'] . "'");
			prnMsg(__('The selected tag has been deleted') , 'success');
		}
		$Description = '';
	}
	else {
		$SQL = "SELECT tagref,
					tagdescription
				FROM tags
				WHERE tagref='" . $_GET['SelectedTag'] . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$Ref = $MyRow['tagref'];
		$Description = $MyRow['tagdescription'];
	}
}
else {
	$Description = '';
	$_GET['SelectedTag'] = '';
}

if (isset($_POST['submit'])) {
	$SQL = "INSERT INTO tags values(NULL, '" . $_POST['Description'] . "')";
	$Result = DB_query($SQL);
}

if (isset($_POST['update'])) {
	$SQL = "UPDATE tags SET tagdescription='" . $_POST['Description'] . "'
		WHERE tagref='" . $_POST['reference'] . "'";
	$Result = DB_query($SQL);
}
echo '<p class="page_title_text">
		<img src="' . $RootPath, '/css/', $Theme, '/images/maintenance.png" title="' . __('Print') . '" alt="" />' . ' ' . $Title . '
	</p>';

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" id="form">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($_GET['Action']) AND $_GET['Action'] == 'edit') {
	echo '<fieldset>
			<legend>', __('Edit Tag details') , '</legend>';
}
else {
	echo '<fieldset>
			<legend>', __('Create Tag details') , '</legend>';
}
echo '<field>
			<label for="Description">' . __('Description') . '</label>
			<input type="text" required="required" autofocus="autofocus" size="30" maxlength="30" name="Description" title="" value="' . $Description . '" />
			<fieldhelp>' . __('Enter the description of the general ledger tag up to 30 characters') . '</fieldhelp>
			<input type="hidden" name="reference" value="' . $_GET['SelectedTag'] . '" />
		</field>
	</fieldset>';

echo '<div class="centre">';
if (isset($_GET['Action']) AND $_GET['Action'] == 'edit') {
	echo '<input type="submit" name="update" value="' . __('Update') . '" />';
}
else {
	echo '<input type="submit" name="submit" value="' . __('Insert') . '" />';
}
echo '</div>
	</form>';

echo '<table class="selection">
	<tr>
		<th>' . __('Tag ID') . '</th>
		<th>' . __('Description') . '</th>
	</tr>';

$SQL = "SELECT tagref,
			tagdescription
		FROM tags
		ORDER BY tagref";

$Result = DB_query($SQL);

while ($MyRow = DB_fetch_array($Result)) {
	echo '<tr>
			<td>' . $MyRow['tagref'] . '</td>
			<td>' . $MyRow['tagdescription'] . '</td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTag=' . $MyRow['tagref'] . '&amp;Action=edit">' . __('Edit') . '</a></td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTag=' . $MyRow['tagref'] . '&amp;Action=delete" onclick="return confirm(\'' . __('Are you sure you wish to delete this GL tag?') . '\');">' . __('Delete') . '</a></td>
		</tr>';
}

echo '</table>';

include('includes/footer.php');
