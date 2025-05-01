<?php

namespace App\Exports;

use App\Models\EmpAttendance;
use Maatwebsite\Excel\Concerns\FromCollection;

class AttendanceRegisterExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return EmpAttendance::all();
    }
}
