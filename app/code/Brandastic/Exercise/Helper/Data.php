<?php

namespace Brandastic\Exercise\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_COUPON_EMAIL_TEMPLATE = 'brandastic/exercise/coupon_email_template';
    const XML_PATH_COUPON_EMAIL_IDENTITY = 'brandastic/exercise/coupon_email_identity';

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\SalesRule\Model\Coupon\Massgenerator
     */
    protected $massGenerator;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\SalesRule\Model\Coupon\Massgenerator $massGenerator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\SalesRule\Model\Coupon\Massgenerator  $massGenerator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        $this->inlineTranslation = $inlineTranslation;
        $this->massGenerator = $massGenerator;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;

        parent::__construct($context);
    }

    /**
     * Generate a unique one time use coupon code
     *
     * @return bool | string
     */
    public function generateCouponCode()
    {
        try {
            // Get rule ID from config storage
            $ruleId = $this->getCouponRuleId();

            $data = array(
                'rule_id' => $ruleId,
                'qty' => 1,
                'length' => '12',
                'format' => 'alphanum',
                'prefix' => '',
                'suffix' => '',
                'dash'=>0
            );

            if (!$this->massGenerator->validateData($data)) {
                return false;
            } else {
                $this->massGenerator->setData($data);
                $this->massGenerator->generatePool();
                $codes = $this->massGenerator->getGeneratedCodes();
                return $codes[0];

            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Send a transactional email containing one-time use
     * coupon code to subscriber's email address
     *
     * @param $email Recipient email address
     * @return $this
     */
    public function sendCouponToSubscriber($email)
    {
        $couponCode = $this->generateCouponCode();

        if (!$this->scopeConfig->getValue(
                self::XML_PATH_COUPON_EMAIL_TEMPLATE,
                ScopeInterface::SCOPE_STORE
            ) || !$this->scopeConfig->getValue(
                self::XML_PATH_COUPON_EMAIL_IDENTITY,
                ScopeInterface::SCOPE_STORE
            )
        ) {
            return $this;
        }
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier(
            $this->scopeConfig->getValue(
                self::XML_PATH_COUPON_EMAIL_TEMPLATE,
                ScopeInterface::SCOPE_STORE
            )
        )->setTemplateOptions(
            [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId(),
            ]
        )->setTemplateVars(
            ['coupon_code' => $this->generateCouponCode()]
        )->setFrom(
            $this->scopeConfig->getValue(
                self::XML_PATH_COUPON_EMAIL_IDENTITY,
                ScopeInterface::SCOPE_STORE
            )
        )->addTo(
            $email,
            $email
        );
        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
        return $this;
    }

    /**
     * @return int
     */
    public function getCouponRuleId()
    {
        return (int)($this->scopeConfig->getValue('brandastic/exercise/sales_rule_id'));
    }

    /**
     * function isActive
     *
     * @return bool
     */
    public function isActive()
    {
        return true;
    }

}