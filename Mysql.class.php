<?php 
/**
*获取数据库的信息采用常量方式,需要引入当前目录下config.php文件
*/
class Mysql {
	private $host;
	private $user;
	private $pwd;
	private $db;
	private $charset;
	private $conn = null;
	public function __construct(){
		$this->host = HOST;
		$this->user = USER;
		$this->pwd = PASSWORD;
		$this->dbName = DB;
		$this->charset = CHARSET;
		
		//连接
		$this->connect($this->host,$this->user,$this->pwd,$this->dbName);
		
		//设置字符集
		$this->setChar($this->charset);
	}
	
	private function connect($h,$u,$p,$db){
		$conn = mysqli_connect($h,$u,$p,$db);
		$this->conn = $conn;
	}
	
	//发送查询
	public function query($sql){
		$rs = mysqli_query($this->conn,$sql);
		return $rs;
	}

	//修改字符集
	private function setChar($char){
		$sql = 'set names '.$char;
		$this->query($sql);
	}

	//记录错误记录
	private function log($str){
		$filename = ROOT."/log/".date('Ymd').'.txt';
		$log = "----------------------------------------\n".date('Y/m/d H:i:s')."\n".$str."\n"."----------------------------------------\n\n";
		file_put_contents($filename, $log , FILE_APPEND);
	}

	//取出多行多列数据
	public function getAll($sql){
		$list = array();
		$rs = $this->query($sql);
		if(!$rs){
			return false;
		}
		while ($row = mysqli_fetch_assoc($rs)){
			$list[] = $row;
		}
		return $list;
	}
	//取出一行数据
	public function getRow($sql){
		$rs = $this->query($sql);
		if(!$rs){
			return false;
		}
		return mysqli_fetch_assoc($rs);
	}
	
	//取单个的值
	public function getOne($sql){
		$rs = $this->query($sql);
		if(!$rs){
			return false;
		}
		return mysqli_fetch_row($rs)[0];
	}
	
	//插入数据或修改数据
	public function Exec($table,$data,$act='insert',$where=0){
		if($act == 'insert'){
			$sql = "insert into ".$table." (";
			$sql .= implode(",",array_keys($data)).") values ('";
			$sql .= implode("','",array_values($data))."');";	
		}else if($act == 'update'){
			$sql = "update ".$table." set ";
			foreach($data as $k=>$v){
				$sql .= $k."='".$v."',";
			}
			$sql = rtrim($sql,",") . " where ".$where;
		}
		$rs = $this->query($sql);
		return $rs?true:false;
	}
	
	//取得上一步insert操作产生的主键
	public function getLastId(){
		return mysqli_insert_id($this->conn);
	}
	
	//取得上一次操作影响的行数
	public function aff(){
		return mysqli_affected_rows($this->conn);
	}
}



?>