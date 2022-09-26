<?php

declare(strict_types=1);

namespace Doublespark\IsotopeMetaImportExportBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Doublespark\IsotopeMetaImportExportBundle\IsotopeMetaImportExportBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(IsotopeMetaImportExportBundle::class)
                ->setLoadAfter(['isotope'])
        ];
    }
}
