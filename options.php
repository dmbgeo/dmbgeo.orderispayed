<?php

use Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc;

$module_id = 'dmbgeo.orderispayed';
$module_path = str_ireplace($_SERVER["DOCUMENT_ROOT"], '', __DIR__) . $module_id . '/';
CModule::IncludeModule('main');
CModule::IncludeModule($module_id);
CModule::IncludeModule('sale');
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);
if ($APPLICATION->GetGroupRight($module_id) < "S") {
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}
$db_dtype = \CSaleStatus::GetList();

$saleStatuses=Array();
while ($ar_dtype = $db_dtype->Fetch()){
	$saleStatuses[$ar_dtype['ID']]=$ar_dtype['NAME'];
}


	$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();

$SITES = \OrderIsPayed::getSites();

foreach ($SITES as $SITE) {
	$aTabs[] = array(
		'DIV' => $SITE['LID'],
		'TAB' => $SITE['NAME'],
		'OPTIONS' => array(
			array('OPTION_STATUS_' . $SITE['LID'], Loc::getMessage('OPTION_STATUS'), 'Y', array('checkbox', 1)),
			array('OPTION_ORDER_TIME_' . $SITE['LID'], Loc::getMessage('OPTION_TIME'), '20', array('text', 40)),
			array('OPTION_ORDER_CANCEL_MESSAGE_' . $SITE['LID'], Loc::getMessage('OPTION_ORDER_CANCEL_MESSAGE'), 'Заказ не оплачен в данное покупателю время!', array('text', 40)),
		),
	);
	$params[] = 'OPTION_STATUS_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_TIME_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_CANCEL_MESSAGE_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_STATUS_N_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_STATUS_Y_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_FILTER_STATUS_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_FILTER_DELIVERY_' . $SITE['LID'];
	$params[] = 'OPTION_ORDER_FILTER_PAY_' . $SITE['LID'];
}
$aTabs[] = array(
	'DIV' => "agent",
	'TAB' => Loc::getMessage('AGENT_SETTING'),
	'OPTIONS' => array(
		array('OPTION_AGENT_STATUS', Loc::getMessage('OPTION_AGENT_STATUS'), '', array('checkbox', "Y")),
		array('OPTION_AGENT_INTERVAL', Loc::getMessage('OPTION_AGENT_INTERVAL'), '3600', array('text', 20)),
	),
);
$params[] = 'OPTION_AGENT_STATUS';
$params[] = 'OPTION_AGENT_INTERVAL';

if ($request->isPost() && $request['Apply'] && check_bitrix_sessid()) {

	foreach ($params as $param) {
		if (array_key_exists($param, $_POST) === true) {

			Option::set($module_id, $param, is_array($_POST[$param]) ? implode(",", $_POST[$param]) : $_POST[$param]);
		} else {
			Option::set($module_id, $param, "N");
		}
	}
	if (($_POST["OPTION_AGENT_STATUS"] ?? "N") == "Y") {
		$newInterval = $_POST["OPTION_AGENT_INTERVAL"] ?? 0;
		if (is_numeric($newInterval) && $newInterval > 60) {
			$newInterval = intval($newInterval);
		} else {
			$newInterval = 3600;
			Option::set($module_id, "OPTION_AGENT_INTERVAL", $newInterval);
		}
		deleteAgent($module_id);
		createAgent($module_id, $newInterval);
	} else {
		deleteAgent($module_id);
	}
}
function deleteAgent($module_id)
{
	\CAgent::RemoveModuleAgents($module_id);
}

function createAgent($module_id, $newInterval)
{
	$interval = intval($newInterval);
	$arFields = array();
	$result = \CAgent::AddAgent(
		'\OrderIsPayed::Agent();', // имя функции
		$module_id, // идентификатор модуля
		"N", // агент не критичен к кол-ву запусков
		$interval, // интервал запуска - 1 сутки
		date("d.m.Y H:i:s", (time() + $interval)), // дата первой проверки - текущее
		"Y", // агент активен
		date("d.m.Y H:i:s", time()), // дата первого запуска - текущее
		1
	);
}

$tabControl = new CAdminTabControl('tabControl', $aTabs);

?>
<? $tabControl->Begin(); ?>

