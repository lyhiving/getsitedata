<?php

// IMAP must be enabled in Google Mail Settings
//two email account
define('GMAIL_EMAIL', '******@163.com');
define('GMAIL_PASSWORD', '*****');
define('GMAIL_HOST', '{imap.163.com:143/imap}INBOX');

define('EMAIL_ACCOUNT', '*****@163.com');
define('EMAIL_PASSWORD', '******');
define('EMAIL_HOST', '{imap.163.com:143/imap}INBOX');
/**************************************************/

define('ATTACHMENTS_DIR', './attach/');
//database cofiguration
define('MAIL_CONNECT', 'mysql');
define('MAIL_DBHOST', 'localhost');
define('MAIL_DBUSER', 'root');
define('MAIL_DBPW', '');

define('MAIL_DBNAME', '××××');
define('MAIL_DBCHARSET', 'utf8');
define('MAIL_DBTABLEPRE', '××××');
define('MAIL_DBCONNECT', 0);

//首页的主键id，若数据库中的值改变了，这点需要对应上
define('INDEX_ID', 2);

$siteType = array(
	1=>	'×××.com.cn',
	2=>	'×××××.com.cn',
	3=>	'××××.com.cn',
	4=>	'×××.××××.com.cn',
	5=>	'×××.××××.com.cn',
	6=>	'××××.×××.com.cn',
);
$reportType = array(
	'全部来源',	
	'昨日统计',
	'受访页面',
	'入口页面',
	'忠诚度',	
	'站内来源',	
)




?>
