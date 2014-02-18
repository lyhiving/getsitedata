<?php

require_once 'unzip.php';
require_once 'csvReader.php';
require_once 'mailConfig.php';

class zipFileHandle{
	
	public $dir = ATTACHMENTS_DIR;//文件目录
	public $filename;
	public $db;
	
	
	function __construct($filename){
		$this->filename = $filename;
		$this->dbconect();
	}
	//解压缩
	function zipToCsv(){
		$zip = new Unzip();
		$zip->allow(array('csv','xlsx','doc','docx'));
		$filelocation = $zip->extract($this->dir.$this->filename);
		return $filelocation[0];
	}
	//文件更名 放入指定文件夹
	function fileRename(){
		$file = $this->zipToCsv();	
		$arr = explode('/', $file);
		$csvfile = end($arr);
		
		$typeArray = explode('_', $this->filename);
		$type = $typeArray['0'];
		
		$newname = $this->dir.'csvdir/'.$type.'_'.$csvfile;
		
		if(is_file($file) && copy($file, $newname)){
		
			unlink($file);
		}
		return array($newname,$type.'_'.$csvfile);
	}
	/**
	 * 
	 * 文件名处理解析
	 * @return multitype:string Ambigous <string> mixed
	 */
	function nameParse(){
		$file = $this->fileRename();
		$typeArray = explode('_', substr($file['1'], 0,-4));//fdc.com.cn_全部来源_20130815-20130815
		list($website,$report,$timestr) = $typeArray;//$website = 'fdc.com.cn'  $report = '全部来源'
		$time = substr($timestr, 0,8); // 20130815
		//查询站点id $sid
		$report = iconv('gb2312', 'utf-8', $report);
		
		$pre = $this->db->prepare("select id from yf_website where site_url='{$website}'");
		$pre->execute();
		$result = $pre->fetch();
		$sid = $result[0];
		return array($sid,$file['0'],$time,$report);
	}
	/**
	 * 对于首页的数据进行解析
	 */
	function indexParse(){
		$nameResult = $this->nameParse();
		list($sid,$file,$time,$report) = $nameResult;
		$sid = INDEX_ID; 
		
		$reader = new Reader($file);
		if ($report == '全部来源') {
			$dataArray = $reader->getAll();
			$allData = array_values(array_shift($dataArray));
			foreach ($dataArray as $v)
			{
				$data[] = array_values($v);
			}
			$this->imIndexSourceData($sid,$time,$data);	
		}elseif ($report == '访问入口'){
			$dataArray = $reader->getRow();
			$data = array_values($dataArray);
			$this->imIndexEntrance($sid, $time, $data, 2);
			
		}elseif ($report == '受访页面'){
			$dataArray = $reader->getRow();
			$data = array_values($dataArray);
			$this->imIndexEntrance($sid, $time, $data, 1);
	
		}else {
			print("------------------a wrong report-------------<br/>");
		}
		$reader->__destruct();
		$this->unlinkFile($file);		
	}	
	
