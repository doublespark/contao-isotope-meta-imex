<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Backend modules
 */
array_insert($GLOBALS['BE_MOD']['isotope'], 5, array
(
    'isotope_meta_imex' => array
    (
        'callback'   => 'Doublespark\IsotopeMetaImportExportBundle\BackendModule\IsotopeMetaImportExport'
    )
));