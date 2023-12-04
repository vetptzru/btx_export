<?
use Bitrix\Main\Page\Asset;


class CRockotEventHandlers
{
	public static function OnBeforeProlog()
	{
    CRockotEventHandlers::modifyDealPage();
	}

	public static function OnProlog() {}


	//------
	public static function modifyDealPage() {
		$dealId = CRockotEventHandlers::getDealId();
		$isDealIframe = CRockotEventHandlers::getDealId();
		if (!$dealId || !$isDealIframe) {
			return;
		}
		// ню "Сделка", "Диск", "Чат" - В сделке добавить ссылки на "Проект", "Диск", "БП сделки"
		?>
		<script>
        document.addEventListener('DOMContentLoaded', function() {
						const customCardHtml = `<?=CRockotEventHandlers::getDealLinkTemplate()?>`;
						const container = document.querySelector("#crm_scope_detail_c_deal_");
						if (container) {
							container.insertAdjacentHTML('beforeend', customCardHtml);
						}
        });
    </script>
		<?php
		Asset::getInstance()->addJs("/bitrix/js/rockot_links_crm/script.js", true);
	}


	public static function isDealIframe() {
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$qwe = $request->getQueryList()->toArray();
		return $qwe["IFRAME"] == "Y";
	}

	public static function getDealId() {
		global $APPLICATION;
		$currentUrl = $APPLICATION->GetCurPage();
		_print_($currentUrl);
		$urlParts = explode('/', $currentUrl);
		$dealId = $urlParts[count($urlParts) - 2];
		return $dealId;
	}

	public static function getDealLinkTemplate() {
		return '
			<div class="main-buttons-item"  title="">
				<a href="/workgroups/group/" class="main-buttons-item-link">
					<span class="main-buttons-item-icon"></span>
					<span class="main-buttons-item-text">
						<span class="main-buttons-item-drag-button" data-slider-ignore-autobinding="true"></span>
						<span class="main-buttons-item-text-title">
							<span class="main-buttons-item-text-box">
								Группы
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

}

function _print_($mes) {
	// file_put_contents("/Applications/MAMP/logs/deb.log", $mes."\n", FILE_APPEND);
}