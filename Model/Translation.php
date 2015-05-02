<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Model;

/**
 * Storage agnostic Translation entity.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class Translation implements TranslationInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Entry
     */
    protected $entry;

    /**
     * @var Locale
     */
    protected $locale;

    /**
     * @var string
     */
    protected $value;

    /**
     * Retrieve Entry identifier.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntry(EntryInterface $entry)
    {
        $entry->addTranslation($this);

        $this->entry = $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale(LocaleInterface $locale)
    {
        $locale->addTranslation($this);

        $this->locale = $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
