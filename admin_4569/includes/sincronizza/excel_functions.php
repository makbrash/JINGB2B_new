<?php
// Funzioni per la gestione dei file Excel
require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Carica e verifica il file Excel
 */
function uploadExcelFile($tempDir) {
    // Debug dell'upload
    error_log("DEBUG UPLOAD: " . print_r($_FILES, true));
    
    if (!isset($_FILES['excelFile'])) {
        error_log("Nessun file ricevuto");
        return ['success' => false, 'message' => 'Nessun file ricevuto'];
    }
    
    if ($_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
        $errMsg = getUploadErrorMessage($_FILES['excelFile']['error']);
        error_log("Errore upload: " . $errMsg);
        return ['success' => false, 'message' => $errMsg];
    }
    
    if (!isValidExcelType($_FILES['excelFile']['type'])) {
        error_log("Tipo file non valido: " . $_FILES['excelFile']['type']);
        return ['success' => false, 'message' => 'Tipo file non valido. Carica un file Excel (.xls o .xlsx)'];
    }
    
    $tempFilePath = saveTemporaryFile($tempDir, $_FILES['excelFile']);
    if (!$tempFilePath['success']) {
        return $tempFilePath;
    }
    
    try {
        verifyExcelFile($tempFilePath['file_path']);
        return [
            'success' => true, 
            'file_path' => $tempFilePath['file_path'],
            'file_name' => $_FILES['excelFile']['name']
        ];
    } catch (Exception $e) {
        error_log("Errore verifica file Excel: " . $e->getMessage());
        unlink($tempFilePath['file_path']);
        return [
            'success' => false,
            'message' => 'Il file non è un file Excel valido'
        ];
    }
}

/**
 * Legge l'anteprima del file Excel
 */
function readExcelPreview($filePath, $maxRows = 5) {
    try {
        if (!file_exists($filePath)) {
            throw new Exception('File non trovato: ' . $filePath);
        }

        $spreadsheet = loadSpreadsheet($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $data = readWorksheetData($worksheet, $maxRows);
        
        return [
            'success' => true,
            'data' => $data['rows'],
            'columns' => $data['columns']
        ];
    } catch (Exception $e) {
        error_log("Errore nella lettura del file Excel: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Errore nella lettura del file Excel: ' . $e->getMessage()
        ];
    }
}

/**
 * Funzioni di supporto
 */
function getUploadErrorMessage($error) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'File troppo grande (limite php.ini)',
        UPLOAD_ERR_FORM_SIZE => 'File troppo grande (limite form)',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE => 'Nessun file caricato'
    ];
    return $messages[$error] ?? 'Errore sconosciuto: ' . $error;
}

function isValidExcelType($type) {
    $allowedTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream'
    ];
    return in_array($type, $allowedTypes);
}

function saveTemporaryFile($tempDir, $file) {
    $tempFileName = uniqid('excel_') . '_' . $file['name'];
    $tempFilePath = $tempDir . '/' . $tempFileName;
    
    if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
        error_log("Impossibile spostare il file in: " . $tempFilePath);
        return [
            'success' => false, 
            'message' => 'Impossibile salvare il file temporaneo'
        ];
    }
    
    return [
        'success' => true,
        'file_path' => $tempFilePath
    ];
}

function verifyExcelFile($filePath) {
    $inputFileType = IOFactory::identify($filePath);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(true);
    $reader->load($filePath);
}

function loadSpreadsheet($filePath) {
    $inputFileType = IOFactory::identify($filePath);
    $reader = IOFactory::createReader($inputFileType);
    $reader->setReadDataOnly(true);
    return $reader->load($filePath);
}

function readWorksheetData($worksheet, $maxRows) {
    $highestRow = min($worksheet->getHighestRow(), $maxRows);
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    $data = [];
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $cell = $worksheet->getCell($columnLetter . $row);
            $rowData[] = formatCellValue($cell);
        }
        $data[] = $rowData;
    }

    return [
        'rows' => $data,
        'columns' => $highestColumnIndex
    ];
}

function formatCellValue($cell) {
    $value = $cell->getValue();
    
    if ($value === null) {
        return '';
    }
    
    if ($cell->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
        if (!\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
            return (string)(float)$value;
        }
    }
    
    return (string)$value;
}
?> 