<?php

namespace Shopware\Category\Loader;

use Doctrine\DBAL\Connection;
use Shopware\Category\Factory\CategoryDetailFactory;
use Shopware\Category\Struct\CategoryDetailCollection;
use Shopware\Category\Struct\CategoryDetailStruct;
use Shopware\Context\Struct\TranslationContext;
use Shopware\CustomerGroup\Loader\CustomerGroupBasicLoader;
use Shopware\Framework\Struct\SortArrayByKeysTrait;
use Shopware\Product\Loader\ProductBasicLoader;

class CategoryDetailLoader
{
    use SortArrayByKeysTrait;

    /**
     * @var CategoryDetailFactory
     */
    private $factory;

    /**
     * @var ProductBasicLoader
     */
    private $productBasicLoader;

    /**
     * @var CustomerGroupBasicLoader
     */
    private $customerGroupBasicLoader;

    public function __construct(
        CategoryDetailFactory $factory,
ProductBasicLoader $productBasicLoader,
CustomerGroupBasicLoader $customerGroupBasicLoader
    ) {
        $this->factory = $factory;
        $this->productBasicLoader = $productBasicLoader;
        $this->customerGroupBasicLoader = $customerGroupBasicLoader;
    }

    public function load(array $uuids, TranslationContext $context): CategoryDetailCollection
    {
        $categories = $this->read($uuids, $context);

        $products = $this->productBasicLoader->load($categories->getProductUuids(), $context);

        $blockedCustomerGroupss = $this->customerGroupBasicLoader->load($categories->getBlockedCustomerGroupsUuids(), $context);

        /** @var CategoryDetailStruct $category */
        foreach ($categories as $category) {
            $category->setProducts($products->getList($category->getProductUuids()));
            $category->setBlockedCustomerGroupss($blockedCustomerGroupss->getList($category->getBlockedCustomerGroupsUuids()));
        }

        return $categories;
    }

    private function read(array $uuids, TranslationContext $context): CategoryDetailCollection
    {
        $query = $this->factory->createQuery($context);

        $query->andWhere('category.uuid IN (:ids)');
        $query->setParameter(':ids', $uuids, Connection::PARAM_STR_ARRAY);

        $rows = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        $structs = [];
        foreach ($rows as $row) {
            $struct = $this->factory->hydrate($row, new CategoryDetailStruct(), $query->getSelection(), $context);
            $structs[$struct->getUuid()] = $struct;
        }

        return new CategoryDetailCollection(
            $this->sortIndexedArrayByKeys($uuids, $structs)
        );
    }
}
