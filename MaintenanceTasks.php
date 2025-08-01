<?php


include('includes/session.php');

$Title = _('Fixed Asset Maintenance Tasks');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetMaintenance';

include('includes/header.php');

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/group_add.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';


if (isset($_POST['Submit'])) {
	if (!is_numeric(filter_number_format($_POST['FrequencyDays'])) OR filter_number_format($_POST['FrequencyDays']) < 0){
		prnMsg(_('The days before a task falls due is expected to be a postive'),'error');
	} else {
		$SQL="INSERT INTO fixedassettasks (assetid,
											taskdescription,
											frequencydays,
											userresponsible,
											manager,
											lastcompleted)
						VALUES( '" . $_POST['AssetID'] . "',
								'" . $_POST['TaskDescription'] . "',
								'" . filter_number_format($_POST['FrequencyDays']) . "',
								'" . $_POST['UserResponsible'] . "',
								'" . $_POST['Manager'] . "',
								'" . Date('Y-m-d') . "' )";
		$ErrMsg = _('The authentication details cannot be inserted because');
		$Result=DB_query($SQL,$ErrMsg);
		unset($_POST['AssetID']);
		unset($_POST['TaskDescription']);
		unset($_POST['FrequencyDays']);
		unset($_POST['Manager']);
		unset($_POST['UserResponsible']);
	}
}

if (isset($_POST['Update'])) {
	if (!is_numeric(filter_number_format($_POST['FrequencyDays'])) OR filter_number_format($_POST['FrequencyDays']) < 0){
		prnMsg(_('The days before a task falls due is expected to be a postive'),'error');
	} else {
		$SQL="UPDATE fixedassettasks SET
				assetid = '" . $_POST['AssetID'] . "',
				taskdescription='".$_POST['TaskDescription'] ."',
				frequencydays='" . filter_number_format($_POST['FrequencyDays'])."',
				userresponsible='" . $_POST['UserResponsible'] . "',
				manager='" . $_POST['Manager'] . "'
				WHERE taskid='".$_POST['TaskID']."'";

		$ErrMsg = _('The task details cannot be updated because');
		$Result=DB_query($SQL,$ErrMsg);
		unset($_POST['AssetID']);
		unset($_POST['TaskDescription']);
		unset($_POST['FrequencyDays']);
		unset($_POST['Manager']);
		unset($_POST['UserResponsible']);
	}
}

if (isset($_GET['Delete'])) {
	$SQL="DELETE FROM fixedassettasks
		WHERE taskid='".$_GET['TaskID']."'";

	$ErrMsg = _('The maintenance task cannot be deleted because');
	$Result=DB_query($SQL,$ErrMsg);
}

$SQL="SELECT taskid,
				fixedassettasks.assetid,
				description,
				taskdescription,
				frequencydays,
				lastcompleted,
				userresponsible,
				realname,
				manager
		FROM fixedassettasks
		INNER JOIN fixedassets
		ON fixedassettasks.assetid=fixedassets.assetid
		INNER JOIN www_users
		ON fixedassettasks.userresponsible=www_users.userid";

$ErrMsg = _('The maintenance task details cannot be retrieved because');
$Result=DB_query($SQL,$ErrMsg);

echo '<table class="selection">
     <tr>
		<th>' . _('Task ID') . '</th>
		<th>' . _('Asset') . '</th>
		<th>' . _('Description') . '</th>
		<th>' . _('Last Completed') . '</th>
		<th>' . _('Person') . '</th>
		<th>' . _('Manager') . '</th>
    </tr>';

while ($MyRow=DB_fetch_array($Result)) {

	if ($MyRow['manager']!=''){
		$ManagerResult = DB_query("SELECT realname FROM www_users WHERE userid='" . $MyRow['manager'] . "'");
		$ManagerRow = DB_fetch_array($ManagerResult);
		$ManagerName = $ManagerRow['realname'];
	} else {
		$ManagerName = _('No Manager Set');
	}

	echo '<tr>
			<td>' . $MyRow['taskid'] . '</td>
			<td>' . $MyRow['description'] . '</td>
			<td>' . $MyRow['taskdescription'] . '</td>
			<td>' . ConvertSQLDate($MyRow['lastcompleted']) . '</td>
			<td>' . $MyRow['realname'] . '</td>
			<td>' . $ManagerName . '</td>
			<td><a href="'.$RootPath.'/MaintenanceTasks.php?Edit=Yes&amp;TaskID=' . $MyRow['taskid'] .'">' . _('Edit') . '</a></td>
			<td><a href="'.$RootPath.'/MaintenanceTasks.php?Delete=Yes&amp;TaskID=' . $MyRow['taskid'] .'" onclick="return confirm(\'' . _('Are you sure you wish to delete this maintenance task?') . '\');">' . _('Delete') . '</a></td>
		</tr>';
}

