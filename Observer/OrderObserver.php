<?php

namespace Unienvios\SendCotacao\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\HTTP\Client\Curl;

class OrderObserver implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    protected $messageManager;
    protected $_curl;
    protected $_quoteRepository;
    protected $_productRepository;
    protected $scopeConfig;

   const XML_PATH_EMAIL_RECIPIENT = 'carriers/unienvios/email';
   const XML_PATH_SENHA_RECIPIENT = 'carriers/unienvios/senha';
   const XML_PATH_STATUS_RECIPIENT = 'carriers/unienvios/active';
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\Order $order,
        ManagerInterface $messageManager,
        \Magento\Framework\HTTP\Client\Curl $curl,
	\Magento\Quote\Model\QuoteRepository $quoteRepository,
	\Magento\Catalog\Model\ProductFactory $productFactory,
	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_objectManager = $objectManager;
        $this->messageManager = $messageManager;
	$this->_curl = $curl;
        $this->_quoteRepository = $quoteRepository;
	$this->productFactory = $productFactory;
	$this->scopeConfig = $scopeConfig;
    }

     public function execute(Observer $observer)
    {
	
	/** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getEntityId();

         if($order->getState() == 'processing') {
    		$this->enviarCotacao();
         }
//	$this->enviarCotacao($order);
// echo "<pre>"; var_dump("teste");exit;
		
    }

    public function apiCreateQuotation($parametros, $token) {

 $parans = json_encode($parametros);
        $this->_curl->addHeader("Content-Type", "application/json");
        $this->_curl->addHeader("email", $this->getReceipentEmail());
        $this->_curl->addHeader("password", $this->getReceipentSenha());
        $this->_curl->addHeader("token", $token);
        $this->_curl->post("https://apihml.unienvios.com.br/external-integration/quotation/create", $parans);
        $response =$this->_curl->getBody();
	
	return $response;

    }

 public function getReceipentEmail() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     return $this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);


     }


 public function getReceipentSenha() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     return $this->scopeConfig->getValue(self::XML_PATH_SENHA_RECIPIENT, $storeScope);


     }

 public function getReceipentStatus() {
     $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

     return $this->scopeConfig->getValue(self::XML_PATH_STATUS_RECIPIENT, $storeScope);


     }

	public function enviarCotacao($order) {
		

	
        if($this->getReceipentStatus() == "1"){

        $shipping_id = str_replace("unienvios_", "", $order->getData('shipping_method'));
        $token = $order->getData("unienvios_token");


        $medidas = [
         "estimate_height" => 0,
         "estimate_width" => 0,
         "estimate_length" => 0,
         "estimate_weight" => 0
        ];

        foreach ($order->getAllItems() as $item) {
            $product = $this->productFactory->create()->load($item->getProductId());
            $width = $product->getResource()->getAttribute('unienvios_width')->getFrontend()->getValue($product);
            $height = $product->getResource()->getAttribute('unienvios_height')->getFrontend()->getValue($product);
            $length = $product->getResource()->getAttribute('unienvios_length')->getFrontend()->getValue($product);
            $weight = $product->getResource()->getAttribute('unienvios_weight')->getFrontend()->getValue($product);
         if ($width) {
                $medidas['estimate_width'] += doubleval($width) * intVal($item->getQtyOrdered());
            }

         if ($height) {
                $medidas['estimate_height'] += doubleval($height) * intVal($item->getQtyOrdered());
            }
         if ($length) {
                 $medidas['estimate_length'] += doubleval($length) * intVal($item->getQtyOrdered());
            }

         if ($weight) {
                 $medidas['estimate_weight'] += doubleval($weight) * intVal($item->getQtyOrdered());
          }


         }


        $parametros = [
        "zipcode_destiny" => str_replace("-", "",  $order->getShippingAddress()->getData("postcode") ),
        "document_recipient" => $order->getData("unienvios_document_recipient"),
        "name_recipient" =>$order->getShippingAddress()->getData("firstname"),
        "email_recipient" => $order->getShippingAddress()->getData("email"),
        "phone_recipient"=> $order->getShippingAddress()->getData("telephone"),
        "estimate_height" => $medidas['estimate_height'],
        "estimate_width" =>  $medidas['estimate_width'],
        "estimate_length" => $medidas['estimate_length'],
        "estimate_weight" => $medidas['estimate_weight'],
        "order_value" => doubleval($order->getSubtotal()),
        "address" =>$order->getShippingAddress()->getData("street"),
        "number" => $order->getData("unienvios_number"),
        "city" => $order->getShippingAddress()->getData("city"),
        "neighbourhood" => $order->getData("unienvios_neighbourhood"),
        "state" => $order->getShippingAddress()->getData("region"),
        "complement" => $order->getData("unienvios_complement"),
        "shipping_id" => $shipping_id
        ];


        $response = $this->apiCreateQuotation($parametros, $token);
	
		if($response != '{"message":"Cotação enviada com sucesso"}'){

        		echo "<pre>"; var_dump($response);exit;
	
		}
        }
	
	
	}

}
