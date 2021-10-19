<?php

namespace Doublespark\IsotopeMetaImportExportBundle\BackendModule;

use Contao\BackendModule;
use Contao\Database;
use Contao\Input;
use Isotope\Model\Product;
use League\Csv\Reader;
use League\Csv\Writer;

class IsotopeMetaImportExport extends BackendModule {

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'isotope_meta_import_export';

    /**
     * Field map
     * array['Label'] = 'database_field_name'
     * @var array
     */
    protected array $arrFieldMap = [
        'Product ID'       => 'id',
        'Name'             => 'name',
        'Alias'            => 'alias',
        'Meta title'       => 'meta_title',
        'Title Chars'      => null,
        'Meta description' => 'meta_description',
        'Desc Chars'       => null
    ];

    protected array $errors   = [];
    protected array $messages = [];
    protected array $warnings = [];

    /**
     * Generate the module
     * @return void
     */
    protected function compile()
    {
        if(Input::post('FORM_SUBMIT') == 'IMPORT_CSV')
        {
            $this->handleImport();
        }

        if(Input::post('FORM_SUBMIT') == 'EXPORT_CSV')
        {
            $this->handleExport();
        }

        $this->Template->errors   = $this->errors;
        $this->Template->messages = $this->messages;
        $this->Template->warnings = $this->warnings;

        $this->Template->rt = \RequestToken::get();
    }

    protected function handleImport()
    {
        if(isset($_FILES['meta_csv']['name']) AND !empty($_FILES['meta_csv']['name']))
        {
            $ext = pathinfo($_FILES['meta_csv']['name'],PATHINFO_EXTENSION);

            if($ext !== 'csv')
            {
                $this->errors[] = 'Please ensure uploaded file is a CSV';
                return;
            }

            $file = $_FILES['meta_csv']['tmp_name'];

            $csv = Reader::createFromPath($file);

            $arrImport = $csv->fetchAssoc(0);

            $i  = 0;
            $rc = 1;

            // Handle each row of the CSV
            foreach($arrImport as $row)
            {
                $rc++;

                $productRow = [];

                // Covert keys from labels to database fields
                foreach($row as $k => $v)
                {
                    if(key_exists($k,$this->arrFieldMap))
                    {
                        if(!is_null($this->arrFieldMap[$k]))
                        {
                            $productRow[$this->arrFieldMap[$k]] = $v;
                        }
                    }
                    else
                    {
                        $this->warnings[] = 'Unknown field: '. $k;
                    }
                }

                if(!isset($productRow['id']) || empty($productRow['id']))
                {
                    $this->warnings[] = 'Row '.$rc.': missing product ID.';
                    continue;
                }

                // Find product
                $productId = $productRow['id'];

                /**
                 * @var $obProduct Product
                 */
                $obProduct = Product::findByPk($productId);

                // Update and save page object
                if(!is_null($obProduct))
                {
                    unset($productRow['id']);

                    $arrSql = [];
                    $arrParams = [];

                    foreach($productRow as $field => $value)
                    {
                        $arrSql[]  = "`$field`=?";
                        $arrParams[] = $value;
                    }

                    $arrParams[] = $productId;

                    Database::getInstance()->prepare('UPDATE tl_iso_product SET '.implode(',',$arrSql).' WHERE id=?')->execute($arrParams);

                    $i++;
                }
                else
                {
                    $this->warnings[] = 'Row '.$rc.': product with ID '.$productRow['id'].' not found, row skipped.';
                }
            }

            $this->messages[] = 'Import complete. Updated '.$i.' products.';
        }
        else
        {
            $this->errors[] = 'No file was uploaded';
        }
    }

    protected function handleExport()
    {
        // This will hold the rows to be exported
        $arrExportRows = [];

        // Header row
        $row = [];

        // Generate the first (header) row form the field map labels
        foreach($this->arrFieldMap as $label => $field)
        {
            $row[] = $label;
        }

        $arrExportRows[] = $row;

        // Get top level products (not variants)
        $objProducts = Product::findBy('pid',0);

        // Items will start on row 2 of the CSV
        $rowNumber = 2;

        if($objProducts)
        {
            // Build page rows
            while($objProducts->next())
            {
                $row = [];

                foreach($this->arrFieldMap as $label => $field)
                {
                    if(!empty($field))
                    {
                        $row[] = $objProducts->$field;
                    }

                    if($label === 'Title Chars')
                    {
                        $row[] = '=LEN(D'.$rowNumber.')';
                    }

                    if($label === 'Desc Chars')
                    {
                        $row[] = '=LEN(F'.$rowNumber.')';
                    }
                }

                $rowNumber++;

                $arrExportRows[] = $row;
            }
        }

        // Create CSV
        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertAll($arrExportRows);
        $csv->output('product-meta-'.date('dmY').'.csv');
        die;
    }

}