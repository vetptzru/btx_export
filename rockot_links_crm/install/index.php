<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

const MODULE_ID = "rockot_links_crm";

Class rockot_links_crm extends CModule
{
	var $MODULE_ID = MODULE_ID;
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
    $this->MODULE_NAME = "Rockot: добавление ссылок в группу";
		$this->MODULE_DESCRIPTION = "Rockot: добавление ссылок в группу";
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
		$eventManager->registerEventHandler("main", "OnBeforeProlog", "rockot_links_crm", "CRockotEventHandlers", "OnBeforeProlog", 49);		
		$eventManager->registerEventHandler("main", "OnProlog", "rockot_links_crm", "CRockotEventHandlers", "OnProlog", 49);

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
		$eventManager->unRegisterEventHandler("main", "OnBeforeProlog", "rockot_links_crm", "CRestEventHandlers", "OnBeforeProlog");
		$eventManager->unRegisterEventHandler("main", "OnProlog", "rockot_links_crm", "CRestEventHandlers", "OnProlog");
	}

}
?>