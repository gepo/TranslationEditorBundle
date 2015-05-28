<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use ServerGrove\Bundle\TranslationEditorBundle\Model\Entry;
use ServerGrove\Bundle\TranslationEditorBundle\Model\Translation;

/**
 * Editor Controller.
 */
class EditorController extends Controller
{
    /**
     * Index action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /* @var $request Request */
        $request = $this->get('request_stack')->getCurrentRequest();
        $filterBundle = $request->query->get('_bundle');
        $filterDomain = $request->query->get('_domain');
        $filterAlias  = $request->query->get('_alias');
        $filterLocale = $request->query->get('_locale') ?: [];

        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $kernelService  = $this->container->get('kernel');

        $sourcePath     = realpath($kernelService->getRootDir().'/../src');
        $kernelDefaultLocale  = $this->getRequest()->getLocale();

        // Retrieving mandatory information
        $localeList = $storageService->findLocaleList();
        $entryList  = $storageService->findEntryList();

        $entryList = array_filter(
            $entryList,
            function ($entry) use ($filterBundle, $filterDomain, $filterAlias, $filterLocale) {
                /* @var $entry Entry */
                if (!empty($filterBundle)) {
                    $bundle = str_replace(['AppDaddy', 'Bundle'], '', $entry->getDomain());
                    if (! preg_match('/'.preg_quote($filterBundle).'/i', $bundle)) {
                        return false;
                    }
                }
                if (!empty($filterDomain)) {
                    if (! preg_match('/'.preg_quote($filterDomain).'/i', $entry->getFileName())) {
                        return false;
                    }
                }
                if (!empty($filterAlias)) {
                    if (! preg_match('/'.preg_quote($filterAlias).'/i', $entry->getAlias())) {
                        return false;
                    }
                }

                $trans = [];

                foreach ($entry->getTranslations() as $t) {
                    /* @var $t Translation */
                    $trans[$t->getLocale()->getLanguage().'_'.$t->getLocale()->getCountry()] = $t;
                }

                foreach ($filterLocale as $locale => $filterLocaleValue) {
                    if (empty($filterLocale[$locale])) {
                        continue;
                    }

                    if (empty($trans[$locale]) || ! preg_match('/'.preg_quote($filterLocaleValue).'/i', $trans[$locale]->getValue())) {
                        return false;
                    }
                }

                return true;
            }
        );

        usort($entryList, function ($a, $b) {
            if ($a->getAlias() < $b->getAlias()) {
                return -1;
            }

            return 1;
        });

        // Processing registered bundles
        $bundleList = array_filter(
            $kernelService->getBundles(),
            function ($bundle) use ($sourcePath) {
                return (strpos($bundle->getPath(), $sourcePath) === 0);
            }
        );

        // Processing default locale
        $defaultLocale = array_filter(
            $localeList,
            function ($locale) use ($kernelDefaultLocale) {
                return $locale->equalsTo($kernelDefaultLocale);
            }
        );
        $defaultLocale = reset($defaultLocale);

        return $this->render(
            'ServerGroveTranslationEditorBundle:Editor:index.html.twig',
            array(
                'bundleList'    => $bundleList,
                'localeList'    => $localeList,
                'entryList'     => $entryList,
                'defaultLocale' => $defaultLocale,

                'filterBundle' => $filterBundle,
                'filterDomain' => $filterDomain,
                'filterAlias'  => $filterAlias,
                'filterLocale' => $filterLocale,
            )
        );
    }

    /**
     * Remove Translation action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeTranslationAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        try {
            $id     = $request->request->get('id');
            $status = $storageService->deleteEntry($id);

            $result = array(
                'result' => $status,
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage(),
            );
        }

        return new JsonResponse($result);
    }

    /**
     * Add Translation action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addTranslationAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        // Retrieve variables
        $rawTranslations = $request->request->get('translations');
        $domain          = $request->request->get('domain');
        $alias           = $request->request->get('alias');

        $rawFileName  = $request->request->get('fileName');
        $fileName     = pathinfo($rawFileName, PATHINFO_FILENAME);
        $format       = pathinfo($rawFileName, PATHINFO_EXTENSION);

        // Check for existent domain/alias
        $entryList =  $storageService->findEntryList(array(
            'domain' => $domain,
            'alias'  => $alias,
        ));

        if (count($entryList)) {
            $result = array(
                'result'  => false,
                'message' => 'The alias already exists. Please update it instead.',
            );

            return new Response(json_encode($result));
        }

        // Create new Entry
        $entry = $storageService->createEntry($domain, $fileName, $format, $alias);

        // Create Translations
        $translations = array_filter($rawTranslations);

        foreach ($translations as $localeId => $translationValue) {
            $locale = $storageService->findLocaleList(array('id' => $localeId));
            $locale = reset($locale);

            $storageService->createTranslation($locale, $entry, $translationValue);
        }

        $storageService->flush();

        // Return reponse according to request type
        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        $result = array(
            'result'  => true,
            'message' => 'New translation added successfully. Reload list for completion.',
        );

        return new JsonResponse($result);
    }

    /**
     * Update Translation action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateTranslationAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        $value = $request->request->get('value');

        $localeList = $storageService->findLocaleList(array('id' => $request->request->get('localeId')));
        $locale     = reset($localeList);

        $entryList  = $storageService->findEntryList(array('id' => $request->request->get('entryId')));
        $entry      = reset($entryList);

        $translationList = $storageService->findTranslationList(array('locale' => $locale, 'entry'  => $entry));
        $translation     = reset($translationList);

        try {
            if ($translation) {
                $translation->setValue($value);

                $storageService->persist($translation);
            } else {
                $storageService->createTranslation($locale, $entry, $value);
            }

            $storageService->flush();

            $result = array(
                'result'  => true,
                'message' => 'Translation updated successfully.',
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage(),
            );
        }

        return new JsonResponse($result);
    }

    /**
     * Update Entry description.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateTranslationDescAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        $value = $request->request->get('value');

        $entryList  = $storageService->findEntryList(array('id' => $request->request->get('entryId')));
        $entry      = reset($entryList);

        if (!$entry) {
            throw new $this->createNotFoundException();
        }

        try {
            $entry->setDescription($value);

            $storageService->persist($entry);
            $storageService->flush();

            $result = array(
                'result'  => true,
                'message' => 'Description updated successfully.',
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage(),
            );
        }

        return new JsonResponse($result);
    }

    /**
     * Remove Locale action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeLocaleAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        try {
            $id     = $request->request->get('id');
            $status = $storageService->deleteLocale($id);

            $result = array(
                'result' => $status,
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage(),
            );
        }

        return new JsonResponse($result);
    }

    /**
     * Add Locale action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addLocaleAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        // Retrieve variables
        $language = $request->request->get('language');
        $country  = $request->request->get('country');

        try {
            // Check for country
            $country = (!empty($country)) ? $country : null;

            // Create new Locale
            $storageService->createLocale($language, $country);

            $storageService->flush();

            $result = array(
                'result'  => true,
                'message' => 'New locale added successfully. Reload list for completion.',
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage(),
            );
        }

        // Return reponse according to request type
        if (!$request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        return new JsonResponse($result);
    }
}
