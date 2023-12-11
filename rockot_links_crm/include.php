<?
use Bitrix\Main\Page\Asset;
use Bitrix\Main;
use  Bitrix\Disk;

Main\Loader::IncludeModule('disk');

class CRockotEventHandlers
{
	public static function OnBeforeProlog()
	{
		$type = CRockotEventHandlers::getPageType();
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

	//------
	public static function modifyGroupPage() {
		$groupId = CRockotEventHandlers::getPageId();
		if (!$groupId) {
			return;
		}
		//----
		$deal = getItemById($groupId);
		// getDiskByGroupId($groupId);
		//---
		if ($deal) {
			CRockotEventHandlers::addLinkToGroupMenu("/crm/deal/details/".$deal["ID"]."/", "Сделка");
		}
		// CRockotEventHandlers::addLinkToGroupMenu("/workgroups/group/$groupId/disk/path/", "Диск");
		// CRockotEventHandlers::addLinkToGroupMenu("/online/", "Чат");
		Asset::getInstance()->addJs("/bitrix/js/rockot_links_crm/script.js", true);
	}
	//------
	public static function modifyDealPage() {
		$dealId = CRockotEventHandlers::getPageId();
		$isDealIframe = CRockotEventHandlers::isDealIframe();
		if (!$dealId || !$isDealIframe) {
			return;
		}
		CRockotEventHandlers::addLinkToMenu("", "Проект");
		CRockotEventHandlers::addLinkToMenu("", "Диск");
		Asset::getInstance()->addJs("/bitrix/js/rockot_links_crm/script.js", true);
		//-----
		$deal = CCrmDeal::GetByID($dealId);
		echo "<pre>";
		var_dump($deal);
		echo "</pre>";
	}

	//------
	public static function addLinkToGroupMenu($link, $title) {
		?>
		<script>
        document.addEventListener('DOMContentLoaded', function() {
						const customCardHtml = `<?=CRockotEventHandlers::getGroupLinkTemplate($link, $title)?>`;
						const container = document.querySelector(".main-buttons-inner-container");
						console.log(container);
						if (container) {
							container.insertAdjacentHTML('beforeend', customCardHtml);
						}
        });
    </script>
		<?
	}

	public static function addLinkToMenu($link, $title) {
		?>
		<script>
        document.addEventListener('DOMContentLoaded', function() {
						const customCardHtml = `<?=CRockotEventHandlers::getDealLinkTemplate($link, $title)?>`;
						const container = document.querySelector("#crm_scope_detail_c_deal_");
						if (container) {
							container.insertAdjacentHTML('beforeend', customCardHtml);
						}
        });
    </script>
		<?
	}


	public static function isDealIframe() {
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$qwe = $request->getQueryList()->toArray();
		return $qwe["IFRAME"] == "Y";
	}

	public static function getPageType() {
		return CRockotEventHandlers::getInfoByURL()["type"];
	}

	public static function getPageId() {
		return CRockotEventHandlers::getInfoByURL()["id"];
	}

	public static function getInfoByURL() {
		global $APPLICATION;
		$currentUrl = $APPLICATION->GetCurPage();
		$urlParts = explode('/', $currentUrl);
		$page = $urlParts[count($urlParts) - 4];
		$type = $urlParts[count($urlParts) - 3];
		$dealId = $urlParts[count($urlParts) - 2];
		$result = ["type" => $type, "id" => $dealId];
		if ($page === "deal") {
			$result["type"] = "deal";
		} else if ($page === "group") {
			$result["type"] = "group";
			$result["id"] = $urlParts[3];
		}
		return $result;
	}

	public static function getGroupId() {

	}

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

	//---------

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

}

function getItemById($itemId) {
	// UF_CRM_1679410842
	if (CModule::IncludeModule('socialnetwork')) {
    $arGroup = CSocNetGroup::GetByID($itemId);
    if ($arGroup) {
				echo "<pre>";
        print_r($arGroup);
				echo "</pre>";
				die();
    }
	}
	$deal = getDealForGroup($itemId);
	return $deal;
}

function getDealForGroup($groupId) {
	if (CModule::IncludeModule('tasks') && CModule::IncludeModule('crm')) {
    $dbRes = CTasks::GetList(
        [],
        ["GROUP_ID" => $groupId],
        ["UF_CRM_TASK"]
    );
    while ($task = $dbRes->Fetch()) {
        // print_r($task);
				foreach ($task['UF_CRM_TASK'] as $crmElement) {
					if (strpos($crmElement, 'D_') === 0) {
							$dealId = substr($crmElement, 2); // Извлекаем ID сделки
							$deal = CCrmDeal::GetByID($dealId);
							if ($deal) {
									// print_r($deal);
									// echo "Название сделки: " . $deal['TITLE'] . "<br>";
									return $deal;
							}
					}
			}
    }
	}
	return null;
}

function getDiskByGroupId($groupId) {
	if (!CModule::IncludeModule('disk')) {
		// echo("none");
    // die('Модуль "Диск" не найден');
	}
	$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByGroupId($groupId);
	if ($storage) {
    $folder = $storage->getRootObject();
    if ($folder) {
        $urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
        // $folderUrl = $urlManager->encodeUrn($urlManager->getPathFolderList($folder->getStorageId(), $folder->getId()));
        // echo "Ссылка на корневую папку диска: " . $folderUrl;
    }
	}
	//
	//https://dev24.icstar.ru/workgroups/group/46/disk/path/
	//https://dev24.icstar.ru/workgroups/group/208/
	// 5025
	// https://dev24.icstar.ru/online/?IM_DIALOG=chat5025
	// https://dev24.icstar.ru/workgroups/group/208/tasks/
}

function _print_($mes) {
	// file_put_contents("/Applications/MAMP/logs/deb.log", $mes."\n", FILE_APPEND);
}