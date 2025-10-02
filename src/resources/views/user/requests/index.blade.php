@extends('layouts.user')

@section('title', '申請一覧')
<link rel="stylesheet" href="{{ asset('css/user/requests/index.css') }}" />
@section('content')
<div class="requests__container">
    <div class="requests__inner">
        <h2 class="requests__title">申請一覧</h2>
        {{-- タブ --}}
        @php
        $activeTab = $activeTab ?? (request('tab') === 'approved' ? 'approved' : 'pending');
        @endphp
        <div class="requests__tabs">
            <a href="{{ route('requests.index', ['tab' => 'pending']) }}"
                class="requests__tab {{ $activeTab === 'pending' ? 'requests__tab-is-active' : '' }}">
                承認待ち
            </a>
            <a href="{{ route('requests.index', ['tab' => 'approved']) }}"
                class="requests__tab {{ $activeTab === 'approved' ? 'requests__tab-is-active' : '' }}">
                承認済み
            </a>
        </div>
        {{-- 共通テーブル --}}
        <div class="requests__panel">
            <table class="requests__table">
                <thead class="requests__thead">
                    <tr class="requests__row">
                        <th class="requests__th requests__th-status">状態</th>
                        <th class="requests__th">名前</th>
                        <th class="requests__th">対象日時</th>
                        <th class="requests__th">申請理由</th>
                        <th class="requests__th">申請日時</th>
                        <th class="requests__th requests__th-action">詳細</th>
                    </tr>
                </thead>
                <tbody class="requests__tbody">
                    {{-- 承認待ちタブ --}}
                    @if ($activeTab === 'pending')
                    @foreach($pendingRequests as $req)
                    <tr class="requests__row">
                        <td class="requests__td">
                            <span class="requests__status-chip requests__status-chip-pending">承認待ち</span>
                        </td>
                        <td class="requests__td">{{ Auth::user()->name }}</td>
                        <td class="requests__td">
                            {{
                                $req->attendance?->work_date
                                ? \Carbon\Carbon::parse($req->attendance->work_date)->format('Y/m/d')
                                : '-'
                            }}
                        </td>
                        <td class="requests__td">{{ $req->reason ?: ($req->remarks ?? '-') }}</td>
                        <td class="requests__td">
                            {{ \Carbon\Carbon::parse($req->latest_at)->locale('ja')->isoFormat('YYYY/MM/DD') }}
                        </td>
                        <td class="requests__td requests__td-action">
                            <a href="{{ route('attendance.show', optional($req->attendance)->id) }}" class="requests__detail-link">詳細</a>
                        </td>
                    </tr>
                    @endforeach
                    @endif

                    {{-- 承認済みタブ --}}
                    @if ($activeTab === 'approved')
                    @foreach($approvedRequests as $req)
                    <tr class="requests__row">
                        <td class="requests__td">
                            <span class="requests__status-chip requests__status-chip-approved">承認済み</span>
                        </td>
                        <td class="requests__td">{{ Auth::user()->name }}</td>
                        <td class="requests__td">
                            {{
                                optional($req->attendance)->work_date
                                ? \Carbon\Carbon::parse($req->attendance->work_date)->format('Y/m/d')
                                : '-'
                            }}
                        </td>
                        <td class="requests__td">{{ $req->remarks ?: ($req->reason ?? '-') }}</td>
                        <td class="requests__td">
                            {{ \Carbon\Carbon::parse($req->latest_at)->locale('ja')->isoFormat('YYYY/MM/DD') }}
                        </td>
                        <td class="requests__td requests__td-action">
                            <a href="{{ route('attendance.show', optional($req->attendance)->id) }}" class="requests__detail-link">詳細</a>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection