<?php
/**
 * OmfBase 
 *	
 * 
 * @abstract	
 * @package 
 * @version 1.1
 * @author Christian Salazar  <christiansalazarh@gmail.com> @salazarchris74
 * @license FREEBSD http://opensource.org/licenses/bsd-license.php
 */
abstract class OmfBase {
	/**
	 * readObject 
	 * 	read an object from an indexed array and returns a single array.
	 * @param array $obj indexed array, keys: id, classname, aux_id, data
	 * @access public
	 * @seealso listObjects
	 * @return array single omf_object array(id, classname, aux_id, data)
	 */
	public function readObject($obj) {
		return array($obj['id'],$obj['classname'],$obj['aux_id'],$obj['data']);
	}
	/**
	 * readRelation 
	 * 	read a relationship from an indexed array and returns a single array.
	 * @param array $obj indexed array having keys: id,parent,child,name,data
	 * @access public
	 * @seealso listRel
	 * @return array non indexed array, pure values.
	 */
	public function readRelation($obj){
		return array($obj['id'], $obj['parent'], $obj['child'], 
			$obj['name'], $obj['data']);
	}
	/**
	 * createObject 
	 * 	abstract. store a new object.
	 * 
	 * @param string $classname 
	 * @param string $data 
	 * @param string $aux_id 
	 * @access protected
	 * @return integer the new ID
	 */
	abstract protected function createObject($classname, $data="", $aux_id="");
	/**
	 * loadObject 
	 * 	abstract. find an object using its primary key value.
	 * @param integer $object_id 
	 * @access public
	 * @return array(id, classname, aux_id, data)
	 */
	abstract public function loadObject($object_id);

	/**
	 * setObjectData 
	 *	set a value for data attribute into the pointed object.
	 * @param integer $object_id 
	 * @param string $data 
	 * @abstract
	 * @access protected
	 * @return void
	 */
	abstract public function setObjectData($object_id, $data);
	abstract public function setObjectAuxId($object_id, $aux_id);
	abstract public function countObjectsByClassname($classname);
	abstract public function setRelationData($rel_id, $data);

	/**
	 * listObjects 
	 *	return objects as an indexed array. filetred by classname.
	 *	must use readObject for each row returned in this indexed array.
	 *
	 *	foreach($this->listObjects() as $row) {
	 *		list($id, $classname, $aux_id, $data) = $this->readObject($row);
	 *		// do something
	 *	}
	 *
	 * @param string $classname 
	 * @abstract
	 * @access public
	 * @seealso readObject
	 * @return array indexed array array('id','classname','aux_id','data')
	 */
	abstract public function listObjects($classname);

	/**
	 * createRel 
	 * 	abstract. store a new relationship between $from and $to, having $name. 
	 *		in other words: from---[something]--->to
	 *		example: car---[parked]--->garage
	 		createRel($carObject, $garageObject, "parked")
		the data argument can be used at any moment, as an example 
		to hold access rules or something else.
	 *	
	 * @param integer $from
	 * @param integer $to 
	 * @param string $name 
	 * @param string $data 
	 * @access protected
	 * @return integer the new ID for this relationship
	 */
	abstract protected function createRel($from, $to, $name, $data="");
	abstract protected function deleteRel($id);
	
	/**
	 * loadRelation 
	 * 	load a relationship using its primary key ($id)
	 * @param integer $id primary key to find for
	 * @abstract
	 * @access public
	 * @return array omf_relation returned by this->readRelation()
	 */
	abstract public function loadRelation($id);

	/**
	 * listRel 
	 *	abstract.list relationships for a given object depending on provided
	 *	attribute name: parent_or_child.  example: if 'parent' is provided then
	 *	returns relationships where object_id is in the relationship.parent.
	 *	extra filtered by: relationship.name.
	 *
	 *	each object returned by this method must be processed by: readRelation
	 *	foreach($this->listRel(...) as $r){
	 *		list($id, $parent, $child, $name, $data) = $this->readRelation($r);
	 *	}
	 * 	
	 * @param integer $object_id 
	 * @param string $parent_or_child 'parent' or 'child'
	 * @param string $name relationship name to be filtered by. can be empty
	 * @access public
	 * @seealso readRelation
	 * @return array array(array(id, parent_id, child_id, name, data))
	 */
	abstract protected function listRel($object_id, $parent_or_child, $name="",$offset=0,$limit=-1);

