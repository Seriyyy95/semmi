<?php

declare(strict_types=1);

namespace App\Services;


class PeriodsService
{
    /**
     * @param string $start_date
     * @param string $end_date
     * @param string $interval
     * @param bool $all
     * @return array
     */
    public function getPeriods(
        string $start_date,
        string $end_date,
        string $interval="month",
        bool $all=false
    ) : array {

        if ($start_date == "0000-00-00") {
            return array();
        }
        $startDate = \DateTime::createFromFormat('Y-m-d', $start_date);
        $endDate = \DateTime::createFromFormat('Y-m-d', $end_date);
        if ($interval == "week") {
            $startDate->modify("this week");
            $endDate->modify("this week +6 days");
        } elseif ($interval == "month") {
            $startDate->modify('first day of this month');
            $endDate->modify("last day of this month");
        } elseif ($interval == "quarter") {
            $this->makeFirstDayOfQuarter($startDate);
            $this->makeLastDayOfQuarter($endDate);
        }
        $periods = array();
        $counter = 0;
        do {
            $counter++;
            $periodEndDate = $endDate->format("Y-m-d");
            if ($interval == "week") {
                $endDate->modify("this week");
            } elseif ($interval == "month") {
                $endDate->modify("first day of this month");
            } elseif ($interval == "quarter") {
                $this->makeFirstDayOfQuarter($endDate);
            }
            $periodStartDate = $endDate->format("Y-m-d");
            if ($interval == "week") {
                $periodName =  $periodStartDate . " - " . $periodEndDate;
            } elseif ($interval == "month") {
                $periodName = $endDate->format("Y") . "-" . $endDate->format("n");
            } elseif ($interval == "quarter") {
                $periodName = $endDate->format("Y") . "Q" . ceil($endDate->format("n")/3);
            }
            $endDate->modify("-1 days");
            array_unshift($periods, array(
                "start_date" => $periodStartDate,
                "end_date" => $periodEndDate,
                "name" => $periodName,
            ));
            if ($all == false && $counter > 10) {
                break;
            }
        } while ($endDate > $startDate);
        return $periods;
    }


    /**
     * @param array $periods
     * @return array
     */
    public function getPeriodsMetadata(array $periods)
    {
        $metadata = array();
        $firstYear = null;
        $lastYear = null;
        foreach ($periods as $index=>$period) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $period["start_date"]);
            $month = $startDate->format("m");
            $year = $startDate->format("Y");

            if ($firstYear == null) {
                $firstYear = $year;
            }
            $lastYear = $year;

            $metadata[$month.$year] = array(
                "period" => $period["start_date"] . " - " . $period["end_date"],
                "index" => $index,
                "month" => $month,
                "year" => $year
            );
        }
        return array(
            "firstYear" => $firstYear,
            "lastYear" => $lastYear,
            "periods" => $metadata,
        );
    }

    /**
     * @param \DateTime $date
     */
    public function makeLastDayOfQuarter(\DateTime $date)
    {
        $month = $date->format('n') ;

        if ($month < 4) {
            $date->modify('last day of march ' . $date->format('Y'));
        } elseif ($month > 3 && $month < 7) {
            $date->modify('first day of june ' . $date->format('Y'));
        } elseif ($month > 6 && $month < 10) {
            $date->modify('first day of september ' . $date->format('Y'));
        } elseif ($month > 9) {
            $date->modify('first day of december ' . $date->format('Y'));
        }
    }

    /**
     * @param \DateTime $date
     */
    public function makeFirstDayOfQuarter(\DateTime $date)
    {
        $month = $date->format('n') ;

        if ($month < 4) {
            $date->modify('first day of january ' . $date->format('Y'));
        } elseif ($month > 3 && $month < 7) {
            $date->modify('first day of april ' . $date->format('Y'));
        } elseif ($month > 6 && $month < 10) {
            $date->modify('first day of july ' . $date->format('Y'));
        } elseif ($month > 9) {
            $date->modify('first day of october ' . $date->format('Y'));
        }
    }
}
