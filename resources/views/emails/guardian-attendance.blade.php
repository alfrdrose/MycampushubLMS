<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Load Poppins -->
    <!--[if !mso]><!-- -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
    </style>
    <!--<![endif]-->
</head>

<body style="margin:0;padding:0;background:#f4f6f8;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:20px;">
<tr>
<td align="center">

<!-- MAIN CONTAINER -->
<table width="600" cellpadding="0" cellspacing="0"
    style="
        background:#ffffff;
        border-radius:8px;
        padding:24px;
        font-family:'Poppins', Arial, Helvetica, sans-serif;
    ">

<!-- LOGO -->
<tr>
<td align="center" style="padding-bottom:20px;">
    <img
        src="{{ asset('assets/images/brand/indigo-logo.png') }}"
        alt="My Campus LMS"
        width="250"
        style="display:block;border:0;outline:none;text-decoration:none;"
    >
</td>
</tr>

<!-- TITLE -->
<tr>
<td align="center" style="padding-bottom:16px;">
    <h3 style="margin:0;color:#333;font-weight:600;">
        Weekly Student Summary
    </h3>
    <p style="margin:6px 0 0;color:#777;font-size:14px;">
        {{ $weekStart }} – {{ $weekEnd }}
    </p>
</td>
</tr>

<!-- GREETING -->
<tr>
<td style="padding-bottom:18px;color:#333;font-size:14px;line-height:1.6;">
    Greetings!<br><br>
    Below is the weekly attendance and classwork summary for
    <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>.
</td>
</tr>

<!-- ===================== -->
<!-- ATTENDANCE TABLE -->
<!-- ===================== -->
<tr>
<td style="padding-bottom:24px;">
    <table width="100%" cellpadding="8" cellspacing="0"
           style="border-collapse:collapse;font-size:13px;text-align:center;">
        <thead>
            <tr style="background:#f0f0f0;">
                <th align="left" style="border-bottom:1px solid #ddd;padding:8px;">
                    Subject
                </th>
                @foreach ($weekDays as $day)
                    <th style="border-bottom:1px solid #ddd;padding:8px;">
                        {{ substr($day, 0, 3) }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($attendance as $subject => $days)
                <tr>
                    <td align="left" style="border-bottom:1px solid #eee;padding:8px;">
                        {{ $subject }}
                    </td>

                    @foreach ($weekDays as $day)
                        @php
                            $status = $days[$day] ?? '—';
                            $color = match(strtolower($status)) {
                                'present' => '#28a745',
                                'absent'  => '#d9534f',
                                'late'    => '#f0ad4e',
                                default   => '#999'
                            };
                        @endphp

                        <td style="border-bottom:1px solid #eee;padding:8px;
                                   color: {{ $color }};
                                   font-weight:600;">
                            {{ $status }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($weekDays) + 1 }}"
                        style="padding:16px;color:#777;text-align:center;">
                        No attendance records available for this week.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</td>
</tr>

<!-- ===================== -->
<!-- CLASSWORK TABLE -->
<!-- ===================== -->
<tr>
<td>
    <h4 style="margin:0 0 10px;color:#333;font-weight:600;">
        Activities & Quizzes Summary
    </h4>

    <table width="100%" cellpadding="8" cellspacing="0"
           style="border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:#f0f0f0;text-align:left;">
                <th style="border-bottom:1px solid #ddd;">Subject</th>
                <th style="border-bottom:1px solid #ddd;">Type</th>
                <th style="border-bottom:1px solid #ddd;">Title</th>
                <th style="border-bottom:1px solid #ddd;">Open</th>
                <th style="border-bottom:1px solid #ddd;">Close</th>
                <th style="border-bottom:1px solid #ddd;text-align:center;">Score</th>
            </tr>
        </thead>

        <tbody>
        @forelse ($weeklyClasswork as $item)
            @php
                $isMissed = $item['score'] === 'Missed';
            @endphp
            <tr>
                <td style="border-bottom:1px solid #eee;">
                    {{ $item['subject'] }}
                </td>
                <td style="border-bottom:1px solid #eee;">
                    {{ $item['type'] }}
                </td>
                <td style="border-bottom:1px solid #eee;">
                    {{ $item['title'] }}
                </td>
                <td style="border-bottom:1px solid #eee;">
                    {{ $item['open_date'] }}
                </td>
                <td style="border-bottom:1px solid #eee;">
                    {{ $item['close_date'] }}
                </td>
                <td style="border-bottom:1px solid #eee;
                           text-align:center;
                           font-weight:600;
                           color: {{ $isMissed ? '#d9534f' : '#28a745' }};">
                    {{ $item['score'] }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6"
                    style="padding:16px;color:#777;text-align:center;">
                    No graded or missed activities/quizzes for this week.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</td>
</tr>

<!-- FOOTER -->
<tr>
<td align="center" style="padding-top:24px;color:#888;font-size:12px;line-height:1.4;">
    © {{ date('Y') }} My Campus LMS<br>
    This is an automated weekly summary. Please do not reply.
</td>
</tr>

</table>
<!-- END MAIN CONTAINER -->

</td>
</tr>
</table>

</body>
</html>