	abstract public function deleteObjById($object_id);
	abstract public function deleteObjByClassname($classname);
	abstract public function insertIndex($classname, $metaname, $hashvalue, $object_id);
	abstract public function updateIndex($classname, $metaname, $hashvalue, $object_id);
	abstract public function findIndex($classname, $metaname, $hashvalue, $offset=0, $limit=-1,$count_only=false);
	abstract public function findIndexValue($classname, $metaname, $object_id);

	/**
	 * create 
	 *	creates a new object. if parent is provided then it creates a new 
	 *	relationship named 'parent' between the parent and this instance
	 * 
	 * @param string $classname the class name, ie: 'contact' 
	 * @param string $data user data to be stored into this object (mysql persistence model store it as a BLOB) 
	 * @param string $aux_id an attached ID
	 * @param integer $parent_id The ID of the parent object (optional)
	 * @access public
	 * @return array omf_object  (id,classname,aux_id,data)
	 */
	public function create($classname, $data=null, $aux_id = null, 
			$parent_id=null, $rel_name = 'parent'){
		$id = $this->createObject($classname, $data, $aux_id);
		if($parent_id != null)
			$this->createRel($parent_id, $id, $rel_name);
		return array($id, $classname, $aux_id, $data);
	}

	public function deleteObject($object_id){
		// find if it has metadata, thats must be deleted
		// because it acts as a 'property' for this object
		foreach($this->getChilds($object_id, "", "metadata") as $obj){
			list($meta_id) = $obj;
			$this->deleteObjById($meta_id);
		}
		$this->deleteObjById($object_id);
	}
	public function deleteObjects($classname){
		while($this->countObjectsByClassname($classname) > 0)
			foreach($this->listObjects($classname,1000) as $row)
				$this->deleteObject($row['id']);
	}

	/**
	 * createRelation 
	 * 	creates a relationship between two objects.
	 *
	 * @param mixed $parent array() or integer. if array: first entry must be id
	 * @param mixed $child array or integer. if array: first entry must be id
	 * @param mixed $rel_name the relationship name
	 * @param mixed $rel_data the relationship data
	 * @access public
	 * @return int the relation id
	 */
	public function createRelation($parent, $child, $rel_name, $rel_data=""){
		$parent_id = $parent; $child_id = $child;
		if(is_array($parent)) list($parent_id) = $parent;
		if(is_array($child)) list($child_id) = $child;
		return $this->createRel($parent_id, $child_id, $rel_name, $rel_data);
	}

	/**
	 * listRelations
	 *		find the relationships for a given object (the subject).
	 *
	 *	suppose you have this objects: 
	 *
	 * 		pedro:  	classname 'Person'
	 * 		pedroJr:  	classname 'Person'
	 * 		christian:  classname 'Person'
	 * 		myhouse:  	classname 'House'
	 * 		mydog:  	classname 'Puppy'
	 *
	 *	and this relationships between them:
	 *
	 *		A1# pedro------[ father  ]---->christian
	 *		A2# pedro------[ father  ]---->pedroJr
	 *		B1# pedroJr----[ brother ]---->christian
	 *		C1# christian--[ owner   ]---->myhouse
	 *		C2# christian--[ owner   ]---->mydog
	 *
	 *	now, we can perform two type of queries: 'forward' or 'backward',
	 *	when forward is selected then we find for relationships in where 
	 *	the subject (pointed by the object_id arg) is in the parent side of
	 *	the relationship.  As opposite, when finding in 'backward' mode then
	 *	we find for those relationships in where the subject is in the child
	 *	side.
	 *
	 *	The relationships are composed of two sides A and B:
	 *
	 *		{A}----[rel_name]---->{B}
	 *
	 *	in where {A} is the 'parent', and {B} is child, so:
	 *		A1# pedro---[father]--->christian
	 *	says:
	 *		"pedro is father of christian"
	 *
	 *	FORWARD QUERY MODE EXAMPLES:
	 *
	 *		listRelations(pedro, 'father', 'Person', 'forward')	
	 *			returns: array(A1#,A2#)
	 *		listRelations(christian, 'father', 'Person', 'forward') 
	 *			returns nothing.
	 *		listRelations(christian, 'owner', 'House', 'forward') 
	 * 			returns array(#C1)
	 *		listRelations(christian, 'owner', 'Puppy', 'forward') 
	 *			returns array(#C2)
	 *
     *	BACKWARD QUERY MODE:	
	 *
	 *		listRelations(christian_id, 'father', 'person', 'backward') 
	 *			returns array(#A1,#A2)
	 *
	 *		as opossite:
	 *		listRelations(christian_id, 'father', 'person', 'forward') 
	 * 			returns: nothing.
	 * 
	 * @param integer $object_id the subject to find for. 
	 * @param string $rel_name filter by relname, if empty use classname
	 * @param string $classname if not empty the filter the result by classname
	 * @param bool $mode search mode. 'forward' or 'backward'. see note
	 * @access public
	 * @return array of omf_relations+object_instance or array() when none found. see also: listRel
	 */
	public function listRelations($object_id, $rel_name, $classname, $mode){
		$relationships = array();
		if($mode == 'forward'){
			$relationships = $this->listRel($object_id, 'parent',$rel_name);	
		}else
			$relationships = $this->listRel($object_id, 'child', $rel_name);
		// now perform filtering:
		$result = array();
		if($relationships != null)
		foreach($relationships as $r){
			list($rel_id, $parent_id, $child_id, $rel_name, $rel_data) 
				= $this->readRelation($r);
			if($mode == 'forward'){
				$object = $this->loadObject($child_id);
			}else{
				$object = $this->loadObject($parent_id);
			}
			list($obj_id, $obj_classname, $obj_aux_id, $obj_data) = $object;
			if(($obj_classname == $classname) 
				|| ($classname == null) || ($classname==""))
				$result[] = array($rel_id, $parent_id, $child_id,	$rel_name, 
					$rel_data, $object); // +object_instance
		}
		return $result;
	}

