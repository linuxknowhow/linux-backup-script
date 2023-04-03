<?php

namespace Backup\Model;

use Backup\Entity;
use DateTime;

class CleanUp {
    private string $date;

    private int $retention_period_years;
    private int $retention_period_months;
    private int $retention_period_weeks;
    private int $retention_period_days;

    public function __construct(string $date, int $retention_period_years, int $retention_period_months, int $retention_period_weeks, int $retention_period_days) {
        $this->date = $date;

        $this->retention_period_years = $retention_period_years;
        $this->retention_period_months = $retention_period_months;
        $this->retention_period_weeks = $retention_period_weeks;
        $this->retention_period_days = $retention_period_days;
    }

    public function do(array $backups): array {
        // Sorting the backups by date in ascending order

        usort($backups, function (Backup $a, Backup $b) {
            if ($a->getYear() !== $b->getYear()) {
                return $a->getYear() > $b->getYear();
            } elseif ($a->getMonth() !== $b->getMonth()) {
                return $a->getMonth() > $b->getMonth();
            } else {
                return $a->getDay() > $b->getDay();
            }
        });

        // Cleaning up backups by year

        if ($this->retention_period_years > 0) {
            $current_year_number = (int)date("Y");

            for ($i = $this->retention_period_years-1; $i >= 0; $i--) {
                $year_number = $current_year_number-$i;

                foreach ($backups as $backup) {
                    if ($backup->getYear() == $year_number) {
                        $backup->markAsPreserved();

                        break;
                    }
                }
            }
        }

        // Cleaning up backups by month

        if ($this->retention_period_months > 0) {
            for ($i = $this->retention_period_months-1; $i >= 0; $i--) {
                $checked_date = new DateTime("-$i months");

                $checked_date_year = $checked_date->format("Y");
                $checked_date_month = $checked_date->format("m");

                foreach ($backups as $backup) {
                    if ($backup->getYear() == $checked_date_year && $backup->getMonth() == $checked_date_month) {
                        $backup->markAsPreserved();

                        break;
                    }
                }
            }
        }

        // Cleaning up backups by week

        if ($this->retention_period_weeks > 0) {
            for ($i = $this->retention_period_weeks-1; $i >= 0; $i--) {
                $checked_date = new DateTime("-$i weeks");

                $checked_date_year = $checked_date->format("Y");
                $checked_date_week = $checked_date->format("W");

                $backup_creation_date = DateTime::createFromFormat('Y-m-d', $this->date);
                $backup_creation_date->setTime(0, 0, 0);

                for ($j = 1; $j <= 7; $j++) {
                    $date = new DateTime();
                    $date->setISODate($checked_date_year, $checked_date_week, $j);
                    $date->setTime(23, 59, 59);

                    if ($date < $backup_creation_date) {
                        $date_year = $date->format("Y");
                        $date_month = $date->format("m");
                        $date_day = $date->format("d");

                        foreach ($backups as $backup) {
                            if ($backup->getYear() == $date_year && $backup->getMonth() == $date_month && $backup->getDay() == $date_day) {
                                $backup->markAsPreserved();

                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // Cleaning up backups by day

        if ($this->retention_period_days > 0) {
            for ($i = $this->retention_period_days-1; $i >= 0; $i--) {
                $checked_date = new DateTime("-$i days");

                $checked_date_year = $checked_date->format("Y");
                $checked_date_month = $checked_date->format("m");
                $checked_date_day = $checked_date->format("d");

                foreach ($backups as $backup) {
                    if ($backup->getYear() == $checked_date_year && $backup->getMonth() == $checked_date_month && $backup->getDay() == $checked_date_day) {
                        $backup->markAsPreserved();

                        break;
                    }
                }
            }
        }

        return $backups;
    }
}
