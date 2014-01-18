Object Modeling Framework (OMF)
==============================

OMF is a framework for handle and persist objects, properties and 
relationships between them having an abstract and unique persistence model for 
all objects leaving you off the traditional data models.

OMF is designed to be implemented in ANY php platform, currently this version 
has been tested over YiiFramework but can be exported to a non-yii platform 
without issues.

OMF is not dependent on a persistence model implemented to store data,
and in consecuence you can move your entire system from one place to 
another distinct one without issues.

OMF helps you to persist and handle objects, properties and relationships 
in realtime, so make changes in your business logic without to worry about 
how the persistence will impact your storage design.

**Spanish speakers doc**

[http://trucosdeprogramacionmovil.blogspot.com/2014/01/omf-object-modeling-framework.html](http://trucosdeprogramacionmovil.blogspot.com/2014/01/omf-object-modeling-framework.html "tutorial de omf en español")

Twitter: @salazarchris74

**USAGE**

The OMF Framework is currently designed to have a persistence model, 
in this case MYSQL but can be another distinct one. The component involved
in persist your objects into a MySQL database is named: OmfDb, this
readme file is targeted to help you in install this component as a
Yii Framwork Component, but you can use it in a non-yii platform with
little changes.

Installation in your config/main:

	'import'=>array(
		'application.models.*',
		'application.components.*',
		'application.extensions.omf.*',
	),
	'components'=>array(
		// using the DB version to persist objects using a Database
		'omf'=>array('class'=>'application.extensions.OmfDb'),
	),

Database:

When using the DB version (the unique one right now) then you are required to
install the sql script provided in this package.  This OMF framework will
persist all objects in this single storage. It uses MYSQL. can be ported.

Api Usage:

Now you can use this object by calling:

	$object_id = Yii::app()->omf->create('MyClassName');

or by direct instance:

	$api = new OmfDb();
	$object_id = $api->create('MyClassName');

**QUICK API RESUME**

You are required to check in detail the provided source code for api details on each method.
the following are a resume of the common usage api methods:

$api can be: Yii::app()->omf or: $api = new OmfDb();

	list($a)  = $api->create("test"); // create object of class 'test'
	list($b)  = $api->create("test", "", "", $a); // b is parent of a. see note.
	list($c)  = $api->create("test"); // c is not a parent of a nither b
	list($d)  = $api->create("test"); // d is not a parent of a nither b
	$api->createRelation($a, $b, "somerelname");  
	$api->createRelation($b, $a, "anotherone");
	$api->createRelation($a, $c, "somerelname");  
	$api->createRelation($a, $d, "another");  

when you create a object as a child of another distinct one, let me say in this form:

	list($a) = $api->create('test');
	list($b) = $api->create('test',"","",$a);

then automatically the framework will create a relationship object named 'parent'
between this two objects:

	A---parent--->B

you can also create your own relationships:

	A--parent-->B
	A--somerelname-->B
	A--somerelname-->C

list all child objects of A, having a relationship named 'somerelname' and being an instance of 'test'.
this can be done too by calling the listRelations method, see later by specifying a 'forward' query mode.

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

finding objects by its primary ID:

	$a = $api->loadObject($id);
	list($_id, $_classname, $_auxid, $_data) = $a;

finding objects by its metadata:

	// find an object of class 'test' by searching for its metadata 'firstname' having the value 'christian':
	// it uses a index to enhance the search process.
	$must_be_a = $api->find("test","firstname","christian");

[read more about how the find method uses an index to enhance a search](https://github.com/christiansalazar/omf/commit/aa4b39e22feb1a2be2ee96b045da35a1cc3c3b59#commitcomment-4997948s "https://github.com/christiansalazar/omf/commit/aa4b39e22feb1a2be2ee96b045da35a1cc3c3b59#commitcomment-4997948s a Yii Framework Component, in your config/main.php file add a component")


to delete instances:

	$this->deleteObject($a); // will delete related metadata too
	$this->deleteObjects("test"); // delete all 'test' instances, be carefull


**author:**

Christian Salazar H. <christiansalazarh@gmail.com>

**licence:**

[http://opensource.org/licenses/bsd-license.php](http://opensource.org/licenses/bsd-license.php "http://opensource.org/licenses/bsd-license.php")


