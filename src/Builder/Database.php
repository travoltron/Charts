<?php

/*
 * This file is part of consoletvs/charts.
 *
 * (c) Erik Campobadal <soc@erik.cat>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ConsoleTVs\Charts\Builder;

use Jenssegers\Date\Date;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * This is the database class.
 *
 * @author Erik Campobadal <soc@erik.cat>
 */
class Database extends Chart
{
    /**
     * @var Collection
     */
    public $data;

    /**
     * Determines the date column.
     *
     * @var string
     */
    public $date_column;

    /**
     * Determines the date format.
     *
     * @var string
     */
    public $date_format = 'l dS M, Y';

    /**
     * Determines the month format.
     *
     * @var string
     */
    public $month_format = 'F, Y';

    /**
     * Determines the hour format.
     *
     * @var string
     */
    public $hour_format = 'D, M j, Y g A';

    /**
     * Determines the dates language.
     *
     * @var string
     */
    public $language;

    public $preaggregated = false;
    public $aggregate_column = null;
    public $aggregate_type = null;
    public $value_data = [];

    /**
     * Create a new database instance.
     *
     * @param Collection $data
     * @param string $type
     * @param string $library
     */
    public function __construct($data, $type = null, $library = null)
    {
        parent::__construct($type, $library);
        $this->date_column = 'created_at';
        $this->language = App::getLocale();
        $this->data = $data;
    }

    /**
     * @param Collection $data
     *
     * @return Database
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set date column to filter the data.
     *
     * @param string $column
     *
     * @return Database
     */
    public function dateColumn($column)
    {
        $this->date_column = $column;

        return $this;
    }

    /**
     * Set fancy date format based on PHP date() function.
     *
     * @param string $format
     *
     * @return Database
     */
    public function dateFormat($format)
    {
        $this->date_format = $format;

        return $this;
    }

    /**
     * Set fancy month format based on PHP date() function.
     *
     * @param string $format
     *
     * @return Database
     */
    public function monthFormat($format)
    {
        $this->month_format = $format;

        return $this;
    }

    /**
     * Set fancy hour format based on PHP date() function.
     *
     * @param string $format
     *
     * @return Database
     */
    public function hourFormat($format)
    {
        $this->hour_format = $format;

        return $this;
    }

    /**
     * Set whether data is preaggregated or should be summed.
     *
     * @param bool $preaggregated
     *
     * @return Database
     */
    public function preaggregated($preaggregated)
    {
        $this->preaggregated = $preaggregated;

        return $this;
    }

