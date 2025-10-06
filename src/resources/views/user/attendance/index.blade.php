@extends('layouts.user')

@section('title', '勤怠一覧')
<link rel="stylesheet" href="{{ asset('css/user/attendance/index.css') }}" />
@section('content')
<div class="attendance__list">
    <div class="attendance__list-container">
        <h2 class="attendance__list-title">勤怠一覧</h2>
        <div class="attendance__list-headrow">
            <form method="GET" action="{{ route('attendance.index') }}" class="attendance__list-monthform">
                <a class="attendance__list-monthbtn" href="{{ route('attendance.index', ['month' => $prevMonth]) }}">← 前月</a>

                {{-- 📅と年月をまとめて表示 --}}
                <label class="attendance__month-label">
                    <span class="attendance__calendar-icon">📅</span>
                    <span class="attendance__month-text">
                        {{ \Carbon\Carbon::parse($month)->format('Y年n月') }}
                    </span>
                    {{-- 非表示だがクリック可能 --}}
                    <input type="month" name="month" value="{{ $month }}" class="attendance__list-month" onchange="this.form.submit()">
                </label>

                <a class="attendance__list-monthbtn" href="{{ route('attendance.index', ['month' => $nextMonth]) }}">翌月 →</a>
            </form>
        </div>
        <table class="attendance__table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($days as $row)
                @php
                $d = $row['date'];
                $a = $row['attendance']; // Attendance|null

                $start = $a?->start_time ? \Carbon\Carbon::parse($a->start_time)->format('H:i') : '';
                $end = $a?->end_time ? \Carbon\Carbon::parse($a->end_time)->format('H:i') : '';

                $breakMinutes = 0;
                if ($a) {
                foreach ($a->breaks as $b) {
                if ($b->break_start && $b->break_end) {
                $breakMinutes += \Carbon\Carbon::parse($b->break_end)->diffInMinutes($b->break_start);
                }
                }
                }

                $workMinutes = 0;
                if ($a?->start_time && $a?->end_time) {
                $workMinutes = \Carbon\Carbon::parse($a->end_time)->diffInMinutes($a->start_time) - $breakMinutes;
                if ($workMinutes < 0) $workMinutes=0;
                    }

                    $breakText=$a ? (floor($breakMinutes/60).':'.str_pad($breakMinutes%60,2,'0',STR_PAD_LEFT)) : '' ;
                    $workText=($a && $a->end_time) ? (floor($workMinutes/60).':'.str_pad($workMinutes%60,2,'0',STR_PAD_LEFT)) : '';
                    @endphp

                    <tr class="attendance__row">
                        @php
                        $weekMap = ['Sun'=>'日','Mon'=>'月','Tue'=>'火','Wed'=>'水','Thu'=>'木','Fri'=>'金','Sat'=>'土'];
                        @endphp
                        <td class="attendance__cell">{{ $d->format('m/d') }}({{ $weekMap[$d->format('D')] }})</td>
                        <td class="attendance__cell">{{ $start }}</td>
                        <td class="attendance__cell">{{ $end }}</td>
                        <td class="attendance__cell">{{ $breakText }}</td>
                        <td class="attendance__cell">{{ $workText }}</td>
                        <td class="attendance__cell">
                            @if ($a)
                            <a href="{{ route('attendance.show', $a->id) }}" class="attendance__detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection