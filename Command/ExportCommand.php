<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Translation\MessageCatalogue;

use Doctrine\Common\Collections\ArrayCollection;
use ServerGrove\Bundle\TranslationEditorBundle\Storage\StorageInterface;
use ServerGrove\Bundle\TranslationEditorBundle\Model\TranslationInterface;

/**
 * Command for exporting translations into files
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ExportCommand extends AbstractCommand
{
    /**
     * @var StorageInterface
     */
    private $storageService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('locale:editor:export')
            ->setDescription('Export translations into files')
            ->addOption('bundle', null, InputOption::VALUE_OPTIONAL, 'Allow to export a single bundle')
            ->addOption('locale', null, InputOption::VALUE_OPTIONAL, 'Export to a single locale')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Restrict the exporting to a single file')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->storageService = $this->getContainer()->get('server_grove_translation_editor.storage');

        // Locales scanning
        $this->output->write('Scanning for locales... ');

        $localeList      = $this->getLocaleList($this->input->getOption('locale'));
        $localeListCount = count($localeList);

        $this->output->writeln(sprintf('found "<info>%s</info>" item(s).', $localeListCount));

        if ( ! $localeListCount) {
            $this->output->writeln('  No locales to be processed.');

            return;
        }

        // Bundles scanning
        $this->output->write('Scanning for bundles... ');

        $bundleList      = $this->getBundleList($this->input->getOption('bundle'));
        $bundleListCount = count($bundleList);

        $this->output->writeln(sprintf('found "<info>%s</info>" item(s).', $bundleListCount));

        if ( ! $bundleListCount) {
            $this->output->writeln('No bundles to be processed.');

            return;
        }

        // Exporting Bundles
        foreach ($bundleList as $bundle) {
            $this->output->writeln('');
            $this->output->writeln(sprintf('<comment>%s</comment>', $bundle->getName()));

            $this->exportBundle($bundle, $localeList);
        }

        $this->output->writeln('');
        $this->output->writeln('Exporting completed.');
    }

    /**
     * Export a Bundle
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     * @param array $localeList
     */
    protected function exportBundle($bundle, $localeList)
    {
        // Entries scanning
        $this->output->write('  Scanning for entries... ');

        $entryList      = $this->getEntryList($bundle->getName(), $this->input->getOption('file'));
        $entryListCount = count($entryList);

        $this->output->writeln(sprintf('found "<info>%s</info>" item(s).', $entryListCount));

        if ( ! $entryListCount) {
            $this->output->writeln('  No entries to be processed.');

            return;
        }

        // Exporting locales
        foreach ($localeList as $locale) {
            $this->output->writeln('');
            $this->output->writeln(sprintf('  <comment>%s</comment>', (string) $locale));

            $this->exportLocale($bundle, $locale, $entryList);
        }
    }

    /**
     * Export a Locale
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     * @param Locale $locale
     * @param array $entryList
     */
    protected function exportLocale($bundle, $locale, $entryList)
    {
        // Translations scanning
        $this->output->write('    Scanning for translations... ');

        $translationList      = $this->getTranslationList($locale, $entryList);
        $translationListCount = count($translationList);

        $this->output->writeln(sprintf('found "<info>%s</info>" item(s).', $translationListCount));

        if ( ! $translationListCount) {
            $this->output->writeln('    No translations to be processed.');

            return;
        }

        // Organizing Translations into files
        $translationFileList      = $this->getTranslationFileList($translationList);
        $translationFileListCount = count($translationFileList);

        // Exporting Translations
        $this->output->writeln(sprintf('    Exporting "<info>%s</info>" locale file(s)... ', $translationFileListCount));

        $this->exportTranslationFileList($bundle, $locale, $translationFileList);

        $this->output->writeln('    Task completed.');
    }

    /**
     * Export a Translation File list
     *
     * @param \Symfony\Component\HttpKernel\Bundle\Bundle $bundle
     * @param Locale $locale
     * @param \Doctrine\Common\Collections\ArrayCollection $translationFileList
     */
    protected function exportTranslationFileList($bundle, $locale, ArrayCollection $translationFileList)
    {
        $writer = $this->getContainer()->get('translation.writer');

        foreach ($translationFileList as $fileName => $translationList) {
            $filePath = sprintf('%s/%s/%s', $bundle->getPath(), self::TRANSLATION_PATH, $fileName);

            $this->output->write(sprintf('    Exporting "<info>%s</info>"... ', $fileName));

            $catalogue = new MessageCatalogue((string) $locale);

            foreach ($translationList as $translation) {
                $catalogue->set($translation->getEntry()->getAlias(), $translation->getValue(), $translation->getEntry()->getFilename());
            }


            $writer->writeTranslations($catalogue, $translation->getEntry()->getFormat(), array('path' => dirname($filePath)));

            $this->output->writeln('<info>DONE</info>');
        }
    }

    /**
     * Retrieve the list of Locales
     *
     * @param string $filterLocaleName
     *
     * @return array
     */
    protected function getLocaleList($filterLocaleName = null)
    {
        // Build filter criteria
        $criteria = array();

        if ($filterLocaleName) {
            $filterLocaleInfo = $this->extractLocaleInformation($filterLocaleName);

            $criteria['language'] = $filterLocaleInfo['language'];
            $criteria['country']  = $filterLocaleInfo['country'];
        }

        return $this->storageService->findLocaleList($criteria);
    }

    /**
     * Retrieve the list of entries
     *
     * @param string $bundleName
     * @param string $filterFileName
     *
     * @return array
     */
    protected function getEntryList($bundleName, $filterFileName = null)
    {
        // Prepare entry search criteria
        $criteria = array('domain' => $bundleName);

        if ($filterFileName) {
            $criteria['fileName'] = $filterFileName;
        }

        // Search for bundle entries
        $entryList      = $this->storageService->findEntryList($criteria);

        return $entryList;
    }

    /**
     * Retrieve the list of translations
     *
     * @param Locale $locale
     * @param array $entryList
     *
     * @return array
     */
    protected function getTranslationList($locale, $entryList)
    {
        $translationList = array();

        foreach ($entryList as $entry) {
            $translations = $entry->getTranslations()->filter(
                function ($item) use ($locale) {
                    return ($item->getLocale() === $locale);
                }
            )->toArray();

            $translation = array_shift($translations);

            if (count($translations) > 0) {
                $this->removeExtra($translations);
            }

            $translationList[] = $translation;
        }

        return array_filter($translationList);
    }

    /**
     * Retrieve the Translations separated by files
     *
     * @param array $translationList
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    protected function getTranslationFileList(array $translationList)
    {
        $translationFileList = new ArrayCollection();

        foreach ($translationList as $translation) {
            $translationFileName = $translation->getEntry()->getFileName();

            if ( ! isset($translationFileList[$translationFileName])) {
                $translationFileList[$translationFileName] = new ArrayCollection();
            }

            $translationFileList[$translationFileName][] = $translation;
        }

        return $translationFileList;
    }

    protected function removeExtra(array $elements)
    {
        foreach ($elements as $elem) {
            /* @var $elem TranslationInterface */
            $this->storageService->removeTranslation($elem);
        }
    }
}