	function imIndexEntrance($sid,$time,$dataArray,$type){
		print("-------------------------start import index entrance source {$type} data------------------<br/>");
		if ($this->checkdata($sid, $time,'yf_basedata')) {
			if ($type==1) {
				$sql = "update yf_basedata set pv='{$dataArray[2]}',uv='{$dataArray[3]}',enter_count='{$dataArray[4]}',outward='{$dataArray[5]}',log_out='{$dataArray[6]}',stay_time='{$dataArray[7]}' where sid='{$sid}' and time='{$time}'";
			}else{
				$sql = "update yf_basedata set visit='{$dataArray[2]}',new_viewer='{$dataArray[3]}',ip='{$dataArray[4]}',step_out='{$dataArray[5]}' where sid='{$sid}' and time='{$time}'";
			}
		}else {
		if ($type==1) {
		$sql = "insert into yf_basedata(sid,time,pv,uv,enter_count,outward,log_out,stay_time) values ('{$sid}','{$time}','{$dataArray[2]}','{$dataArray[3]}','{$dataArray[4]}','{$dataArray[5]}','{$dataArray[6]}','{$dataArray[7]}')";
		}else {
			$sql = "insert into yf_basedata(sid,time,visit,new_viewer,ip,step_out) values ('{$sid}','{$time}','{$dataArray[2]}','{$dataArray[3]}','{$dataArray[4]}','{$dataArray[5]}')";
		}
		}
		$result = $this->db->exec($sql);
		if ($result) {
			print("------------------------- import index entrance source {$type} data success------------------<br/>");
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * 处理首页  的全部来源 报告数据
	 * @param unknown $sid
	 * @param unknown $time
	 * @param unknown $dataArray
	 * @return void|boolean
	 */
	function imIndexSourceData($sid,$time,$dataArray){
		print("-------------------------start import index all source data------------------<br/>");
		if ($this->checkdata($sid, $time, 'yf_source')) return;
		
		$sql = "insert into yf_source (sid,time,type,pv,visit,uv,new_viewer) values ";
		$type = 1;
		foreach ($dataArray as $value){
			$sql1 ="('{$sid}','{$time}','{$type}','{$value[2]}','$value[3]','$value[4]','$value[5]')";
			$result = $this->db->exec($sql.$sql1);
			if (!$result) {
				return false;
			}
			$type++ ;
		}
		if ($type==4) {
			print("---------------------import index all source data success-----------------<br/>");
			return true;
		}
		return false;
	}
	
	
	//csv文件解析
	function csvParse(){
		$nameResult = $this->nameParse();
		list($sid,$file,$time,$report) = $nameResult;
		
		$reader = new Reader($file);
		if ($report == '全部来源') {
			$dataArray = $reader->getAll();
			$allData = array_values(array_shift($dataArray));
			foreach ($dataArray as $v)
			{
				$data[] = array_values($v);
			}
			$this->imBaseData($sid, $time, $allData, 1);
			$this->imSourceData($sid, $time, $data);
			
		}elseif ($report == '趋势分析'){
			$dataArray = $reader->getRow();
			$data = array_values($dataArray);
			$this->imBaseData($sid, $time, $data, 2);
			
		}elseif ($report == '忠诚度'){	
			$header = $reader->getHeaders();
			$headertype = iconv('gb2312', 'utf-8', $header['1']);
			$dataArray = $reader->getAll();
			array_shift($dataArray);
			foreach ($dataArray as $v)
			{
				$data[] = array_values($v);
			}
			if ($headertype == '访问页数') {
				$type = 1;
			}elseif ($headertype == '访问深度'){
				$type = 2;
			}elseif ($headertype == '访问时长'){
				$type = 3;
			}elseif ($headertype == '访问频次'){
				$type = 4;
			}else {
				return false;
			}
			$this->imLoyalSource($sid, $time, $data, $type);
			
		}elseif ($report == '外部链接'){
			$dataArray = $reader->getAll();
			array_shift($dataArray);
			$data_slice = array_slice($dataArray, 0,5);
			foreach ($data_slice as $v)
			{
				$data[] = array_values($v);
			}
			$this->imextLink($sid, $time, $data);
			
		}else {
			print("------------------a wrong report-------------<br/>");
		}
		$reader->__destruct();
		$this->unlinkFile($file);
	}
	
	function unlinkFile($file)
	{
		if (is_file($file)) {
			if(unlink($file)){
				return true;
			}
			return false;
		}
	}
	
	/**
	 * 
	 * 导入忠诚度数据 
	 * @param 站点id $sid  
	 * @param 导入时间 $time
	 * @param 导入数组 $data
	 * @param 指标类型 $type
	 */
		function imLoyalSource($sid,$time,$data,$type)
		{
			print("----------------------start import loyal {$type} source-----------------<br/>");
			$table = '';
			$key = '';
			if ($type==1) {    //访问页数
				$table = 'yf_viewpage';
				$key = 'page_num';
				
			}elseif ($type==2){  //访问深度
				$table = 'yf_viewdepth';
				$key = 'depth_num';
				
			}elseif ($type==3){   //访问时长
				$table = 'yf_viewtime';
				$key = 'view_time';
				
			}elseif ($type==4){   //访问频次
				$table = 'yf_viewcount';
				$key = 'view_count';
				
			}else {
				return false;
			}
			$sql = "insert into {$table} (sid,{$key},visit,rate,time) values ";
			$count = 0;
			$count_num = count($data);
			if ($count_num>0) {
				foreach ($data as $v){
					$temp = iconv('gb2312', 'utf-8', $v[1]);
					$sql1 = "('{$sid}','{$temp}','{$v[2]}','{$v[3]}','{$time}')";
					$re = $this->db->exec($sql.$sql1);
					if ($re) {
						$count++;
					}
				}
			}
			if ($count == $count_num) {
				print("---------------------import loyal source {$type} success---------------------<br/>");
				return true;
			}else {
				return false;
			}
			
		}
		/**
		 * 外部链接  存储 前5条记录
		 * @param 站点id  $sid
		 * @param unknown $time
		 * @param 前五条记录二维数组  $dataArray 
		 */
	function imextLink($sid,$time,$dataArray){
		print("---------------------start import external link-----------------------<br/>");
		
		$sql = "insert into yf_extlink (sid,time,link,pv,visit,uv,new_viewer,step_out,avg_page) values ";
		$count = 0;
		$count_num = count($dataArray);
		foreach ($dataArray as $v){
			$sql1 = "('{$sid}','{$time}','{$v[1]}','{$v[2]}','{$v[3]}','{$v[4]}','{$v[5]}','{$v[6]}','{$v[7]}')";
			$re = $this->db->exec($sql.$sql1);
			if ($re) {
				$count++;
			}
		}
		if ($count == $count_num) {
			print ("--------------------------import external link success-----------------------<br/>");
			
			return true;
		}else {
			return false;
		}
		
	}
	
	/**
	 * 全部来源数据-昨日统计-基础数据表yf_basedata
	 * @param $sid 站点id
	 * @param $time 时间  格式 20130820
	 * @param $dataArray  报告数据数组  一维数组
	 * @param $type =1  全部来源  $type=2 昨日统计
	 */
	function imBaseData($sid,$time,$dataArray,$type){
		print("-----------------------start import base data----------------<br/>");
		
		if ($this->checkdata($sid, $time,'yf_basedata')) {
			if ($type==1) {
				$sql = "update yf_basedata set pv='{$dataArray[2]}',visit='{$dataArray[3]}',uv='{$dataArray[4]}',new_viewer='{$dataArray[5]}',step_out='{$dataArray[6]}',avg_page='{$dataArray[7]}' where sid='{$sid}' and time='{$time}'";
			}else{
				$sql = "update yf_basedata set ip='{$dataArray[2]}',avg_time='{$dataArray[3]}',transform='{$dataArray[4]}' where sid='{$sid}' and time='{$time}'";	
			}
		}else {
			if ($type==1) {
				$sql = "insert into yf_basedata(sid,time,pv,visit,uv,new_viewer,step_out,avg_page) values ('{$sid}','{$time}','{$dataArray[2]}','{$dataArray[3]}','{$dataArray[4]}','{$dataArray[5]}','{$dataArray[6]}','{$dataArray[7]}')";		
			}else {
				$sql = "insert into yf_basedata(sid,time,ip,avg_time,transform) values ({$sid},{$time},{$dataArray[2]},{$dataArray[3]},{$dataArray[4]})";	
			}
		}
		$result = $this->db->exec($sql);
		if ($result) {
			print("-------------------------import base data success---------------<br/>");
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * 插入不同来源的数据   【全部来源】 报告中其他数据
	 * @param 站点id $sid
	 * @param 时间 $time   格式 20130820
	 * @param 数据数组 $dataArray  二维数组
	 */
	function imSourceData($sid,$time,$dataArray)
	{
		print("-------------------------start import all source data------------------<br/>");
		if ($this->checkdata($sid, $time, 'yf_source')) return;

		$sql = "insert into yf_source (sid,time,type,pv,visit,uv,new_viewer,step_out,avg_page) values ";
		$type = 1;
		foreach ($dataArray as $value){
			$sql1 ="('{$sid}','{$time}','{$type}','{$value[2]}','$value[3]','$value[4]','$value[5]','$value[6]','$value[7]')";
			$result = $this->db->exec($sql.$sql1);
			if (!$result) {
				return false;
			}
			$type++ ;
		}
		if ($type==3) {
		print("---------------------import all source data success-----------------<br/>");
		return true;				
		}
		return false;
	}
	
	//检查数据库是否有当日的记录
	function checkdata($sid,$time,$table)
	{
		$sql = "select count(*) as num from {$table} where sid='{$sid}' and time='{$time}'";
		$arr = $this->db->prepare($sql);
		$arr->execute();
		$result = $arr->fetch();
		if ($result['num']) {
			return true;
		}else {
			return false;
		}
	}
	/**
	 * 删除压缩包文件
	 */
	function __destruct(){
		if (is_dir(ATTACHMENTS_DIR)) {
			$handle = opendir(ATTACHMENTS_DIR);
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if (substr($file, -3 ,3) == 'zip') {
						print("-------------start delete zip files-------------------");
						if (unlink(ATTACHMENTS_DIR.$file)){
							print("-----------------delete success-------------------");
						}else {
							print("---------------no such file or had deleted----------");
						}
					}
				}
			}
			closedir($handle);
		}	
	}
	/**
	 * PDO 数据库连接
	 */
	function dbconect(){
		$this->db = new PDO(MAIL_CONNECT.":host=".MAIL_DBHOST.";dbname=".MAIL_DBNAME, MAIL_DBUSER, MAIL_DBPW, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '".MAIL_DBCHARSET."';"));
	}
	
	
}