	/**
	 * getChilds 
	 *	is a helper method, it calls listRelations but instead of return the 
	 *	relationship itself it returns the object part.
	 *
	 *	R1: [christian:Person]----buy--->[computer1:Hardware]
	 *	R2: [somestore:Store]----buy--->[computer1:Hardware]
	 *	R3: [desktop]----holds--->[computer2]
	 *	R4: [christian]----buy--->[mouse1:Accesories]
	 *	R5: [christian]----holds--->[mouse2:Hardware]
	 *
	 *	getChilds(christian,'buy','Hardware') returns: array(computer1)
	 *	getChilds(christian,'buy') returns: array(computer1,mouse1)
	 *	getChilds(christian,'holds') returns: array(mouse2)
	 *	getChilds(christian,'', 'Hardware') returns: array(computer1,mouse2)
	 *
	 * @param integer $object_id 
	 * @param string $rel_name  optional if blank then use classname
	 * @param string $classname  optional see also listRelations
	 * @access public
	 * @return array of omf_objects. see also listRelations.
	 */
	public function getChilds($object_id, $rel_name, $classname=''){
		$objects = array();
		foreach($this->listRelations(
			$object_id, $rel_name, $classname, 'forward') as $relPlus){
				list($id, $parent,$child, $name, $data, $object) = $relPlus;
				$found=false;
				foreach($objects as $obj)
					if($obj[0] == $object[0])
						$found = true;
				if($found == false) $objects[] = $object;
			}
		return $objects;
	}

	/**
	 * getParents
	 *	is a helper method, instead of return relationships it returns objects
	 *
	 *	R1: [christian:Person]----buy--->[computer:Hardware]
	 *	R2: [somestore:Store]----buy--->[computer:Hardware]
	 *	R3: [desktop]----holds--->[computer]
	 *	R4: [christian]----buy--->[mouse:Accesories]
	 *
	 *	getParent(computer,'buy','Person') returns array(christian)
	 *	getParent(computer,'buy') returns array(christian,somestore)
	 *
	 * @param mixed $object_id 
	 * @param mixed $rel_name the desired relationship type
	 * @param mixed $classname optional see also listRelations
	 * @access public
	 * @return array of omf_objects see also listRelations
	 */
	public function getParents($object_id, $rel_name, $classname=''){
		$objects = array();
		foreach($this->listRelations(
			$object_id, $rel_name, $classname, 'backward') as $relPlus){
			list($id, $parent,$child, $name, $data, $object) = $relPlus;
			$found=false;
			foreach($objects as $obj)
				if($obj[0] == $object[0])
					$found = true;
			if($found == false) $objects[] = $object;
		}
		return $objects;
	}

