<?php
class OmfTest extends OmfDb {
	private function log($what,$data){
		printf("[%s][%s]\n",$what,$data);
	}
	public function run(){
		$this->testlowlevelobjectapi();	
		$this->testlowlevelindexapi();
		$this->testlowlevelrelationsapi();
		$this->testhighlevelcoreapi();
		$this->testhighlevelmetaapi();
		$this->testlist();
		$this->testfetch();
		//$this->testfetchsort();
		$this->testgetobject();
	}
	public function testlowlevelobjectapi(){
		printf("[".__METHOD__."] ... ");
		$ar = 
		array(
			array('test',null, null),
			array('test',123, null),
			array('test',123,999),
		);
		$this->deleteObjByClassname("test");
		$ids=array();
		foreach($ar as $t){
			list($classname, $data, $aux_id) = $t;
			$id = $this->createObject($classname,$data,$aux_id);
			if($id == null) throw new Exception("createObject fails.");
			$obj = $this->loadObject($id);
			if($obj == null) throw new Exception("loadObject fails. id=[{$id}]");
			if(count($obj) != 4) throw new Exception(
				"loadObject bad object. obj=".json_encode($obj));
			list($_id, $_classname, $_aux_id, $_data) = $obj;
			if($_id != $id) throw new Exception("id not equal");
			if($_classname != $classname) throw new Exception("classname fail");
			if($_data != $data) throw new Exception("data fail");
			if($_aux_id != $aux_id) throw new Exception("aux_id fail");
			$ids[] = $id;
		}
		$objects = $this->listObjects('test');
		if(count($objects) != 3) throw new Exception("listObjects fails");
		foreach($objects as $obj){
			list($id, $classname, $aux_id, $data) = $this->readObject($obj);
			$obj2 = $this->loadObject($id);
			list($_id, $_classname, $_aux_id, $_data) = $obj2;
			if($_classname != $classname) throw new Exception("classname fail");
			if($_data != $data) throw new Exception("data fail");
			if($_aux_id != $aux_id) throw new Exception("aux_id fail");
			$this->setObjectData($id, 777);
			$this->setObjectAuxId($id, 888);
			$obj3 = $this->loadObject($id);
			list($id3,$classname3, $aux_id3, $data3) = $obj3;
			if($data3 != 777) throw new Exception("setObjectData fails on id=".$id);
			if($aux_id3 != 888) throw new Exception("setObjectAuxId fails on id=".$id);
		}
		foreach($ids as $id){
			$this->deleteObjById($id);
			if($this->loadObject($id)) throw new Exception("delete fails. id = ".$id);
		}
		$objects = $this->listObjects('test');
		if(count($objects) != 0) throw new Exception("listObjects fails. objects not deleted.");
		printf("OK\n");
	}
	public function testlowlevelindexapi(){
		printf("[".__METHOD__."] ... ");
		$this->getDb()->createCommand()->delete("omf_index");
		$hv = hash('md5','test');
		$hv2 = hash('md5','test2');
		$a = $this->createObject("test");
		$b = $this->createObject("test");
		$c = $this->createObject("test");

		$this->insertIndex('test', 't1', $hv, $a);
		$_hv = $this->findIndexValue('test', 't1', $a);
		if($_hv != $hv) throw new Exception("findIndexValue must return: ".$hv.", instead returns: ".$_hv);
		$objects = $this->findIndex('test', 't1', $hv);
		if(count($objects) != 1) throw new Exception("must be 1");
		if($objects[0]['object_id'] != $a) throw new Exception("must be ".$a);

		$this->updateIndex('test', 't1', $hv2, $a);
		$_hv2 = $this->findIndexValue('test', 't1', $a);
		if($_hv2 != $hv2) throw new Exception("findIndexValue must return: ".$hv2.", instead returns: ".$_hv2);
		
		$this->getDb()->createCommand()->delete("omf_index");
		$this->insertIndex('test', 't1' , $hv, $a);	
		$this->insertIndex('test', 't1' , $hv, $b);	
		$this->insertIndex('test', 't1' , $hv, $c);
		if(3 != $this->countIndex('test', 't1', $hv)) throw new Exception("must be 3");

		$this->getDb()->createCommand()->delete("omf_index");
		printf("OK\n");
	}
	public function testlowlevelrelationsapi(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjByClassname("test");
		$a = $this->createObject("test");
		$b = $this->createObject("test");
		$c = $this->createObject("test");

		$rxidAB = $this->createRel($a, $b, "x","d");
		if($rxidAB == null) throw new Exception("cant create relation a --x--> b.");
		$rx = $this->loadRelation($rxidAB);
		list($_id, $_p, $_c, $_n, $_d) = $rx;
		if($_id != $rxidAB) throw new Exception("loadrelation. id fail");
		if($_p != $a) throw new Exception("loadrelation. parent fail");
		if($_c != $b) throw new Exception("loadrelation. child fail");
		if($_n != "x") throw new Exception("loadrelation. name fail");
		if($_d != "d") throw new Exception("loadrelation. date fail");
		
		$rxidAC = $this->createRel($a, $c, "x","d");

		$nonexistingRel = $this->listRel($a, 'parent', 'non_existing');
		if($nonexistingRel != null) throw new Exception("must be null");

		$rrA = $this->listRel($a, 'parent', 'x');
		if(count($rrA) != 2) throw new Exception("listRel A x parent, fail");
		$rrB = $this->listRel($b, 'child', 'x');
		if(count($rrB) != 1) throw new Exception("listRel B x child, fail");
		$rrC = $this->listRel($c, 'child', 'x');
		if(count($rrC) != 1) throw new Exception("listRel C x child, fail");
		$rrAc = $this->listRel($a, 'child', 'x');
		if(count($rrAc) != 0) throw new Exception("listRel A x child, fail");

		// test non existing relationships
		$rrA = $this->listRel($a, 'parent', 'z');
		if(count($rrA) != 0) throw new Exception("listRel A x parent, fail");
		$rrB = $this->listRel($b, 'child', 'z');
		if(count($rrB) != 0) throw new Exception("listRel B x child, fail");
		$rrC = $this->listRel($c, 'child', 'z');
		if(count($rrC) != 0) throw new Exception("listRel C x child, fail");
		$rrAc = $this->listRel($a, 'child', 'z');
		if(count($rrAc) != 0) throw new Exception("listRel A x child, fail");

		// test list all relationships pointing to any class
		$all_a = $this->listRel($a,"parent");
		if(count($all_a) != 2) throw new Exception("must be 2");
		$all_b = $this->listRel($b,"parent");
		if(count($all_b) != 0) throw new Exception("must be 0");
		$all_c = $this->listRel($c,"parent");
		if(count($all_c) != 0) throw new Exception("must be 0");

		$this->setRelationData($rxidAB, "testdata");
		list($_id2, $_p2, $_c2, $_n2, $_d2) = $this->loadRelation($rxidAB);
		if($_d2 != 'testdata') throw new Exception("setRelationData fails");

		$this->deleteRel($rxidAB);
		$rrA = $this->listRel($a, 'parent', 'x');
		if(count($rrA) != 1) throw new Exception("after delete AB. listRel A x parent, fail");
		$this->deleteRel($rxidAC);
    	$rrC = $this->listRel($a, 'parent', 'x');
    	if(count($rrC) != 0) throw new Exception("after delete AC. listRel A x parent, fail");

		$this->deleteObjByClassname("test");
		printf("OK\n");
	}
	public function testhighlevelcoreapi(){
		printf("[".__METHOD__."] ... ");
	
		list($a)  = $this->create("test");
		list($b)  = $this->create("test", "", "", $a);
		list($c)  = $this->create("test", "", "", $a);
		list($cc) = $this->create("testb", "", "", $a);
		$this->createRelation($a, $b, "extra");
		$this->createRelation($b, $a, "extra");
		$this->createRelation($b, $c, "parent");
	
		$nullrels = $this->listRelations($a, "non_existing", "non_existing", "forward");
		if($nullrels != null) throw new Exception("must be null");
		$nullrels = $this->listRelations($a, "non_existing", "non_existing", "backward");
		if($nullrels != null) throw new Exception("must be null");
		$nullrels = $this->getChilds($a, "non_existing", "non_existing");
		if($nullrels != null) throw new Exception("must be null");

		// childs

		$c1 = $this->getChilds($a, "parent", "test");
		if(count($c1) != 2) throw new Exception("must be 2");
		list($id0, $classname0, $aux0, $data0) = $c1[0];
		list($id1, $classname1, $aux1, $data1) = $c1[1];
		if($id0 != $b) throw new Exception("must be B");
		if($id1 != $c) throw new Exception("must be C");

		$c2 = $this->getChilds($a, "parent", "testb");
		if(count($c2) != 1) throw new Exception("must be 1");
		list($id0, $classname0, $aux0, $data0) = $c2[0];
		if($id0 != $cc) throw new Exception("must be CC");

		$c3 = $this->getChilds($a, "parent");
		if(count($c3) != 3) throw new Exception("must be 3");
		list($_b) = $c3[0];
		list($_c) = $c3[1];
		list($_cc) = $c3[2];
		if($_b != $b) throw new Exception("_b not b");
		if($_c != $c) throw new Exception("_c not c");
		if($_cc != $cc) throw new Exception("_cc not cc");

		$c4 = $this->getChilds($b, "parent");
		if(count($c4) != 1) throw new Exception("must be 1");
		list($id4) = $c4[0];
		if($id4 != $c) throw new Exception("must be C");

		$c4 = $this->getChilds($b, "extra", "test");
		if(count($c4) != 1) throw new Exception("must be 1");
		list($id4) = $c4[0];
		if($id4 != $a) throw new Exception("must be A");

		$c4b = $this->getChilds($b, "parent", "testb");
		if(count($c4b) != 0) throw new Exception("must be 0");

		$t = $this->getChilds($c, "parent");
		if(count($t) != 0) throw new Exception("must be 0");

		$t = $this->getChilds($cc, "parent");
		if(count($t) != 0) throw new Exception("must be 0");

		// parents

		// A
		$t = $this->getParents($a, "inexistingrel");
		if(count($t)) throw new Exception("must be zero");
		
		$t = $this->getParents($a, "parent");
		if(count($t)) throw new Exception("must be zero");

		$t = $this->getParents($a, "extra");
		if(count($t) != 1) throw new Exception("must be 1");
		list($x) = $t[0];
		if($x != $b) throw new Exception("must be B");

		// B
		$t = $this->getParents($b, "inexistingrel");
		if(count($t)) throw new Exception("must be zero");
		
		$t = $this->getParents($b, "extra");
		if(count($t) != 1) throw new Exception("must be 1");
		list($x) = $t[0];
		if($x != $a) throw new Exception("must be A");

		$t = $this->getParents($b, "parent");
		if(count($t) != 1) throw new Exception("must be 1");
		list($x) = $t[0];
		if($x != $a) throw new Exception("must be A");

		// C
		$t = $this->getParents($c, "inexistingrel");
		if(count($t)) throw new Exception("must be zero");
		$t = $this->getParents($c, "extra");
		if(count($t)) throw new Exception("must be zero");
		$t = $this->getParents($c, "parent");
		if(count($t) != 2) throw new Exception("must be 1");
		list($x) = $t[0];
		if($x != $a) throw new Exception("must be A");
		list($x) = $t[1];
		if($x != $b) throw new Exception("must be B");

		// CC
		$t = $this->getParents($cc, "inexistingrel");
		if(count($t)) throw new Exception("must be zero");
		
		$t = $this->getParents($cc, "parent");
		if(count($t) != 1) throw new Exception("must be 1");
		list($x) = $t[0];
		if($x != $a) throw new Exception("must be A");

		$t = $this->getParents($cc, "extra");
		if(count($t) != 0) throw new Exception("must be 0");

		// test: find by child classname
		$all_1 = $this->getChilds($a,"","test");
		if(count($all_1) != 2) throw new Exception("must be 2");
		$all_2 = $this->getChilds($a,"","testb");
		if(count($all_2) != 1) throw new Exception("must be 1");


		$this->deleteObjects("test");
		$this->deleteObjects("testb");

		printf("OK\n");
	}
	public function testhighlevelmetaapi(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");
		$t1 = $this->buildMetanameRel("t1");
		$t2 = $this->buildMetanameRel("t2");

		list($a)  = $this->create("test");
		$this->set($a, "t1", "123");
		$b = $this->getChild($a, $t1);
		list($b_id, $classname, $aux_id, $data) = $b;
		if($classname != "metadata") throw new Exception("must be metadata");
		if($data != "123") throw new Exception("must be 123");
		$this->deleteObject($a);
		$bb = $this->loadObject($b_id);	
		if($bb != null) throw new Exception("metadata not deleted after A deletion");

		list($a)  = $this->create("test");
		$this->set($a, "t1", "123");
		$val = $this->get($a, "t1");
		if($val != "123") throw new Exception("val must be 123");
		$this->deleteObject($a);

		list($a)  = $this->create("test");
		$val = $this->get($a, "t1", "z");
		if($val != "z") throw new Exception("val must be z");
		$this->deleteObject($a);

		list($a)  = $this->create("test");
		$val = $this->get($a, "t1");
		if($val != "") throw new Exception("val must be empty");
		$this->deleteObject($a);

		list($a)  = $this->create("test");
		$this->set($a, "t1","123");
		$this->set($a, "t1","456");
		$val = $this->get($a, "t1");
		if($val != "456") throw new Exception("val must be 456");
		$metadata = $this->getChilds($a, "", "metadata");
		if(count($metadata) != 1) throw new Exception("must be only one relationship");
		$this->deleteObject($a);

		list($a)  = $this->create("test");
		$this->set($a, "t1", "123");
		$this->set($a, "t2", "456");
		$val1 = $this->get($a, "t1");
		$val2 = $this->get($a, "t2");
		if($val1 != "123") throw new Exception("val must be 123");
		if($val2 != "456") throw new Exception("val must be 456");
		$propertys = $this->listPropertys($a);
		if(count($propertys) != 2) throw new Exception("must have 2 propertys");
		if($propertys[0] != "t1") throw new Exception("must be t1. it has: ".json_encode($propertys));
		if($propertys[1] != "t2") throw new Exception("must be t2. it has: ".json_encode($propertys));
		$this->deleteObject($a);

		list($a)  = $this->create("test");
		$this->set($a, array("t1"=>'123', "t2"=>'456'));
		if('123' != $this->get($a,'t1')) throw new Exception("must be 123");
		if('456' != $this->get($a,'t2')) throw new Exception("must be 456");
		$this->deleteObject($a);

		$this->getDb()->createCommand()->delete("omf_index");
		list($a)  = $this->create("test");
		$this->set($a, array("t1"=>'123', "t2"=>'456'));
		$v1 = $this->findIndexValue("test",$this->buildMetanameRel("t1"),$a);
		if($v1 != hash("md5","123")) throw new Exception("must be 123");
		$v2 = $this->findIndexValue("test",$this->buildMetanameRel("t2"),$a);
		if($v2 != hash("md5","456")) throw new Exception("must be 456");
		$this->deleteObject($a);

		$this->getDb()->createCommand()->delete("omf_index");
		list($a)  = $this->create("test");
		$this->set($a, array("t1"=>'123', "t2"=>'456'));
		list($b)  = $this->create("test");
		$this->set($b, array("t1"=>'123', "t2"=>'789'));
		list($c)  = $this->create("test");
		$this->set($c, array("t1"=>'222', "t2"=>'012'));

		$r = $this->find("test","t1","123");
		if(count($r) != 2) throw new Exception("must be 2. ");
		if($this->get($r[0][0],"t1") != "123") throw new Exception("must be 123");
		if($this->get($r[1][0],"t1") != "123") throw new Exception("must be 123");
		$r = $this->find("test","t2","789");
		if(count($r) != 1) throw new Exception("must be 1");
		if($this->get($r[0][0],"t2") != "789") throw new Exception("must be 789");
		$r = $this->find("test","t1","789");
		if(count($r) != 0) throw new Exception("must be 0");
		$r = $this->find("test","t1","999");
		if(count($r) != 0) throw new Exception("must be 0");

		$this->deleteObject($a);
		$this->deleteObject($b);
		$this->deleteObject($c);

		$this->deleteObjects("test");
		//$this->getDb()->createCommand()->delete("omf_index");
		printf("OK\n");
	}
	public function testlist(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");
		
		$n1 = 10;
		$n2 = 100;

		// keeps a list of reincident values, counters per value
		$a = array();
		for($i=0;$i<$n1;$i++)
			$a[$i] = 0;
		// a fixed list to be used for comparison
		$stor=array();
		// create objects, having an attribute named 'x' having value
		// that value stored and counted in $a for future comparison
		for($i=0;$i<$n2;$i++){
			list($id) = $this->create('test');
			$v = rand(0,$n1-1);
			$a[$v]++;
			$this->set($id, 'x', $v);
			$stor[$id] = array();
			$stor[$id]['i'] = $i;
			$stor[$id]['x'] = $v;
		}
		// now compare the results when calling the method
		foreach($a as $v=>$counter){
			$counted = $this->listObjectsBy('test','x',$v,null,null,true);
			if($counted != $counter)
				throw new Exception(sprintf("value %s was generated %s times "
					."but listObjects detects: %s",$v,$counter,$counted));

			$objects = $this->listObjectsBy('test','x',$v);
			$counted = count($objects);
			if($counted != $counter)
				throw new Exception(sprintf("value %s was generated %s times "
					."but listObjects detects: %s",$v,$counter,$counted));

			//test pagination
			// we have a full objects list, having x=$v in all its items
			// proceeding to list this objects having x=$v again but with 
			// pagination, having each page the same items in comparison
			// to the objects array
			//
			$ipp=3;
			$pages = $this->calculatePages($counted, $ipp);
			for($page=0;$page<$pages;$page++){
				$offset = $this->calculatePageOffset($ipp, $page);
				$paged = $this->listObjectsBy('test','x',$v, $ipp, $offset);
				$len = count($paged);
				for($i=0;$i<$len;$i++){
					list($id1,$cn1,$au1,$da1) = $this->readObject($objects[$offset + $i]);
					list($id2,$cn2,$au2,$da2) = $this->readObject($paged[$i]);
					if($id1 !== $id2) throw new Exception("error");
					if($cn1 !== $cn2) throw new Exception("error");
					if($au1 !== $au2) throw new Exception("error");
					if($da1 !== $da2) throw new Exception("error");
				}
			}			
		}

		// test using an inexisting status, so nothing must be returned
		// must never return null, it will break foreach statements

		if(0 !== $this->countObjectsByClassname(null)) throw new Exception("error");
		if(null === $this->listObjects(null)) throw new Exception("error");

		if(0 !== $this->listObjectsBy(null,null,null,0,0,true)) throw new Exception("error");
		if(0 !== $this->listObjectsBy("test","xx",null,0,0,true)) throw new Exception("error");
		if(0 !== $this->listObjectsBy("test","x","???",0,0,true)) throw new Exception("error");

		if(null === $this->listObjectsBy(null,null,null)) throw new Exception("error");
		if(null === $this->listObjectsBy("test",null,null)) throw new Exception("error");
		if(null === $this->listObjectsBy("test","xx",null)) throw new Exception("error");
		if(null === $this->listObjectsBy("test","x","???")) throw new Exception("error");

		if(0 !== $this->find(null,null,null,0,0,true)) throw new Exception("error");
		if(0 !== $this->find("test","xx",null,0,0,true)) throw new Exception("error");
		if(0 !== $this->find("test","x","???",0,0,true)) throw new Exception("error");

		if(null === $this->find(null,null,null,0,0,false)) throw new Exception("error");
		if(null === $this->find("test",null,null,0,0,false)) throw new Exception("error");
		if(null === $this->find("test","xx",null,0,0,false)) throw new Exception("error");
		if(null === $this->find("test","x","???",0,0,false)) throw new Exception("error");

		foreach($this->listObjects(null) as $dummy){ }
		foreach($this->listObjects("none") as $dummy){ }
		foreach($this->listObjectsBy(null,null,null,0,0,false) as $dummy){ }
		foreach($this->find(null,null,null,0,0,false) as $dummy){ }

		$this->deleteObjects("test");
		$ipp=2;
		$items=array();
		for($i=0;$i<5;$i++){
			list($id) = $this->create("test");
			$items[] = $id;
		}
		$this->set($items[0],'test','x');	
		$this->set($items[1],'test','y');	
		$this->set($items[2],'test','x');	
		$this->set($items[3],'test','y');	
		$this->set($items[4],'test','x');	

		if(3 !== $this->listObjectsBy("test","test","x",0,0,true)) throw new Exception("error");
		if(2 !== $this->listObjectsBy("test","test","y",0,0,true)) throw new Exception("error");
		if(5 !== $this->listObjectsBy("test",null,null,0,0,true)) throw new Exception("error");
	
		printf("OK\n");
	}

