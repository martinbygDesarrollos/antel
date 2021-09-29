<?php

require_once URL_UTILS . 'PHPExcel/Classes/PHPExcel.php';

class generateExcel{

	public function createExcel($listContract){
		$objPHPExcel = new PHPExcel();
		PHPExcel_Settings::setLocale('es_es');
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', 'Grupo')
		->setCellValue('B1', 'Usuario')
		->setCellValue('C1', 'Contrato')
		->setCellValue('D1', 'Celular')
		->setCellValue('E1', 'Importe')
		->getStyle('A:L')
		->getAlignment()
		->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$index = 1;
		$importeTotal = 0;
		foreach ($listContract as $key => $row) {
			$index++;
			$objPHPExcel->getActiveSheet()->setCellValueExplicit('A'. $index, $row['grupo'] ,PHPExcel_Cell_DataType::TYPE_STRING);
			$objPHPExcel->getActiveSheet()->setCellValue('B' . $index, $row['usuario']);
			$objPHPExcel->getActiveSheet()->setCellValue('C' . $index, $row['contrato']);
			$objPHPExcel->getActiveSheet()->setCellValue('D' . $index, $row['celular']);
			$objPHPExcel->getActiveSheet()->setCellValue('E' . $index, $row['importe']);
			$objPHPExcel->getActiveSheet()->getStyle('E' . $index)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
			if($row['importe'] >  0)
				$importeTotal += $row['importe'];
		}

		$objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCFF');
		$objPHPExcel->getActiveSheet()->getStyle('A1:E1')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('E2'.':E'.$index)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFeeeeff');

		//ULTIMA LINEA
		$objPHPExcel->getActiveSheet()->getStyle('A'. ($index+2) .':E'.($index+2))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('FFEEFFEE');
		$objPHPExcel->getActiveSheet()->setCellValue('D' . ($index+2), "Importe total:");
		$objPHPExcel->getActiveSheet()->setCellValue('E' . ($index+2), $importeTotal);
		$objPHPExcel->getActiveSheet()->getStyle('E' . ($index+2))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$objPHPExcel->getActiveSheet()->getStyle('A'. ($index+2) .':E' . ($index+2))->getFont()->setBold(true);

		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

		$objPHPExcel->getActiveSheet()->setTitle('Contratos antel');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$location = dirname(dirname(__DIR__)) . "/public/files/";
		$objWriter->save($location . "excel.xlsx");

		$b64Doc = chunk_split(base64_encode(file_get_contents($location . "excel.xlsx")));
		return $b64Doc;
	}
}