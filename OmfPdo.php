<?php
require_once("OmfBase.php");
/**
 * OmfPdo
 *	 provides a PDO implementation for OmfBase
 *	 please install db tables using the provided sql scripts located at the same file directory.
 * 
 * @uses OmfBase
 * @version 1.1
 * @author Christian Salazar  <christiansalazarh@gmail.com> @salazarchris74
 * @license FREEBSD http://opensource.org/licenses/bsd-license.php
 */
class OmfPdo extends OmfBase {
	protected $db;
	
	public function __construct(){
		$this->getDb();
	}
	public function __destruct(){
		if($this->db){
			$this->db = null;
		}
	}
	public function getDb(){
		if(null == $this->db){
			$this->db= new PDO("mysql:host=".DB_HOST.";"
				."dbname=".DB_NAME.";charset=".DB_CHARSET, DB_USER, DB_PASSWORD);
		}
		return $this->db;
	}
	public function _objects_name(){
		return "omf_object";
	}
	public function _index_name(){
		return "omf_index";
	}
	public function _relationship_name(){
		return "omf_relationship";
	}

// 1. low level core api
	protected function createObject($classname, $data=null, $aux_id = null){
		$st = $this->db->prepare(
			sprintf("INSERT INTO %s(classname, data) "
				."VALUES(:cs, :data)",$this->_objects_name()));
		$st->execute(array(":cs"=>$classname, ":data"=>$data));
		return $this->db->lastInsertId();
	}
	public function loadObject($object_id){
		$stmt = $this->db->prepare(sprintf("SELECT * FROM %s WHERE id=:id",
			$this->_objects_name()));
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if($rows != null){
			return $this->readObject($rows[0]);
		}else return null;
	}
	public function listObjects($classname){
		$stmt = $this->db->prepare(sprintf("SELECT * FROM %s "
			."where classname = :cs",
			$this->_objects_name()));
		$stmt->bindValue(':cs', $classname, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function countObjectsByClassname($classname){
		$stmt = $this->db->prepare(sprintf("SELECT * FROM %s "	
    		."where classname = :cs",
    		$this->_objects_name()));
    	$stmt->bindValue(':cs', $classname, PDO::PARAM_STR);
    	$stmt->execute();
    	return $stmt->rowCount();
	}
	public function deleteObjById($object_id) {
		$stmt = $this->db->prepare(sprintf("DELETE FROM %s "
			."where id = :id",
			$this->_objects_name()));
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->execute();
	}
	public function deleteObjByClassname($classname){
		$stmt = $this->db->prepare(sprintf("DELETE FROM %s "
			."where classname = :cs",
			$this->_objects_name()));
		$stmt->bindValue(':cs', $classname, PDO::PARAM_STR);
		$stmt->execute();
	}
	public function setObjectData($object_id, $data){
		$stmt = $this->db->prepare(sprintf("UPDATE %s "
			." set data = :data where id = :id",
			$this->_objects_name()));
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->bindValue(':data', $data, PDO::PARAM_STR);
		$stmt->execute();
	}
	public function setObjectAuxId($object_id, $value){
		$stmt = $this->db->prepare(sprintf("UPDATE %s "
			." set aux_id = :value where id = :id",
			$this->_objects_name()));
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->bindValue(':value', $value, PDO::PARAM_STR);
		$stmt->execute();
	}
// 2. low level relations api
	protected function createRel($from, $to, $name, $data=""){
		$st = $this->db->prepare(
			sprintf("INSERT INTO %s(parent, child,name,data) "
				."VALUES(:parent,:child,:name,:data)",
					$this->_relationship_name()));
		$st->execute(array('parent'=>$from,'child'=>$to, 
				'name'=>$name,'data'=>$data));
		return $this->db->lastInsertId();
	}
	protected function findRel($from, $to, $name){
		$st = $this->db->prepare(
			sprintf("SELECT id from %s "
				."WHERE parent=:parent and child=:child and name=:name",
					$this->_relationship_name()));
		$st->execute(array('parent'=>$from,'child'=>$to,'name'=>$name));
		if($rows = $st->fetchAll(PDO::FETCH_ASSOC)){
			foreach($rows as $row)
				return $row['id']; // paranoic. but it is supposed to have only one.
		}
		return null;
	}
	public function loadRelation($id){
		$stmt = $this->db->prepare(sprintf("SELECT * FROM %s WHERE id=:id",
			$this->_relationship_name()));
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if($rows != null){
			return $this->readRelation($rows[0]);
		}else return null;
	}
	protected function listRel($object_id, $parent_or_child, $name="",$offset=0,$limit=-1){
		$w = "(".$parent_or_child." = :obj)";
		$p = array(":obj"=>$object_id);
		if($name != "") { $w .= " and (name = :n)"; $p[':n'] = $name;  }

		$stmt = $this->db->prepare(sprintf("SELECT * FROM %s WHERE %s",
			$this->_relationship_name(),$w));
		$stmt->execute($p);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function setRelationData($rel_id, $data){
		$stmt = $this->db->prepare(sprintf("UPDATE %s "
			." set data = :data where id = :id",
			$this->_relationship_name()));
		$stmt->bindValue(':id', $rel_id, PDO::PARAM_INT);
		$stmt->bindValue(':data', $data, PDO::PARAM_STR);
		$stmt->execute();
	}
	public function deleteRel($id){
		$stmt = $this->db->prepare(sprintf("DELETE FROM %s "
			."where id = :id",
			$this->_relationship_name()));
		$stmt->bindValue(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
	}
//
	//for testing pruposes
	public function deleteAllIndexRecords(){
		$stmt = $this->db->prepare(sprintf("DELETE FROM %s",
			$this->_index_name()));
		$stmt->execute();
	}
// 3. low level index api
	public function insertIndex($classname, $metaname, $hashvalue, $object_id){
		$st = $this->db->prepare(
			sprintf("INSERT INTO %s(classname,metaname,hashvalue,object_id) "
				."VALUES(:cs,:mn,:hs,:obj)",
					$this->_index_name()));
		$st->execute(array(':cs'=>$classname,':mn'=>$metaname,
			':hs'=>$hashvalue,':obj'=>$object_id));
		return $this->db->lastInsertId();
	}
	public function updateIndex($classname, $metaname, $hashvalue, $object_id){
		$stmt = $this->db->prepare(sprintf("UPDATE %s "
			." set hashvalue=:hashvalue where classname=:classname and "
				."metaname=:metaname and object_id=:id",
			$this->_index_name()));
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->bindValue(':hashvalue', $hashvalue, PDO::PARAM_STR);
		$stmt->bindValue(':classname', $classname, PDO::PARAM_STR);
		$stmt->bindValue(':metaname', $metaname, PDO::PARAM_STR);
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->execute();
	}
	public function findIndex($classname, $metaname, $hashvalue, $offset=0, $limit=-1,$count_only=false){
		if($limit==-1)
			$limit=1000000;
		$sql = sprintf("SELECT object_id FROM %s "	
			." where classname = :classname and metaname=:metaname "
				." and hashvalue=:hashvalue "
				." LIMIT %s,%s"
			,$this->_index_name(),$offset,$limit);
		$stmt = $this->db->prepare($sql);
		$stmt->bindValue(':hashvalue', $hashvalue, PDO::PARAM_STR);
		$stmt->bindValue(':classname', $classname, PDO::PARAM_STR);
		$stmt->bindValue(':metaname', $metaname, PDO::PARAM_STR);
		$stmt->execute();
		if(true==$count_only){
			return $stmt->rowCount();
		}else{
			$data=null;
			if($rows = $stmt->fetchAll(PDO::FETCH_ASSOC)){
				$data = array();
				foreach($rows as $row)
					$data[] = $row["object_id"];
				return $data;
			}	
			return null;
		}
	}	
	public function findIndexValue($classname, $metaname, $object_id){
		$stmt = $this->db->prepare(sprintf("SELECT hashvalue FROM %s "	
			."where classname = :classname and metaname=:metaname and object_id=:id order by id desc",
			$this->_index_name()));
		$stmt->bindValue(':classname', $classname, PDO::PARAM_STR);
		$stmt->bindValue(':metaname', $metaname, PDO::PARAM_STR);
		$stmt->bindValue(':id', $object_id, PDO::PARAM_INT);
		$stmt->execute();
		if($rows = $stmt->fetchAll(PDO::FETCH_ASSOC)){
			return 1*$rows[0]["hashvalue"];
		}else
		return 0;
	}
	public function enumClassnames(){
		$stmt = $this->db->prepare(sprintf(
			"SELECT classname,count(id) objects FROM %s "	
			."group by classname order by classname",
			$this->_objects_name()));
		$stmt->execute();
		if($rows = $stmt->fetchAll(PDO::FETCH_ASSOC)){
			$list = array();
			foreach($rows as $row)
				$list[$row['classname']] = $row['objects'];
			return $list;
		}else
		return array();
	}
	public function rebuildIndexes(){
		$stmt = $this->db->prepare(sprintf("DELETE FROM %s",
			$this->_index_name()));
		$stmt->execute();
		//
		$s1 = $this->db->prepare(sprintf(
			"SELECT id,classname FROM %s",$this->_objects_name()));
		$s1->execute();
		while($data = $s1->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)){
			list($id, $classname) = $data;
			$attributes = $this->getAttributes($id);
			//printf("%s,%s...\n",$id,$classname);
			foreach($attributes as $name=>$value){
				//printf("\t%s = %s\n",$name,$value);
				$this->setIndex($classname, $name, $value, $id);
			}
		}	
	}
}
