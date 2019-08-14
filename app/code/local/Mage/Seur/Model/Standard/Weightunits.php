<?php
class Mage_Seur_Model_Standard_Weightunits {
	public function toOptionArray() {
		return array(
			array('value'=>1000,	'label'=>Mage::helper('seur')->__('Kilogramos')),
			array('value'=>1,		'label'=>Mage::helper('seur')->__('Gramos')),
		);
	}
}