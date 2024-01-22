<?
use Bitrix\Main\Loader;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Driver;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\Workflow\Entity\WorkflowInstanceTable;
use Bizproc\Workflow\Entity\WorkflowStateTable;
use Bitrix\Bizproc;

Loader::includeModule('disk');
Loader::includeModule('crm');
Loader::includeModule('bizproc');

class CBpListEventHandlers
{
	public static function OnBeforeProlog()
	{
	}

	public static function OnProlog()
	{
	}

	public static function OnEpilog()
	{

		// $urlInfo = self::getInfoByURL();
		// if ($urlInfo["entity"] == $urlInfo["type"] && $urlInfo["type"] == "bizproc") {
		// 	self::modifyBpListPage();
		// }



		if (!self::isNeededPage()) {
			return;
		}


		self::print("002");
		$document = self::getDocumentObject();
		if (!$document) {
			return;
		}

		self::print("003");
		self::varPrint($document);
		// self::getBpListByDeal($document["id"]);
		// self::addHtmlInFrame();

		if (!isset($document["clean"]) || !$document["clean"]["entity"] || !$document["clean"]["id"]) {
			return;
		}


		$table = self::htmlBpTable($document["clean"]["entity"], $document["clean"]["id"]);

		if (!$table) {
			return;
		}

		echo $table;

		self::print("004");
	}


	private static function htmlBpTable($type, $id)
	{
		if ($type != 'DEAL') {
			return false;
		}
		$dealId = "D_$id";

		/*
		$elements = CIBlockElement::GetList(
			[], // Критерии сортировки
			["IBLOCK_ID" => [16, 41, 73], "PROPERTY_PROEKT" => $dealId],
			false, // Параметры навигации
			false, // Параметры выборки
			['IBLOCK_NAME', 'ID', 'NAME', "DATE_CREATE", "IBLOCK_ID", "WF_STATUS_ID", "LOCK_STATUS", "USER_NAME", "PROPERTY_PROEKT"]
		);

		$html = '<h2 class="bizproc-document-section-title">Список БП</h2><ul class="bizproc-document-list bizproc-document-workflow-list-item">';

		while ($element = $elements->GetNext()) {
			// Обрабатываем каждый элемент и его свойства
			// var_dump($element);
			$html .= '<li class="bizproc-list-item bizproc-document-process bizproc-document-finished">';
			$html .= '<table class="bizproc-table-main" cellpadding="0" border="0"><thead><tr><th colspan="2">
					<span data-role="workflow-name">' . $element["IBLOCK_NAME"] . '</span></th></tr></thead><tbody><tr>
					<td colspan="2">' . $element["NAME"] . '</td></tr><tr>
					<td class="bizproc-field-name">Дата текущего состояния:</td>
					<td class="bizproc-field-value">' . $element["DATE_CREATE"] . '</td></tr><tr>
					<td class="bizproc-field-name">Текущий статус:</td>
					<td class="bizproc-field-value">Завершен</td></tr></tbody></table>';
			$html .= '</li>';
		}

		$html .= '</ul>';
		return $html;
		*/

		$bpList = self::getAllBpsByDealId($dealId);

		$html = '<h2 class="bizproc-document-section-title">Список БП</h2><ul class="bizproc-document-list bizproc-document-workflow-list-item">';

		foreach ($bpList as $element) {
			
			$status = $element['STATE'] == 'InProgress' ? 'В процессе' : 'Завершен';

			$html .= '<li class="bizproc-list-item bizproc-document-process bizproc-document-finished">';
			$html .= '<table class="bizproc-table-main" cellpadding="0" border="0"><thead><tr><th colspan="2">
					<span data-role="workflow-name">' . $element["IBLOCK_NAME"] . '</span></th></tr></thead><tbody><tr>
					<td colspan="2">' . $element["NAME"] . '</td></tr><tr>
					<td class="bizproc-field-name">Дата текущего состояния:</td>
					<td class="bizproc-field-value">' . $element["STARTED"] . '</td></tr><tr>
					<td class="bizproc-field-name">Текущий статус:</td>
					<td class="bizproc-field-value">'.$status.' ('.$element["STATE_TITLE"].')</td></tr></tbody></table>';
			$html .= '</li>';
		}
		$html .= '</ul>';

		return $html;

	}