<form method='post' action='<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request['lang'] ?>' name='DMBGEO_settings'>

	<? $n = count($aTabs); ?>
	<? foreach ($aTabs as $key => $aTab) :
		if ($aTab['OPTIONS']) : ?>
			<? $tabControl->BeginNextTab(); ?>
			<? __AdmSettingsDrawList($module_id, $aTab['OPTIONS']); ?>
			<? if ($aTab['DIV'] !== "agent") : ?>
				<tr>
					<?
					$OPTION_ORDER_STATUS_N = COption::GetOptionString($module_id, 'OPTION_ORDER_STATUS_N_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_STATUS_N"); ?><a name="opt_OPTION_ORDER_STATUS_N_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="1" id='OPTION_ORDER_STATUS_N_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_STATUS_N_<?= $aTab['DIV']; ?>">


						<? foreach ($saleStatuses as $key => $status) : ?>
								<?
								$option = '';
								$option .= '<option value="' . $key . '"';
								if ($key == $OPTION_ORDER_STATUS_N) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $status;
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endforeach; ?>

						</select>
					</td>
				</tr>
				<tr>
					<?
					$OPTION_ORDER_STATUS_Y = COption::GetOptionString($module_id, 'OPTION_ORDER_STATUS_Y_' . $aTab['DIV']);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_STATUS_Y"); ?><a name="opt_OPTION_ORDER_STATUS_Y_<?= $aTab['DIV']; ?>"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="1" id='OPTION_ORDER_STATUS_Y_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_STATUS_Y_<?= $aTab['DIV']; ?>">


					

							<? foreach ($saleStatuses as $key => $status) : ?>
								<?
								$option = '';
								$option .= '<option value="' .$key . '"';
								if ($key == $OPTION_ORDER_STATUS_Y) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $status;
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endforeach; ?>

						</select>
					</td>
				</tr>
				<tr>
					<?
					$OPTION_ORDER_FILTER_STATUS = COption::GetOptionString($module_id, 'OPTION_ORDER_FILTER_STATUS_' . $aTab['DIV']);
					$OPTION_ORDER_FILTER_STATUS = explode(',', $OPTION_ORDER_FILTER_STATUS);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_FILTER_STATUS"); ?><a name="opt_OPTION_ORDER_FILTER_STATUS_<?= $aTab['DIV']; ?>[]"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="5" multiple id='OPTION_ORDER_FILTER_STATUS_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_FILTER_STATUS_<?= $aTab['DIV']; ?>[]">

							<? foreach ($saleStatuses as $key => $status) : ?>
								<?
								$option = '';
								$option .= '<option value="' . $key . '"';
								if (in_array($key, $OPTION_ORDER_FILTER_STATUS)) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $status;
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endforeach; ?>

						</select>
					</td>
				</tr>
				<tr>
					<?
					$OPTION_ORDER_FILTER_DELIVERY = COption::GetOptionString($module_id, 'OPTION_ORDER_FILTER_DELIVERY_' . $aTab['DIV']);
					$OPTION_ORDER_FILTER_DELIVERY = explode(',', $OPTION_ORDER_FILTER_DELIVERY);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_FILTER_DELIVERY"); ?><a name="opt_OPTION_ORDER_FILTER_DELIVERY_<?= $aTab['DIV']; ?>[]"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="5" multiple id='OPTION_ORDER_FILTER_DELIVERY_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_FILTER_DELIVERY_<?= $aTab['DIV']; ?>[]">


							<? $db_dtype = \CSaleDelivery::GetList(
								array(),
								array(
									"ACTIVE" => "Y"
								)
							);
							?>

							<? while ($ar_dtype = $db_dtype->Fetch()) : ?>
								<?
								$option = '';
								$option .= '<option value="' . $ar_dtype['ID'] . '"';
								if (in_array($ar_dtype['ID'], $OPTION_ORDER_FILTER_DELIVERY)) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $ar_dtype['NAME'];
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endwhile; ?>

						</select>
					</td>
				</tr>
				<tr>
					<?
					$OPTION_ORDER_FILTER_PAY = COption::GetOptionString($module_id, 'OPTION_ORDER_FILTER_PAY_' . $aTab['DIV']);
					$OPTION_ORDER_FILTER_PAY = explode(',', $OPTION_ORDER_FILTER_PAY);
					?>
					<td class="adm-detail-valign-top adm-detail-content-cell-l" width="50%"><? echo Loc::getMessage("OPTION_ORDER_FILTER_PAY"); ?><a name="opt_OPTION_ORDER_FILTER_PAY_<?= $aTab['DIV']; ?>[]"></a></td>
					<td width="50%" class="adm-detail-content-cell-r">
						<select size="5" multiple id='OPTION_ORDER_FILTER_PAY_<?= $aTab['DIV']; ?>' name="OPTION_ORDER_FILTER_PAY_<?= $aTab['DIV']; ?>[]">


							<? $db_dtype = \CSalePaySystem::GetList(
								array(),
								array(
									"ACTIVE" => "Y"
								)
							);
							?>

							<? while ($ar_dtype = $db_dtype->Fetch()) : ?>
								<?
								$option = '';
								$option .= '<option value="' . $ar_dtype['ID'] . '"';
								if (in_array($ar_dtype['ID'], $OPTION_ORDER_FILTER_PAY)) {
									$option .= ' selected="selected" ';
								}
								$option .= '>';
								$option .= $ar_dtype['NAME'];
								$option .= '</option>';
								?>
								<? echo $option; ?>
							<? endwhile; ?>

						</select>
					</td>
				</tr>
			<? endif ?>

		<? endif ?>
	<? endforeach; ?>
	<?

	$tabControl->Buttons(); ?>

	<input type="submit" name="Apply" value="<? echo GetMessage('MAIN_SAVE') ?>">
	<input type="reset" name="reset" value="<? echo GetMessage('MAIN_RESET') ?>">
	<?= bitrix_sessid_post(); ?>
</form>
<? $tabControl->End(); ?>