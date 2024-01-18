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

	public static function OnEndBufferContent(&$content)
	{
		$urlInfo = self::getInfoByURL();

		// $content = var_export($urlInfo, true);
		// return; 
		
		if (self::shouldReplaceContent($urlInfo) && !self::checkUserAccess()) {

			$hiddenPrice = '';
			
			$newContent = $content;
			$newContent = preg_replace("/'OPPORTUNITY'\:[ ]*'[0-9]{1,}\.[0-9]{1,}'/iU", "'OPPORTUNITY': '".$hiddenPrice."'", $newContent);
			$newContent = preg_replace("/'OPPORTUNITY_ACCOUNT'\:[ ]*'[0-9]{1,}\.[0-9]{1,}'/iU", "'OPPORTUNITY_ACCOUNT': '".$hiddenPrice."'", $newContent);

			// CRM
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY_WITH_CURRENCY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY_WITH_CURRENCY': '".$hiddenPrice."'", $newContent);
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY_ACCOUNT_WITH_CURRENCY': '".$hiddenPrice."'", $newContent);
			$newContent = preg_replace("/'FORMATTED_OPPORTUNITY'\:[ ]*'[^']{1,}'/iU", "'FORMATTED_OPPORTUNITY': '".$hiddenPrice."'", $newContent);

			// Kanban
			$newContent = preg_replace("/'entity_price'\:[ ]*'[^']{1,}'/iU", "'entity_price': '".$hiddenPrice."'", $newContent);
			$newContent = preg_replace("/'price_formatted'\:[ ]*'[^']{1,}'/iU", "'price_formatted': '".$hiddenPrice."'", $newContent);


			// Ajax
			$newContent = preg_replace("/\\\"ENTITY_AMOUNT\\\"\:[ ]*[0-9]{1,}\.[0-9]{1,}/iU", '"ENTITY_AMOUNT": "'.$hiddenPrice.'"', $newContent);
			$newContent = preg_replace("/\\\"TOTAL_AMOUNT\\\"\:[ ]*[0-9]{1,}\.[0-9]{1,}/iU", '"TOTAL_AMOUNT": "'.$hiddenPrice.'"', $newContent);
			
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

	private static function checkUserAccess() {
		global $USER;
		$arGroups = CUser::GetUserGroup($USER->GetID());
		$arGroups = CUser::GetUserGroup($USER->GetID());
    if (in_array(self::$crmGroupId, $arGroups)) {
        return true;
    }
		return false;
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
	public static function isPageInIframe() {
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$queryList = $request->getQueryList()->toArray();
		return $queryList["IFRAME"] == "Y";
	}
}