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

class CHideItemsEventHandlers
{

	private static $crmGroupId = 8;


	public static function OnEpilog() {
		// global $APPLICATION;
		// self::print("OnEpilog: BEGIN: ---------");
		// self::print("OnEpilog: END: ---------");
		// $r = $APPLICATION->EndBufferContentMan(); 
		// self::varPrint($r);
		// echo $r;
	}

	public static function OnEndBufferContent(&$content)
	{

		// return;

		$urlInfo = self::getInfoByURL();

		if ($_REQUEST['action'] == 'crmmobile.Controller.EntityDetails.load') {

			self::print("OnEndBufferContent: BEGIN: ---------");
			global $APPLICATION;
			// self::print("UrlInfo: ");
			// self::varPrint($urlInfo);
			// self::print("Request: ");
			// self::varPrint($_REQUEST);
			self::print("Content: ");
			self::varPrint($content);
			// self::print("Input: ");
			// self::varPrint(file_get_contents('php://input'));
			self::print("App buffered: ");
			self::varPrint(get_class_methods($APPLICATION));

			// if ($APPLICATION->buffered) {
				// $cc = $APPLICATION->EndBufferContent();
				// self::varPrint($cc);
				// $APPLICATION->StartBufferContent();

			// }
			self::print("OnEndBufferContent: END: ---------");

		}



		if (self::shouldReplaceContent($urlInfo) && !self::checkUserAccess()) {

			$hiddenPrice = '';

			$newContent = $content;
			$newContent = preg_replace("/'OPPORTUNITY'\:[ ]*'[0-9]{1,}\.[0-9]{1,}'/iU", "'OPPORTUNITY': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'OPPORTUNITY_ACCOUNT'\:[ ]*'[0-9]{1,}\.[0-9]{1,}'/iU", "'OPPORTUNITY_ACCOUNT': '" . $hiddenPrice . "'", $newContent);

			// CRM
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY_WITH_CURRENCY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY_WITH_CURRENCY': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY': '" . $hiddenPrice . "'", $newContent);


			// Kanban
			$newContent = preg_replace("/[\\]?'entity_price[\\]?'\:[ ]*[\\]?'[\w+\.]+[\\]?'/iU", "'entity_price': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/[\\]?'price_formatted[\\]?'\:[ ]*[\\]?'[\w+\.]+[\\]?'/iU", "'price_formatted': '" . $hiddenPrice . "'", $newContent);

			self::print("Kanban");
			self::varPrint($newContent);


			// Ajax
			$newContent = preg_replace("/\\\"ENTITY_AMOUNT\\\"\:[ ]*[0-9]{1,}\.[0-9]{1,}/iU", '"ENTITY_AMOUNT": "' . $hiddenPrice . '"', $newContent);
			$newContent = preg_replace("/\\\"TOTAL_AMOUNT\\\"\:[ ]*[0-9]{1,}\.[0-9]{1,}/iU", '"TOTAL_AMOUNT": "' . $hiddenPrice . '"', $newContent);

		}

		if ($urlInfo["entity"] == "crm" && $urlInfo["type"] == "deal" && $urlInfo["id"]) {
			// HTML
			$newContent = preg_replace("/<span class=\\\"main-grid-cell-content\\\"[^>]{1,}>[&nbsp;0-9\.]{1,} руб\.<\/span>/iU", '<span class="main-grid-cell-content">' . $hiddenPrice . '</span>', $newContent);
			$content = $newContent;
		}
	}

	public static function shouldReplaceContent($urlInfo)
	{
		// if ($urlInfo["type"] == "deal" && $urlInfo["id"] > 0 && $urlInfo["isIframe"]) {
		if ($urlInfo["type"] == "deal") {
			return true;
		}
		return false;
	}

	private static function checkUserAccess()
	{
		global $USER;
		global $DB;

		if (!$USER) {
			return false;
		}

		$userId = "U" . $USER->GetID();
		$roleId = self::$crmGroupId;
		$result = false;

		$strSql = "SELECT * FROM b_crm_role_relation WHERE RELATION = '$userId' AND ROLE_ID = $roleId";

		$dbRes = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
		while ($arRes = $dbRes->Fetch()) {
			$result = true;
		}
		return $result;

	}

	//---------
	/**
	 * Get type and id for page
	 */
	private static function getInfoByURL()
	{
		global $APPLICATION;
		$currentUrl = $APPLICATION->GetCurPage();
		$result = self::getUrlInfoByString($currentUrl);
		return $result;
	}

	/**
	 * Get type and id by URL
	 */
	public static function getUrlInfoByString($currentUrl)
	{
		$result = ["entity" => "", "type" => "", "id" => "", "isIframe" => self::isPageInIframe()];

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

	/**
	 * Check is iframe page
	 */
	public static function isPageInIframe()
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$queryList = $request->getQueryList()->toArray();
		return $queryList["IFRAME"] == "Y";
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
}