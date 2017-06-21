<?php
/**
 *
 * Copyright © 2015 Customcommerce. All rights reserved.
 */
namespace Custom\Customemail\Controller\Button;

class Index extends \Magento\Framework\App\Action\Action
{

	/**
	* Recipient email config path
	*/
	const XML_PATH_EMAIL_RECIPIENT = 'contact/email/recipient_email';
	/**
	* @var \Magento\Framework\Mail\Template\TransportBuilder
	*/
	protected $_transportBuilder;

	/**
	* @var \Magento\Framework\Translate\Inline\StateInterface
	*/
	protected $inlineTranslation;

	/**
	* @var \Magento\Framework\App\Config\ScopeConfigInterface
	*/
	protected $scopeConfig;

	/**
	* @var \Magento\Store\Model\StoreManagerInterface
	*/
	protected $storeManager; 
	/**
	* @var \Magento\Framework\Escaper
	*/
	protected $_escaper;
	/**
	* @param \Magento\Framework\App\Action\Context $context
	* @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
	* @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
	* @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	* @param \Magento\Store\Model\StoreManagerInterface $storeManager
	*/
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
		\Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Framework\Escaper $escaper
		) {
		parent::__construct($context);
		$this->_transportBuilder = $transportBuilder;
		$this->inlineTranslation = $inlineTranslation;
		$this->scopeConfig = $scopeConfig;
		$this->storeManager = $storeManager;
		$this->_escaper = $escaper;
	}

	/**
	* Post user question
	*
	* @return void
	* @throws \Exception
	*/
	public function execute()
	{
		$post = $this->getRequest()->getPostValue();
		
		$this->inlineTranslation->suspend();
		
		$result = array();
		
		try 
		{		
			$postObject = new \Magento\Framework\DataObject();
			$postObject->setData($post);

			$error = false;

			$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
			//$this->scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT, $storeScope);

			$sender = [
			'name' => $this->_escaper->escapeHtml($post['title']),
			'email' => $this->_escaper->escapeHtml($post['email']),
			];

			$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE; 
			
			$transport = $this->_transportBuilder
			->setTemplateIdentifier('send_button_email_template') // this code we have mentioned in the email_templates.xml
			->setTemplateOptions(
			[
			'area' => \Magento\Framework\App\Area::AREA_FRONTEND, // this is using frontend area to get the template file
			'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
			]
			)
			->setTemplateVars(array(
				'title'=>$post['title'],
				'subject'=>$post['subject'],
				'email'=>$post['email'],
				'message'=>$post['message']
				))
			->setFrom($sender)
			->addTo($this->scopeConfig->getValue('trans_email/ident_general/email', $storeScope))
			->getTransport();
			
			$transport->sendMessage(); ;
			$this->inlineTranslation->resume();
			
			$result['success'] = true;
			
			$result['message'] = 'The enquiry has been submitted successfully.';
			
		
		} catch (\Exception $e) 
		{
			$e->getMessage();
			
			$result['success'] = false;
			
			$result['message'] = 'There was an error '.$e->getMessage().' while submitting enquiry.';
			
		}
		header('Content-Type: application/json');
		echo json_encode($result);
	}
}
