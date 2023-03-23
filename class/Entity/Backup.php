<?php

namespace Backup\Entity;

class Backup {
    private string $filename;
    private string $fullpath;

    private int $year;
    private int $month;
    private int $day;

    private bool $preserved;

    public function __construct(string $filename, string $fullpath, int $year, int $month, int $day) {
        $this->filename = $filename;
        $this->fullpath = $fullpath;

        $this->year = $year;
        $this->month = $month;
        $this->day = $day;

        $this->preserved = false;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getFullpath() {
        return $this->fullpath;
    }

    public function getYear() {
        return $this->year;
    }

    public function getMonth() {
        return $this->month;
    }

    public function getDay() {
        return $this->day;
    }

    public function markAsPreserved() {
        $this->preserved = true;
    }

    public function isPreserved() {
        return $this->preserved;
    }

}
