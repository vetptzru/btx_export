<?
use Bitrix\Main\Localization\Loc;

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

	public function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__.'/version.php');

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    $this->MODULE_NAME = "Rockot: добавление ссылок в группу";
		$this->MODULE_DESCRIPTION = "Rockot: добавление ссылок в группу";

	}


	function InstallDB($install_wizard = true)
	{
    RegisterModule(MODULE_ID);
		return true;
	}

	function UnInstallDB($arParams = Array())
	{
    UnRegisterModule(MODULE_ID);
		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{
		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
    $this->InstallFiles();
    $this->InstallDB(false);
	}

	function DoUninstall()
	{
    $this->UnInstallDB();
	}
}
?>