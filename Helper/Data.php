<?php
namespace Emizentech\Reviewnotification\Helper;
 
/**
 * Custom Module Email helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
 
 	 /**
     * Sender email config path
     */
    const XML_PATH_EMAIL_SENDER = 'reviewnotification/active_display/sender_email_identity';
    
        /**
     * Email template config path
     */
    const XML_PATH_EMAIL_TEMPLATE = 'reviewnotification/active_display/email_template';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;
 
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
 
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;
 
    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;
     
    /**
     * @var string
    */
    protected $temp_id;
 
    /**
    * @param Magento\Framework\App\Helper\Context $context
    * @param Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    * @param Magento\Store\Model\StoreManagerInterface $storeManager
    * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    * @param Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->_scopeConfig = $context;
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder; 
    }
 
    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
 
    /**
     * Return store 
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
 
    /**
     * Return template id according to store
     *
     * @return mixed
     */
    public function getTemplateId($xmlPath)
    {
        return $this->getConfigValue($xmlPath, $this->getStore()->getStoreId());
    }
 
    /**
     * [generateTemplate description]  with template file and tempaltes variables values                
     * @param  Mixed $emailTemplateVariables 
     * @param  Mixed $senderInfo             
     * @param  Mixed $receiverInfo           
     * @return void
     */
    public function generateTemplate($emailTemplateVariables,$senderInfo,$receiverInfo)
    {
     		$postObject = new \Magento\Framework\DataObject();
            $postObject->setData($emailTemplateVariables);
            
            $receiveremail = $this->scopeConfig->getValue('reviewnotification/active_display/emailto', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $receivername = $this->scopeConfig->getValue('reviewnotification/active_display/nameto', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        	$template =  $this->_transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND, /* here you can defile area and
                                                                                 store of template for which you prepare it */
                        'store' => $this->_storeManager->getStore()->getId(),
                    ]
                )
               ->setTemplateVars(['data' => $postObject])
                ->setFrom($this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER, \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
                ->addTo($receiveremail,$receivername);
        return $this;        
    }
 
  
    public function sendReviewNotification($emailTemplateVariables,$senderInfo,$receiverInfo)
    {
    
    	$enable = $this->scopeConfig->getValue('reviewnotification/active_display/scope', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
 		if($enable==1){
        $this->inlineTranslation->suspend();    
        $this->generateTemplate($emailTemplateVariables,$senderInfo,$receiverInfo);    
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();        
        $this->inlineTranslation->resume();
        }
    }
 
}