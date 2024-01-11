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
		self::addHtmlSection("111111");
		self::print("GOGOGO!");
		self::getFiltredBpList();
	}

	// ------ HELPERS --------
	//
	// -----------------------

	public static function addHtmlSection($html)
	{
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const customCardHtml = `<?= $html ?>`;
				const container = document.querySelector("#workarea-content");
				if (container) {
					container.insertAdjacentHTML('beforeend', customCardHtml);
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
			'filter' => ["WORKFLOW_TEMPLATE_ID" => 16], // $filter,
			'limit' => 10,
			'offset' => 0,
		]);




		self::print("Before while");

		while ($row = $iterator->fetch()) {

			$templateName = self::getBpTemplateName($row, $templateList);
			$row["_TEMPLATE_NAME"] = $templateName;

			$documentName = self::getBpDocumentName($row);
			$row["_DOCUMENT_NAME"] = $documentName;

			$startedBy = self::getStartedBy($row);
			$row["_STARTED_BY"] = $startedBy;

			$documentUrl = self::getBpDocumentUrl($row);
			$row["DOCUMENT_URL"] = $documentUrl;

			self::dump($row);
		}
	}


	static function getBpDocumentName($row)
	{
		return CBPDocument::getDocumentName([
			$row['MODULE_ID'],
			$row['ENTITY'],
			$row['DOCUMENT_ID'],
		]);
	}

	static function getBpTemplateName($row, $templatesList)
	{
		return $row['WORKFLOW_TEMPLATE_ID'] && isset($templatesList[$row['WORKFLOW_TEMPLATE_ID']])
			? $templatesList[$row['WORKFLOW_TEMPLATE_ID']]
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
			$row['MODULE_ID'],
			$row['ENTITY'],
			$row['DOCUMENT_ID'],
		]);
	}




	static function getStartedBy($row)
	{
		if (!empty($row['STARTED_BY'])) {
			return CUser::FormatName(
				"#LAST_NAME# #NAME# #SECOND_NAME#",
				[
					'LOGIN' => $row['STARTED_USER_LOGIN'],
					'NAME' => $row['STARTED_USER_NAME'],
					'LAST_NAME' => $row['STARTED_USER_LAST_NAME'],
				],
				true
			) . " [" . $row['STARTED_BY'] . "]";
		}
		return "";
	}


}