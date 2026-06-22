<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ChedReportExport implements FromArray, WithHeadings
{
    public function __construct(private array $data) {}

    public function headings(): array
    {
        return ['Field', 'Value'];
    }

    public function array(): array
    {
        $rows = [
            ['Club', $this->data['club_name']],
            ['Adviser', $this->data['adviser_name']],
            ['Activity Title', $this->data['activity_title']],
            ['Activity Type', $this->data['activity_type']],
            ['Date', $this->data['date']],
            ['Time', $this->data['time']],
            ['Venue', $this->data['venue']],
            ['Officer-in-Charge', $this->data['officer_in_charge']],
            ['Objectives', $this->data['objectives']],
            ['Description', $this->data['description']],
            ['Number of Participants', $this->data['participant_count']],
            ['', ''],
            ['Participants', ''],
        ];

        foreach ($this->data['participants'] as $name) {
            $rows[] = ['', $name];
        }

        return $rows;
    }
}
