<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main;
use Bitrix\Disk;

Main\Loader::IncludeModule('disk');

const UF_GROUP = 'UF_CRM_1679410842';
const UF_DISK = 'UF_CRM_1679410808';


class CRockotEventHandlers
{
	public static function OnBeforeProlog()
	{
		$type = RockotRequestHelper::getPageType();
		if ($type == 'deal') {
			CRockotEventHandlers::modifyDealPage();
			return;
		}
		if ($type === 'group') {
			CRockotEventHandlers::modifyGroupPage();
			return;
		}
	}

	public static function OnProlog() {}

	//---------------------------------------
	// Group page
	//---------------------------------------
	public static function modifyGroupPage() {
		$groupId = RockotRequestHelper::getPageId();
		$isAjaxRequest = RockotRequestHelper::isAjaxRequest();
		if (!$groupId || $isAjaxRequest) {
			return;
		}
		$deal = RockotGroup::findDealByGroupId($groupId);
		if ($deal) {
			RockotGroup::addLinkToGroupMenu("/crm/deal/details/".$deal["ID"]."/", "Сделка");
		}

		// Asset::getInstance()->addJs("/bitrix/js/rockot_links_crm/script.js", true);

	}

	//---------------------------------------
	// Deal page
	//---------------------------------------
	public static function modifyDealPage() {
		CModule::IncludeModule('crm');

		$dealId = RockotRequestHelper::getPageId();
		$isDealIframe = RockotRequestHelper::isPageInIframe();
		$isAjaxRequest = RockotRequestHelper::isAjaxRequest();
		
		if (!$dealId || !$isDealIframe || $isAjaxRequest) {
			return;
		}

		$links = RockotDeal::getGroupAndDiskLinksByDeal($dealId);

		if ($links["group"]) {
			RockotDeal::addLinkToMenu($links["group"], "Проект");
		}
		if ($links["disk"]) {
			RockotDeal::addLinkToMenu($links["disk"], "Диск");	
		}
		
		$bpList = RockotDeal::getBpListByDealId($dealId);

		// Asset::getInstance()->addJs("/bitrix/js/rockot_links_crm/script.js", true);

	}
}

///-----

class RockotRequestHelper {

	/**
	 * Get type and id for page
	 */
	public static function getInfoByURL() {
		global $APPLICATION;
		$currentUrl = $APPLICATION->GetCurPage();
		$result = RockotRequestHelper::getUrlInfoByString($currentUrl);
		return $result;
	}

	/**
	 * Get type and id by URL
	 */
	public static function getUrlInfoByString($currentUrl) {
		$result = ["entity" => "", "type" => "", "id" => ""];

		$parsed = parse_url($currentUrl);
		$parts = explode("/", $parsed["path"]);

		$result["entity"] = $parts[1];
		$result["type"] = $parts[2];

		if ($result["entity"] == "workgroups" && $result["type"] == "group") {
			$result["id"] = intval($parts[3]);
		} elseif ($result["entity"] == "crm" && $result["type"] == "deal") {
			$result["id"] =  intval($parts[4]);
		}
		return $result;
	}

	/**
	 * Get page type
	 */
	public static function getPageType() {
		return RockotRequestHelper::getInfoByURL()["type"];
	}

	/**
	 * Get page ID
	 */
	public static function getPageId() {
		return RockotRequestHelper::getInfoByURL()["id"];
	}

