<?php

namespace Modules\Wallet\Services\Charts;

use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;

class ChartService
{
	public static function createPieChart(
		array $labels,
		array $values,
		string $title = "Chart"
	) {
		// Pastikan ada data
		if (empty($labels) || empty($values)) {
			return null;
		}

		// Konversi nilai
		$convertedValues = array_map(function ($value) {
			return is_numeric($value) ? $value / 100 : 0;
		}, $values);

		// Data series untuk kategori
		$dataSeriesLabels = [
			new DataSeriesValues(
				DataSeriesValues::DATASERIES_TYPE_STRING,
				null,
				null,
				count($labels),
				$labels
			),
		];

		// Data series untuk nilai
		$dataSeriesValues = [
			new DataSeriesValues(
				DataSeriesValues::DATASERIES_TYPE_NUMBER,
				null,
				null,
				count($convertedValues),
				$convertedValues
			),
		];

		// Buat data series
		$series = new DataSeries(
			DataSeries::TYPE_PIECHART,
			null,
			range(0, count($dataSeriesValues) - 1),
			$dataSeriesLabels,
			$dataSeriesValues
		);

		// Buat chart
		$chart = new Chart(
			"chart_" . uniqid(),
			new Title($title),
			new Legend(),
			new PlotArea(null, [$series]),
			true
		);

		return $chart;
	}

	public static function createBarChart(
		array $categories,
		array $data1,
		array $data2 = [],
		string $title = "Chart"
	) {
		$dataSeries = [];

		// Series 1
		if (!empty($data1)) {
			$convertedData1 = array_map(function ($value) {
				return is_numeric($value) ? $value / 100 : 0;
			}, $data1);

			$dataSeries[] = new DataSeriesValues(
				DataSeriesValues::DATASERIES_TYPE_NUMBER,
				null,
				null,
				count($convertedData1),
				$convertedData1,
				null,
				null,
				DataSeriesValues::DIRECTION_VERTICAL
			);
		}

		// Series 2
		if (!empty($data2)) {
			$convertedData2 = array_map(function ($value) {
				return is_numeric($value) ? $value / 100 : 0;
			}, $data2);

			$dataSeries[] = new DataSeriesValues(
				DataSeriesValues::DATASERIES_TYPE_NUMBER,
				null,
				null,
				count($convertedData2),
				$convertedData2,
				null,
				null,
				DataSeriesValues::DIRECTION_VERTICAL
			);
		}

		if (empty($dataSeries)) {
			return null;
		}

		// Category labels
		$categoryLabels = new DataSeriesValues(
			DataSeriesValues::DATASERIES_TYPE_STRING,
			null,
			null,
			count($categories),
			$categories
		);

		$series = new DataSeries(
			DataSeries::TYPE_BARCHART,
			DataSeries::GROUPING_CLUSTERED,
			range(0, count($dataSeries) - 1),
			[$categoryLabels],
			$dataSeries
		);

		$chart = new Chart(
			"chart_" . uniqid(),
			new Title($title),
			new Legend(),
			new PlotArea(null, [$series]),
			true
		);

		return $chart;
	}
}