	/**
	 * set 
	 * 	a setter. it creates attributes for a given object.
	 *
	 *	suppose you want to set this attributes to a 'mycar' object:
	 *
	 *		mycar.plate = 123456
	 *		mycar.color = blue
	 *
	 *	how the attributes are attached to an object in omf ?
	 *
	 *		OBJECT        RELATIONSHIP  OBJECT
	 *		(parent) ------------------ (child)
	 *	  =======================================================
	 *		[mycar: Car]----[plate]---->[metadata {data=123456}]
	 *		[mycar: Car]----[color]---->[metadata {data=color}]
	 *	
	 *	the "value" part of any attribute is an object of class "metadata", and the
	 *	attribute name part is hold as a relationship between the "mycar" and 
	 *	the new "metadata" object.
	 *	
	 * @param integer $object_id 
	 * @param mixed $metaname array(key=>value) or string(metavalue required)
	 * @param string $metavalue (null when metaname is array)
	 * @access public
	 * @return void
	 */
	public function set($object_id, $metaname, $metavalue=null){
		if(is_array($metaname)){
			foreach($metaname as $key=>$value)
				$this->set($object_id, $key, $value);
		}else{
			$_metaname = $this->buildMetanameRel($metaname);
			if(null != 
				($object = $this->getChilds($object_id,$_metaname,'metadata'))){
				$this->setObjectData($object[0][0],$metavalue);
			}else{
				$newobj = $this->create("metadata", $metavalue, $object_id);
				$this->createRel($object_id, $newobj[0], $_metaname, "");
			}
			list($p_id, $p_classname) = $this->loadObject($object_id);
			$this->setIndex($p_classname, $metaname, $metavalue, $object_id,0);
		}
	}

	/**
	 * get 
	 * 	read an attribute from a subject pointed by object_id
	 *	
	 * @param integer $object_id 
	 * @param string $metaname 
	 * @access public
	 * @return string the metadata object.data
	 */
	public function get($object_id, $metaname, $defvalue=""){
		$object = $this->getChilds($object_id, 
			$this->buildMetanameRel($metaname), "metadata");
		if($object == null) return $defvalue;
		list($id, $classname, $aux_id, $data) = $object[0];
		return $data;
	}

	/**
	 * setIndex 
	 *	save a value in the index database. 
	 * @param mixed $classname the object_id classname
	 * @param mixed $metaname  the property name of this object_id to be saved
	 * @param mixed $metavalue the property value of this object_id to be saved
	 * @param mixed $object_id the object whos remaining attributes belongs to.
	 * @access public
	 * @return void
	 */
	public function setIndex($classname, $metaname, $metavalue, $object_id){
		$_metaname = $this->buildMetanameRel($metaname);
		$hv = hash('md5',$metavalue);
		$found_hv = $this->findIndexValue($classname, $_metaname, $object_id);
		if($hv !== $found_hv){
			$this->insertIndex($classname, $_metaname,$hv, $object_id);
		}else{
			$this->updateIndex($classname, $_metaname,$hv, $object_id);
		}
	}

	/**
	 * listPropertys
	 *	list all the property names for this object. 
	 * 	the return value is the pure property name, see also: buildMetanameRel
	 * @param integer $object_id 
	 * @access public
	 * @return array a string array, each entry the pure property name
	 */
	public function listPropertys($object_id){
		$names = array();
		foreach($this->listRelations($object_id, "", "metadata", "forward") 
			as $rel){
			list($id, $parent, $child, $name, $data) = $rel;
			$pos = strlen("metaname_");
			$purename = substr($name,$pos);
			$names[] = $purename;
		}
		return $names;
	}

	/**
	 * buildMetanameRel
	 *	used to create relationship names for metadata objects
	 * 	example:  [person]--[dateofbirth]-->[value:Metadata, {data=08/06/1974}]
	 *	in this example the relationship name is "dateofbirth", but is prefixed
	 *	using "metaname_dateofbirth" in order to keep them separated.
	 *
	 * @param string $rel_name 
	 * @access protected
	 * @return string name for a relationship between object and value
	 */
	protected function buildMetanameRel($rel_name){
		return "metaname_".$rel_name;
	}

	public function findByAttribute($classname, $metaname, $value,$offset=0,$limit=-1,$count_only=false){
		$_metaname = $this->buildMetanameRel($metaname);
		if($results = $this->findIndex($classname, $_metaname, md5($value),
			$offset,$limit,$count_only)){
				if(true==$count_only)
					return $results;
			$objects = array();
			foreach($results as $obj_id){
				$objects[] = $this->loadObject($obj_id);
			}
			return $objects;
		}
		else
		return null;
	}

	/**
	 * getAttributes
	 *	return an array having all declared attributes and values for a given object
	 * 
	 * @param int $object_id 
	 * @access public
	 * @return array array("attr1"=>"value",...)
	 */
	public function getAttributes($object_id){
		$object = $this->loadObject($object_id);
		if(!$object)
			return null;
		$r=array();
		foreach($this->listPropertys($object_id) as $property_name)
			$r[$property_name] = $this->get($object_id, $property_name);
		list($_id, $_cs) = $object;
		if(!isset($r["id"])) $r["id"]=$object_id;
		if(!isset($r["classname"])) $r["classname"]=$_cs;
		return $r;
	}
}
