<?php
require_once("../../../../wp-config.php");
require_once("OmfPdo.php");
class OmfTest extends OmfPdo {
	private function log($what,$data){
		printf("[%s][%s]\n",$what,$data);
	}
	public function run(){
		$this->testlowlevelobjectapi();
		$this->testlowlevelrelationsapi();
		$this->testhighlevelcoreapi();
		$this->testlowlevelindexapi();
		$this->testhighlevelmetaapi();
		$this->testhighlevelfinders();
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
		$objects = $this->listObjects("test");
		if(count($objects) != 0) throw new Exception("listObjects fails must be 0. count=".count($objects));
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
			$ids[] = $id;
		}
		$objects = $this->listObjects('test');
		if(count($objects) != 3) throw new Exception("listObjects fails. count=".count($objects));
		assert('3===$this->countObjectsByClassName("test")');
		foreach($objects as $obj){
			list($id, $classname, $aux_id, $data) = $this->readObject($obj);
			$obj2 = $this->loadObject($id);
			list($_id, $_classname, $_aux_id, $_data) = $obj2;
			if($_classname != $classname) throw new Exception("classname fail");
			if($_data != $data) throw new Exception("data fail");
			$this->setObjectData($id, 777);
			$obj3 = $this->loadObject($id);
			list($id3,$classname3, $aux_id3, $data3) = $obj3;
			if($data3 != 777) throw new Exception("setObjectData fails on id=".$id);
		}
		foreach($ids as $id){
			$this->deleteObjById($id);
			if($this->loadObject($id)) throw new Exception("delete fails. id = ".$id);
		}
		$objects = $this->listObjects('test');
		if(count($objects) != 0) throw new Exception("listObjects fails. objects not deleted.");
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
	public function testlowlevelindexapi(){
		printf("[".__METHOD__."] ... ");
		$this->deleteAllIndexRecords();
		$hv = hash('md5','test');
		$hv2 = hash('md5','test2');
		$a = $this->createObject("test");
		$b = $this->createObject("test");
		$c = $this->createObject("test");

		$this->insertIndex('test', 't1', $hv, $a);
		$_hv = $this->findIndexValue('test', 't1', $a);
		if($_hv != $hv) throw new Exception("findIndexValue must return: ".$hv.", instead returns: ".$_hv);
		$objects = $this->findIndex('test', 't1', $hv);
		if(count($objects) != 1) throw new Exception("must be 1. ".json_encode($objects));
		if($objects[0] != $a) throw new Exception("must be ".$a);

		$this->updateIndex('test', 't1', $hv2, $a);
		$_hv2 = $this->findIndexValue('test', 't1', $a);
		if($_hv2 != $hv2) throw new Exception("findIndexValue must return: ".$hv2.", instead returns: ".$_hv2);
		
		$this->deleteAllIndexRecords();
		$this->insertIndex('test', 't1' , $hv, $a);	
		$this->insertIndex('test', 't1' , $hv, $b);	
		$this->insertIndex('test', 't1' , $hv, $c);
		if(3 != $this->findIndex('test', 't1', $hv,0,-1,true)) 
			throw new Exception("must be 3");
		$this->deleteAllIndexRecords();
		printf("OK\n");
	}
	public function testhighlevelmetaapi(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");
		$t1 = $this->buildMetanameRel("t1");
		$t2 = $this->buildMetanameRel("t2");

		list($a)  = $this->create("test");
		$this->set($a, "t1", "123");
		$b = $this->getChilds($a, $t1);
		list($b_id, $classname, $aux_id, $data) = $b[0];
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

		$this->deleteAllIndexRecords();
		list($a)  = $this->create("test");
		$this->set($a, array("t1"=>'123', "t2"=>'456'));
		$v1 = $this->findIndexValue("test",$this->buildMetanameRel("t1"),$a);
		if($v1 != hash("md5","123")) throw new Exception("must be 123");
		$v2 = $this->findIndexValue("test",$this->buildMetanameRel("t2"),$a);
		if($v2 != hash("md5","456")) throw new Exception("must be 456");
		$this->deleteObject($a);

		$this->deleteObjects("test");
		$this->deleteAllIndexRecords();

		// test getAttributes
		list($a) = $this->create("test");
		list($b) = $this->create("test");
		$this->set($a,"k1","k1");
		$this->set($a,"k2","k2");
		$this->set($a,"k3","k3");
		$this->set($b,"z1","z1");
		$this->set($b,"z2","z2");
		$this->set($b,"z3","z3");
		$ra = $this->getAttributes($a);
		$rb = $this->getAttributes($b);
		assert('$ra["k1"]=="k1"');
		assert('$ra["k2"]=="k2"');
		assert('$ra["k3"]=="k3"');
		assert('$rb["z1"]=="z1"');
		assert('$rb["z2"]=="z2"');
		assert('$rb["z3"]=="z3"');
		$this->deleteObjects("test");
		$this->deleteAllIndexRecords();
		printf("OK\n");
	}

	public function testhighlevelfinders(){
		printf("[".__METHOD__."] ... ");
		$this->deleteObjects("test");
		list($a) = $this->create("test");	
		list($b) = $this->create("test");	
		list($c) = $this->create("test");
		list($d) = $this->create("test");
		$this->set($a,"xkey","1");
		$this->set($b,"xkey","2");
		$this->set($c,"xkey","3");
		$this->set($d,"xkey","2");
		$r = $this->findByAttribute("test","xkey","1",0,-1,true);
		assert('1==$r');
		$r = $this->findByAttribute("test","xkey","2",0,-1,true);
		assert('2==$r');
		$r = $this->findByAttribute("test","xkey","9",0,-1,true);
		assert('0==$r');
		$r = $this->findByAttribute("test","xkey","2");	
		assert('2==count($r)');
		assert('$r[0][0]==$b');
		assert('$r[1][0]==$d');
		$this->deleteObjects("test");
		// pager finder
		list($a) = $this->create("test");//0
		list($b) = $this->create("test");//1
		list($c) = $this->create("test");//2 *
		list($d) = $this->create("test");//3 *
		list($e) = $this->create("test");//4 *
		list($f) = $this->create("test");//5
		list($g) = $this->create("test");//6
		list($h) = $this->create("test");//7
		foreach(array($a,$b,$c,$d,$e,$f,$g,$h) as $objid)
			$this->set($objid,"key","1");
		$r = $this->findByAttribute("test","key","1",0,-1,true);
		assert('8==$r');
		$r = $this->findByAttribute("test","key","1",2,3,true);
		assert('3==$r');
		$r = $this->findByAttribute("test","key","1",2,3);
		assert('$c==$r[0][0]');
		assert('$d==$r[1][0]');
		assert('$e==$r[2][0]');
		$this->deleteObjects("test");
		printf("OK\n");
	}
}
printf("OmfTest in progress..\n");
$inst = new OmfTest();
$inst->run();
printf("\nend\n");
