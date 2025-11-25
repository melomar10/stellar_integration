<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Exporta datos a Excel
     * 
     * @param array $data Array de datos a exportar
     * @param array $headers Encabezados de las columnas
     * @param string $filename Nombre del archivo (sin extensión)
     * @return StreamedResponse
     */
    public function exportToExcel(array $data, array $headers, string $filename = 'export'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer encabezados
        $columnIndex = 0;
        foreach ($headers as $header) {
            $column = $this->getColumnLetter($columnIndex + 1);
            $sheet->setCellValue($column . '1', $header['label']);
            $sheet->getColumnDimension($column)->setAutoSize(true);
            $columnIndex++;
        }

        // Estilo para encabezados
        $headerRange = 'A1:' . $this->getColumnLetter(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '39B77F'], // Color primario del panel
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Agregar datos
        $row = 2;
        foreach ($data as $item) {
            $columnIndex = 0;
            foreach ($headers as $header) {
                $column = $this->getColumnLetter($columnIndex + 1);
                $value = $this->getValueFromItem($item, $header['key']);
                $sheet->setCellValue($column . $row, $value);
                $columnIndex++;
            }
            $row++;
        }

        // Aplicar bordes a todas las celdas con datos
        $dataRange = 'A1:' . $this->getColumnLetter(count($headers)) . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Ajustar altura de filas
        $sheet->getRowDimension(1)->setRowHeight(25);
        for ($i = 2; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(20);
        }

        // Crear respuesta de descarga
        $writer = new Xlsx($spreadsheet);
        
        return new StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Obtiene el valor de un item usando una clave (soporta notación de punto)
     * 
     * @param mixed $item
     * @param string $key
     * @return mixed
     */
    private function getValueFromItem($item, string $key)
    {
        if (is_array($item)) {
            $keys = explode('.', $key);
            $value = $item;
            foreach ($keys as $k) {
                if (isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return '';
                }
            }
            return $value;
        } elseif (is_object($item)) {
            $keys = explode('.', $key);
            $value = $item;
            foreach ($keys as $k) {
                if (isset($value->$k)) {
                    $value = $value->$k;
                } else {
                    return '';
                }
            }
            return $value;
        }
        
        return '';
    }

    /**
     * Convierte un número de columna a letra (A, B, C, ..., Z, AA, AB, ...)
     * 
     * @param int $number Número de columna (1 = A, 2 = B, etc.)
     * @return string
     */
    private function getColumnLetter(int $number): string
    {
        $letter = '';
        $number--; // Convertir a base 0
        while ($number >= 0) {
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intval($number / 26) - 1;
        }
        return $letter;
    }
}

