<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Emizentech\Reviewnotification\Controller\Product;

use Magento\Framework\Controller\ResultFactory;
use Magento\Review\Model\Review;

class Post extends \Magento\Review\Controller\Product\Post
{
	public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $data = $this->reviewSession->getFormData(true);
       
        if ($data) {
            $rating = [];
            if (isset($data['ratings']) && is_array($data['ratings'])) {
                $rating = $data['ratings'];
            }
        } else {
            $data = $this->getRequest()->getPostValue();
            $rating = $this->getRequest()->getParam('ratings', []);
        }
        if (($product = $this->initProduct()) && !empty($data)) {
            /** @var \Magento\Review\Model\Review $review */
            $review = $this->reviewFactory->create()->setData($data);
            $review->unsetData('review_id');

            $validate = $review->validate();
            if ($validate === true) {
                try {
                    $review->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
                        ->setEntityPkValue($product->getId())
                        ->setStatusId(Review::STATUS_PENDING)
                        ->setCustomerId($this->customerSession->getCustomerId())
                        ->setStoreId($this->storeManager->getStore()->getId())
                        ->setStores([$this->storeManager->getStore()->getId()])
                        ->save();

                    foreach ($rating as $ratingId => $optionId) {
                        $this->ratingFactory->create()
                            ->setRatingId($ratingId)
                            ->setReviewId($review->getId())
                            ->setCustomerId($this->customerSession->getCustomerId())
                            ->addOptionVote($optionId, $product->getId());
                    }

                    $review->aggregate();
	
				/* Here we prepare data for our email  */
 
 
				/* Receiver Detail  */
				$receiverInfo = [
					'name' => 'Reciver Name',
					'email' => 'magentoking@gmail.com',
				];
 
				/* Sender Detail  */
				$senderInfo = [
					'name' => 'Sender Name',
					'email' => 'info@emizentech.com',
				];
 
 
				/* Assign values for your template variables  */
				$emailTempVariables = array();
				$emailTempVariables['nickname'] = $data['nickname'];
				$emailTempVariables['detail'] = $data['detail'];
				$emailTempVariables['title'] = $data['title'];
				$emailTempVariables['productname'] = $product->getName();
				
				/* We write send mail function in helper because if we want to 
				   use same in other action then we can call it directly from helper */ 
 
				/* call send mail method from helper or where you define it*/ 
				$this->_objectManager->get('Emizentech\Reviewnotification\Helper\Data')->sendReviewNotification(
					  $emailTempVariables,
					  $senderInfo,
					  $receiverInfo
				  );
                    
                    
                    
                    $this->messageManager->addSuccess(__('You submitted your review for moderation.'));
                } catch (\Exception $e) {
                    $this->reviewSession->setFormData($data);
                    $this->messageManager->addError(__('We can\'t post your review right now.'));
                }
            } else {
                $this->reviewSession->setFormData($data);
                if (is_array($validate)) {
                    foreach ($validate as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                } else {
                    $this->messageManager->addError(__('We can\'t post your review right now.'));
                }
            }
        }
        $redirectUrl = $this->reviewSession->getRedirectUrl(true);
        $resultRedirect->setUrl($redirectUrl ?: $this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }
}