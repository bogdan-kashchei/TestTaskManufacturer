<?php


namespace Mageap\TestManufacturer\Controller\Index;


use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EavAttribute;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class OptionsWithProductManufacturer extends Action
{
    private $resultJsonFactory;
    protected $attribute;
    protected $eavAttribute;
    protected $attributeRepository;
    protected $collectionFactory;
    protected $productRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Attribute $attribute,
        Repository $attributeRepository,
        EavAttribute $eavAttribute,
        Context $context,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        parent::__construct($context);
        $this->attributeRepository = $attributeRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attribute = $attribute;
        $this->eavAttribute = $eavAttribute;
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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

    public function getAttributeManufacturerId()
    {
        return $this->eavAttribute->getIdByCode('catalog_product', 'manufacturer');
    }

    public function getManufacturer()
    {
        $attributeModel = $this->attribute->load($this->getAttributeManufacturerId());
        $attributeCode = $attributeModel->getAttributeCode();
        return $this->attributeRepository->get($attributeCode)->getOptions();
    }

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


        $manufacturerProductsOptions = [];
        foreach ($manufacturerProducts as $manufacturerProduct) {
            foreach ($manufacturerProduct as $manufacturerOption) {
                array_push($manufacturerProductsOptions, $manufacturerOption->getManufacturer());
            }
        }


        $uniqueOptions = [];
        foreach ($this->getManufacturer() as $manufacturer) {
            if (!in_array($manufacturer->getData(), $uniqueOptions))
                array_push($uniqueOptions, $manufacturer->getValue());
        }
        $newArrayAttribute = [];
        foreach ($this->getManufacturer() as $value) {

            $newArrayAttribute += [$value->getValue()=>$value->getLabel()];
        }


        $productQtyManufacturer = [];
        for ($i = 0; $i < count($manufacturerProductsOptions); $i++) {
            for ($j = 0; $j < count($uniqueOptions); $j++) {
                if ($manufacturerProductsOptions[$i] == $uniqueOptions[$j]) {
                    $productQtyManufacturer[$uniqueOptions[$j]] = 1;
                }
            }
        }



        $sameKeys = array_intersect(array_keys($newArrayAttribute), array_keys($productQtyManufacturer));
        $uniqueKeys = array_intersect(array_flip($newArrayAttribute), $sameKeys);
        $data = array_flip($uniqueKeys);


        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($data);
    }
}
