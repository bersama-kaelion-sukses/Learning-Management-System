<?php

namespace App\Exports;

use App\Models\LmsLoginSession;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LoginSessionExport implements FromCollection, WithHeadings
{
    protected $startMonth;
    protected $endMonth;

    public function __construct($startMonth = null, $endMonth = null)
    {
        $this->startMonth = $startMonth;
        $this->endMonth   = $endMonth;
    }

    public function collection()
    {
        // Tentukan tanggal awal & akhir
        $startDate = $this->startMonth ? $this->startMonth . '-01' : '2000-01-01';
        $endDate   = $this->endMonth
            ? date("Y-m-t", strtotime($this->endMonth . '-01'))
            : now();

        // Ambil data login session
        return LmsLoginSession::whereBetween('login_time', [$startDate, $endDate])
            ->orderBy('login_time', 'desc')
            ->get([
                'login_id',
                'emp_id',
                'user_id',
                'role_id',
                'sub_role',
                'session',
                'login_time',
                'logout_time',
                'device_info',
                'ip_address',
                'is_success',
            ]);
    }

    public function headings(): array
    {
        return [
            'Login ID',
            'Employee ID',
            'User ID',
            'Role ID',
            'Sub Role',
            'Session',
            'Login Time',
            'Logout Time',
            'Device Info',
            'IP Address',
            'Is Success',
        ];
    }
}
