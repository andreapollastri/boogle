@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<x-page-header title="Dashboard" :description="'Welcome back, ' . auth()->user()->name" />

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-sm text-gray-500">Projects</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_projects'] }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-sm text-gray-500">Open exceptions</p>
        <p class="text-3xl font-bold text-red-600 mt-1">{{ $stats['open_exceptions'] }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <p class="text-sm text-gray-500">Total exceptions</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total_exceptions'] }}</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6">
    {{-- Recent exceptions --}}
    <div class="bg-white rounded-2xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Recent exceptions</h2>
        </div>
        @if($recentExceptions->isEmpty())
            <div class="px-6 py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-gray-500">No exceptions yet 🎉</p>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($recentExceptions as $ex)
                    <li class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <a href="{{ route('panel.projects.exceptions.show', [$ex->project_id, $ex->id]) }}" class="block">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-medium text-gray-900 truncate">{{ $ex->exception ?: 'Unknown exception' }}</p>
                                <x-status-badge :status="$ex->status" />
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $ex->project?->title }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $ex->created_at->diffForHumans() }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