	private static function getAllBpsByDealId($dealId) {
		global $DB;
		$strSql = "
			SELECT  
				el.ID as ELEMENT_ID,
				iblock.ID as IBLOCK_ID,
				iblock.NAME as IBLOCK_NAME,
				el.NAME,
				ws.DOCUMENT_ID,
				ws.WORKFLOW_TEMPLATE_ID,
				ws.STATE,
				ws.STATE_TITLE,
				ws.STARTED,
				ws.STARTED_BY,
				prop.VALUE as PROP_PROJECT,
				iblock_prop.CODE as PROP_CODE
			FROM 
				b_iblock_element AS el 
			INNER JOIN 
				b_bp_workflow_state AS ws 
				ON el.ID = ws.DOCUMENT_ID
			INNER JOIN 
				b_iblock_element_property AS prop 
				ON el.ID = prop.IBLOCK_ELEMENT_ID
			INNER JOIN 
				b_iblock_property AS iblock_prop
				ON prop.IBLOCK_PROPERTY_ID = iblock_prop.ID
			INNER JOIN 
				b_iblock AS iblock
				ON iblock.ID = el.IBLOCK_ID
			WHERE 1
				AND el.IBLOCK_ID IN (16, 41, 73)
				AND iblock_prop.CODE = 'PROEKT'
				AND prop.VALUE = '$dealId'
				AND ws.STATE IN ('InProgress', 'Completed')
			ORDER BY ws.STATE DESC
			LIMIT 1000
		";
		$dbRes = $DB->Query($strSql);
		$result = [];
		while ($arRes = $dbRes->Fetch()) {
			$result[] = $arRes;
		}
		return $result;
	} 



	private static function modifyBpListPage()
	{
		$list = self::getFiltredBpList();
		$html = self::getHtmlByArray($list);
		self::addHtmlSection($html);
	}

	// ------ HELPERS --------
	//
	// -----------------------

	private static function getHtmlByArray($row)
	{

		// <th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Дата изменения</span></th>
		// <th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Завис</span></th>
		// <th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Дата начала</span></th>


		// <td class="main-grid-cell main-grid-cell-left"><span class="main-grid-cell-content">'.$item["WS_STARTED_BY"].'</span></td>
		// <td class="main-grid-cell main-grid-cell-left"><span class="main-grid-cell-content">'.$item["WS_STARTED_BY"].'</span></td>
		// <td class="main-grid-cell main-grid-cell-left"><span class="main-grid-cell-content">'.$item["WS_STARTED_BY"].'</span></td>

		$result = '
			<div style="padding:10px;">
				<h3>Отфильтрованные бизнес процессы</h3>
				<table class="main-grid-table">
					<thead class="main-grid-header">
					<tr class="main-grid-row-head">
						<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Модуль</span></th>
						<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Документ</span></th>
						<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Запустил</span></th>
						<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable"><span class="main-grid-cell-head-container">Бизнесс процесс</span></th>
					</tr>
					</thead>
					<tbody>
		';
		foreach ($row as $item) {
			$result .= '
				<tr class="main-grid-row main-grid-row-body">
					<td class="main-grid-cell main-grid-cell-center">Процесс</td>
					<td class="main-grid-cell main-grid-cell-left"><span class="main-grid-cell-content"><a href="' . $item["_DOCUMENT_URL"] . '">' . $item["_DOCUMENT_NAME"] . '</a></span></td>
					<td class="main-grid-cell main-grid-cell-left"><span class="main-grid-cell-content">' . $item["_STARTED_BY"] . '</span></td>
					<td class="main-grid-cell main-grid-cell-left"><span class="main-grid-cell-content">' . $item["_TEMPLATE_NAME"] . '</span></td>
				</tr>
			';
		}
		$result .= "</tbody></table></div>";
		return $result;
	}


	public static function addHtmlSection($html)
	{
		/*?>
					<script>
						document.addEventListener('DOMContentLoaded', function () {
							const customCardHtml = `<?= $html ?>`;
							// const container = document.querySelector("#workarea-content");
							const container = document.querySelector(".workarea-content-paddings");

							if (container) {
								container.insertAdjacentHTML('beforeend', customCardHtml);
								// container.insertAdjacentHTML('afterbegin', customCardHtml);
							}
						});
					</script>
					<?*/
	}

