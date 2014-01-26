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

### STEP2: in your config/main.php

	'import'=>array(
		'application.models.*',
		'application.components.*',
		'application.extensions.omf.*',
	),
	'components'=>array(
		// using the DB version to persist objects using a Database
		'omf'=>array('class'=>'application.extensions.OmfDb'),
	),

### STEP3: provide storage

When using the DB version of OMF (OmfDb.php) then you are required to
install the sql script provided in this package.  

This OMF framework will persist all objects in this single storage. 
It uses MYSQL. can be ported.

API USAGE
---------

Now you can use this object by calling:

	$object_id = Yii::app()->omf->create('MyClassName');

or by direct instance:

	$api = new OmfDb();
	$object_id = $api->create('MyClassName');

##QUICK API RESUME

You are required to check in detail the provided source code for api details on each method.
the following are a resume of the common usage api methods:

###Create Objects

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

###Listing (low level), see also 'fetch' below.

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

###Handling Metadata (Getters and Setters)

	$api->set($a, 'color', 'yellow');
	$api->set($a, 'firstname', 'christian');
	$api->set($a, 'lastname', 'salazar');
	$api->set($a, 'whateverYouwant_inrealTime', 'abc123');
	$api->set($a, array('dogs'=>7, 'birds'=>0, 'cats'=>1));

now using getters:

	$must_be_yellow = $api->get($a, 'color');
	$must_be_7 = $api->get($a, 'dogs');

Finding objects by its primary omf ID
-------------------------------------

	$a = $api->loadObject($id);
	list($_id, $_classname, $_auxid, $_data) = $a;

FINDING AN OBJECT BY ITS ATTRIBUTES
-----------------------------------

All objects in OMF shares the same autonumeric, because they all are objects 
no matter of what class belongs each.

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
	
	// ok we want OMF to get us the full object having propertys finding it by
	// some property value or inclusive its primary id:

	$guy = $this->getObject('Person',array('social_security_number'=>'123'));

	// now guy has all its propertys rendered:

	printf("The guy names are: %s %s,  ssn: %s",
		$guy['firstname'],$guy['lastname'],$guy['social_security_number']);

~~~

About the array() argument when calling getObject:

You may think passing various attributes to search for using this argument, 
this will come in the short comming future, by now, only one attribute=>value 
is allowed.

LISTING OBJECTS
---------------

there are various methods, recomended for your application is: fetch(...)

* listObjects
	low level. returns omf_objects by its classname.
~~~
[php]
		foreach($api->listObjects('someclass') as $obj){
			list($id, $classname, $auxid, $data) = $obj;
			$someproperty = $api->get($id,'someproperty');
		}
~~~

* listObjectsBy
	low level, but allowing pagination, and a filter to avoid retrieving the whole database

~~~
[php]
foreach($api->listObjectsBy('someclass','someattr','xyz',-1,0,false) as $obj){
			list($id, $classname, $auxid, $data) = $api->readObject($obj);
			$someproperty = $api->get($id,'someproperty');
		}

~~~

* find 
	offers you low level listing and pagination options (similar to listObjectsBy).



~~~
[php]
		// find all 'Person' having property 'likes_jam' equal to 'yes'
		// the result can be huge, so lets OMF to paginate it:
		$items_per_page = 5;
		$counter = $api->find("Person","likes_jam","yes", null, null, true);
		// suppose counter is 10000
		$pages = $api->calculatePages($counter, $items_per_page);
		// now display page 7
		$page = 7;
		$offset = $this->calculatePageOffset($items_per_page,$page);
		$objects = $api->find("Person","likes_jam","yes",$offset,$items_per_page);
		// display objects:
		foreach($objects as $obj){
			list($id, $classname, $aux_id, $data) = $obj;
			// do something
		}

~~~

* fetch (recomended)
	retrive objects and its propertys, those one selected by you, having options
	to paginate and only return counters (usefull for paginators), you can
	filter too which objects can be returned (having a filter).

	example:



~~~
[php]
		$objects = $api->fetch('Person',
			array('favoritecolor'=>'blue'),	// filter
			array('firstname','lastname',),	// fill this attributes
			3,								// only 3 objects
			4,								// starting from index position 4
			false							// false mean: return objects
											// true mean: count only
		);

		foreach($objects as $obj_id=>$attributes){
			printf("ID: %s\n".obj_id);
			foreach($attributes as $name=>$value)
				printf("[%s] = [%s]\n", $name, $value);
		}

~~~

OBJECT DELETION
---------------

	$this->deleteObject($a); // will delete related metadata too
	$this->deleteObjects("test"); // delete all 'test' instances, be carefull

