@extends('layouts.admin')

@section('title', '修正申請承認')
<link rel="stylesheet" href="{{ asset('css/admin/requests/approve.css') }}" />
@section('content')
<div class="req-approve__container">
    <h2 class="req-approve__title">勤怠詳細</h2>

    @if (session('status'))
    <p class="req-approve__flash">{{ session('status') }}</p>
    @endif

    @php
    $att = $group->attendance;
    $user = $group->user;
    $isApproved = $group->status === 'approved';

    $fmt = function($dt) {
    return $dt ? \Carbon\Carbon::parse($dt)->format('H:i') : '-';
    };
    @endphp

    <div class="req-approve__card">
        <table class="req-approve__table">
            <tr>
                <th class="req-approve__th">名前</th>
                <td class="req-approve__td">{{ $user?->name ?? '-' }}</td>
            </tr>
            <tr>
                <th class="req-approve__th">日付</th>
                <td class="req-approve__td">
                    <span class="date-year">
                        {{ \Carbon\Carbon::parse($att->work_date)->format('Y年') }}
                    </span>
                    {{ \Carbon\Carbon::parse($att->work_date)->format('n月j日') }}
                </td>
            </tr>
            {{-- 出退勤修正がある場合 --}}
            @php
            $workReq = $requests->firstWhere('break_time_id', null);
            $start = $workReq?->requested_start_time ?? $att?->start_time;
            $end = $workReq?->requested_end_time ?? $att?->end_time;
            @endphp
            <tr>
                <th class="req-approve__th">出勤・退勤</th>
                <td class="req-approve__td">
                    {{ $fmt($start) }}<span class="time-separator">〜</span>{{ $fmt($end) }}
                </td>
            </tr>
            {{-- 休憩修正（複数あってもループで表示） --}}
            @foreach($requests->whereNotNull('break_time_id') as $br)
            @php
            $orig = optional($att?->breaks)->firstWhere('id', $br->break_time_id);
            $bs = $br->requested_start_time ?? $orig?->break_start;
            $be = $br->requested_end_time ?? $orig?->break_end;
            @endphp
            <tr>
                <th class="req-approve__th">休憩</th>
                <td class="req-approve__td">
                    {{ $fmt($bs) }}<span class="time-separator">〜</span>{{ $fmt($be) }}
                </td>
            </tr>
            @endforeach

            <tr>
                <th class="req-approve__th">備考</th>
                <td class="req-approve__td">{{ $requests->first()->remarks ?? '-' }}</td>
            </tr>
        </table>
    </div>
    <div class="req-approve__actions">
        <form method="POST" action="{{ route('admin.requests.approve', $group->submission_id) }}">
            @csrf
            <button type="submit"
                class="req-approve__btn"
                {{ $isApproved ? 'disabled' : '' }}>
                {{ $isApproved ? '承認済み' : '承認' }}
            </button>
        </form>
    </div>
</div>
@endsection