	public function testfetch(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");

		$ipp=2;
		$items=array();

		for($i=0;$i<5;$i++){
			list($id) = $this->create("test");
			$items[] = $id;
			$this->set($id,'a','a'.$id);
			$this->set($id,'b','b'.$id);
			$this->set($id,'c','c'.$id);
		}

		$this->set($items[0],'test','x');	
		$this->set($items[1],'test','y');	
		$this->set($items[2],'test','x');	
		$this->set($items[3],'test','y');	
		$this->set($items[4],'test','x');	

		if(null === $this->fetch(null,null,null,null,null,null)) throw new Exception("error");
		foreach($this->fetch(null,null,null,null,null,null) as $dummy) { }
		if(null === $this->fetch('test',array(),array(),null,null,true)) throw new Exception("error");
		if(null === $this->fetch('test',array(),array(),null,null,true)) throw new Exception("error");

		if(5 !== $this->fetch('test',null,null,-1,0,true)) throw new Exception("error");
		if(null === $this->fetch('test',null,null,-1,0,false)) throw new Exception("error");
		if(5 !== count($this->fetch('test',null,null,-1,0,false))) throw new Exception("error");

		if(2 !== count($this->fetch('test',null,null,2,0,false))) throw new Exception("error");
		if(2 !== count($this->fetch('test',null,null,2,2,false))) throw new Exception("error");
		if(1 !== count($this->fetch('test',null,null,2,4,false))) throw new Exception("error");
		
		$r = $this->fetch('test',null,array('a','b'),-1,0,false);
		foreach($r as $id=>$attr){
			if($attr['a'] !== 'a'.$id) throw new Exception("error.id=".$id.",".json_encode($attr));
			if($attr['b'] !== 'b'.$id) throw new Exception("error.id=".$id.",".json_encode($attr));
			if(isset($attr['c'])) throw new Exception("error.id=".$id.".c must not exists here");
		}

		$f = array('bad'=>'filter');
		if(0 !== $this->fetch('test',$f,null,-1,0,true)) throw new Exception("error");
		if(0 !== count($this->fetch('test',$f,null,-1,0,false))) throw new Exception("error");

		$f = array('test'=>'x');
		if(3 !== $this->fetch('test',$f,null,-1,0,true)) throw new Exception("error");
		$f = array('test'=>'y');
		if(2 !== $this->fetch('test',$f,null,-1,0,true)) throw new Exception("error");

		$f = array('test'=>'x');
		if(3 !== count($this->fetch('test',$f,null,-1,0,false))) throw new Exception("error");
		$f = array('test'=>'y');
		if(2 !== count($this->fetch('test',$f,null,-1,0,false))) throw new Exception("error");

		$f = array('test'=>'x');
		$r = $this->fetch('test',$f,array('a','b'),-1,0,false);
		foreach($r as $id=>$attr){
			if($attr['a'] !== 'a'.$id) throw new Exception("error.id=".$id.",".json_encode($attr));
			if($attr['b'] !== 'b'.$id) throw new Exception("error.id=".$id.",".json_encode($attr));
			if(isset($attr['c'])) throw new Exception("error.id=".$id.".c must not exists here");
		}

		printf("OK\n");
	}

