<?php
require_once('/var/yii/framework/yii.php');
require_once('extensions/omf/OmfBase.php');
require_once('extensions/omf/OmfPdo.php');

$database=dirname(__FILE__).'/config/database.php';

$db = require($database);
if(!preg_match("/host=([a-z0-9]+)/i",$db['connectionString'],$m1))
	die("invalid db settings\n");
if(!preg_match("/dbname=([a-z0-9]+)/i",$db['connectionString'],$m2))
	die("invalid db settings\n");
define('DB_HOST', $m1[1]);
define('DB_NAME', $m2[1]);
define('DB_CHARSET', $db['charset']);
define('DB_USER', $db['username']);
define('DB_PASSWORD', $db['password']);

	function console($id='',$method,$name="",$value="",$verbose='yes'){
		$api = new OmfPdo;
		if("create"==$method){
			list($id) = $api->create($name,"","",$value);
			printf("created, object_id: $id\n");
		}elseif("createrel" == $method){
			list($rid) = $api->createRelation($id, $value, $name);
			printf("relationship created. $rid\n");
		}elseif("get"==$method){
			printf("GET: [%s]\n",$api->get($id,$name));
		}elseif("set"==$method){
			$api->set($id,$name,$value);
			printf("now it has: [%s]\n",$api->get($id,$name));
		}elseif("delete"==$method){
			$api->deleteObject($id);
			printf("object deleted.\n");
		}elseif("deleteclass"==$method){
			$api->deleteObjects($id);
			printf("objects removed.\n");
		}elseif("deleterel"==$method){
			$api->deleteRel($id);
			printf("relationship deleted.\n");
		}elseif("find" == $method){
			$h=null;
			$fields = $name!="" ? explode(",",$name) : null;
			foreach($api->listObjects($id) as $o){
				$obj_id = $o['id'];
				if(!$h){
					$h = array_merge(array("id"),
						$fields ? $fields : $api->listPropertys($obj_id));
					foreach($h as $hname)
						printf("[%-10s]\t",substr($hname,0,9));
					printf("\n");
				}
				foreach($h as $hname){
					$v = $hname=='id' ? $obj_id : 
						$api->get($obj_id, $hname);
					printf("[%-10s]\t",substr($v,0,10));
				}
				printf("\n");
			}
		}elseif("classes" == $method){
			foreach($api->enumClassnames() as $classname=>$objects){
				$_classname = str_replace(
					" ","_",sprintf("%-45s",$classname));
				printf("%s\t%s\n",$_classname, $objects);
			}
		}elseif("export" == $method){
			$fields = $name!="" ? explode(",",$name) : null;
			$h=null;
			printf("[ ");
			$comma="";
			foreach($api->listObjects($id) as $o){
				$obj_id = $o['id'];
				if(null == $h){
					if($fields){
						$h = $fields;			
					}else
						$h = $api->listPropertys($obj_id);
				}
				$obj = array();
				$obj["id"] = $obj_id;
				$obj["classname"] = $o['classname'];
				foreach($h as $field){
					if('id'==$field || 'classname'==$field) continue;
					$obj[$field] = $api->get($obj_id, $field);
				}
				printf("%s%s",$comma,json_encode($obj));
				$comma="\n, ";
			}
			printf(" ]\n");
		}elseif("import"==$method){
			// id: inputfile name: relname value: objectid in relationship
			if($f = fopen($id,"r")){
				$line="";
				while($t = fgets($f,4096))
					$line .= $t;
				fclose($f);
				$_relname = ""; $_parent=""; $rinfo="";
				if($name != "") {
					$_relname = $name;
					$_parent = $value;
					$pobj = $api->loadObject($_parent);
					if(null == $pobj)
						die("invalid object id specified for relationship: $_parent\n");
					$_classname = $pobj[1];
					$rinfo = "[#$_parent:$_classname]---{$_relname}-->[:importedObject]";
					printf("\n\trelationship: $rinfo\n\n");
				}
				if($data = json_decode($line)){
					foreach($data as $r){
						$_id="_";
						list($_id) = $api->create($r->classname);
						if("" != $_relname)
							$api->createRelation($_parent, $_id, $_relname);
						printf("#%s %s\n",$_id,$r->classname);
						$attrs = array();
						foreach($r as $fieldname=>$fieldvalue){
							$attrs[$fieldname] = $fieldvalue;
							printf("\t[%s,%s]\n",$fieldname,$fieldvalue);
						}
						$api->set($_id, $attrs);
						printf("\n");
					}
					printf("done\n");
				}else
				printf("bad json format.\n");
			}else
			printf("file not found.\n");
		}elseif("view"==$method){
			$r = $api->getAttributes($id);
			if($r)
			foreach($r as $attr=>$value)
				printf("%20s:\t%s\n",$attr,$value);
			if("yes"==$verbose){
				list($_id,$_cs,$_a,$_d) = $api->loadObject($id);
				foreach($api->listRelations($id,"","","forward") as $r){
					list($relid, $p,$c,$relname,$data,$obj) = $r;
					list($_id,$_classname,$_alt,$_data) = $obj;
					if("metadata" != $_classname)
					printf("[#%s][%10s:%-10s]----{%s}---->[%s:%s]\n",
						$relid,
						$id,$_cs,$relname,$c,$_classname);
				}
				foreach($api->listRelations($id,"","","backward") as $r){
					list($relid, $p,$c,$relname,$data,$obj) = $r;
					list($_id,$_classname,$_alt,$_data) = $obj;
					if("metadata" != $_classname)
					printf("[#%s] [%10s:%-10s]-----{%s}---->[%s:%s]\n",
						$relid,
						$p,$_classname,
						$relname,
						$id,$_cs
					);
				}
			}
		}else
		die("bad method. must be 'create','set','get','delete','deleterel','find','classes'\n");
	}
