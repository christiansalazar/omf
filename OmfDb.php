<?php
/**
 * OmfDb 
 *	 please install db tables using the provided sql scripts located at the same file directory.
 *	
 * 
 * @uses OmfBase
 * @abstract	
 * @package 
 * @version 1.0
 * @author Christian Salazar  <christiansalazarh@gmail.com> @salazarchris74
 * @license FREEBSD http://opensource.org/licenses/bsd-license.php
 */
class OmfDb extends OmfBase {
	public function getDb(){
		return Yii::app()->db;
	}
	public function _objects_name(){
		return "omf_object";
	}
	public function _relationship_name(){
		return "omf_relationship";
	}
	
	protected function createObject($classname, $data=null, $aux_id = null){
		$this->getDb()->createCommand()->insert($this->_objects_name(),array(
			'classname'=>$classname,'data'=>$data,'aux_id'=>$aux_id));
		return $this->getDb()->getLastInsertID($this->_objects_name());
	}
	public function loadObject($object_id){
		$row = $this->getDb()->createCommand()
			->select('id, classname, aux_id, data')
			->from($this->_objects_name())->where("id = :id",array(":id"=>$object_id))
			->queryRow();
		if($row != null){
			return $this->readObject($row);
		}else return null;
	}
	public function listObjects($classname, $limit=-1, $offset=0){
		return $this->getDb()->createCommand()
			->select('id, classname, aux_id, data')
			->from($this->_objects_name())
			->where("classname = :cn",array(":cn"=>$classname))
			->offset($offset)
			->limit($limit)
			->queryAll();
	}
	public function countObjectsByClassname($classname){
		if($row = $this->getDb()->createCommand()
			->select('count(id) counter')
			->from($this->_objects_name())->where("classname = :cn",
					array(":cn"=>$classname))
			->queryRow()) return (1*$row['counter']);
		return 0;
	}
	public function setObjectData($object_id, $data){
		return $this->getDb()->createCommand()
			->update($this->_objects_name(),array("data"=>$data)
			,"id = :id",array(":id"=>$object_id));
	}
	public function setObjectAuxId($object_id, $aux_id){
		return $this->getDb()->createCommand()
			->update($this->_objects_name(),
			array("aux_id"=>$aux_id),"id = :id",array(":id"=>$object_id));
	}
	public function deleteObjById($object_id) {
		return $this->getDb()->createCommand()
			->delete($this->_objects_name(),"id = :id",
			array(":id"=>$object_id));
	}
	public function deleteObjByClassname($classname){
		return $this->getDb()->createCommand()
			->delete($this->_objects_name(),"classname = :cn",
			array(":cn"=>$classname));
	}
	
	protected function createRel($from, $to, $name, $data=""){
		$this->getDb()->createCommand()->insert($this->_relationship_name(),
			array('parent'=>$from,'child'=>$to, 
				'name'=>$name,'data'=>$data));
		return $this->getDb()->getLastInsertID($this->_relationship_name());
	}
	public function loadRelation($id){
		if($row = $this->getDb()->createCommand()
			->select('id, parent, child, name, data')
			->from($this->_relationship_name())
			->where("(id = :id)", array(':id'=>$id))
			->queryRow()) return $this->readRelation($row);
		return null;
	}
	protected function listRel($object_id, $parent_or_child, $name=""){
		$p = array(":obj"=>$object_id);
		$w = "(".$parent_or_child." = :obj)";
		if($name != "") { $w .= " and (name = :n)"; $p[':n'] = $name;  }
		return $this->getDb()->createCommand()
			->select('id, parent, child, name, data')
			->from($this->_relationship_name())
			->where($w,$p)
			->queryAll();
	}
	public function setRelationData($rel_id, $data){
		return $this->getDb()->createCommand()
			->update($this->_relationship_name(),
			array("data"=>$data),"id = :id",array(":id"=>$rel_id));
	}
	public function deleteRel($id){
		$this->getDb()->createCommand()->delete($this->_relationship_name()
			,"id = :id",array(":id"=>$id));
	}
}