	private function _printA($result){
		printf("\n");
		$index=0;
		foreach($result as $id=>$attr){
			printf("%-10s %-10s %-10s\n",
				$index,$id,$attr['a']);
			$index++;
		}
	}

	/*
	public function testfetchsort(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");

		$ipp=2;
		$items=array();

		for($i=0,$n=10;$i<5;$i++,$n+=10){
			list($id) = $this->create("test");
			$items[] = $id;
			$this->set($id,'a',$n);
		}

		$r1 = $this->fetch('test',null,array('a'),-1,0,false,"");
		$this->_printA($r1);	

		$r2 = $this->fetch('test',null,array('a'),3,1,false,array('a'));
		$this->_printA($r2);	

		printf("OK\n");
	}
	*/

	public function testgetobject(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");
		
		list($id0) = $this->create('test');
		$this->set($id0,'a','a0');
		$this->set($id0,'b','b0');

		list($id1) = $this->create('test');
		$this->set($id1,'a','a1');
		$this->set($id1,'b','b1');

		if(null !== $this->getObject('test',null)) throw new Exception("error");
		if(null !== $this->getObject('test',array())) throw new Exception("error");
		if(null !== $this->getObject('test',array('x'=>'y'))) throw new Exception("error");
		if(null !== $this->getObject('test',array('a'=>'y'))) throw new Exception("error");

		$ax = $this->getObject('test',array('a'=>'a0'));
		$ay = $this->getObject('test',array('id'=>$id0));
		$az = $this->getObject('test',array('id'=>$id1));

		if(null === $ax) throw new Exception("error");
		if(null === $ay) throw new Exception("error");
		if(null === $az) throw new Exception("error");

		foreach(array($ax,$ay,$az) as $index=>$obj){
			if($index <= 1){
				if($id0 !== $obj['id']) throw new Exception("error.index=".$index);
				if('test' !== $obj['classname']) throw new Exception("error.index=".$index);
				if("a0" !== $obj['a']) throw new Exception("error.index=".$index);
				if("b0" !== $obj['b']) throw new Exception("error.index=".$index);
			}else{
				if($id1 !== $obj['id']) throw new Exception("error.index=".$index);
				if('test' !== $obj['classname']) throw new Exception("error.index=".$index);
				if("a1" !== $obj['a']) throw new Exception("error.index=".$index);
				if("b1" !== $obj['b']) throw new Exception("error.index=".$index);
			}
		}
		printf("OK\n");
	}
}
