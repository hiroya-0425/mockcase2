@extends('layouts.admin')

@section('title', '申請一覧')
<link rel="stylesheet" href="{{ asset('css/admin/requests/index.css') }}" />
@section('content')
<div class="requests-admin__container">
    <h2 class="requests-admin__title">申請一覧</h2>

    @php
    // タブ判定（デフォは pending）
    $activeTab = $activeTab ?? (request('tab') === 'approved' ? 'approved' : 'pending');
    $isPending = $activeTab === 'pending';
    $list = $isPending ? $pendingRequests : $approvedRequests;
    $statusLabel = $isPending ? '承認待ち' : '承認済み';
    @endphp

    {{-- タブ --}}
    <div class="requests-admin__tabs">
        <a href="{{ route('admin.requests.index', ['tab' => 'pending']) }}"
            class="requests-admin__tab {{ $isPending ? 'requests-admin__tab-is-active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.requests.index', ['tab' => 'approved']) }}"
            class="requests-admin__tab {{ $isPending ? '' : 'requests-admin__tab-is-active' }}">
            承認済み
        </a>
    </div>
    {{-- テーブル（タブに応じて片方だけ表示） --}}
    <div class="requests-admin__panel">
        <table class="requests-admin__table">
            <thead class="requests-admin__thead">
                <tr class="requests-admin__row">
                    <th class="requests-admin__th requests-admin__th-status">状態</th>
                    <th class="requests-admin__th">名前</th>
                    <th class="requests-admin__th">対象日時</th>
                    <th class="requests-admin__th">申請理由</th>
                    <th class="requests-admin__th">申請日時</th>
                    <th class="requests-admin__th requests-admin__th-action">詳細</th>
                </tr>
            </thead>
            <tbody class="requests-admin__tbody">
                @forelse($list as $req)
                <tr class="requests-admin__row">
                    <td class="requests-admin__td">
                        <span class="requests-admin__chip {{ $isPending ? 'requests-admin__chip-pending' : 'requests-admin__chip-approved' }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td class="requests-admin__td">
                        {{ optional($req->user)->name ?? '-' }}
                    </td>
                    <td class="requests-admin__td">
                        {{
                            $req->attendance?->work_date
                            ? \Carbon\Carbon::parse($req->attendance->work_date)->locale('ja')->isoFormat('YYYY/MM/DD')
                            : '-'
                        }}
                    </td>
                    <td class="requests-admin__td">
                        {{ $req->reason ?: ($req->remarks ?? '-') }}
                    </td>
                    <td class="requests-admin__td">
                        {{ \Carbon\Carbon::parse($req->latest_at)->locale('ja')->isoFormat('YYYY/MM/DD') }}
                    </td>
                    <td class="requests-admin__td requests-admin__td-action">
                        <a href="{{ route('admin.requests.show', $req->submission_id) }}"
                        class="requests-admin__detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr class="requests-admin__row">
                    <td class="requests-admin__td" colspan="6"></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection