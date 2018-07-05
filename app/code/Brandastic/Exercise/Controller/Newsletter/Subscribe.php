<?php

namespace Brandastic\Exercise\Controller\Newsletter;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

class Subscribe extends \Magento\Newsletter\Controller\Subscriber\NewAction
{
    /**
     * Brandastic Exercise Helper
     *
     * @var \Brandastic\Exercise\Helper\Data
     */
    protected $brandasticExerciseHelper;

    /**
     * Subscribe constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement
     * @param \Brandastic\Exercise\Helper\Data $brandasticExerciseHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Brandastic\Exercise\Helper\Data $brandasticExerciseHelper
    ) {
        $this->brandasticExerciseHelper = $brandasticExerciseHelper;

        parent::__construct(
            $context,
            $subscriberFactory,
            $customerSession,
            $storeManager,
            $customerUrl,
            $customerAccountManagement
        );
    }

    /**
     * Save newsletter subscription action
     *
     * @return ResultFactory
     */
    public function execute()
    {
        // Post form data from newsletter subscription form
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
            $email = (string)$this->getRequest()->getPost('email');
            try {
                $this->validateEmailFormat($email);
                $this->validateGuestSubscription();
                $this->validateEmailAvailable($email);

                // Check if customer has already subscribed
                $subscriber = $this->_subscriberFactory->create()->loadByEmail($email);
                if ($subscriber->getId()
                    && $subscriber->getSubscriberStatus() == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
                ) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('This email address is already subscribed.')
                    );
                }

                // Subscribe the customer to newsletter
                $status = $this->_subscriberFactory->create()->subscribe($email);
                if ($status == \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE) {
                    $this->messageManager->addSuccess(__('The confirmation request has been sent.'));
                } else {
                    $this->messageManager->addSuccess(__('Thank you for your subscription.'));

                    // If subscription was a success, send autogenerated welcome coupon.
                    $this->brandasticExerciseHelper->sendCouponToSubscriber($email);
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addException(
                    $e,
                    __('There was a problem with the subscription: %1', $e->getMessage())
                );
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong with the subscription.'));
            }
        }

        // Redirect back to referring page
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
    }
}