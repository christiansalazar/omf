Object Modeling Framework (OMF)
==============================

As it name sugest it, is a framework for handling objects and the relationships between them. You can build
an entire system using purely this framework.  

#USAGE

As a Yii Framework Component, in your config/main.php file add a component 

	'import'=>array(
		'application.models.*',
		'application.components.*',
		'application.extensions.omf.*',
	),
	'components'=>array(
		// using the DB version to persist objects using a Database
		'omf'=>array('class'=>'application.extensions.OmfDb'),
	),

Now you can use this object by calling:

	$object_id = Yii::app()->omf->create('MyClassName');

or by direct instance:

	$api = new OmfDb();
	$object_id = $api->create('MyClassName');

#QUICK API RESUME

You are required to check in detail the provided source code for api details on each method.
the following are a resume of the common usage api methods:

$api can be: Yii::app()->omf or: $api = new OmfDb();

	list($a)  = $api->create("test"); // create object of class 'test'
	list($b)  = $api->create("test", "", "", $a); // b is parent of a
	list($c)  = $api->create("test"); // c is not a parent of a nither b
	list($d)  = $api->create("test"); // d is not a parent of a nither b
	$api->createRelation($a, $b, "somerelname");  
	$api->createRelation($b, $a, "anotherone");
	$api->createRelation($a, $c, "somerelname");  
	$api->createRelation($a, $d, "another");  

list $a child objects having a relationship named 'somerelname' and being an instance of 'test'

	$a_childs = $api->getChilds($a, "somerelname", "test");
	list($b_id, $classname_b, $aux0, $data0) = $a_childs[0];  // this must be B
	list($c_id, $classname_c, $aux1, $data1) = $a_childs[1];  // this must be C

	// see also $api->getParent(..) to perform the opposite case: 
	// finding the parents of B will return A, and C will return A too.

list relationship objects instead of listing objects pointed by relationships:

	// case forward: will return the relationships objects named 'somerelname' having:
	//	parent: A and child: any 'test' instance.  (because 'forward' keyword)
	//
	//  A-----somerelame--->B	this
	//  A-----somerelame--->C	and this will be returned, 'forward' means 'from A to ..'
	//  A-----another--->D		
	//  
	$relationships = $api->listRelations($a, "somerelname", "test", "forward");
	foreach($relationships as $rel) {
		// see also listRelations to check details of $rel object
	}

	// case backward: will return the relationships objects named 'somerelname' having:
	//	parent: A and child: any 'test' instance. PAY ATTENTION to the $b argument
	//
	//  A-----somerelame--->B	this will be returned, 'backward' means 'from B to ..'
	//  A-----somerelame--->C	not returned
	//  A-----another--->D		not returned
	//
	$relationships = $api->listRelations($b, "somerelname", "test", "forward");

metadata api (attributes getter & setters):

	$api->set($a, 'color', 'yellow');
	$api->set($a, 'firstname', 'christian');
	$api->set($a, 'lastname', 'salazar');
	$api->set($a, 'whateverYouwant_inrealTime', 'abc123');
	$api->set($a, array('dogs'=>7, 'birds'=>0, 'cats'=>1));

now using getters:

	$must_be_yellow = $api->get($a, 'color');
	$must_be_7 = $api->get($a, 'dogs');

to delete instances:

	$this->deleteObject($a); // will delete related metadata too
	$this->deleteObjects("test"); // delete all 'test' instances, be carefull


**author:**

Christian Salazar H. <christiansalazarh@gmail.com>

**licence:**

[http://opensource.org/licenses/bsd-license.php](http://opensource.org/licenses/bsd-license.php "http://opensource.org/licenses/bsd-license.php")


