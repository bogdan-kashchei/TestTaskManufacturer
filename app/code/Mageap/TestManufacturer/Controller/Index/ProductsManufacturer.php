<?php


namespace Mageap\TestManufacturer\Controller\Index;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Magento\Framework\App\Action\Context;

class ProductsManufacturer extends \Magento\Framework\App\Action\Action
{
    protected $productFactory;
    protected $collectionFactory;
    protected $resultPageFactory;
    protected $productRepository;
    protected $searchCriteriaBuilder;
    private $resultJsonFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        ProductFactory $productFactory,
        CollectionFactory $collectionFactory,
        Context $context,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        JsonFactory $resultJsonFactory
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context);
    }

    public function getProducts($fieldName, $fieldValue, $filterType)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter($fieldName, $fieldValue, $filterType)->create();
        $products = $this->productRepository->getList($searchCriteria);
        return $products->getItems();
    }

    public function getProductCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*');
        return $collection;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productCollection = $this->getProductCollection();
        $optionId = [];
        foreach ($productCollection as $product) {
            if ($product->getManufacturer() != null) {
                array_push($optionId, (string)$product->getManufacturer());

            }
        }
        $manufacturerProducts = [];
        foreach ($optionId as $option) {
            $items = $this->getProducts('manufacturer', "$option", "like");
            array_push($manufacturerProducts, $items);
        }

        $jsonProducts = [];
        foreach ($manufacturerProducts as $manufacturerProduct) {
            foreach ($manufacturerProduct as $manufacturerOption) {
                array_push($jsonProducts, $manufacturerOption->getData());
            }
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($jsonProducts);
    }
}
