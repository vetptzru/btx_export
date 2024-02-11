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


	public static function OnEpilog()
	{
		// global $APPLICATION;
		// self::print("OnEpilog: BEGIN: ---------");
		// self::print("OnEpilog: END: ---------");
		// $r = $APPLICATION->EndBufferContentMan(); 
		// self::varPrint($r);
		// echo $r;
	}

	public static function OnEndBufferContent(&$content)
	{

		$newContent = $content;
		$hiddenPrice = '';

		$urlInfo = self::getInfoByURL();

		// echo "<pre>";
		// var_dump(self::getApplicationPage());
		// die();

		// if ($_REQUEST['action'] == 'crm.timeline.comment.load') {

		// 	$content = preg_replace("/\\\"html\\\"\:[ ]*\\\'.*\\\"/iU", 'html: ""', $content);

		// 	return;
		// }

		if (self::checkUserAccess()) {
			return;
		}

		if (self::getApplicationPage() == "/bitrix/components/bitrix/crm.timeline/ajax.php") {
			$json = json_decode($content);

			if (!$json) {
				return;
			}

			if (!$json->HISTORY_ITEMS) {
				return;
			}

			$result = [];
			foreach ($json->HISTORY_ITEMS as $item) {
				if (
					isset($item->layout) &&
					isset($item->layout->icon) &&
					isset($item->layout->icon->code) &&
					$item->layout->icon->code == 'comment'
				) {
					continue;
				}
				$result[] = $item;
			}
			$json->HISTORY_ITEMS = $result;

			$content = json_encode($json);

			return;
		}


		// $newContent = $content;
		// $hiddenPrice = '';


		// if ($urlInfo[""])



		if (self::shouldHideComments($urlInfo)) {
			$newContent = self::replaceHistoryDataComment($newContent);
			$newContent = self::replaceFixedDataComment($newContent);
		}

		if (self::shouldReplaceContent($urlInfo)) {

			$newContent = preg_replace("/'OPPORTUNITY'\:[ ]*'[0-9]{1,}\.[0-9]{1,}'/iU", "'OPPORTUNITY': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'OPPORTUNITY_ACCOUNT'\:[ ]*'[0-9]{1,}\.[0-9]{1,}'/iU", "'OPPORTUNITY_ACCOUNT': '" . $hiddenPrice . "'", $newContent);

			// CRM
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY_WITH_CURRENCY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY_WITH_CURRENCY': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY': '" . $hiddenPrice . "'", $newContent);


			// Kanban
			$newContent = preg_replace("/'entity_price'\:[ ]*'[^']{1,}'/iU", "'entity_price': '" . $hiddenPrice . "'", $newContent);
			$newContent = preg_replace("/'price_formatted'\:[ ]*'[^']{1,}'/iU", "'price_formatted': '" . $hiddenPrice . "'", $newContent);


			// Ajax
			$newContent = preg_replace("/\\\"ENTITY_AMOUNT\\\"\:[ ]*[0-9]{1,}\.[0-9]{1,}/iU", '"ENTITY_AMOUNT": "' . $hiddenPrice . '"', $newContent);
			$newContent = preg_replace("/\\\"TOTAL_AMOUNT\\\"\:[ ]*[0-9]{1,}\.[0-9]{1,}/iU", '"TOTAL_AMOUNT": "' . $hiddenPrice . '"', $newContent);

		}

		if ($urlInfo["entity"] == "crm" && $urlInfo["type"] == "deal" && $urlInfo["id"]) {
			// HTML
			$newContent = preg_replace("/<span class=\\\"main-grid-cell-content\\\"[^>]{1,}>[&nbsp;0-9\.]{1,} руб\.<\/span>/iU", '<span class="main-grid-cell-content">' . $hiddenPrice . '</span>', $newContent);
		}

		$content = $newContent;
	}

	public static function shouldReplaceContent($urlInfo)
	{
		if ($urlInfo["type"] == "deal") {
			return true;
		}
		return false;
	}

	private static function shouldHideComments($urlInfo)
	{
		if ($urlInfo["type"] == "deal" && $urlInfo["id"] > 0 && $urlInfo["isIframe"]) {
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

	private static function replaceHistoryDataComment($newContent) {
		if (preg_match("/historyData: (\[.*\}\]),[ \t\n]{1,}historyNavigation/iU", $newContent, $out)) {
			$json = json_decode('{"list":' . $out[1] . '}');
			$result = [];
			foreach ($json->list as $list) {
				if (
					isset($list->layout) &&
					isset($list->layout->icon) &&
					isset($list->layout->icon->code) &&
					$list->layout->icon->code == 'comment'
				) {
					continue;
				}
				$result[] = $list;
			}

			$jsonOut = json_encode($result);
			$newContent = preg_replace("/historyData: (\[.*\}\]),[ \t\n]{1,}historyNavigation/iU", "historyData: " . $jsonOut . ",historyNavigation", $newContent);
		}
		return $newContent;
	}

	private static function replaceFixedDataComment($newContent) {
		if (preg_match("/fixedData: (\[.*\}\]),[ \t\n]{1,}ajaxId/iU", $newContent, $out)) {
			$json = json_decode('{"list":' . $out[1] . '}');
			$result = [];
			foreach ($json->list as $list) {
				if (
					isset($list->layout) &&
					isset($list->layout->icon) &&
					isset($list->layout->icon->code) &&
					$list->layout->icon->code == 'comment'
				) {
					continue;
				}
				$result[] = $list;
			}

			$jsonOut = json_encode($result);
			$newContent = preg_replace("/fixedData: (\[.*\}\]),[ \t\n]{1,}ajaxId/iU", "historyData: " . $jsonOut . ",historyNavigation", $newContent);
		}
		return $newContent;
	}

	//---------
	/**
	 * Get type and id for page
	 */
	private static function getInfoByURL()
	{
		$currentUrl = self::getApplicationPage();
		$result = self::getUrlInfoByString($currentUrl);
		return $result;
	}

	private static function getApplicationPage()
	{
		global $APPLICATION;
		return $APPLICATION->GetCurPage();
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