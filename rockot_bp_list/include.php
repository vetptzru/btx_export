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

		// $urlInfo = self::getInfoByURL();P
		// if ($urlInfo["entity"] == $urlInfo["type"] && $urlInfo["type"] == "bizproc") {
		// 	self::modifyBpListPage();
		// }

		self::print("001");
		if (!self::isNeededPage()) {
			return;
		}


		self::print("002");
		$document = self::getDocumentObject();
		if (!$document) {
			return;
		}

		self::print("003");
		self::getBpListByDeal($document["id"]);

		self::print("004");

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
			<h3></h3>
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
		$result .= "</tbody></table>";
		return $result;
	}


	public static function addHtmlSection($html)
	{
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const customCardHtml = `<?= $html ?>`;
				// const container = document.querySelector("#workarea-content");
				const container = document.querySelector(".workarea-content-paddings");

				if (container) {
					// container.insertAdjacentHTML('beforeend', customCardHtml);
					container.insertAdjacentHTML('afterbegin', customCardHtml);
				}
			});
		</script>
		<?
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

		return ["type" => $documentType, "id" => $documentId];
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


	static function getFiltredBpList($document)
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
			$row["DOCUMENT_URL"] = $documentUrl;

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

		self::print("doc: ".var_export($documentId, true));

		$filter = [
			// '=MODULE_ID' => $documentId[0],
			// '=ENTITY' => $documentId[1],
			// '=DOCUMENT_ID' => $documentId[2],
			'=MODULE_ID' => "crm",
			'=ENTITY' => "CCrmDocumentDeal",
			'=DOCUMENT_ID' => $documentId[2],
			'=INSTANCE.ID' => null,
			'WORKFLOW_TEMPLATE_ID' => [206, 20, 18, 110]
		];

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
			],
			'filter' => $filter,
			'limit' => 100,
			'offset' => 0,
			'order' => ['MODIFIED' => 'DESC'],
		])->fetchAll();


		self::print(var_export($rows, true));
	}


}