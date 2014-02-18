<?php
/**
 * 除首页外，其他站点的 报告下载，压缩包解压，cvs文件处理，数据入库
 * 
 */

set_time_limit(600);

require_once('./ImapMailbox.php');
require_once './mailConfig.php';
require_once './zipFileHandle.php';

header("Content-type:text/html;charset=utf8");

$mailbox = new ImapMailbox(GMAIL_HOST, GMAIL_EMAIL, GMAIL_PASSWORD, ATTACHMENTS_DIR, 'utf-8');
$mails = array();

// Get some mail
$mailsIds = $mailbox->searchMailBox("ALL");
if(!$mailsIds) {
	die('Mailbox is empty');
}
 
foreach ($mailsIds as $k=>$v)
{
	$mailbox->getMail($v);
}

if (is_dir(ATTACHMENTS_DIR)) {
	$handle = opendir(ATTACHMENTS_DIR);
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			if (substr($file, -3 ,3) == 'zip') {
				$fileHandle = new zipFileHandle($file); 
				$fileHandle->csvParse();
				$fileHandle->__destruct();
			}
		}
	}
	closedir($handle);
}




?>