	public static function addHtmlInFrame()
	{
		self::print("ADD me");
		/*?>
					<script>
						document.addEventListener('DOMContentLoaded', function () {
							const customCardHtml = `test`;
							// const container = document.querySelector("#workarea-content");
							const container = document.querySelector(".bizproc-page-document");
							console.log(container);

							if (container) {
								container.insertAdjacentHTML('beforeend', customCardHtml);
								// container.insertAdjacentHTML('afterbegin', customCardHtml);
							}
						});
					</script>
					<?*/
	}

	/**
	 * Print message
	 */
	static public function dump($value)
	{
		echo "<pre>";
		var_dump($value);
		echo "</pre>";
	}


	/**
	 * Log message in file
	 */
	static public function print($message)
	{
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/deb.log", $message . "\n", FILE_APPEND);
	}

	static public function varPrint($obj)
	{
		self::print(var_export($obj, true));
	}


	private static function isNeededPage()
	{
		global $APPLICATION;
		$currentUrl = $APPLICATION->GetCurPage();
		if (strripos($currentUrl, "bitrix/components/bitrix/bizproc.document/lazyload.ajax.php")) {
			return true;
		}
		return false;
	}

	private static function getDocumentId()
	{
		$payload = self::getPayload();
		return $payload["DOCUMENT_ID"];
	}

	private static function getDocumentObject()
	{
		$payload = self::getPayload();
		if (!$payload["MODULE_ID"] || !$payload["ENTITY"] || !$payload["DOCUMENT_TYPE"] || !$payload["DOCUMENT_ID"]) {
			return false;
		}
		$documentType = [$payload["MODULE_ID"], $payload["ENTITY"], $payload["DOCUMENT_TYPE"]];
		$documentId = [$payload["MODULE_ID"], $payload["ENTITY"], $payload["DOCUMENT_ID"]];
		list($entity, $id) = explode("_", $payload["DOCUMENT_ID"]);

		return ["type" => $documentType, "id" => $documentId, "clean" => ["entity" => $entity, "id" => $id]];
	}

	private static function getPayload()
	{
		$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
		$params = isset($componentData['params']) && is_array($componentData['params']) ? $componentData['params'] : [];

		$componentParams = [];
		$componentParams['MODULE_ID'] = $params['MODULE_ID'] ?? null;
		$componentParams['ENTITY'] = $params['ENTITY'] ?? null;
		$componentParams['DOCUMENT_TYPE'] = $params['DOCUMENT_TYPE'] ?? null;
		$componentParams['DOCUMENT_ID'] = $params['DOCUMENT_ID'] ?? null;

		return $componentParams;
	}


	/**
	 * Get type and id for page
	 */
	private static function getInfoByURL()
	{
		global $APPLICATION;
		$currentUrl = $APPLICATION->GetCurPage();
		$result = RockotRequestHelper::getUrlInfoByString($currentUrl);
		return $result;
	}

	/**
	 * Get type and id by URL
	 */
	public static function getUrlInfoByString($currentUrl)
	{
		$result = ["entity" => "", "type" => "", "id" => ""];

		$parsed = parse_url($currentUrl);
		$parts = explode("/", $parsed["path"]);

		$result["entity"] = $parts[1];
		$result["type"] = $parts[2];

		if ($result["entity"] == "workgroups" && $result["type"] == "group") {
			$result["id"] = intval($parts[3]);
		} elseif ($result["entity"] == "crm" && $result["type"] == "deal") {
			$result["id"] = intval($parts[4]);
		}
		return $result;
	}

	//----------


