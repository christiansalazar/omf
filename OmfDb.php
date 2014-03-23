<?php
/**
 * OmfDb 
 *	 please install db tables using the provided sql scripts located at the same file directory.
 *
 *	 this class must be used as direct intance either or as yii component.
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
	public function _index_name(){
		return "omf_index";
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
	/**
	 * listObjectsBy 
	 *	return objects having a key attribute.
	 *	
	 *	return values:
	 *		integer >= 0, when calling with counter_only = true, never null
	 *		array, never null when calling with counter_only = false
	 * 
	 * @param mixed $classname 
	 * @param mixed $attribute 
	 * @param mixed $value 
	 * @param mixed $limit 
	 * @param int $offset 
	 * @param mixed $counter_only 
	 * @access public
	 * @return mixed see note
	 */
	public function listObjectsBy($classname, $attribute, $value, $limit=-1, $offset=0, $counter_only=false){
		//	remember the metadata architecture in OMF
		//
		//	[A:Someclass]----[metaname_attributenme]--->[:Metadata{data=value}]
		//		parent    								   child
		//
		//	this equivalent to say:  A.attributename = value
		//
		$fields = 'obj.id, obj.classname, obj.aux_id, obj.data';
		if($counter_only===true) $fields = "count(obj.id) as counter";
		$relname = $this->buildMetanameRel($attribute);
		if($counter_only===true){
			$row=array();
			if(!empty($attribute)){
			$row = $this->getDb()->createCommand()
			->select($fields)
			->from($this->_objects_name()." obj")
			->leftjoin($this->_relationship_name()." R","R.parent = obj.id")
			->leftjoin($this->_objects_name()." C","R.child = C.id")
			->where("obj.classname = :cn and R.name = :rn and C.data = :v",
				array(":cn"=>$classname,":rn"=>$relname,":v"=>$value))
			->queryRow();
			}else{
			$row = $this->getDb()->createCommand()	
			->select($fields)                          		
			->from($this->_objects_name()." obj")
			->where("obj.classname = :cn",
				array(":cn"=>$classname))
			->queryRow();                                   		
			}
			if(!$row) return 0;
			return 1*$row['counter'];
		}else{
			$cmd = null;
			if(!empty($attribute)){
			$cmd = $this->getDb()->createCommand()             	
			->select($fields)                                  	
			->from($this->_objects_name()." obj")
			->leftjoin($this->_relationship_name()." R","R.parent = obj.id")
			->leftjoin($this->_objects_name()." C","R.child = C.id")
			->where("obj.classname = :cn and R.name = :rn and C.data = :v",
				array(":cn"=>$classname,":rn"=>$relname,":v"=>$value))
			;
			}else{
			$cmd = $this->getDb()->createCommand()
			->select($fields)
			->from($this->_objects_name()." obj")
			->where("obj.classname = :cn",
				array(":cn"=>$classname,))
			;
			}
			$rows = $cmd->offset($offset)
			->limit($limit)
			->queryAll();
			if(empty($rows)) return array();
			return $rows;
		}
	}
	public function countObjectsByClassname($classname){
		if($row = $this->getDb()->createCommand()
			->select('count(id) counter')
			->from($this->_objects_name())->where("classname = :cn",
					array(":cn"=>$classname))
			->queryRow()) return (1*$row['counter']);
		return 0;
	}
	public function insertIndex($classname, $metaname, $hashvalue, $object_id){
		$this->getDb()->createCommand()->insert($this->_index_name(),array(
			'classname'=>$classname,'metaname'=>$metaname,
				'hashvalue'=>$hashvalue, 'object_id'=>$object_id));
	}
	public function updateIndex($classname, $metaname, $hashvalue, $object_id){
		$this->getDb()->createCommand()->update($this->_index_name(),array(
			"hashvalue"=>$hashvalue),
			"classname=:cn and metaname=:mn and object_id=:id",
			array(":cn"=>$classname,":mn"=>$metaname,":id"=>$object_id));
	}
	public function countIndex($classname, $metaname, $hashvalue){
		$r = $this->getDb()->createCommand()->select('count(object_id) as cnt')
		->from($this->_index_name())
		->where("classname=:cn and metaname=:mn and hashvalue=:hv"
			,array(':cn'=>$classname,':mn'=>$metaname,':hv'=>$hashvalue))
		->queryRow();
		if($r) return 1*($r['cnt']);
		return 0;
	}
	public function findIndex($classname, $metaname, $hashvalue, $offset=0, $limit=-1){
		return $this->getDb()->createCommand()->select('object_id')
		->from($this->_index_name())
		->where("classname=:cn and metaname=:mn and hashvalue=:hv"
			,array(':cn'=>$classname,':mn'=>$metaname,':hv'=>$hashvalue))
		->offset($offset)
		->limit($limit)
		->queryAll();
	}
	public function findIndexValue($classname, $metaname, $object_id){
		if($r = $this->getDb()->createCommand()
			->select('hashvalue')
			->from($this->_index_name())
			->where("classname=:cn and metaname=:mn and object_id=:id",
			array(":cn"=>$classname,":mn"=>$metaname,":id"=>$object_id))
			->queryRow()){
			return $r['hashvalue'];		
		}else
		return null;
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
	protected function listRel($object_id, $parent_or_child, $name="",$offset=0,$limit=-1){
		$p = array(":obj"=>$object_id);
		$w = "(".$parent_or_child." = :obj)";
		if($name != "") { $w .= " and (name = :n)"; $p[':n'] = $name;  }
		return $this->getDb()->createCommand()
			->select('id, parent, child, name, data')
			->from($this->_relationship_name())
			->where($w,$p)
			->offset($offset)
			->limit($limit)
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

	/**
	 * listObjectsHavingRelationship
	 *
	 *	find objects in the database having a given relationship name.
	 *
	 *	sample structure:
	 *
	 *		[parent:anyclass]----somerelname---->[target: someclassname]
	 *											|
	 *											|---email---->[Metadata]
	 *											|
	 *											|---firstname---->[Metadata]
	 *
	 *	having this sample structure, the method usage is:	
	 *	
	 *		$ar = $this->listObjectsHavingRelationship('somerelname','parent','someclassname');
	 *		foreach($ar as $obj){
	 *			$parent_id = $obj['parent_id'];  	// parent object
	 *			$target_id = $obj['target_id'];		// the target object listed
	 *			$attributes = $obj['attributes'];	// target attributes
	 *			
	 *			
	 *		}
	 *
	 *	about $direction:
	 *
	 *		it dictates how to handle the relationship used to find objects:
	 *
	 *		'parent': [anyclass]---------------->[target]
	 *			in this case we are looking for target objects in the right side of this
	 *
	 *		'child'	: [anyclass]<----------------[target]
	 * 
	 * @param mixed $rel_name 
	 * @param mixed $direction 'parent' or 'child'
	 * @param mixed $classname 
	 * @param string $keywords 
	 * @param int $offset 
	 * @param mixed $limit 
	 * @param mixed $countonly 
	 * @access protected
	 * @return array  array fields: ('parent_id','target_id','attributes') see note.
	 */
	protected function listObjectsHavingRelationship($rel_name,$direction,$classname,$keywords="",$offset=0,$limit=-1,$countonly=false){
			
		$opposite_direction = $direction=='parent' ? 'child' : 'parent';

		$tmpTableName='_OmfTdcAllObjects'.rand(100000,999999);
		$where_clause = 
			 " rtarget.name = :targetrelname"
			." and target.classname = :cs and meta.classname = :csmeta"
			;
		$where_params = array(
			":targetrelname"=>$rel_name,
			":cs"=>$classname,
			":csmeta"=>"metadata",
		);
		$cmd1 = $this->getDb()->createCommand();
		$cmd2 = $this->getDb()->createCommand();

		$cmd1->select(
			"rtarget.".$direction." parent_id, target.id target_id,"
			."GROUP_CONCAT(lcase(substr(rmeta.name,10)),'=',lcase(meta.data)) attributes")
			->from($this->_relationship_name()." rtarget")
			->leftjoin($this->_objects_name()." target","target.id = rtarget.".$opposite_direction)
			->leftjoin($this->_relationship_name()." rmeta","rmeta.parent = target.id")
			->leftjoin($this->_objects_name()." meta","rmeta.child = meta.id")
			->where($where_clause,$where_params)
			->group('parent_id, target_id');

		$cmd3 = $this->getDb()->createCommand(
			"drop temporary table if exists ".$tmpTableName.";");
		$cmd3->execute();
		$cmd3 = $this->getDb()->createCommand(
			"create temporary table ".$tmpTableName." as ".$cmd1->text);
		$cmd3->execute($where_params);

		$fields="";
		if($countonly==true)
			$fields="count(object_id) counter";

		$cmd2->select($fields)->from($tmpTableName);
		if(!empty($keywords)){
			$w = "";$and="";$n=0;
			$params = array();
			foreach(explode(" ",$keywords) as $keyw){
				$kname = ":keyw_".$n;
				$w .= $and." (attributes like lcase(".$kname.")) ";
				$and=" and ";
				$params[$kname] = '%'.$keyw.'%';
				$n++;
			}
			$cmd2->where($w,$params);
		}
		if($countonly == true){
			$r = $cmd2->queryRow();
			return 1*($r['counter']);
		}else{
			$cmd2->offset($offset)->limit($limit);
			return $cmd2->queryAll();
		}
	}

}
