<?
use Bitrix\Main\Loader;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Driver;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Bizproc\Workflow\Entity\WorkflowInstanceTable;

Loader::includeModule('disk');
Loader::includeModule('crm');
Loader::includeModule('bizproc');

class CBpListEventHandlers
{
	public static function OnBeforeProlog()
	{
		$urlInfo = self::getInfoByURL();
		if ($urlInfo["entity"] == $urlInfo["type"] && $urlInfo["type"] == "bizproc") {
			self::modifyBpListPage();
		}
	}

	public static function OnProlog()
	{
	}

	private static function modifyBpListPage()
	{
		$list = self::getFiltredBpList();
		// self::addHtmlSection("<pre>!!!!!!!!!!!!! I'm here !!!!!!!!!!!!</pre>");
		$log = "<pre>" . var_export($list, true) . "</pre>";
		// self::addHtmlSection("<h4>???</h4>");
		self::print($log);
		$html = self::getHtmlByArray($list);
		self::print($html);
		self::addHtmlSection($html);
		self::print("Done");
	}

	// ------ HELPERS --------
	//
	// -----------------------

	private static function getHtmlByArray($row)
	{
		$result = '
			<table class="main-grid-table">
				<thead class="main-grid-header">
				<tr class="main-grid-row-head">
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Модуль</th>
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Документ</th>
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Дата изменения</th>
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Завис</th>
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Дата начала</th>
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Запустил</th>
					<th class="main-grid-cell-head main-grid-cell-left main-grid-col-no-sortable">Бизнесс процесс</th>
				</tr>
				</thead>
				<tbody>
		';
		foreach ($row as $item) {
			$result.= '
				<tr class="main-grid-row main-grid-row-body main-grid-cell main-grid-cell-left">
					<td class="main-grid-cell main-grid-cell-left">Процесс/td>
					<td class="main-grid-cell main-grid-cell-left">'.$item["_DOCUMENT_NAME"].'</td>
					<td class="main-grid-cell main-grid-cell-left">'.$item["WS_STARTED_BY"].'</td>
					<td class="main-grid-cell main-grid-cell-left">'.$item["WS_STARTED_BY"].'</td>
					<td class="main-grid-cell main-grid-cell-left">'.$item["WS_STARTED_BY"].'</td>
					<td class="main-grid-cell main-grid-cell-left">'.$item["_STARTED_BY"].'</td>
					<td class="main-grid-cell main-grid-cell-left">'.$item["_TEMPLATE_NAME"].'</td>
				</tr>
			';
		}
		$result.= "</tbody></table>";
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
			'filter' => ["WORKFLOW_TEMPLATE_ID" => 206], // $filter,
			'limit' => 10,
			'offset' => 0,
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


}