echo '</table>';


echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="form1">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<fieldset>';

if (isset($_GET['Edit'])) {
	echo '<legend>', _('Edit Maintenance Task'), '</legend>';
	echo '<field>
			<label for="TaskID">' . _('Task ID') . '</label>
			<fieldtext>' . $_GET['TaskID'] . '</fieldtext>
		</field>';
	echo '<input type="hidden" name="TaskID" value="'.$_GET['TaskID'].'" />';
	$SQL="SELECT assetid,
				taskdescription,
				frequencydays,
				lastcompleted,
				userresponsible,
				manager
			FROM fixedassettasks
			WHERE taskid='".$_GET['TaskID']."'";
	$ErrMsg = _('The maintenance task details cannot be retrieved because');
	$Result=DB_query($SQL,$ErrMsg);
	$MyRow=DB_fetch_array($Result);
	$_POST['TaskDescription'] = $MyRow['taskdescription'];
	$_POST['FrequencyDays'] = $MyRow['frequencydays'];
	$_POST['UserResponsible'] = $MyRow['userresponsible'];
	$_POST['Manager'] = $MyRow['manager'];
	$_POST['AssetID'] = $MyRow['assetid'];
} else {
	echo '<legend>', _('Create Maintenance Task'), '</legend>';
}

if (!isset($_POST['TaskDescription'])){
	$_POST['TaskDescription']='';
}
if (!isset($_POST['FrequencyDays'])){
	$_POST['FrequencyDays']='';
}
if (!isset($_POST['UserResponsible'])){
	 $_POST['UserResponsible']= '';
}
if (!isset($_POST['Manager'])){
	$_POST['Manager']='';
}
if (!isset($_POST['AssetID'])){
	$_POST['AssetID']='';
}

echo '<field>
		<label for="AssetID">' . _('Asset to Maintain').':</label>
		<select required="required" name="AssetID">';
$AssetSQL="SELECT assetid, description FROM fixedassets";
$AssetResult=DB_query($AssetSQL);
while ($MyRow=DB_fetch_array($AssetResult)) {
	if ($MyRow['assetid']==$_POST['AssetID']) {
		echo '<option selected="selected" value="'.$MyRow['assetid'].'">' . $MyRow['assetid'] . ' - ' . $MyRow['description']  . '</option>';
	} else {
		echo '<option value="'.$MyRow['assetid'].'">' . $MyRow['assetid'] . ' - ' . $MyRow['description']  . '</option>';
	}
}
echo '</select>
	</field>';

echo '<field>
		<label for="TaskDescription">' . _('Task Description').':</label>
		<textarea name="TaskDescription" required="required" cols="40" rows="3">' . $_POST['TaskDescription'] . '</textarea>
	</field>';

echo '<field>
		<label for="TaskDescription">' . _('Days Before Task Due').':</label>
		<input type="text" class="integer" required="required" name="FrequencyDays" size="5" maxlength="5" value="' . $_POST['FrequencyDays'] . '" />
	</field>';

echo '<field>
		<label for="UserResponsible">' . _('Responsible') . ':</label>
		<select required="required" name="UserResponsible">';
$UserSQL="SELECT userid FROM www_users";
$UserResult=DB_query($UserSQL);
while ($MyRow=DB_fetch_array($UserResult)) {
	if ($MyRow['userid']==$_POST['UserResponsible']) {
		echo '<option selected="selected" value="'.$MyRow['userid'].'">' . $MyRow['userid'] . '</option>';
	} else {
		echo '<option value="'.$MyRow['userid'].'">' . $MyRow['userid'] . '</option>';
	}
}
echo '</select>
	</field>';

echo '<field>
		<label for="Manager">' . _('Manager').':</label>
		<select required="required" name="Manager">';
if ($_POST['Manager']==''){
	echo '<option selected="selected" value="">' . _('No Manager') . '</option>';
} else {
	echo '<option value="">' . _('No Manager') . '</option>';
}
$ManagerSQL="SELECT userid FROM www_users";
$ManagerResult=DB_query($UserSQL);
while ($MyRow=DB_fetch_array($ManagerResult)) {
	if ($MyRow['userid']==$_POST['Manager']) {
		echo '<option selected="selected" value="'.$MyRow['userid'].'">' . $MyRow['userid'] . '</option>';
	} else {
		echo '<option value="'.$MyRow['userid'].'">' . $MyRow['userid'] . '</option>';
	}
}
echo '</select>
	</field>
</fieldset>';

if (isset($_GET['Edit'])) {
	echo '<div class="centre">
			<input type="submit" name="Update" value="'._('Update Task').'" />
		</div>';
} else {
	echo '<div class="centre">
			<input type="submit" name="Submit" value="'._('Enter New Task').'" />
		</div>';
}
echo '</form>';
include('includes/footer.php');
