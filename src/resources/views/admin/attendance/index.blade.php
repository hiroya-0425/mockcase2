@extends('layouts.admin')

@section('title', '勤怠一覧（管理者）')
<link rel="stylesheet" href="{{ asset('css/admin/attendance/index.css') }}" />
@section('content')
<div class="adminatt__container">
    <h2 class="adminatt__title">
        {{ $date->locale('ja')->isoFormat('YYYY年M月D日') }}の勤怠
    </h2>

    {{-- ナビ（前日 / 当日 / 翌日） --}}
    <div class="adminatt__nav">
        <a class="adminatt__nav-btn" href="{{ route('admin.attendance.index', ['date' => $prevDate]) }}">← 前日</a>
        <div class="adminatt__nav-date">
            <span class="adminatt__nav-icon">🗓️</span>
            {{ $date->format('Y/m/d') }}
        </div>
        <a class="adminatt__nav-btn" href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}">翌日 →</a>
    </div>
    <table class="adminatt__table">
        <thead class="adminatt__thead">
            <tr class="adminatt__row">
                <th class="adminatt__th adminatt__th-name">名前</th>
                <th class="adminatt__th">出勤</th>
                <th class="adminatt__th">退勤</th>
                <th class="adminatt__th">休憩</th>
                <th class="adminatt__th">合計</th>
                <th class="adminatt__th adminatt__th-action">詳細</th>
            </tr>
        </thead>
        <tbody class="adminatt__tbody">
            @forelse($rows as $r)
            <tr class="adminatt__row">
                <td class="adminatt__td adminatt__td-name">{{ $r['user']->name }}</td>
                <td class="adminatt__td">{{ $r['start'] }}</td>
                <td class="adminatt__td">{{ $r['end'] }}</td>
                <td class="adminatt__td">{{ $r['break_total'] }}</td>
                <td class="adminatt__td">{{ $r['work_total'] }}</td>
                <td class="adminatt__td adminatt__td-action">
                    @if ($r['attendance']?->id)
                    {{-- 管理側の詳細が未実装なら、ひとまずユーザー側の詳細へ --}}
                    <a class="adminatt__detail-link" href="{{ route('admin.attendance.show', $r['attendance']->id) }}">詳細</a>
                    @else
                    <span class="adminatt__detail-link adminatt__detail-link-disabled">-</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr class="adminatt__row">
                <td class="adminatt__td" colspan="6">ユーザーが存在しません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection