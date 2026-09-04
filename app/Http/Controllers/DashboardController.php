<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CommunicationThread;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PendingMessageCase;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'metrics' => [
                'customers' => Customer::count(),
                'openDeals' => Deal::where('status', 'open')->count(),
                'pipelineValue' => (float) Deal::where('status', 'open')->sum('expected_amount'),
                'todayAppointments' => Appointment::whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])->count(),
                'openTasks' => CrmTask::whereIn('status', ['open', 'in_progress'])->count(),
                'pendingMessages' => PendingMessageCase::whereIn('status', ['open', 'overdue'])->count(),
            ],
            'upcomingAppointments' => Appointment::with('customer:id,display_name')
                ->where('starts_at', '>=', now())->orderBy('starts_at')->limit(6)->get(),
            'recentLeads' => Lead::latest()->limit(5)->get(['id', 'name', 'status', 'created_at']),
            'taskQueue' => CrmTask::whereIn('status', ['open', 'in_progress'])->orderByRaw('due_at is null, due_at')->limit(6)->get(),
            'channels' => CommunicationThread::query()->select('channel', DB::raw('count(*) as total'))->groupBy('channel')->pluck('total', 'channel'),
        ]);
    }
}
