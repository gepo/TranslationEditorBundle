<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Translation;

use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\MessageCatalogue;
use ServerGrove\Bundle\TranslationEditorBundle\Storage\StorageInterface;
use ServerGrove\Bundle\TranslationEditorBundle\Model\LocaleInterface;
use ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface;

class Translator extends BaseTranslator
{
    private $storage;
    private $locales = array();

    public function __construct(StorageInterface $storage, MessageSelector $selector, $loaderIds = array(), array $options = array())
    {
        parent::__construct('en_US', $selector, $loaderIds, $options);

        $this->storage = $storage;
        $this->initialize();
    }

    private function initialize()
    {
        $this->locale = [];

        foreach ($this->storage->findLocaleList() as $locale) {
            /* @var $locale LocaleInterface */
            $this->locales[$locale->getLanguage().'_'.$locale->getCountry()] = $locale;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        if (!isset($this->locales[$locale])) {
            return $id; // FIXME
        }

        $entry = $this->storage->findEntryList([
            'fileName' => $domain,
            'alias' => $id,
        ]);
        $entry = reset($entry);
        if (!$entry) {
            return $id; // FIXME
        }
        $translation = $this->storage->findTranslationList([
            'locale' => $this->locales[$locale],
            'entry' => $entry,
        ]);
        $translation = reset($translation);
        if (!$translation) {
            return $id; //FIXME
        }
        /* @var $translation TranslationInterface */

        return strtr($translation->getValue(), $parameters);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function transChoice($id, $number, array $parameters = array(), $domain = null, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        $id = (string) $id;

        $catalogue = $this->getCatalogue($locale);
        if (!$catalogue->defines($id, $domain)) {
            if ($cat = $catalogue->getFallbackCatalogue()) {
                $catalogue = $cat;
                $locale = $catalogue->getLocale();
            } else {
                return $id; //FIXME
            }
        }

        if (!isset($this->locales[$locale])) {
            return $id; // FIXME
        }

        $entry = $this->storage->findEntryList([
            'fileName' => $domain,
            'alias' => $id,
        ]);
        $entry = reset($entry);
        if (!$entry) {
            return $id; // FIXME
        }
        $translation = $this->storage->findTranslationList([
            'locale' => $this->locales[$locale],
            'entry' => $entry,
        ]);
        $translation = reset($translation);
        if (!$translation) {
            return $id; //FIXME
        }
        /* @var $translation TranslationInterface */

        return strtr($this->selector->choose($translation->getValue(), (int) $number, $locale), $parameters);
    }

    public function getCatalogue($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        if (!isset($this->locales[$locale])) {
            return;
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        return $this->catalogues[$locale];
    }

    protected function loadCatalogue($locale)
    {
        $this->initializeCatalogue($locale);
    }

    protected function initializeCatalogue($locale)
    {
        $this->assertValidLocale($locale);

        try {
            $this->doLoadCatalogue($locale);
        } catch (NotFoundResourceException $e) {
            if (!$this->computeFallbackLocales($locale)) {
                throw $e;
            }
        }
//        $this->loadFallbackCatalogues($locale);
    }

    private function doLoadCatalogue($locale)
    {
        /* @var $catelogue MessageCatalogue */
        $this->catalogues[$locale] = $catelogue = new MessageCatalogue($locale);

        $translations = $this->storage->findTranslationList([
            'locale' => $this->locales[$locale],
        ]);

        $messagesData = array();

        foreach ($translations as $translation) {
            /* @var $translation TranslationInterface */
            $domain = $translation->getEntry()->getFileName();

            if (!isset($messagesData[$domain])) {
                $messagesData[$domain] = array();
            }

            $messagesData[$domain][$translation->getEntry()->getAlias()] = $translation->getValue();
        }

        foreach ($messagesData as $domain => $messages) {
            $catelogue->add($messages, $domain);
        }
    }
}
