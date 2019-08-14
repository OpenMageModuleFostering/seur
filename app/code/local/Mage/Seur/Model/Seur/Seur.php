<?php
class Mage_Seur_Model_Seur_Seur extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

	protected $_code = 'seur';
	protected $total;
	protected $weightType;
	protected $weightPackage;
	protected $weightUnitPrice;
	protected $shippingPrice;
	protected $_total;
	
	public function getTitle(){		
		return $this->getConfigData('title');
	}

	public function getAllowedMethods(){
		return array($this->_code => $this->getConfigData('msg'));
	}

	public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
		// Check if this method is active
		if (!$this->getConfigFlag('active')) {
			return false;
		}

		// Check if this method is even applicable (must ship from Spain)
		$origCountry 	=	Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
		$result 		=	Mage::getModel('shipping/rate_result');

		$error = Mage::getModel('shipping/rate_result_error');
		if ($origCountry != "ES") {
			if($this->getConfigData('showmethod')){
				$error->setCarrier($this->_code)
					->setCarrierTitle($this->getConfigData('title'))
					->setErrorMessage($this->getConfigData('specificerrmsg'));
				$result->append($error);
				return $result;
			} else {
				return false;
			}
		}

		//check if cart order value falls between the minimum and maximum order amounts required
		$packagevalue = $request->getBaseCurrency()->convert($request->getPackageValue(), $request->getPackageCurrency());
		$minorderval = (int)$this->getConfigData('min_order_value');
		$maxorderval = (int)$this->getConfigData('max_order_value');
		if(
			/* EL PAQUETE ES MENOR O IGUAL AL MINIMO Y EL MINIMO ESTA HABILITADO*/
			($packagevalue <= $minorderval) && ($minorderval > 0) || 
			/* EL PAQUETE ES MAYOR O IGUAL AL MAXIMO Y EL MAXIMO ESTA HABILITADO*/
			(($maxorderval != 0) && ($packagevalue >= $maxorderval))){
			if($this->getConfigData('showmethod')){
				$error->setCarrier($this->_code)
					->setCarrierTitle($this->getConfigData('title'));
				$currency	=	Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);
				/* SI EL MINIMO Y EL MAXIMO ESTA HABILITADO*/
				if($minorderval != 0 && $maxorderval != 0){
					$errorMsg=Mage::helper('seur')->__('Package value must be between %s and %s',Mage::app()->getStore()->formatPrice($minorderval),Mage::app()->getStore()->formatPrice($maxorderval));
				/* SI EL MAXIMO ESTA HABILITADO*/
				}elseif($maxorderval != 0){
					$errorMsg=Mage::helper('seur')->__('Package value must be less than %s',
						Mage::app()->getStore()->formatPrice($maxorderval)
					);
				/* SI EL MINIMO ESTA HABILITADO*/
				}else{
					$errorMsg	=	Mage::helper('seur')->__('Package value must be higher than %s',Mage::app()->getStore()->formatPrice($minorderval));
				}
				$error->setErrorMessage($errorMsg);
				$result->append($error);
				
				return $result;
			} else {
				return false;
			}
		}

		// Armo el precio segun el peso del paquete y las configuraciones del sistema
		$weightType			= $this->getConfigData('weight_units'); 							// Si son gramos o kilogramos
		$weightPackage		= $request->getPackageWeight() * $weightType;
		$weightUnitPrice 	= $this->getConfigData('xtra');
		$shippingPrice 		= $this->getConfigData('handling_fee');
		
		// al peso hay que siempre hacerlo entero y si es 1,1 kg toma como que pesa 2 kg. (KILOGRAMOS)
		$weightTotal	=	($weightPackage/$weightType);
		if (!is_int($weightTotal))	$weightTotal	=	ceil($weightTotal);

		$total		=	$shippingPrice + ($weightUnitPrice * $weightTotal);
		
		$rate = Mage::getModel('shipping/rate_result_method');
		$rate->setCarrier($this->_code);
		$rate->setCarrierTitle($this->getConfigData('title'));
		$rate->setMethod($this->_code);
		$rate->setMethodTitle($this->getConfigData('msg'));
		$rate->setCost($shippingPrice);
		$rate->setPrice($total);
		
		$result->append($rate);
		
		
		return $result;
	}

}