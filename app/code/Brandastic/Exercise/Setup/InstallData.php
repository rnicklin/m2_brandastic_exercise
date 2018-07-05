<?php
/**
 * @namespace   Brandastic
 * @module      Exercise
 * @author      Robert Nicklin
 * @email       nicklin.robert@gmail.com
 * @date        7/2/2018 5:35 PM
 */

namespace Brandastic\Exercise\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;

class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $_configWriter;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Cms\Model\PageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * Construct
     *
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory
    ) {
        $this->_configWriter = $configWriter;
        $this->_logger = $logger;
        $this->_pageFactory = $pageFactory;
        $this->_ruleFactory = $ruleFactory;
    }

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $htmlContent = <<<HTML
<form class="form subscribe" novalidate="novalidate" action="/brandastic/newsletter/subscribe/" method="post" id="newsletter-validate-detail">
    <div class="field newsletter">
        <label class="label" for="newsletter"><span>Sign Up for Our Newsletter:</span></label>
        <div class="control">
            <input name="email" type="email" id="newsletter" placeholder="Enter your email address" data-validate="{required:true, 'validate-email':true}">
        </div>
    </div>
    <div class="actions">
        <button class="action subscribe primary" title="Subscribe" type="submit">
            <span>Subscribe</span>
        </button>
    </div>
</form>
HTML;

        // Create the CMS Page to display the newsletter subscription form
        try {
            $page = $this->_pageFactory->create()->load('newsletter-coupon');
            $page->setTitle('Brandastic Newsletter Coupon Exercise')
                ->setIdentifier('newsletter-coupon')
                ->setIsActive(true)
                ->setPageLayout('1column')
                ->setStores(array(0))
                ->setContent($htmlContent)
                ->save();
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        // Setup the sales rule to generate coupon codes
        try {
            $rule = $this->_ruleFactory->create();
            $rule->setName('Brandastic Exercise 15% OFF Any Product')
                ->setIsActive(1)
                ->setWebsiteIds(array('1'))
                ->setCustomerGroupIds(array('0','1','2','3'))
                ->setCouponType(2)
                ->setUseAutoGeneration(1)
                ->setSimpleAction('by_percent')
                ->setDiscountAmount(15)
                ->setDiscountQty(1);
            $rule->save();
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        // Store the sales rule ID in config storage for later use
        try {
            $this->_configWriter->save(
                'brandastic/exercise/sales_rule_id',
                $rule->getRuleId(),
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                Store::DEFAULT_STORE_ID
            );
        } catch(\Exception $e) {
            die($e->getMessage());
        }

        $setup->endSetup();

    }
}