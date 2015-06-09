<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Storage;

use ServerGrove\Bundle\TranslationEditorBundle\Model\Locale;
use ServerGrove\Bundle\TranslationEditorBundle\Model\Entry;

/**
 * Doctrine MongoDB Storage.
 *
 * @author Ken Golovin <kengolovin@gmail.com>
 */
class MongoDBStorage extends AbstractStorage implements StorageInterface
{
    const CLASS_LOCALE      = 'ServerGrove\Bundle\TranslationEditorBundle\Model\Locale';
    const CLASS_ENTRY       = 'ServerGrove\Bundle\TranslationEditorBundle\Model\Entry';
    const CLASS_TRANSLATION = 'ServerGrove\Bundle\TranslationEditorBundle\Model\Translation';

    /**
     * {@inheritdoc}
     */
    protected function getLocaleClassName()
    {
        return self::CLASS_LOCALE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntryClassName()
    {
        return self::CLASS_ENTRY;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTranslationClassName()
    {
        return self::CLASS_TRANSLATION;
    }

    /**
     * {@inheritdoc}
     */
    public function findLocaleList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder($this->getLocaleClassName())->sort('language', 'asc');

        $this->hydrateCriteria($builder, $criteria);

        return iterator_to_array($builder->getQuery()->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function findEntryList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder($this->getEntryClassName())->sort('alias', 'asc');

        if (isset($criteria['locale']) && $criteria['locale'] instanceof Locale) {
            $builder->field('locale.$id')->equals(new \MongoId($criteria['locale']->getId()));
            unset($criteria['locale']);
        }

        $this->hydrateCriteria($builder, $criteria);

        return iterator_to_array($builder->getQuery()->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslationList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder($this->getTranslationClassName());

        if (isset($criteria['locale']) && $criteria['locale'] instanceof Locale) {
            $builder->field('locale.$id')->equals(new \MongoId($criteria['locale']->getId()));
        }
        if (isset($criteria['entry']) && $criteria['entry'] instanceof Entry) {
            $builder->field('entry.$id')->equals(new \MongoId($criteria['entry']->getId()));
        }

        return iterator_to_array($builder->getQuery()->execute());
    }

    /**
     * Populate a criteria builder.
     *
     * @param \Doctrine\ODM\MongoDB\Query\Builder $builder
     * @param array                               $criteria
     */
    protected function hydrateCriteria($builder, array $criteria = array())
    {
        foreach ($criteria as $fieldName => $fieldValue) {
            $builder->field($fieldName)->equals($fieldValue);
//            $builder->addOr($builder->expr()->field($fieldName)->equals($fieldValue));
        }
    }
}