$help=
 	"create classname optionalParentId\n".
	"createrel parentId childId somerelname\n".
	"set id attribute value\n".
	"get id attribute\n".
	"list Classname fieldlist(field1,field2 optional)\n".
	"export Classname fieldlist(field1,field2 optional)\n".
	"import inputfilename (relname parentId optional)\n".
	"view objectid\n".
	"delete objectid\n".
	"deleteclass classname\n".
	"deleterel relid\n";
$showhelp=false;
$a = $_SERVER['argv'];
$args = array("id"=>"","method"=>"classes","name"=>"","value"=>"");
$len = count($a);
$ok=false;
if($len > 1){
	$cmd = $a[1];
	if(("create" == $cmd) && ($len >= 3)){
		$args["method"]=$cmd;
		$args["name"]=$a[2];
		$args["value"]=isset($a[3]) ? $a[3] : "";
		$ok=true;
	}else
	if(("createrel" == $cmd) && (5==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$args["value"]=$a[3];
		$args["name"]=$a[4];
		$ok=true;
	}else
	if(("set" == $cmd) && (5==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$args["name"]=$a[3];
		$args["value"]=$a[4];
		$ok=true;
	}else
	if(("get" == $cmd) && (4==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$args["name"]=$a[3];
		$ok=true;
	}else
	if(("list" == $cmd) && ($len >= 3)){
		$args["method"]="find";
		$args["id"]=$a[2];
		$args["name"]=isset($a[3]) ? $a[3] : "";
		$ok=true;
	}else
	if(("export" == $cmd) && ($len >= 3)){
		$args["method"]="export";
		$args["id"]=$a[2];
		$args["name"]=isset($a[3]) ? $a[3] : "";
		$ok=true;
	}else
	if(("view" == $cmd) && (3==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$ok=true;
	}else
	if(("delete" == $cmd) && (3==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$ok=true;
	}else
	if(("deleteclass" == $cmd) && (3==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$ok=true;
	}else
	if(("deleterel" == $cmd) && (3==$len)){
		$args["method"]=$cmd;
		$args["id"]=$a[2];
		$ok=true;
	}else
	if(("import" == $cmd) && ((3==$len) || (5==$len))){
		$args["method"]="import";
		$args["id"]=$a[2];
		if(5==$len){
			$args["name"] = $a[3];
			$args["value"] = $a[4];
		}
		$ok=true;
	}else{
		$showhelp=true;
	}
}else{
	$showhelp=true;
	$ok=true;
}
printf("OMF Console.\n");
if(true==$ok)
	console($args['id'],$args['method'],$args['name'],$args['value']);
if($showhelp)printf("\n%s\n",$help);

