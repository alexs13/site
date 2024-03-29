<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/template_message.php")));
global $MESS;
include_once($file);

if (function_exists("__forum_default_template_show_message"))
	return false;

function __forum_default_template_show_message($arMessages, $message, $arResult, $arParams, $component = false)
{
	$message = (is_array($message) ? $message : array());
	$arMessages = (is_array($arMessages) ? $arMessages : array($arMessages));
	$arResult = (is_array($arResult) ? $arResult : array($arResult));

	if ($arParams["SHOW_RATING"] == 'Y'):
		$arAuthorId = array();
		$arPostId = array();
		$arTopicId = array();
		foreach ($arMessages as $res)
		{
			$arAuthorId[] = $res['AUTHOR_ID'];
			if ($res['NEW_TOPIC'] == "Y")
				$arTopicId[] = $res['TOPIC_ID'];
			else
				$arPostId[] = $res['ID'];
		}
		if (!empty($arAuthorId)):
			$arRatingResult = CRatings::GetRatingResult($arParams["RATING_ID"] , $arAuthorId);
		endif;

		if (!empty($arPostId))
			$arRatingVote['FORUM_POST'] = CRatings::GetRatingVoteResult('FORUM_POST', $arPostId);

		if (!empty($arTopicId))
			$arRatingVote['FORUM_TOPIC'] = CRatings::GetRatingVoteResult('FORUM_TOPIC', $arTopicId);
	endif;

	$iCount = count($arMessages); // messages count
	$iNumber = 0; // message number in list

	foreach ($arMessages as $res):
		$iNumber++;

		if ($arParams["SHOW_VOTE"] == "Y" && $res["PARAM1"] == "VT" && intVal($res["PARAM2"]) > 0 && IsModuleInstalled("vote")):
			?>
		<div class="forum-info-box forum-post-vote">
			<div class="forum-info-box-inner">
				<a name="message<?=$res["ID"]?>"></a><?
				?><?$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:voting.current", $arParams["VOTE_TEMPLATE"],
				array(
					"VOTE_ID" => $res["PARAM2"],
					"VOTE_CHANNEL_ID" => $arParams["VOTE_CHANNEL_ID"],
					"PERMISSION" => $arResult["VOTE_PERMISSION"],
					"VOTE_RESULT_TEMPLATE" => POST_FORM_ACTION_URI,
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"NEED_SORT" => "N",
					"SHOW_RESULTS" => "Y"),
				(($component && $component->__component && $component->__component->__parent) ? $component->__component->__parent : null),
				array("HIDE_ICONS" => "Y"));?>
			</div>
		</div>
		<?
		endif;
		?><!--MSG_<?=$res["ID"]?>--><?
			?><table cellspacing="0" border="0" class="forum-post-table <?=($iNumber == 1 ? "forum-post-first " : "")?><?
					?><?=($iNumber == $iCount ? "forum-post-last " : "")?><?
					?><?=($iNumber%2 == 1 ? "forum-post-odd " : "forum-post-even ")?><?
					?><?=($res["APPROVED"] == "Y" ? "" : " forum-post-hidden ")?><?
					?><?=(in_array($res["ID"], $message) ? " forum-post-selected " : "")?>" <?
						?>id="message<?=$res["ID"]?>">
				<tbody>
				<tr>
				<td class="forum-cell-user">
					<div class="forum-user-info">
						<?
						if ($res["AUTHOR_ID"] > 0):
							?>
							<div class="forum-user-name"><a href="<?=$res["URL"]["AUTHOR"]?>"><span><?=$res["AUTHOR_NAME"]?></span></a></div>
							<?
							if (is_array($res["AVATAR"]) && !empty($res["AVATAR"]["HTML"])):
								?>
								<div class="forum-user-avatar"><?
									?><a href="<?=$res["URL"]["AUTHOR"]?>" title="<?=GetMessage("F_AUTHOR_PROFILE")?>"><?
										?><?=$res["AVATAR"]["HTML"]?></a></div>
								<?
							else:
								?>
								<div class="forum-user-register-avatar"><?
									?><a href="<?=$res["URL"]["AUTHOR"]?>" title="<?=GetMessage("F_AUTHOR_PROFILE")?>"><span><!-- ie --></span></a></div>
								<?
							endif;
						else:
							?>
							<div class="forum-user-name"><span><?=$res["AUTHOR_NAME"]?></span></div>
							<div class="forum-user-guest-avatar"><!-- ie --></div>
							<?
						endif;

						if (!empty($res["AUTHOR_STATUS"])):
							?>
							<div class="forum-user-status <?=(!empty($res["AUTHOR_STATUS_CODE"]) ? "forum-user-".$res["AUTHOR_STATUS_CODE"]."-status" : "")?>"><?
								?><span><?=$res["AUTHOR_STATUS"]?></span></div>
							<?
						endif;

						?>
						<div class="forum-user-additional">
							<?
							if (intVal($res["NUM_POSTS"]) > 0):
								?>
								<span><?=GetMessage("F_NUM_MESS")?> <span><?=$res["NUM_POSTS"]?></span></span>
								<?
							endif;

							if (COption::GetOptionString("forum", "SHOW_VOTES", "Y")=="Y" && $res["AUTHOR_ID"] > 0 &&
								($res["NUM_POINTS"] > 0 || $res["VOTES"]["ACTION"] == "VOTE" || $res["VOTES"]["ACTION"] == "UNVOTE")):
								?>
								<span><?=GetMessage("F_POINTS")?> <span><?=$res["NUM_POINTS"]?></span><?
									if ($res["VOTING"] == "VOTE" || $res["VOTING"] == "UNVOTE"):
										?>&nbsp;(<span class="forum-vote-user"><?
										?><a onclick="return fasessid(this);" href="<?=$res["URL"]["AUTHOR_VOTE"]?>" title="<?
											?><?=($res["VOTING"] == "VOTE" ? GetMessage("F_NO_VOTE_DO") : GetMessage("F_NO_VOTE_UNDO"));?>"><?
											?><?=($res["VOTING"] == "VOTE" ? "+" : "-");?></a></span>)<?
									endif;
									?></span>
								<?
							endif;
							if ($arParams["SHOW_RATING"] == 'Y' && $res["AUTHOR_ID"] > 0):
								?>
								<span>
				<?
									$GLOBALS["APPLICATION"]->IncludeComponent(
										"bitrix:rating.result", "",
										Array(
											"RATING_ID" => $arParams["RATING_ID"],
											"ENTITY_ID" => $arRatingResult[$res['AUTHOR_ID']]['ENTITY_ID'],
											"CURRENT_VALUE" => $arRatingResult[$res['AUTHOR_ID']]['CURRENT_VALUE'],
											"PREVIOUS_VALUE" => $arRatingResult[$res['AUTHOR_ID']]['PREVIOUS_VALUE'],
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
									?>
				</span>
								<?
							endif;
							if (!empty($res["~DATE_REG"])):
								?>
								<span><?=GetMessage("F_DATE_REGISTER")?> <span><?=$res["DATE_REG"]?></span></span>
								<?
							endif;
							?>
						</div>
						<?
						if (!empty($res["DESCRIPTION"])):
							?>
							<div class="forum-user-description"><span><?=$res["DESCRIPTION"]?></span></div>
							<?
						endif;

						?>
					</div>
				</td>
				<td class="forum-cell-post">
					<span style='position:absolute;'><a id="message<?=$res["ID"]?>">&nbsp;</a></span><? /* IE9 */ ?>
					<div class="forum-post-date">
						<div class="forum-post-number"><noindex><a href="http://<?=$_SERVER["HTTP_HOST"]?><?=$res["URL"]["MESSAGE"]?>#message<?=$res["ID"]?>" <?
							?>onclick="prompt(oText['ml'], this.href); return false;" title="<?=GetMessage("F_ANCHOR")?>" rel="nofollow">#<?=$res["NUMBER"]?></a></noindex><?
							if ($arResult["USER"]["PERMISSION"] >= "Q" && $res["SHOW_CONTROL"] != "N"):
								?>&nbsp;<input type="checkbox" name="message_id[]" value="<?=$res["ID"]?>" id="message_id_<?=$res["ID"]?>_" <?
								if (in_array($res["ID"], $message)):
									?> checked="checked" <?
								endif;
								if (isset($arParams['iIndex']))
								{
									?> onclick="SelectPost(this.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode, <?=$arParams['iIndex']?>, this.value)" /><?
								} else {
									?> onclick="SelectPost(this.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode)" /><?
								}
							endif;
							?></div>
						<?if ($arParams["SHOW_RATING"] == 'Y'):?>
						<div class="forum-post-rating" style="float: right;padding-right: 10px; padding-top: 2px;">
							<?
							$voteEntityType = $res['NEW_TOPIC'] == "Y" ? "FORUM_TOPIC" : "FORUM_POST";
							$voteEntityId = $res['NEW_TOPIC'] == "Y" ? $res['TOPIC_ID'] : $res['ID'];
							$GLOBALS["APPLICATION"]->IncludeComponent(
								"bitrix:rating.vote", $arParams["RATING_TYPE"],
								Array(
									"ENTITY_TYPE_ID" => $voteEntityType,
									"ENTITY_ID" => $voteEntityId,
									"OWNER_ID" => $res['AUTHOR_ID'],
									"USER_VOTE" => $arRatingVote[$voteEntityType][$voteEntityId]['USER_VOTE'],
									"USER_HAS_VOTED" => $arRatingVote[$voteEntityType][$voteEntityId]['USER_HAS_VOTED'],
									"TOTAL_VOTES" => $arRatingVote[$voteEntityType][$voteEntityId]['TOTAL_VOTES'],
									"TOTAL_POSITIVE_VOTES" => $arRatingVote[$voteEntityType][$voteEntityId]['TOTAL_POSITIVE_VOTES'],
									"TOTAL_NEGATIVE_VOTES" => $arRatingVote[$voteEntityType][$voteEntityId]['TOTAL_NEGATIVE_VOTES'],
									"TOTAL_VALUE" => $arRatingVote[$voteEntityType][$voteEntityId]['TOTAL_VALUE'],
									"PATH_TO_USER_PROFILE" => $arParams["~URL_TEMPLATES_PROFILE_VIEW"]
								),
								$arParams["component"],
								array("HIDE_ICONS" => "Y")
							);?>
						</div>
						<?endif;?>
						<span><?=$res["POST_DATE"]?></span>
					</div>
					<div class="forum-post-entry">
						<div class="forum-post-text" id="message_text_<?=$res["ID"]?>"><?=$res["POST_MESSAGE_TEXT"]?></div>
						<?
						if (!empty($res["FILES"]))
						{
							$arFilesHTML = array("thumb" => array(), "files" => array());

							foreach ($res["FILES"] as $arFile)
							{
								if (!in_array($arFile["FILE_ID"], $res["FILES_PARSED"]))
								{
									$arFileTemplate = $GLOBALS["APPLICATION"]->IncludeComponent("bitrix:forum.interface", "show_file",
										Array(
											"FILE" => $arFile,
											"SHOW_MODE" => $arParams["ATTACH_MODE"],
											"WIDTH" => $arParams["ATTACH_SIZE"],
											"HEIGHT" => $arParams["ATTACH_SIZE"],
											"CONVERT" => "N",
											"FAMILY" => "FORUM",
											"SINGLE" => "Y",
											"RETURN" => "ARRAY",
											"SHOW_LINK" => "Y"
										),
										null,
										array("HIDE_ICONS" => "Y")
									);
									if (!empty($arFileTemplate["DATA"]))
										$arFilesHTML["thumb"][] = $arFileTemplate["RETURN_DATA"];
									else
										$arFilesHTML["files"][] = $arFileTemplate["RETURN_DATA"];
								}
							}

							if (!empty($arFilesHTML["thumb"]) || !empty($arFilesHTML["files"]))
							{
								?>
								<div class="forum-post-attachments">
									<label><?=GetMessage("F_ATTACH_FILES")?></label>
									<?
									if (!empty($arFilesHTML["thumb"]))
									{
										?><div class="forum-post-attachment forum-post-attachment-thumb"><fieldset><?=implode("", $arFilesHTML["thumb"])?></fieldset></div><?;
									}
									if (!empty($arFilesHTML["files"]))
									{
										?><div class="forum-post-attachment forum-post-attachment-files"><ul><li><?=implode("</li><li>", $arFilesHTML["files"])?></li></ul></div><?;
									}
									?>
								</div>
								<?
							}
						}

						if (!empty($res["EDITOR_NAME"])):
							?><div class="forum-post-lastedit">
								<span class="forum-post-lastedit"><?=GetMessage("F_EDIT_HEAD")?>
									<span class="forum-post-lastedit-user"><?
										if (!empty($res["URL"]["EDITOR"])):
											?><a href="<?=$res["URL"]["EDITOR"]?>"><?=$res["EDITOR_NAME"]?></a><?
										else:
											?><?=$res["EDITOR_NAME"]?><?
										endif;
										?></span> - <span class="forum-post-lastedit-date"><?=$res["EDIT_DATE"]?></span>
									<?
									if (!empty($res["EDIT_REASON"])):
										?>
										<span class="forum-post-lastedit-reason">(<span><?=$res["EDIT_REASON"]?></span>)</span>
										<?
									endif;
									?>
							</span></div><?
						endif;

						if (strLen($res["SIGNATURE"]) > 0):
							?>
							<div class="forum-user-signature">
								<div class="forum-signature-line"></div>
								<span><?=$res["SIGNATURE"]?></span>
							</div>
							<?
						endif;
						?>
					</div>
					<?
					if ($arParams["PERMISSION_ORIGINAL"] >= "Q"):
						?>
						<div class="forum-post-entry forum-user-additional forum-user-moderate-info">
							<?
							if ($res["SOURCE_ID"] == "EMAIL"):
								?>
								<span><?=GetMessage("F_SOURCE_ID")?>: <?
									if (!empty($res["MAIL_HEADER"])):
										if ($res["PANELS"]["MAIL"] == "Y" && !empty($res["XML_ID"])):
											$res["MAIL_HEADER"] .= "<br /><a href=\"/bitrix/admin/mail_message_view.php?MSG_ID=".$res["XML_ID"]."\">".GetMessage("F_ORIGINAL_MESSAGE")."</a>";
										endif;
										?><a href="#" onclick="this.nextSibling.style.display=(this.nextSibling.style.display=='none'?'':'none'); return false;" title="<?=GetMessage("F_EMAIL_ADD_INFO")?>">e-mail</a><?

										?><div>
										<div class="forum-note-box forum-note-success">
											<div class="forum-note-box-text">
												<?=preg_replace("/\r\n/", "<br />", $res["MAIL_HEADER"])?>
											</div>
										</div>
									</div><?
									else:
										?><span>e-mail</span> <?
									endif;

									?></span>
								<?
							endif;
							if ($res["IP_IS_DIFFER"] == "Y"):
								?>
								<span>IP<?=GetMessage("F_REAL_IP")?>: <span><?=$res["AUTHOR_IP"];?> / <?=$res["AUTHOR_REAL_IP"];?></span></span>
								<?
							else:
								?>
								<span>IP: <span><?=$res["AUTHOR_IP"];?></span></span>
								<?
							endif;
							if ($res["PANELS"]["STATISTIC"] == "Y"):
								?>
								<span><?=GetMessage("F_USER_ID")?>: <span><a href="/bitrix/admin/guest_list.php?lang=<?=LANG_ADMIN_LID?><?
									?>&amp;find_id=<?=$res["GUEST_ID"]?>&amp;set_filter=Y"><?=$res["GUEST_ID"];?></a></span></span>
								<?
							endif;

							if ($res["PANELS"]["MAIN"] == "Y"):
								?>
								<span><?=GetMessage("F_USER_ID_USER")?>: <span><?
									?><a href="/bitrix/admin/user_edit.php?lang=<?=LANG_ADMIN_LID?>&amp;ID=<?=$res["AUTHOR_ID"]?>"><?=$res["AUTHOR_ID"];?></a></span></span>
								<?
							endif;
							?>
						</div>
						<?
					elseif ($res["SOURCE_ID"] == "EMAIL"):
						?>
						<div class="forum-post-entry forum-user-additional forum-user-moderate-info">
							<span><?=GetMessage("F_SOURCE_ID")?>: <span>e-mail</span></span>
						</div>
						<?
					endif;
					?>
				</td>
				</tr>
				<tr>
					<td class="forum-cell-contact">
						<div class="forum-contact-links">
							<?
							if ($arParams["SHOW_MAIL"] == "Y" && strlen($res["EMAIL"]) > 0):
								?>
								<span class="forum-contact-email"><a href="<?=$res["URL"]["AUTHOR_EMAIL"]?>" title="<?=GetMessage("F_EMAIL_TITLE")?>">E-mail</a></span>
								<?
							else:
								?>
								&nbsp;
								<?
							endif;
							?>
						</div>
					</td>
					<td class="forum-cell-actions">
						<div class="forum-action-links">
							<?
							if ($res["NUMBER"] == 1):
								if ($res["PANELS"]["MODERATE"] == "Y"):
									if ($arResult["TOPIC"]["APPROVED"] != "Y"):
										?>
										<span class="forum-action-show"><a onclick="return fasessid(this);" href="<?
											?><?=$GLOBALS["APPLICATION"]->GetCurPageParam("ACTION=SHOW_TOPIC", array("ACTION", "sessid"))?>"><?
											?><?=GetMessage("F_SHOW_TOPIC")?></a></span>
										<?
									elseif (false):
										?>
										<span class="forum-action-hide"><a onclick="return fasessid(this);" href="<?
											?><?=$GLOBALS["APPLICATION"]->GetCurPageParam("ACTION=HIDE_TOPIC", array("ACTION", "sessid"))?>"><?
											?><?=GetMessage("F_HIDE_TOPIC")?></a></span>
										<?
									endif;
								endif;
								if ($res["PANELS"]["DELETE"] == "Y"):
									?>
									&nbsp;&nbsp;<span class="forum-action-delete"><a onclick="if(confirm(oText['cdt'])){return fasessid(this);}return false;" href="<?
										?><?=$GLOBALS["APPLICATION"]->GetCurPageParam("ACTION=DEL_TOPIC", array("ACTION", "sessid"))?>"><?
										?><?=GetMessage("F_DELETE_TOPIC")?></a></span>
									<?
									if ($res["SOURCE_ID"] == "EMAIL"):
										?>
										&nbsp;&nbsp;<span class="forum-action-spam"><a onclick="if(confirm(oText['cdt'])){return fasessid(this);}return false;" href="<?
											?><?=$GLOBALS["APPLICATION"]->GetCurPageParam("ACTION=SPAM_TOPIC", array("ACTION", "sessid"))?>"><?
											?><?=GetMessage("F_SPAM")?></a></span>
										<?
									endif;
								endif;
								if ($res["PANELS"]["EDIT"] == "Y" && $arResult["USER"]["PERMISSION"] >= "U"):
									?>
									&nbsp;&nbsp;<span class="forum-action-edit"><a href="<?=$res["URL"]["MESSAGE_EDIT"]?>"><?=GetMessage("F_EDIT_TOPIC")?></a></span>
									<?
								elseif ($res["PANELS"]["EDIT"] == "Y"):
									?>
									&nbsp;&nbsp;<span class="forum-action-edit"><a href="<?=$res["URL"]["MESSAGE_EDIT"]?>"><?=GetMessage("F_EDIT")?></a></span>
									<?
								endif;
							else:
								if ($res["PANELS"]["MODERATE"] == "Y"):
									if ($res["APPROVED"] == "Y"):
										?>
										<span class="forum-action-hide"><a <?
											if ($arParams['AJAX_POST'] == 'Y') { ?>onclick="return forumActionComment(this, 'MODERATE');"<? }
											else { ?>onclick="return fasessid(this);"<? }
											?> href="<?=$res["URL"]["MESSAGE_SHOW"]?>"><?=GetMessage("F_HIDE")?></a></span>&nbsp;&nbsp;
										<?
									else:
										?>
										<span class="forum-action-show"><a <?
											if ($arParams['AJAX_POST'] == 'Y') { ?>onclick="return forumActionComment(this, 'MODERATE');"<? }
											else { ?>onclick="return fasessid(this);"<? }
											?> href="<?=$res["URL"]["MESSAGE_SHOW"]?>"><?=GetMessage("F_SHOW")?></a></span>&nbsp;&nbsp;
										<?
									endif;
								endif;
								if ($res["PANELS"]["DELETE"] == "Y"):
									?>
									<span class="forum-action-delete"><noindex><a rel="nofollow" <?
										if ($arParams['AJAX_POST'] == 'Y') {
											?>onclick="return forumActionComment(this, 'DEL');"<?
										} else {
											?>onclick="if(confirm(oText['cdm'])){return fasessid(this);}return false;"<?
										} ?> href="<?=$res["URL"]["MESSAGE_DELETE"]?>" <?
										?>><?=GetMessage("F_DELETE")?></a></noindex></span>&nbsp;&nbsp;
									<?
									if ($res["SOURCE_ID"] == "EMAIL"):
										?>
										<span class="forum-action-spam"><a href="<?=$res["URL"]["MESSAGE_SPAM"]?>" <?
											?>onclick="if (confirm(oText['cdm'])){return fasessid(this);}return false;"><?=GetMessage("F_SPAM")?></a></span>&nbsp;&nbsp;
										<?
									endif;
								endif;
								if ($res["PANELS"]["EDIT"] == "Y"):
									?>
									<span class="forum-action-edit"><a href="<?=$res["URL"]["MESSAGE_EDIT"]?>"><?=GetMessage("F_EDIT")?></a></span>&nbsp;&nbsp;
									<?
								endif;
							endif;

							if ($arResult["USER"]["RIGHTS"]["ADD_MESSAGE"] == "Y"):
								if ($res["NUMBER"] == 1):
									?>&nbsp;&nbsp;<?
								endif;

								if ($arResult["FORUM"]["ALLOW_QUOTE"] == "Y"):
									?>
									<span class="forum-action-quote"><a title="<?=GetMessage("F_QUOTE_HINT")?>" href="#postform" <?
										?> onmousedown="if (window['quoteMessageEx']){quoteMessageEx('<?=$res["FOR_JS"]["AUTHOR_NAME"]?>', 'message_text_<?=$res["ID"]?>')}"><?
										?><?=GetMessage("F_QUOTE")?></a></span>
									<?
									if ($arParams["SHOW_NAME_LINK"] == "Y"):
										?>
										&nbsp;&nbsp;<span class="forum-action-reply"><a href="#postform" title="<?=GetMessage("F_INSERT_NAME")?>" <?
										?> onmousedown="reply2author('<?=$res["FOR_JS"]["AUTHOR_NAME"]?>,', 'message_text_<?=$res["ID"]?>')"><?
										?><?=GetMessage("F_NAME")?></a></span>
										<?
									endif;
								elseif ($arParams["SHOW_NAME_LINK"] != "Y"):
									?>
									<span class="forum-action-reply"><a href="#postform" <?
										?> onmousedown="reply2author('<?=$res["FOR_JS"]["AUTHOR_NAME"]?>,', 'message_text_<?=$res["ID"]?>')"><?
										?><?=GetMessage("F_REPLY")?></a></span>
									<?
								endif;
							else:
								?>
								&nbsp;
								<?
							endif;
							?>
						</div>
					</td>
				</tr>
				</tbody>
		<?
		if ($iNumber < $iCount || $arParams["FIRST_MESSAGE_ID"] == $res["ID"]):
			?>
			</table><!--MSG_END_<?=$res["ID"]?>-->
		<?
		endif;
	endforeach;
}
?>