<?php

use GFExcel\Renderer\PHPExcelMultisheetRenderer;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MultiFormSingleSheetRenderer extends PHPExcelMultisheetRenderer
{
    public function renderOutput($extension = 'xlsx', $save = false)
    {
        $this->assembleWorksheets();
        parent::renderOutput($extension, $save);
    }

    /**
     * Retrieves all data from worksheets, and copies it to a single worksheet.
     * N.B. It will lose all styling and column widths.
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function assembleWorksheets()
    {
        $assemebled = new Worksheet();
        $line = 1;
        foreach ($this->spreadsheet->getWorksheetIterator() as $index => $worksheet) {
            // copy all rows to new worksheet
            $assemebled->fromArray($worksheet->toArray(), null, 'A' . $line);
            // Keep track of row number for next iteration
            $line += $worksheet->getHighestRow();
            // remove the worksheet, free up memory.
            $this->spreadsheet->removeSheetByIndex($index);
            // Unescape this if you want 1 emtpy line between data sets.
         //   $line++;
        }
        // lose all worksheets
        $this->spreadsheet->disconnectWorksheets();
        // Add the new worksheet to the spreadsheet
        $this->spreadsheet->addSheet($assemebled, 0);
        $this->spreadsheet->setActiveSheetIndex(0);
    }
}
