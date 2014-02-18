<?php

require_once 'unzip.php';
require_once 'csvReader.php';

header("Content-type:text/html;charset=gb2312");

//检索文档目录
$zipfile = "******_1493742142663559283_2013081520130815.zip";

//解压操作
$zip = new Unzip();
$zip->allow(array('csv'));
$location = $zip->extract('./attach/'.$zipfile);
$arr = explode('/', $location[0]);
$csvfile = $arr['2'];

$typeArray = explode('_', $zipfile);
$type = $typeArray['0']; 

$newname = './attach/csvdir/'.$type.'_'.$csvfile;

if(is_file($location[0]) && copy($location[0], $newname)){
	
	unlink($location[0]);
	
	$reader = new Reader($newname);
	while ($row = $reader->getRow()) {
 	   var_dump($row);
	}
	var_dump($reader);

}
// $row = 1;
// $handle = fopen("./attach/20130814-20130814.csv","r");
// while ($data = fgetcsv($handle, 1000, ",")) {
// 	$num = count($data);
// 	echo "<p> $num fields in line $row: <br>\n";
// 	$row++;
// 	for ($c=0; $c < $num; $c++) {
// 	echo $data[$c] . "<br>\n";
// 	}
// 	}
// fclose($handle);


?>