    /**
     * Set the Date language that is going to be used.
     *
     * @param string $language
     *
     * @return Database
     */
    public function language($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set the column in which this program should use to aggregate. This is useful for summing/averaging columns.
     *
     * @param string $aggregateColumn - name of the column to aggregate
     * @param string $aggregateType - type of aggregation (sum, avg, min, max, count, ...)
     *                                Must be Laravel collection commands.
     * @see Illuminate\Support\Collection
     *
     * @return Database
     */
    public function aggregateColumn($aggregateColumn, $aggregateType)
    {
        $this->aggregate_column = $aggregateColumn;
        $this->aggregate_type = $aggregateType;

        return $this;
    }

    /**
     * Group the data hourly based on the creation date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @param bool $fancy
     *
     * @return Database
     */
    public function groupByHour($day = null, $month = null, $year = null, $fancy = false)
    {
        $labels = [];
        $values = [];

        $format = 'd-m-Y H:00:00';
        Date::setLocale($this->language);

        $day = $day ?: date('d');
        $month = $month ?: date('m');
        $year = $year ?: date('Y');

        $begin = (new Date('00:00:00'))->setDate($year, $month, $day);
        $end = (clone $begin)->modify('+1 day');

        $daterange = new \DateInterval($begin, new \DateInterval('PT1H'), $end);
        foreach ($daterange as $date) {

            $label = ucfirst($date->format($fancy ? $this->hour_format : $format));

            $value = $this->getCheckDateValue($date, $format, $label);

            array_push($labels, $label);
            array_push($values, $value);
        }
        $this->labels($labels);
        $this->values($values);

        return $this;
    }

    /**
     * Group the data daily based on the creation date.
     *
     * @param int $month
     * @param int $year
     * @param bool $fancy
     *
     * @return Database
     */
    public function groupByDay($month = null, $year = null, $fancy = false)
    {
        $labels = [];
        $values = [];

        $format = 'd-m-Y';
        Date::setLocale($this->language);

        $month = $month ?: date('m');
        $year = $year ?: date('Y');

        $begin = (new Date('00:00:00'))->setDate($year, $month, 1);
        $end = (clone $begin)->modify('+1 month');

        $daterange = new \DatePeriod($begin, new \DateInterval('P1D'), $end);
        foreach ($daterange as $date) {

            $label = ucfirst($date->format($fancy ? $this->date_format : $format));

            $value = $this->getCheckDateValue($date, $format, $label);

            array_push($labels, $label);
            array_push($values, $value);
        }
        $this->labels($labels);
        $this->values($values);

        return $this;
    }

    /**
     * Group the data monthly based on the creation date.
     *
     * @param int $year
     * @param bool $fancy
     *
     * @return Database
     */
    public function groupByMonth($year = null, $fancy = false)
    {
        $labels = [];
        $values = [];

        $format = 'm-Y';
        Date::setLocale($this->language);

        $year = $year ?: date('Y');

        $begin = (new Date('00:00:00'))->setDate($year, 1, 1);
        $end = (clone $begin)->modify('+1 year');

        $daterange = new \DatePeriod($begin, new \DateInterval('P1M'), $end);
        foreach ($daterange as $date) {

            $label = ucfirst($date->format($fancy ? $this->month_format : $format));

            $value = $this->getCheckDateValue($date, $format, $label);

            array_push($labels, $label);
            array_push($values, $value);
        }

        $this->labels($labels);
        $this->values($values);

        return $this;
    }

    /**
     * Group the data yearly based on the creation date.
     *
     * @param int $number
     *
     * @return Database
     */
    public function groupByYear($number = 4)
    {
        $labels = [];
        $values = [];

        $format = 'Y';
        $today = new Date();

        for ($i = 0; $i < $number; $i++) {
            $date = (clone $today)->modify('-' . $i . ' years');

            $label = $date->format($format);
            $value = $this->getCheckDateValue($date, $format, $label);

            array_push($labels, $label);
            array_push($values, $value);
        }

        $this->labels(array_reverse($labels));
        $this->values(array_reverse($values));

        return $this;
    }

    /**
     * Group the data based on the column.
     *
     * @param string $column
     * @param string $relationColumn
     * @param array $labelsMapping
     *
     * @return Database
     */
    public function groupBy($column, $relationColumn = null, array $labelsMapping = [])
    {
        $labels = [];
        $values = [];

        if ($relationColumn && strpos($relationColumn, '.') !== false) {
            $relationColumn = explode('.', $relationColumn);
        }

        foreach ($this->data->groupBy($column) as $data) {
            $label = $data[0];

            if (is_null($relationColumn)) {
                $label = $label->$column;
            } elseif (is_array($relationColumn)) {
                foreach ($relationColumn as $boz) {
                    $label = $label->$boz;
                }
            } else {
                $label = $data[0]->$relationColumn;
            }

            array_push($labels, array_key_exists($label, $labelsMapping) ? $labelsMapping[$label] : $label);
            array_push($values, count($data));
        }

        $this->labels($labels);
        $this->values($values);

        return $this;
    }

    /**
     * Group the data based on the latest days.
     *
     * @param int $number
     * @param bool $fancy
     *
     * @return Database
     */
    public function lastByDay($number = 7, $fancy = false)
    {
        $labels = [];
        $values = [];

        $format = 'd-m-Y';
        Date::setLocale($this->language);

        $today = new Date();
        for ($i = 0; $i < $number; $i++) {
            $date = (clone $today)->modify('-' . $i . ' days');

            $label = ucfirst($date->format($fancy ? $this->date_format : $format));

            $value = $this->getCheckDateValue($date, $format, $label);

            array_push($labels, $label);
            array_push($values, $value);
        }

        $this->labels(array_reverse($labels));
        $this->values(array_reverse($values));

        return $this;
    }

    /**
     * Group the data based on the latest months.
     *
     * @param int $number
     * @param bool $fancy
     *
     * @return Database
     */
    public function lastByMonth($number = 6, $fancy = false)
    {
        $labels = [];
        $values = [];

        $format = 'm-Y';
        Date::setLocale($this->language);

        $today = (new Date())->modify('fist day of month');
        for ($i = 0; $i < $number; $i++) {
            $date = (clone $today)->modify('-' . $i . ' months');

            $label = ucfirst($date->format($fancy ? $this->month_format : $format));

            $value = $this->getCheckDateValue($date, $format, $label);

            array_push($labels, $label);
            array_push($values, $value);
        }

        $this->labels(array_reverse($labels));
        $this->values(array_reverse($values));

        return $this;
    }

    /**
     * Alias for groupByYear().
     *
     * @param int $number
     *
     * @return Database
     */
    public function lastByYear($number = 4)
    {
        return $this->groupByYear($number);
    }

    /**
     * This is a simple value generator for the three types of summations used in this Chart object when sorted via data.
     *
     * @param \DateTimeInterface $checkDate - a string in the format 'Y-m-d H:ii::ss' Needs to resemble up with $formatToCheck to work
     * @param string $formatToCheck - a string in the format 'Y-m-d H:ii::ss' Needs to resemble up with $checkDate to work
     * @param string $label
     * @return mixed
     */
    private function getCheckDateValue(\DateTimeInterface $checkDate, $formatToCheck, $label)
    {
        $date_column = $this->date_column;
        $data = $this->data;

        $filter_function = function ($value) use ($checkDate, $date_column, $formatToCheck) {
            return $checkDate->format($formatToCheck) == (new \DateTimeImmutable($value->$date_column))->format($formatToCheck);
        };

        if ($this->preaggregated) {
            // Since the column has been preaggregated, we only need one record that matches the search
            $valueData = $data->first($filter_function);
            $value = $valueData !== null ? $valueData->aggregate : 0;
        } else {
            // Set the data represented. Return the relevant value.
            $valueData = $data->filter($filter_function);

            if ($valueData !== null) {
                // Do an aggregation, otherwise count the number of records.
                $value = $this->aggregate_column ? $valueData->{$this->aggregate_type}($this->aggregate_column) : $valueData->count();
            } else {
                $value = 0;
            }

            // Store the datasets by label.
            $this->value_data[$label] = $valueData;
        }

        return $value;
    }
}
