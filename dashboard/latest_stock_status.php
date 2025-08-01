<?php

if (basename($_SERVER['SCRIPT_NAME']) != 'Dashboard.php') {
	// allow dashboard applet to run standalone
	$DirectoryLevelsDeep = 1;
	$PathPrefix = __DIR__ . '/../';
	require_once ($PathPrefix . 'includes/session.php');
	$DashBoardURL = $RootPath . '/index.php';
}

	$ScriptTitle = _('Stock Status');

	$SQL = "SELECT DISTINCT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
	$DashboardResult = DB_query($SQL);
	$DashboardRow = DB_fetch_array($DashboardResult);

	echo '<table class="DashboardTable">
			<thead>
				<tr>
					<th colspan="4">
						<div class="CanvasTitle">', $ScriptTitle, '
						<a class="CloseButton" href="', $DashBoardURL, '?Remove=', urlencode($DashboardRow['id']), '" target="_parent" title="', _('Remove this applet from dashboard'), '" id="CloseButton" href="#">X</a>
						</div>
					</th>
				</tr>';

	$SQL = "SELECT id FROM dashboard_scripts WHERE scripts='" . basename(basename(__FILE__)) . "'";
	$DashboardResult = DB_query($SQL);
	$DashboardRow = DB_fetch_array($DashboardResult);

	$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.longdescription,
						stockmaster.mbflag,
						stockmaster.discontinued,
						SUM(locstock.quantity) AS qoh,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster
					LEFT JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid,
						locstock
					WHERE stockmaster.stockid=locstock.stockid
					GROUP BY stockmaster.stockid,
						stockmaster.description,
						stockmaster.longdescription,
						stockmaster.units,
						stockmaster.mbflag,
						stockmaster.discontinued,
						stockmaster.decimalplaces
					ORDER BY stockmaster.discontinued, stockmaster.stockid LIMIT 5";
	$SearchResult = DB_query($SQL);

	echo '<tr>
			<th class="SortedColumn">', _('Code'), '</th>
			<th class="SortedColumn">', _('Description'), '</th>
			<th>', _('Total Quantity on Hand'), '</th>
			<th>', _('Units'), '</th>
		</tr>
	</thead>';

	echo '<tbody>';
	while ($Row = DB_fetch_array($SearchResult)) {
		$QOH = locale_number_format($Row['qoh'], $Row['decimalplaces']);

		echo '<tr class="striped_row">
			<td>', $Row['stockid'], '</td>
			<td>', $Row['description'], '</td>
			<td class="number">', $QOH, '</td>
			<td> ', $Row['units'], '</td>
		</tr>';

	}

	echo '</tbody>
	</table>';
