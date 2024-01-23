<?
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Class rockot_hide_items extends CModule
{
	var $MODULE_ID = "rockot_hide_items";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	public function __construct() {
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    $this->MODULE_NAME = "Rockot: скрытия информации в сделки";
		$this->MODULE_DESCRIPTION = "скрытия некторой информации в сделки для пользователей";
	}

	function DoInstall() {
    $this->InstallFiles();
    $this->InstallDB();
		ModuleManager::registerModule($this->MODULE_ID);
		$this->installEvents();
	}

	function InstallDB() {
		return true;
	}

	public function installEvents() {
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler("main", "OnEndBufferContent", "rockot_hide_items", "CHideItemsEventHandlers", "OnEndBufferContent", 50);
		$eventManager->registerEventHandler("main", "OnBeforeEndBufferContent", "rockot_hide_items", "CHideItemsEventHandlers", "OnBeforeEndBufferContent", 50);
	}

	public function installFiles() {
		CopyDirFiles(
				__DIR__.'/assets/scripts',
				Application::getDocumentRoot().'/bitrix/js/'.$this->MODULE_ID.'/',
				true,
				true
		);
	}

	//--------------------------------

	function DoUninstall()
	{
		$this->uninstallFiles();
    $this->UnInstallDB();
		$this->uninstallEvents();
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	function UnInstallDB() {
		return true;
	}

	

	public function uninstallFiles() {
		Directory::deleteDirectory(
				Application::getDocumentRoot().'/bitrix/js/'.$this->MODULE_ID
		);
		Option::delete($this->MODULE_ID);
	}

	public function uninstallEvents() {
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler("main", "OnEndBufferContent", "rockot_hide_items", "CHideItemsEventHandlers", "OnEndBufferContent");
		$eventManager->unRegisterEventHandler("main", "OnBeforeEndBufferContent", "rockot_hide_items", "CHideItemsEventHandlers", "OnBeforeEndBufferContent");
	}

}

