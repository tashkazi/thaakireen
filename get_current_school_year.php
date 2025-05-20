<?php
header('Content-Type: application/json');

function getCurrentSchoolYear(): array {
    $month = (int)date('n'); // 1 = Jan, 12 = Dec
    $year = (int)date('Y');

    if ($month >= 9) {
        // September to December → new school year
        return [
            "school_year" => "$year/" . ($year + 1),
            "status" => "active"
        ];
    } elseif ($month >= 1 && $month <= 7) {
        // January to July → same academic year
        return [
            "school_year" => ($year - 1) . "/$year",
            "status" => "active"
        ];
    } else {
        // August is a locked period
        return [
            "school_year" => "locked",
            "status" => "locked"
        ];
    }
}

echo json_encode(getCurrentSchoolYear());
