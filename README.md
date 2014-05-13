Object Modeling Framework (OMF)
==============================

[https://github.com/christiansalazar/omf.git](https://github.com/christiansalazar/omf.git "https://github.com/christiansalazar/omf.git")

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

OMF CLASS DIAGRAM
-----------------

![Class Diagram][1]

USAGE
-----

The OMF Framework is currently designed to have a persistence model, 
in this case MYSQL but can be another distinct one. The component involved
in persist your objects into a MySQL database is named: OmfDb, this
readme file is targeted to help you in install this component as a
Yii Framwork Component, but you can use it in a non-yii platform with
little changes.

#Installation in your config/main:

github repository:

[https://github.com/christiansalazar/omf.git](https://github.com/christiansalazar/omf.git "https://github.com/christiansalazar/omf.git")

### STEP1: install it in your extensions directory:

	cd protected/extensions
	git clone https://github.com/christiansalazar/omf.git

### STEP2: Setup the Database argumets as follows:

	define('DB_NAME', 'mydatabase');
	define('DB_USER', 'root');
	define('DB_PASSWORD', '123456');
	define('DB_HOST', 'localhost');
	define('DB_CHARSET', 'utf8');

API USAGE
---------

Now you can use this object by calling:

###Basic object creation

	$api = new OmfPdo();
	
	list($a)  = $api->create("test"); // create object of class 'test'
	list($b)  = $api->create("test", "", "", $a); // b is parent of a. see note.
	list($c)  = $api->create("test"); // c is not a parent of a nither b
	list($d)  = $api->create("test"); // d is not a parent of a nither b

###Relationships

	$api->createRelation($a, $b, "somerelname");  
	$api->createRelation($b, $a, "anotherone");
	$api->createRelation($a, $c, "somerelname");  
	$api->createRelation($a, $d, "another");  

	$a_childs = $api->getChilds($a, "somerelname", "test");
	list($b_id, $classname_b, $aux0, $data0) = $a_childs[0];  // this must be B
	list($c_id, $classname_c, $aux1, $data1) = $a_childs[1];  // this must be C
	
	// note: as opposite case of $api->getChilds($a,...) 
	// use $api->getParents($a,...) to query the parent objects for $a.
	
	$relationships = $api->listRelations($a, "somerelname", "test", "forward");
	foreach($relationships as $rel) {
		// see also listRelations to check details of $rel object
		
	}

###Handling Metadata (Getters and Setters)

	$api->set($a, 'color', 'yellow');
	$api->set($a, 'firstname', 'christian');
	$api->set($a, 'lastname', 'salazar');
	$api->set($a, 'whateverYouwant_inrealTime', 'abc123');
	$api->set($a, array('dogs'=>7, 'birds'=>0, 'cats'=>1));

now using getters:

	$must_be_yellow = $api->get($a, 'color');
	$must_be_7 = $api->get($a, 'dogs');

###Finding objects by its primary ID

	$a = $api->loadObject($id);
	list($_id, $_classname, $_auxid, $_data) = $a;

###Finding objects by some attribute vale

Suppose you create some test objects of class Person having some attributes:

~~~
[php]
	list($person_id) = $api->create('Person');
	$api->set($person_id,'firstname','jhonn');
	$api->set($person_id,'lastname','doe');
	$api->set($person_id,'social_security_number','123');

	list($person_id) = $api->create('Person');
	$api->set($person_id,'firstname','matty');
	$api->set($person_id,'lastname','doral');
	$api->set($person_id,'social_security_number','456');

	$matty = $api->find("Person","firstname","matty");
	if(null != $matty){
		list($id) = $matty;
		echo "lastname is: ".$api->get($id,"lastname");
	}
~~~

###Delete Objects

	$api->deleteObject($a); // will delete related metadata too
	$api->deleteObjects("test"); // delete all 'test' instances, be carefull


**author:**

Christian Salazar H. <christiansalazarh@gmail.com>

**licence:**

[http://opensource.org/licenses/bsd-license.php](http://opensource.org/licenses/bsd-license.php "http://opensource.org/licenses/bsd-license.php")

[1]:https://github.com/christiansalazar/omf/blob/master/omf-class-diagram-1.jpg?raw=true