	/**
	 * Check is ajax request
	 */
	public static function isAjaxRequest() {
		if (isset($_REQUEST["ajax_request"]) && $_REQUEST["ajax_request"] === "Y") {
			return true;
		}
		return false;
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

class RockotDeal {

	/**
	 * Get group and disk links for deal
	 */
	static public function getGroupAndDiskLinksByDeal($dealId) {
		$result = ["group" => null, "disk" => null];
		CModule::IncludeModule('crm');
		$dbRes = CCrmDeal::GetListEx(
				[],
				["ID" => $dealId],
				false,
				false,
				[
					"UF_CRM_1679410842", // Group link
					"UF_CRM_1679410808" // Disk link
				]
		);
		while ($deal = $dbRes->Fetch()) {
				$result["group"] = $deal["UF_CRM_1679410842"];
				$result["disk"] = $deal["UF_CRM_1679410808"];
		}
		return $result;
	}

	/**
	 * Get list of BP for deal
	 */
	static public function getBpListByDealId() {
		$result = [];
		if (!CModule::IncludeModule("bizproc") || !CModule::IncludeModule("crm")) {
			return $result;
		}
		$documentType = ['crm', 'CCrmDocumentDeal', 'DEAL'];
		$documentId = 'DEAL_'.$dealId;

		$dbRes = CBPStateService::GetDocumentStates($documentType, $documentId);

		foreach ($documentStates as $state) {
			array_push($stack, $state);
		}
		return $result;
	}

	/**
	 * Get HTML template for menu item
	 */
	public static function getDealLinkTemplate($link, $title) {
		return '
			<div class="main-buttons-item"  title="">
				<a href="'.$link.'" class="main-buttons-item-link">
					<span class="main-buttons-item-icon"></span>
					<span class="main-buttons-item-text">
						<span class="main-buttons-item-drag-button" data-slider-ignore-autobinding="true"></span>
						<span class="main-buttons-item-text-title">
							<span class="main-buttons-item-text-box">
								'.$title.'
								<span class="main-buttons-item-menu-arrow">
							</span>
						</span>
					</span>
					<span class="main-buttons-item-edit-button" data-slider-ignore-autobinding="true"></span>
					<span class="main-buttons-item-text-marker"></span>
				</span>
				<span class="main-buttons-item-counter"></span>
			</a>
		</div>';
	}

	/**
	 * Get HTML template for menu item v2
	 */
	public static function getDealLinkTemplate2($link, $title) {
		return '
			<div class="main-buttons-item" id="" data-disabled="false" data-class="" data-id="" data-item="" data-top-menu-id="" title="" draggable="true" tabindex="-1" data-link="">
				<a href="'.$link.'" class="main-buttons-item-link">
					<span class="main-buttons-item-icon"></span>
					<span class="main-buttons-item-text">
						<span class="main-buttons-item-drag-button" data-slider-ignore-autobinding="true"></span>
						<span class="main-buttons-item-text-title">
							<span class="main-buttons-item-text-box">
								'.$title.'
								<span class="main-buttons-item-menu-arrow"></span>
							</span>
						</span>
						<span class="main-buttons-item-edit-button" data-slider-ignore-autobinding="true"></span>
						<span class="main-buttons-item-text-marker"></span>
					</span>
					<span class="main-buttons-item-counter"></span>
				</a>
			</div>
		';
	}

	/**
	 * Add link to deal menu
	 */
	public static function  addLinkToMenu($link, $title) {
		?>
		<script>
        document.addEventListener('DOMContentLoaded', function() {
						const customCardHtml = `<?=RockotDeal::getDealLinkTemplate2($link, $title)?>`;
						const container = document.querySelector(".main-buttons-inner-container");
						if (container) {
							container.insertAdjacentHTML('afterbegin', customCardHtml);
						}
        });
    </script>
		<?
	}

}

class RockotGroup {

	/**
	 * Add link to group menu
	 */
	public static function addLinkToGroupMenu($link, $title) {
		?>
		<script>
        document.addEventListener('DOMContentLoaded', function() {
						const customCardHtml = `<?=RockotGroup::getGroupLinkTemplate($link, $title)?>`;
						const container = document.querySelector(".main-buttons-inner-container");
						console.log(container);
						if (container) {
							container.insertAdjacentHTML('beforeend', customCardHtml);
						}
        });
    </script>
		<?
	}

	/**
	 * Get HTML template for menu item 
	 */
	public static function getGroupLinkTemplate($link, $title) {
		return '
			<div class="main-buttons-item tasks_role_link" id="" data-disabled="false" data-class="" data-id="view_role_auditor" data-locked="" data-top-menu-id="group_panel_menu_1" data-parent-item-id="view_all" title="" draggable="true" tabindex="-1" data-link="item-jco2rlut">
				<a class="main-buttons-item-link" href="'.$link.'">
					<span class="main-buttons-item-icon"></span><span class="main-buttons-item-text">
						<span class="main-buttons-item-drag-button" data-slider-ignore-autobinding="true"></span>
						<span class="main-buttons-item-text-title">
							<span class="main-buttons-item-text-box">'.$title.'</span>
						</span>
						<span class="main-buttons-item-edit-button" data-slider-ignore-autobinding="true"></span>
						<span class="main-buttons-item-text-marker"></span>
					</span>
					<span class="main-buttons-item-counter"></span>
				</a>
			</div>
		';
	}

	/**
	 * Find linked deal by group Id
	 */
	public static function findDealByGroupId($groupId) {
		if (CModule::IncludeModule('crm')) {
			$filter = ['!'.UF_GROUP => ''];
			$select = ['ID', 'TITLE', UF_GROUP];
	
			$dbRes = CCrmDeal::GetListEx([], $filter, false, false, $select);
			while ($deal = $dbRes->Fetch()) {
					RockotDebugger::dump($deal);
					if ($deal[UF_GROUP]) {
						$info = RockotRequestHelper::getUrlInfoByString($deal[UF_GROUP]);
						if ($info["id"] == $groupId) {
							return $deal;
						}
					}
			}	
		}
		return null;
	}
}

class RockotDebugger {

	/**
	 * Print message
	 */
	static public function dump($value) {
		echo "<pre>";
		var_dump($value);
		echo "</pre>";
	}


	/**
	 * Log message in file
	 */
	static public function print($message) {
		file_put_contents($_SERVER['DOCUMENT_ROOT']."/deb.log", $mes."\n", FILE_APPEND);
	}

	/**
	 * Write log to browser console
	 */
	static public function console($message) {
		?>
			<script>console.log(`<?=$message?>`)</script>
		<?
	}
}

//////////
// https://dev24.icstar.ru/workgroups/group/46/disk/path/
// https://dev24.icstar.ru/workgroups/group/208/
// 5025
// https://dev24.icstar.ru/online/?IM_DIALOG=chat5025
// https://dev24.icstar.ru/workgroups/group/208/tasks/