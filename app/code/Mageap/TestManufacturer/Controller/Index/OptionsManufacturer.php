<?php


namespace Mageap\TestManufacturer\Controller\Index;


use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EavAttribute;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class OptionsManufacturer extends Action
{
    private $resultJsonFactory;
    protected $attribute;
    protected $eavAttribute;
    protected $attributeRepository;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Attribute $attribute,
        Repository $attributeRepository,
        EavAttribute $eavAttribute,
        Context $context)
    {
        parent::__construct($context);
        $this->attributeRepository = $attributeRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->attribute = $attribute;
        $this->eavAttribute = $eavAttribute;
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
        $data = [];
        foreach ($this->getManufacturer() as $manufacturer) {
            array_push($data,$manufacturer->getData());
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($data);
    }
}
