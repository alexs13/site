<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class controller extends CModule
{
	var $MODULE_ID = "controller";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function controller()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = CONTROLLER_VERSION;
			$this->MODULE_VERSION_DATE = CONTROLLER_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage('CTRL_INST_NAME');
		$this->MODULE_DESCRIPTION = GetMessage('CTRL_INST_DESC');
	}

	function InstallDB()
	{
		global $DB;

		RegisterModule("controller");
		if (!$DB->Query("SELECT 'x' FROM b_controller_member WHERE 1=0", true))
			$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/db/".strtolower($DB->type)."/install.sql");

		CAgent::AddAgent("CControllerMember::UnregisterExpiredAgent();", "controller", "Y", 86400);
		CAgent::AddAgent("CControllerAgent::CleanUp();", "controller", "N", 86400);

		RegisterModuleDependences("perfmon", "OnGetTableSchema", "controller", "controller", "OnGetTableSchema");

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB;

		UnRegisterModuleDependences("perfmon", "OnGetTableSchema", "controller", "controller", "OnGetTableSchema");

		CAgent::RemoveAgent('CControllerMember::UnregisterExpiredAgent();');
		CAgent::RemoveAgent('CControllerAgent::CleanUp();');

		if(!array_key_exists("savedata", $arParams) || ($arParams["savedata"] != "Y"))
		{
			if ($DB->Query("SELECT 'x' FROM b_controller_member", true))
				$errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		UnRegisterModule("controller");

		return true;
	}

	function InstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/admin/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", false);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/controller", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", True, True);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", True, True);
			if(IsModuleInstalled('bizproc'))
			{
				CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/bizproc/templates", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/templates", true, true);
				$langs = CLanguage::GetList(($b=""), ($o=""));
				while($lang = $langs->Fetch())
					CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/lang/".$lang["LID"]."/install/bizproc/templates", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/lang/".$lang["LID"]."/templates", true, true);
			}
		}
		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/controller/install/admin/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
			DeleteDirFiles($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/controller/install/themes/.default/', $_SERVER['DOCUMENT_ROOT'].'/bitrix/themes/.default');//css
			DeleteDirFilesEx('/bitrix/themes/.default/icons/controller/');//icons
			DeleteDirFilesEx('/bitrix/images/controller/');//images
		}
		return true;
	}

	function InstallEvents()
	{
		global $DB;
		$sIn = "'CONTROLLER_MEMBER_REGISTER', 'CONTROLLER_MEMBER_CLOSED'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/events/set_events.php");
		}
		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		$sIn = "'CONTROLLER_MEMBER_REGISTER', 'CONTROLLER_MEMBER_CLOSED'";
		$DB->Query("DELETE FROM b_event_message WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return true;
	}

	function DoInstall()
	{
		global $DB, $APPLICATION;
		$RIGHT = $APPLICATION->GetGroupRight("controller");
		if($RIGHT < "W")
			return;

		if(!CBXFeatures::IsFeatureEditable("Controller"))
		{
			$APPLICATION->ThrowException(GetMessage("MAIN_FEATURE_ERROR_EDITABLE"));
			$APPLICATION->IncludeAdminFile(GetMessage("CTRL_INST_STEP1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/step.php");
		}
		else
		{
			$this->InstallDB();
			$this->InstallFiles();
			$this->InstallEvents();
			CBXFeatures::SetFeatureEnabled("Controller", true);
			$APPLICATION->IncludeAdminFile(GetMessage("CTRL_INST_STEP1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/step.php");
		}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION, $step;
		$RIGHT = $APPLICATION->GetGroupRight("controller");
		if($RIGHT>="W")
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("CTRL_INST_STEP1_UN"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				//message types and templates
				if($_REQUEST["save_templates"] != "Y")
				{
					$this->UnInstallEvents();
				}
				$this->UnInstallFiles();
				CBXFeatures::SetFeatureEnabled("Controller", false);
				$GLOBALS["errors"] = $this->errors;
				$APPLICATION->IncludeAdminFile(GetMessage("CTRL_INST_STEP1_UN"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/controller/install/unstep.php");
			}
		}
	}

	function GetModuleRightList()
	{
		$arr = array(
			"reference_id" => array("D", "L", "R", "T", "V", "W"),
			"reference" => array(
				"[D] ".GetMessage("CTRL_PERM_D"),
				"[L] ".GetMessage("CTRL_PERM_L"),
				"[R] ".GetMessage("CTRL_PERM_R"),
				"[T] ".GetMessage("CTRL_PERM_T"),
				"[V] ".GetMessage("CTRL_PERM_V"),
				"[W] ".GetMessage("CTRL_PERM_W"),
				)
			);
		return $arr;
	}

	function OnGetTableSchema()
	{
		return array(
			"controller" => array(
				"b_controller_group" => array(
					"ID" => array(
						"b_controller_member" => "CONTROLLER_GROUP_ID",
						"b_controller_counter_group" => "CONTROLLER_GROUP_ID",
					)
				),
				"b_controller_member" => array(
					"ID" => array(
						"b_controller_task" => "CONTROLLER_MEMBER_ID",
						"b_controller_log" => "CONTROLLER_MEMBER_ID",
						"b_controller_counter_value" => "CONTROLLER_MEMBER_ID",
						"b_controller_member_log" => "CONTROLLER_MEMBER_ID",
					),
					"MEMBER_ID" => array(
						"b_controller_command" => "MEMBER_ID",
					),
				),
				"b_controller_task" => array(
					"ID" => array(
						"b_controller_command" => "TASK_ID",
						"b_controller_log" => "TASK_ID",
					),
				),
				"b_controller_counter" => array(
					"ID" => array(
						"b_controller_counter_group" => "CONTROLLER_COUNTER_ID",
						"b_controller_counter_value" => "CONTROLLER_COUNTER_ID",
					),
				),
			),
			"main" => array(
				"b_user" => array(
					"ID" => array(
						"b_controller_group" => "MODIFIED_BY",
						"b_controller_group^" => "CREATED_BY",
						"b_controller_member" => "MODIFIED_BY",
						"b_controller_member" => "CREATED_BY",
						"b_controller_log" => "USER_ID",
						"b_controller_member_log" => "USER_ID",
					)
				),
			),
		);
	}
}
?>
