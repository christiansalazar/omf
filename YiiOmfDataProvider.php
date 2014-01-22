<?php
/**
 * YiiOmfDataProvider
 *	provides your application with a YiiFramework CDataProvider interface.
 *

	USAGE:

	// suppose you have created some objects of class Person 
	// having attributes: firstname and lastname, so proceed to list them:

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

 * @uses CDataProvider
 * @author Cristian Salazar H. <christiansalazarh@gmail.com> @salazarchris74 
 * @license FreeBSD {@link http://www.freebsd.org/copyright/freebsd-license.html}
 */
class YiiOmfDataProvider extends CDataProvider {

	private $api;
	private $classname;
	private $attributes;

	public $keyField='id'; // dont change it.
	public $caseSensitiveSort=true;
	public $having_attribute=null;
	public function __construct($classname,$config=array())
	{
		$this->classname= $classname;
		foreach($config as $key=>$value)
			$this->$key=$value;
	}
	protected function _fetch($limit,$offset){
		$result = array();
		foreach($this->api->fetch($this->classname,$this->having_attribute,
				$this->attributes,$limit,$offset,false) as $id=>$attr){
			$row = array();
			foreach($attr as $name=>$value)
				$row[$name] = $value;
			$result[] = $row;
		}
		return $result;
	}
	protected function fetchData()
	{
		if(($pagination=$this->getPagination())!==false)
		{
			$pagination->setItemCount($this->getTotalItemCount());
			return $this->_fetch($pagination->getLimit(),
				$pagination->getOffset());
		}else{
			return $this->_fetch(-1,0);
		}
	}
	protected function fetchKeys()
	{
		if($this->keyField!=='id')
			throw new CHttpException("please provide a keyField = 'id'");
		$keys=array();
		foreach($this->getData() as $i=>$data)
			$keys[$i]=is_object($data) ? $data->{$this->keyField} : $data[$this->keyField];
		return $keys;
	}
	protected function calculateTotalItemCount()
	{
		return $this->api->fetch($this->classname,
			$this->having_attribute,null, null , null, true);
	}
}