	static function getFiltredBpList()
	{
		$result = [];
		$templateList = self::getBpTemplateList();
		$select = [
			"ID",
			"MODIFIED",
			"OWNER_ID",
			"OWNED_UNTIL",
			"WS_MODULE_ID" => "MODULE_ID",
			"WS_ENTITY" => "ENTITY",
			"WS_DOCUMENT_ID" => "DOCUMENT_ID",
			"WS_STARTED" => "STARTED",
			"WS_STARTED_BY" => "STARTED_BY",
			"WS_WORKFLOW_TEMPLATE_ID" => "WORKFLOW_TEMPLATE_ID",
			"WS_STARTED_USER_NAME" => "STARTED_USER.NAME",
			"WS_STARTED_USER_LAST_NAME" => "STARTED_USER.LAST_NAME",
			"WS_STARTED_USER_LOGIN" => "STARTED_USER.LOGIN"
		];

		$iterator = WorkflowInstanceTable::getList([
			'order' => [],
			'select' => $select,
			'filter' => ["WORKFLOW_TEMPLATE_ID" => [206, 20, 18, 110]], // $filter,
			'limit' => 500,
			'offset' => 0
		]);


		while ($row = $iterator->fetch()) {

			$templateName = self::getBpTemplateName($row, $templateList);
			$row["_TEMPLATE_NAME"] = $templateName;

			$documentName = self::getBpDocumentName($row);
			$row["_DOCUMENT_NAME"] = $documentName;

			$startedBy = self::getStartedBy($row);
			$row["_STARTED_BY"] = $startedBy;

			$documentUrl = self::getBpDocumentUrl($row);
			$row["_DOCUMENT_URL"] = $documentUrl;

			// self::dump($row);
			$result[] = $row;
		}
		return $result;
	}


	static function getBpDocumentName($row)
	{
		return CBPDocument::getDocumentName([
			$row['WS_MODULE_ID'],
			$row['WS_ENTITY'],
			$row['WS_DOCUMENT_ID'],
		]);
	}

	static function getBpTemplateName($row, $templatesList)
	{
		return $row['WS_WORKFLOW_TEMPLATE_ID'] && isset($templatesList[$row['WS_WORKFLOW_TEMPLATE_ID']])
			? $templatesList[$row['WS_WORKFLOW_TEMPLATE_ID']]
			: null;
	}

	static function getBpTemplateList()
	{
		$templatesFilter = [];
		$templatesList = ['' => ""];
		$dbResTmp = \CBPWorkflowTemplateLoader::GetList(
			['NAME' => 'ASC'],
			$templatesFilter,
			false,
			false,
			['ID', 'NAME']
		);
		while ($arResTmp = $dbResTmp->GetNext()) {
			$templatesList[$arResTmp['ID']] = $arResTmp['NAME'];
		}
		return $templatesList;
	}

	static function getBpDocumentUrl($row)
	{
		return CBPDocument::GetDocumentAdminPage([
			$row['WS_MODULE_ID'],
			$row['WS_ENTITY'],
			$row['WS_DOCUMENT_ID'],
		]);
	}




	static function getStartedBy($row)
	{
		if (!empty($row['WS_STARTED_BY'])) {
			return CUser::FormatName(
				"#LAST_NAME# #NAME# #SECOND_NAME#",
				[
					'LOGIN' => $row['WS_STARTED_USER_LOGIN'],
					'NAME' => $row['WS_STARTED_USER_NAME'],
					'LAST_NAME' => $row['WS_STARTED_USER_LAST_NAME'],
				],
				true
			) . " [" . $row['WS_STARTED_BY'] . "]";
		}
		return "";
	}


	static function getBpListByDeal($documentId)
	{
		$workflows = [];
		$size = 20;

		// self::print("doc: ".var_export($documentId, true));

		$filter = [
			// '=MODULE_ID' => $documentId[0],
			// '=ENTITY' => $documentId[1],
			// '=DOCUMENT_ID' => $documentId[2],
			'=MODULE_ID' => "crm",
			'=ENTITY' => "CCrmDocumentDeal",
			'=DOCUMENT_ID' => $documentId[2],
			'=INSTANCE.ID' => null,
			'WORKFLOW_TEMPLATE_ID' => 206 // TODO: add more 
		];

		// self::print("doc: ".var_export($filter, true));

		// if ($ids) {
		// 	$filter = [
		// 		'@ID' => $ids,
		// 		'=INSTANCE.ID' => null,
		// 	];
		// }

		$rows = Bizproc\Workflow\Entity\WorkflowStateTable::getList([
			'select' => [
				'ID',
				'TEMPLATE_NAME' => 'TEMPLATE.NAME',
				'STATE_TITLE',
				'STATE_NAME' => 'STATE',
				'MODIFIED',
				'WORKFLOW_TEMPLATE_ID'
			],
			'filter' => $filter,
			'limit' => 1000,
			'offset' => 0,
			'order' => ['MODIFIED' => 'DESC'],
		])->fetchAll();


		self::print(var_export($rows, true));
	}


}