<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .logo-container {

            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            /*max-height: 60px;*/
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 8px;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .note {
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <!-- Header with Logo -->
    <div class="h-16 w-16 object-contain justify-center logo-container">
        @if($firmSquareLogo)
            <img src="{{ $firmSquareLogo }}" alt="Company Square Logo" />
        @elseif ($firmWideLogo)
            <img src="{{ $firmWideLogo }}" alt="Company Wide Logo" />
        @endif
    </div>
    <div class="title">
        @if($rawComponents && $rawComponents->count() > 0)
            PAYSLIP FOR THE MONTH OF {{ strtoupper(date('F Y', strtotime($rawComponents->first()->salary_period_from))) }}
        @else
            PAYSLIP
        @endif
    </div>

    @if($selectedEmployee)
        <!-- Employee Details Section -->
        <table>
            <tr>
                <td class="font-bold" style="width: 25%">EMPLOYEE CODE</td>
                <td>: {{ $selectedEmployee->emp_job_profile->employee_code}}</td>
                <td class="font-bold" style="width: 25%">DATE OF JOINING</td>
                <td>: {{ optional($selectedEmployee->emp_job_profile)->doh?->format('jS M Y') }}</td>
            </tr>
            <tr>
                <td class="font-bold">NAME</td>
                <td>: {{ $selectedEmployee->fname }} {{ $selectedEmployee->lname }}</td>
                <td class="font-bold">MONTH</td>
                <td>:
                    {{ $rawComponents && $rawComponents->count() > 0 ? date('M-y', strtotime($rawComponents->first()->salary_period_from)) : '' }}
                </td>
            </tr>
            <tr>
                <td class="font-bold">DEPARTMENT</td>
                <td>: {{ optional($selectedEmployee->emp_job_profile)->department?->title }}</td>
                <td class="font-bold">BANK ACCOUNT NO.</td>
                <td>: {{ $selectedEmployee->bank_account->bankaccount ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="font-bold">DESIGNATION</td>
                <td>: {{ optional($selectedEmployee->emp_job_profile)->designation?->title }}</td>
                <td class="font-bold">PAY LEVEL</td>
                <td>: {{ optional($selectedEmployee->emp_job_profile)->paylevel ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="font-bold">PAN NUMBER</td>
                <td>: {{ $selectedEmployee->emp_personal_detail->panno ?? 'N/A' }}</td>
                <td class="font-bold">PRAN NUMBER</td>
                <td>: {{ $selectedEmployee->emp_job_profile->pran_number ?? 'N/A' }}</td>
            </tr>
        </table>

        <!-- Salary Components -->
        <table>
            <tr>
                <th style="width: 40%">EARNINGS</th>
                <th style="width: 10%">AMOUNT (in Rs.)</th>
                <th style="width: 40%">DEDUCTIONS</th>
                <th style="width: 10%">AMOUNT (in Rs.)</th>
            </tr>

            @php 
                            $salaryComponentsCollection = collect($salaryComponents);
                $maxRows = max(
                    $salaryComponentsCollection->where('nature', 'earning')->count(),
                    $salaryComponentsCollection->where('nature', 'deduction')->count()
                );
                $earnings = $salaryComponentsCollection->where('nature', 'earning')->values();
                $deductions = $salaryComponentsCollection->where('nature', 'deduction')->values();
            @endphp

                @for($i = 0; $i < $maxRows; $i++)
                    <tr>
                        <td>{{ isset($earnings[$i]) ? strtoupper($earnings[$i]['title']) : '' }}</td>
                        <td class="text-right">{{ isset($earnings[$i]) ? number_format($earnings[$i]['amount'], 0) : '' }}</td>
                        <td>{{ isset($deductions[$i]) ? strtoupper($deductions[$i]['title']) : '' }}</td>
                        <td class="text-right">{{ isset($deductions[$i]) ? number_format($deductions[$i]['amount'], 0) : '' }}</td>
                    </tr>
                @endfor

            <!-- Totals -->
            <tr>
                <td class="font-bold">GROSS SALARY</td>
                <td class="text-right font-bold">{{ number_format($totalEarnings, 0) }}</td>
                <td class="font-bold">TOTAL DEDUCTIONS</td>
                <td class="text-right font-bold">{{ number_format($totalDeductions, 0) }}</td>
                </tr>
            <!-- Net Salary -->
            <tr>
                <td colspan="2" class="font-bold">NET SALARY</td>
                    <td colspan="2" class="text-right font-bold">{{ number_format($netSalary, 0) }}</td>
                </tr>

           </table>

            <!-- Note -->
            <div class="note">
                Note:-This is a computer generated salary slip, hence does not require signature.
            </div>
    @endif
</body>
</html> 