#OMF and Yii Framework integration
----------------------------------

##CFormModel and Form Management

This method can be easily used in Yii Framework when working with CFormModel to
pass attributes to a CFormModel:

~~~
[php]
 	// the action in /protected/controllers/XXXController.php
	public function actionEditBill($billkey){
		$model = new EditBillForm();
		// 1. load your form with attributes values comming from OMF:
		$model->attributes = 
			Yii::app()->omf->getObject('Bill',array('key'=>$billkey));
		if(isset($_POST['EditBillForm']))
		{
			$model->attributes=$_POST['EditBillForm'];
			if($model->validate()){
		// 2. now save your values back to OMF:
				Yii::app()->omf->set($model->id,$model->attributes);
			}
		}
		$this->render('editbill',array('model'=>$model));
	}

	// the model in /protected/models/EditBillForm.php
	class EditBillForm extends CFormModel
	{
		public $id;			// the OMF unique ID for your instance
	
		public $item;		// this 5 attribues were declared in OMF
		public $amount;		// using $omfapi->set($id, 'amount', 123);
		public $from;		// and so on, in were $id is your model->id
		public $to;
		public $txn_id;

		public function rules()
		{
			return array(
				array('item, amount, from, to', 'required'),
				array('txn_id', 'safe'),
			);
		}
		public function attributeLabels()
		{
			return array(
				'billkey'=>'Bill Number',
				'item'=>'Item',
				'amount'=>'$ Amount',
				'from'=>'From Date',
				'to'=>'To Date',
				'txn_id'=>'Transaction',
			);
		}
	}

~~~

##OMF and CDataProvider - YiiOmfDataProvider

Lets start by supposing you have some Person instances created in Omf  having 
some attributes: firstname and lastname, so proceed to list them in a CGridView:

~~~
[php]
	$dataProvider = new YiiOmfDataProvider("Person", array(
			'api' => Yii::app()->omf,
			'id'=>'list-person-objects',

			// this is optional, to get a more specific list
			'having_attribute' => array('someattribute'=>'something'),

			'attributes' => array('id','classname','firstname','lastname'),
			'sort'=>array(
				'attributes'=>array('id','lastname'),
			),
			'pagination'=>array(
				'pageSize'=>3,
			),
		)
	);

	// show results in a CGridView, CListView etc

	$this->widget('zii.widgets.grid.CGridView', array(
		 'dataProvider'=>$dataProvider,
	));

~~~

##OMF and CArrayDataProvider

CArrayDataProvider is always a nice option when treating with small result sets.

~~~
[php]

	// 1. lets build an array well recognized by CArrayDataProvider
	//	that is a key=>pair array
	//
	$data = array();
	foreach($this->billing->listBillQuotes($agent_id) as $r){
		list($id,$bk,$item,$amount,$from,$to,$txn) = $r;
		$data[] = array(
			'billkey'=>CHtml::link($bk,array('editbill','billkey'=>$bk)),
			'bk'=>$bk,
			'item'=>$item,
			'amount'=>$amount,
			'from'=>date('F j, Y',strtotime($from)),
			'to'=>date('F j, Y',strtotime($to)),
			'transaction'=>$txn,
			'is_paid'=>!empty($txn),
		);
	}

	// 2. build a data provider
	//
	$dataProvider = new CArrayDataProvider($data, array(
			'id'=>'billaccount-bill',
			'keyField'=>'billkey',
			'sort'=>array(
				'attributes'=>array('from','to','billkey','transaction','item'),
				'defaultOrder'=>array('from'),
			),
			'pagination'=>array(
				'pageSize'=>10,
			),
		)
	);

	// 3. lets show the result in a CGridView
	//
	$this->widget('zii.widgets.grid.CGridView', array(
		'id'=>'bill-list',
		'dataProvider'=>$dataProvider,
		'columns'=>array(
			array('name'=>'billkey','type'=>'html','header'=>'Bill Number'),
			array('name'=>'item','type'=>'raw','header'=>'Item'),
			array('name'=>'amount','type'=>'raw','header'=>'Amount($)'),
			array('name'=>'from','type'=>'raw','header'=>'From Date'),
			array('name'=>'to','type'=>'raw','header'=>'Expire'),
			array('name'=>'transaction','type'=>'raw','header'=>'Transaction Number'),
		),
	));

~~~

**author:**

Christian Salazar H. <christiansalazarh@gmail.com>

**licence:**

[http://opensource.org/licenses/bsd-license.php](http://opensource.org/licenses/bsd-license.php "http://opensource.org/licenses/bsd-license.php")

[1]:https://github.com/christiansalazar/omf/blob/master/omf-class-diagram-1.jpg?raw=true
