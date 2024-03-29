<?global $DOCUMENT_ROOT, $MESS;
if (!function_exists("CreatePattern"))
{
	function CreatePattern($pattern="", $DICTIONARY_ID=0)
	{
		$separator = "";
		$NotWord = "\s.,;:!?\#\-\*\|\[\]\(\)";
		$word_separator = "[".$NotWord."]";
		
		$pattern = strToLower(trim($pattern));
		
		if (strLen($pattern) <= 0)
			return false;
			
		$DICTIONARY_ID = intval($DICTIONARY_ID);
		$res = "";
		if ($DICTIONARY_ID == 0)
			$DICTIONARY_ID = (COption::GetOptionString("forum", "FILTER_DICT_T", '', LANG));
		elseif ($DICTIONARY_ID < 0)
			$DICTIONARY_ID = 0;
		
		$strSql = 
			"SELECT ID, LETTER, REPLACEMENT, DICTIONARY_ID
			FROM b_forum_letter
			WHERE DICTIONARY_ID=".intval($DICTIONARY_ID);
		$letters = $GLOBALS["DB"]->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$lettPatt = array();
		$lettersPatt = array();
		while ($lett = $letters->Fetch())
		{
			$space = false;
			$arrRes = array();
			$arrRepl = array();
			
			$arrRepl = explode(",", $lett["REPLACEMENT"]);
			for ($ii = 0; $ii < count($arrRepl); $ii++)
			{
				$arrRepl[$ii] = trim($arrRepl[$ii]);
				if (strLen($lett["LETTER"])==1)
				{
					if (strLen($arrRepl[$ii]) == 1 )
						$arrRes[$ii] = $arrRepl[$ii]."+";
					elseif (substr($arrRepl[$ii], 0, 1) == "(" && (substr($arrRepl[$ii], -1, 1) == ")" || substr($arrRepl[$ii], -2, 1) == ")"))
					{
						if (substr($arrRepl[$ii], -1, 1) == ")")
							$arrRes[$ii] = $arrRepl[$ii]."+";
						else
							$arrRes[$ii] = $arrRepl[$ii];
					}
					elseif (strLen($arrRepl[$ii]) > 1 )
						$arrRes[$ii] = "[".$arrRepl[$ii]."]+";
					else 
						$space = true;
				}
				else 
				{
					if (strLen($arrRepl[$ii]) > 0)
						$arrRes[$ii] = $arrRepl[$ii];
				}
			}
			
			if (strLen($lett["LETTER"])==1)
			{
				if ($space)
					$arrRes[] = "";
	//					$lettPatt[$lett["LETTER"]] = str_replace("+", "*", $lettPatt[$lett["LETTER"]]);
				$lettPatt[$lett["LETTER"]] = implode("|", $arrRes);
			}
			else 
			{
				$lettersPatt["/".preg_quote($lett["LETTER"])."/is".BX_UTF_PCRE_MODIFIER] = "(".implode("|", $arrRes).")";
			}
		}
		foreach ($lettersPatt as $key => $val)
			$pattern = preg_replace($key, $val, $pattern);
		for ($ii = 0; $ii < strLen($pattern); $ii++)
		{
			if (is_set($lettPatt, substr($pattern, $ii, 1)))
				$res .= "(".$lettPatt[substr($pattern, $ii, 1)].")";
			else 
			{
				$ord = ord(substr($pattern, $ii, 1));
				if ((48>$ord) || ((64>$ord) and ($ord>57)) || ((97>$ord) and ($ord>90)) || ((127>$ord) and ($ord>122)))
				{
					if ($ord == 42)
						$res .= "[^".$NotWord."]*";
					elseif ($ord == 43)
						$res .= "[^".$NotWord."]+";
					elseif ($ord == 63)
						$res .= ".?";
					else
						$res .= substr($pattern, $ii, 1);
				}
				else 
					$res .= substr($pattern, $ii, 1)."+";
			}
			$res .= $separator;
		}
		$res = "/(?<=".$word_separator.")(".$res.")(?=".$word_separator.")/is".BX_UTF_PCRE_MODIFIER;
		return $res;
	}
}
if (!function_exists("GenPatternAll"))
{
	function GenPatternAll($DICTIONARY_ID_W=0, $DICTIONARY_ID_T=0)
	{
		$DICTIONARY_ID_W = intVal($DICTIONARY_ID_W);
		$DICTIONARY_ID_T = intVal($DICTIONARY_ID_T);
		if (!$DICTIONARY_ID_W)
			$DICTIONARY_ID_W = (COption::GetOptionString("forum", "FILTER_DICT_W", '', LANG));
		if (!$DICTIONARY_ID_T)
			$DICTIONARY_ID_T = (COption::GetOptionString("forum", "FILTER_DICT_T", '', LANG));
		if ($DICTIONARY_ID_W):
			$strSql = 
				"SELECT FM.ID, FM.DICTIONARY_ID, FM.WORDS, FM.PATTERN, FM.REPLACEMENT, FM.DESCRIPTION,  FM.USE_IT, FM.PATTERN_CREATE ".
				"FROM b_forum_filter FM ".
				"WHERE FM.DICTIONARY_ID=".intVal($DICTIONARY_ID_W);
			$db_res = $GLOBALS["DB"]->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($res = $db_res->Fetch())
			{
				if ((strlen(trim($res["WORDS"]))>0) && ($res["PATTERN_CREATE"] == "TRNSL")):
					$pattern = CreatePattern(trim($res["WORDS"]), $DICTIONARY_ID_T);
					if ($pattern)
					{
						$strUpdate = $GLOBALS["DB"]->PrepareUpdate("b_forum_filter", array("PATTERN"=>$pattern));
						$strSql = "UPDATE b_forum_filter SET ".$strUpdate." WHERE ID=".$res["ID"];
						$GLOBALS["DB"]->QueryBind($strSql, Array("PATTERN"=>$pattern), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					}
				endif;
			}
			return true;
		endif;
		return false;
	}
}

IncludeModuleLangFile(__FILE__);

if (class_exists("forum")) return;

Class forum extends CModule
{
	var $MODULE_ID = "forum";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";
	
	function forum()
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
			$this->MODULE_VERSION = FORUM_VERSION;
			$this->MODULE_VERSION_DATE = FORUM_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("FORUM_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("FORUM_MODULE_DESCRIPTION");
	}
	
	function InstallDB()
	{
		$this->errors = false;
		$arInstall = array(
			"INSTALL_SMILES" => ($_REQUEST["install_forum"] == "Y" && $_REQUEST["INSTALL_SMILES"] != "Y" ? "N" : "Y"),
			"INSTALL_FILTER" => ($_REQUEST["install_forum"] == "Y" && $_REQUEST["INSTALL_FILTER"] != "Y" ? "N" : "Y"));
		
		if (!$GLOBALS["DB"]->Query("SELECT 'x' FROM b_forum", true))
		{
			$this->errors = $GLOBALS["DB"]->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/install.sql");
			
			if($this->errors !== false)
			{
				$GLOBALS["APPLICATION"]->ThrowException(implode("", $this->errors));
				return false;
			}
		}

		RegisterModule("forum");
		
		CAgent::AddAgent("CForumStat::CleanUp();","forum");
		CAgent::AddAgent("CForumFiles::CleanUp();", "forum");
		
		RegisterModuleDependences("main", "OnGroupDelete", "forum", "CForumNew", "OnGroupDelete");
		RegisterModuleDependences("main", "OnBeforeLangDelete", "forum", "CForumNew", "OnBeforeLangDelete");
		RegisterModuleDependences("main", "OnFileDelete", "forum", "CForumFiles", "OnFileDelete");

		RegisterModuleDependences("search", "OnReindex", "forum", "CForumNew", "OnReindex");
		RegisterModuleDependences("main", "OnUserDelete", "forum", "CForumUser", "OnUserDelete");
		RegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", "main", "CIBlockPropertyTopicID", "GetUserTypeDescription", 100, "/modules/forum/tools/prop_topicid.php");
		RegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", "forum", "CForumTopic", "OnBeforeIBlockElementDelete");
		RegisterModuleDependences("main", "OnEventLogGetAuditTypes", "forum", "CForumEventLog", "GetAuditTypes");
		RegisterModuleDependences("main", "OnEventLogGetAuditHandlers", "forum", "CEventForum", "MakeForumObject");	
		RegisterModuleDependences("socialnetwork", "OnSocNetGroupDelete", "forum", "CForumUser", "OnSocNetGroupDelete");
		RegisterModuleDependences("socialnetwork", "OnSocNetLogFormatEvent", "forum", "CForumMessage", "OnSocNetLogFormatEvent");

		RegisterModuleDependences('mail', 'OnGetFilterList', 'forum', 'CForumEMail', 'OnGetSocNetFilterList');
		
		RegisterModuleDependences("main", "OnAfterAddRating", "forum", "CRatingsComponentsForum", "OnAfterAddRating", 100);
		RegisterModuleDependences("main", "OnAfterUpdateRating", "forum", "CRatingsComponentsForum", "OnAfterUpdateRating", 100);
		RegisterModuleDependences("main", "OnSetRatingsConfigs", "forum", "CRatingsComponentsForum", "OnSetRatingConfigs", 100);
		RegisterModuleDependences("main", "OnGetRatingsConfigs", "forum", "CRatingsComponentsForum", "OnGetRatingConfigs", 100);
		RegisterModuleDependences("main", "OnGetRatingsObjects", "forum", "CRatingsComponentsForum", "OnGetRatingObject", 100);
		
		RegisterModuleDependences("main", "OnGetRatingContentOwner", "forum", "CRatingsComponentsForum", "OnGetRatingContentOwner", 100);
		
		if ($arInstall["INSTALL_SMILES"] == "Y")
		{
			$bInsSmiles = False;
			if ($GLOBALS["DB"]->TableExists("b_forum_smile") || $GLOBALS["DB"]->TableExists("B_FORUM_SMILE"))
			{
				$bInsSmiles = true;
				$db_res = $GLOBALS["DB"]->Query("SELECT * FROM b_forum_smile", true);
				if ($db_res && $db_res->Fetch())
					$bInsSmiles = false;
			}
			if ($bInsSmiles)
			{
				$this->errors = $GLOBALS["DB"]->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/smile.sql");
				$by = "LID"; $order = "DESC";
				$sites = CLanguage::GetList($by, $order, Array());
				while($site = $sites->Fetch())
				{
					$this->errors = $GLOBALS["DB"]->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/".$site["LID"]."/smile.sql");
				}
			}
		}
		if ($GLOBALS["DB"]->TableExists("b_forum_pm_folder") || $GLOBALS["DB"]->TableExists("B_FORUM_PM_FOLDER"))
		{
			$db_res = $GLOBALS["DB"]->Query("SELECT ID FROM b_forum_pm_folder WHERE USER_ID IS NULL OR USER_ID <= 0");
			if (!($db_res && $res = $db_res->Fetch()))
			{
				$this->errors = $GLOBALS["DB"]->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/install2.sql");
			}
		}
		if ($arInstall["INSTALL_FILTER"] == "Y")
		{
			if (($GLOBALS["DB"]->TableExists("b_forum_dictionary") || $GLOBALS["DB"]->TableExists("B_FORUM_DICTIONARY")) && 
				($GLOBALS["DB"]->TableExists("b_forum_filter") || $GLOBALS["DB"]->TableExists("B_FORUM_FILTER")))
			{
				$by = "LID"; $order = "DESC";
				$sites = CLanguage::GetList($by, $order, Array());
				while($site = $sites->Fetch())
				{
					if (!in_array($site["LID"], array("ru", "en", "de")))
						continue;

					$tmp_res_q = $GLOBALS["DB"]->Query(
					"SELECT 
						FD.ID, COUNT(FF.ID) AS COUNT_WORDS
						FROM b_forum_dictionary FD
					LEFT JOIN b_forum_filter FF ON (FD.ID=FF.DICTIONARY_ID)
					WHERE FD.ID=".($site["LID"] == "ru" ? "1" : ($site["LID"] == "de" ? "5" : "3"))."
					GROUP BY FD.ID", True);
					if (!($tmp_res_q && ($res = $tmp_res_q->Fetch())))
					{
						if(file_exists(	$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/".$site["LID"]."/".$site["LID"].".sql"))
							$this->errors = $GLOBALS["DB"]->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/".$site["LID"]."/".$site["LID"].".sql");
					}
					if ($site["LID"] == "ru")
					{
						GenPatternAll(1, 2);
						COption::SetOptionString("forum", "FILTER_DICT_W", "1", "", "ru");
						COption::SetOptionString("forum", "FILTER_DICT_T", "2", "", "ru");
					}
					elseif ($site["LID"] == "de")
					{
						GenPatternAll(5, 6);
						COption::SetOptionString("forum", "FILTER_DICT_W", "5", "", "de");
						COption::SetOptionString("forum", "FILTER_DICT_T", "6", "", "de");
					}
					else
					{
						GenPatternAll(3, 4);
						COption::SetOptionString("forum", "FILTER_DICT_W", "3", "", "en");
						COption::SetOptionString("forum", "FILTER_DICT_T", "4", "", "en");
					}
				}
			}
		}
		COption::SetOptionString("forum", "FILTER", "N");
		
		return true;
	}
	
	function UnInstallDB($arParams = array())
	{
		$this->errors = false;
		$arSQLErrors = array();
		
		if(CModule::IncludeModule("search"))
			CSearch::DeleteIndex("forum");

		if(array_key_exists("savedata", $arParams) && $arParams["savedata"] != "Y")
		{
			$this->errors = $GLOBALS["DB"]->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/".$GLOBALS["DBType"]."/uninstall.sql");
		}
		if(!empty($this->errors))
		{
			$GLOBALS["APPLICATION"]->ThrowException(implode("", $arSQLErrors));
			return false;
		}
		
		UnRegisterModuleDependences("iblock", "OnIBlockPropertyBuildList", "main", "CIBlockPropertyTopicID", "GetUserTypeDescription");
		UnRegisterModuleDependences("iblock", "OnBeforeIBlockElementDelete", "forum", "CForumTopic", "OnBeforeIBlockElementDelete");
		UnRegisterModuleDependences("main", "OnUserDelete", "forum", "CForumUser", "OnUserDelete");
		UnRegisterModuleDependences("main", "OnFileDelete", "forum", "CForumFiles", "OnFileDelete");
		UnRegisterModuleDependences("search", "OnReindex", "forum", "CForumNew", "OnReindex");
		UnRegisterModuleDependences("main", "OnPanelCreate", "forum", "CForumNew", "OnPanelCreate");
		UnRegisterModuleDependences("main", "OnBeforeLangDelete", "forum", "CForumNew", "OnBeforeLangDelete");
		UnRegisterModuleDependences("main", "OnGroupDelete", "forum", "CForumNew", "OnGroupDelete");
		UnRegisterModuleDependences("main", "OnEventLogGetAuditTypes", "forum", "CForumEventLog", "GetAuditTypes");
		UnRegisterModuleDependences("main", "OnEventLogGetAuditHandlers", "forum", "CEventForum", "MakeForumObject");
		UnRegisterModuleDependences("socialnetwork", "OnSocNetGroupDelete", "forum", "CForumUser", "OnSocNetGroupDelete");

		UnRegisterModuleDependences("main", "OnAfterAddRating",    "forum", "CRatingsComponentsForum", "OnAfterAddRating");
		UnRegisterModuleDependences("main", "OnAfterUpdateRating", "forum", "CRatingsComponentsForum", "OnAfterUpdateRating");
		UnRegisterModuleDependences("main", "OnSetRatingsConfigs", "forum", "CRatingsComponentsForum", "OnSetRatingConfigs");
		UnRegisterModuleDependences("main", "OnGetRatingsConfigs", "forum", "CRatingsComponentsForum", "OnGetRatingConfigs");
		UnRegisterModuleDependences("main", "OnGetRatingsObjects", "forum", "CRatingsComponentsForum", "OnGetRatingObject");
		UnRegisterModuleDependences("main", "OnGetRatingContentOwner", "forum", "CRatingsComponentsForum", "OnGetRatingContentOwner");
		
		CAgent::RemoveAgent("CForumTopic::CleanUp();","forum");
		CAgent::RemoveAgent("CForumStat::CleanUp();","forum");
		CAgent::RemoveAgent("CForumFiles::CleanUp();","forum");
		UnRegisterModule("forum");
	
		return true;
	}
	
	function InstallEvents()
	{
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/events.php");	
		return true;
	}

	function UnInstallEvents()
	{
		$GLOBALS["DB"]->Query(
			"DELETE FROM b_event_type WHERE EVENT_NAME IN ('NEW_FORUM_MESSAGE','EDIT_FORUM_MESSAGE','NEW_FORUM_PRIV','NEW_FORUM_PRIVATE_MESSAGE') ", 
			false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$GLOBALS["DB"]->Query(
			"DELETE FROM b_event_message WHERE EVENT_NAME IN ('NEW_FORUM_MESSAGE','EDIT_FORUM_MESSAGE','NEW_FORUM_PRIV','NEW_FORUM_PRIVATE_MESSAGE') ", 
			false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	function InstallFiles()
	{
		if($_SERVER["DevServer"] != "Y" && $_ENV["COMPUTERNAME"]!="BX")
		{
			CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/images/forum/", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/images",  $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/forum", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/themes/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/forum", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/public/templates", $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates", true, true);
		}
		return true;
	}

	function UnInstallFiles()
	{
		if($_SERVER["DevServer"] != "Y" && $_ENV["COMPUTERNAME"]!="BX")
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin");
			DeleteDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/themes/.default/",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
			DeleteDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/public/templates/.default/page_templates/forum/",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/page_templates/forum/");//page template
			DeleteDirFilesEx("/bitrix/themes/.default/icons/forum/");//icons
		}
		return true;
	}

	function DoInstall()
	{
		$GLOBALS["errors"] = false;
		
		if (IsModuleInstalled("forum"))
			return false;
		if (!check_bitrix_sessid())
			return false;
		$this->errors = false;
		$step = IntVal($_REQUEST["step"]);
		if($step != 2)
			$GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("FORUM_INSTALL1"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/do_install1.php");
		else
		{
			// Check Fatal errors
			if (!$this->InstallDB() || !empty($this->errors))
			{
				$GLOBALS["errors"] = $this->errors;
				$GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("FORUM_INSTALL2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/do_install2.php");
			}

			$this->InstallEvents();
			$this->InstallFiles();
			
			if ($_REQUEST["install_forum"] == "Y" && $_REQUEST["REINDEX"] == "Y")
			{
				CModule::IncludeModule("forum");
				if (CModule::IncludeModule("search"))
					CSearch::ReIndexModule("forum");
			}
			
			$GLOBALS["errors"] = $this->errors;
			$GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("FORUM_INSTALL2"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/do_install2.php");
		}
		
		return empty($this->errors);
	}

	function DoUninstall()
	{
		if (!check_bitrix_sessid())
			return false;
		$GLOBALS["errors"] = false;
		$step = IntVal($_REQUEST["step"]);
		if($step<2)
			$GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("FORUM_DELETE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/do_uninstall1.php");
		else
		{
			if ($this->UnInstallDB(array("savedata" => $_REQUEST["savedata"])))
			{
				if (CModule::IncludeModule("search"))
					CSearch::DeleteIndex("forum");
				$this->UnInstallEvents();
				$this->UnInstallFiles();
			}
			$GLOBALS["CACHE_MANAGER"]->CleanAll();
			$GLOBALS["stackCacheManager"]->CleanAll();
			$GLOBALS["errors"] = $this->errors;
			$GLOBALS["APPLICATION"]->IncludeAdminFile(GetMessage("FORUM_DELETE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/install/do_uninstall2.php");
		}
	}
}
?>
