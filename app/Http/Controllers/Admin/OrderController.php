<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallSession;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function index()
    {
        $calls = collect();
        if (Schema::hasTable('call_sessions')) {
            $calls = CallSession::with(['consumer', 'provider'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => 'CALL-' . $session->id,
                        'user' => $session->consumer->name ?? 'N/A',
                        'user_mail' => $session->consumer->email ?? 'N/A',
                        'astro' => $session->provider->name ?? 'N/A',
                        'type' => 'Call',
                        'duration' => $session->duration_seconds > 0 ? sprintf('%dm %02ds', floor($session->duration_seconds / 60), $session->duration_seconds % 60) : 'Pending',
                        'amount' => '₹' . number_format($session->total_cost, 2),
                        'amount_value' => $session->total_cost,
                        'status' => $this->mapStatus($session->status),
                        'raw_status' => $session->status,
                        'date' => optional($session->created_at)->format('M d, Y'),
                        'created_at' => $session->created_at,
                    ];
                });
        }

        $chats = collect();
        if (Schema::hasTable('chat_sessions')) {
            $chats = ChatSession::with(['consumer', 'provider'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => 'CHAT-' . $session->id,
                        'user' => $session->consumer->name ?? 'N/A',
                        'user_mail' => $session->consumer->email ?? 'N/A',
                        'astro' => $session->provider->name ?? 'N/A',
                        'type' => 'Chat',
                        'duration' => $session->duration_seconds > 0 ? sprintf('%dm %02ds', floor($session->duration_seconds / 60), $session->duration_seconds % 60) : 'Pending',
                        'amount' => '₹' . number_format($session->total_cost, 2),
                        'amount_value' => $session->total_cost,
                        'status' => $this->mapStatus($session->status),
                        'raw_status' => $session->status,
                        'date' => optional($session->created_at)->format('M d, Y'),
                        'created_at' => $session->created_at,
                    ];
                });
        }

        $orders = $calls->merge($chats)
            ->sortByDesc('created_at')
            ->values();

        $totalVolume = $orders->count();
        $today = Carbon::today();
        $todayHigh = $orders->filter(function ($order) use ($today) {
            return $order['created_at'] && $order['created_at']->gte($today);
        })->max('amount_value') ?: 0;

        $completedCount = $orders->where('status', 'Completed')->count();
        $completionPercent = $totalVolume ? round(($completedCount / $totalVolume) * 100, 1) : 100;
        $refundReq = $orders->where('status', 'Cancelled')->count();

        return view('admin.orders.index', [
            'orders' => $orders,
            'totalVolume' => $totalVolume,
            'todayHigh' => $todayHigh,
            'completionPercent' => $completionPercent,
            'refundReq' => $refundReq,
        ]);
    }

    public function create()
    {
        $customers = User::where('user_type', 'user')->orderBy('name')->get();
        $astrologers = User::where('user_type', 'astrologer')->orderBy('name')->get();

        return view('admin.orders.create', [
            'customers' => $customers,
            'astrologers' => $astrologers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:call,chat',
            'consumer_id' => 'required|exists:users,id',
            'provider_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'duration_seconds' => 'required|integer|min:0',
            'status' => 'required|in:initiated,ringing,accepted,ongoing,completed,missed,rejected,failed',
            'started_at' => 'nullable|date',
        ]);

        $validated['total_cost'] = $validated['amount'];
        $validated['rate_per_minute'] = $validated['duration_seconds'] > 0
            ? round($validated['amount'] / max(1, ceil($validated['duration_seconds'] / 60)), 2)
            : 0;

        if ($validated['type'] === 'call') {
            $session = CallSession::create([
                'consumer_id' => $validated['consumer_id'],
                'provider_id' => $validated['provider_id'],
                'status' => $validated['status'],
                'started_at' => $validated['started_at'] ?? now(),
                'ended_at' => $validated['status'] === 'completed' ? now() : null,
                'duration_seconds' => $validated['duration_seconds'],
                'rate_per_minute' => $validated['rate_per_minute'],
                'total_cost' => $validated['total_cost'],
            ]);
        } else {
            $session = ChatSession::create([
                'consumer_id' => $validated['consumer_id'],
                'provider_id' => $validated['provider_id'],
                'status' => $validated['status'],
                'started_at' => $validated['started_at'] ?? now(),
                'ended_at' => $validated['status'] === 'completed' ? now() : null,
                'duration_seconds' => $validated['duration_seconds'],
                'rate_per_minute' => $validated['rate_per_minute'],
                'total_cost' => $validated['total_cost'],
            ]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'Order created successfully.');
    }

    public function show($type, $id)
    {
        $session = $this->findOrder($type, $id);

        if (!$session) {
            abort(404);
        }

        return view('admin.orders.show', [
            'order' => $this->buildOrderData($session, $type),
            'type' => $type,
        ]);
    }

    public function destroy($type, $id)
    {
        $session = $this->findOrder($type, $id);

        if (!$session) {
            return redirect()->route('admin.orders.index')->with('error', 'Order not found.');
        }

        $session->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }

    public function byAstrologer(Request $request)
    {
        $callSessions = Schema::hasTable('call_sessions')
            ? CallSession::select('provider_id', 'status', 'total_cost', 'created_at')->get()
            : collect();

        $chatSessions = Schema::hasTable('chat_sessions')
            ? ChatSession::select('provider_id', 'status', 'total_cost', 'created_at')->get()
            : collect();

        $allSessions = $callSessions->merge($chatSessions);
        $providers = User::where('user_type', 'astrologer')->orderBy('name')->get();

        $astrologers = $providers->map(function ($provider) use ($allSessions) {
            $sessions = $allSessions->where('provider_id', $provider->id);
            $totalOps = $sessions->count();
            $monthlyHigh = $sessions->filter(function ($session) {
                return $session->created_at && $session->created_at->isCurrentMonth();
            })->count();
            $weeklyRun = $sessions->filter(function ($session) {
                return $session->created_at && $session->created_at->isCurrentWeek();
            })->count();
            $realTime = $sessions->where('status', 'ongoing')->count();
            $revenue = $sessions->sum('total_cost');

            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'total' => $totalOps,
                'month' => $monthlyHigh,
                'week' => $weeklyRun,
                'today' => $realTime,
                'rev' => $revenue,
                'rev_display' => '₹' . number_format($revenue, 0),
            ];
        });

        $topRevenue = $astrologers->sortByDesc('rev')->first() ?? ['name' => 'N/A', 'rev_display' => '₹0'];
        $highestVolume = $astrologers->sortByDesc('total')->first() ?? ['name' => 'N/A', 'total' => 0];
        $avgOrderValue = $allSessions->count() ? $allSessions->sum('total_cost') / $allSessions->count() : 0;

        return view('admin.orders.by-astrologer', [
            'astrologers' => $astrologers,
            'topRevenue' => $topRevenue,
            'highestVolume' => $highestVolume,
            'avgOrderValue' => $avgOrderValue,
        ]);
    }

    public function providerOrders($providerId)
    {
        $provider = User::where('user_type', 'astrologer')->findOrFail($providerId);

        $calls = collect();
        if (Schema::hasTable('call_sessions')) {
            $calls = CallSession::with(['consumer', 'provider'])
                ->where('provider_id', $provider->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => 'CALL-' . $session->id,
                        'type' => 'Call',
                        'user' => $session->consumer->name ?? 'N/A',
                        'user_mail' => $session->consumer->email ?? 'N/A',
                        'duration' => $session->duration_seconds > 0 ? sprintf('%dm %02ds', floor($session->duration_seconds / 60), $session->duration_seconds % 60) : 'Pending',
                        'amount' => '₹' . number_format($session->total_cost, 2),
                        'amount_value' => $session->total_cost,
                        'status' => $this->mapStatus($session->status),
                        'raw_status' => $session->status,
                        'created_at' => optional($session->created_at)->format('M d, Y'),
                        'created_at_raw' => $session->created_at,
                    ];
                });
        }

        $chats = collect();
        if (Schema::hasTable('chat_sessions')) {
            $chats = ChatSession::with(['consumer', 'provider'])
                ->where('provider_id', $provider->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => 'CHAT-' . $session->id,
                        'type' => 'Chat',
                        'user' => $session->consumer->name ?? 'N/A',
                        'user_mail' => $session->consumer->email ?? 'N/A',
                        'duration' => $session->duration_seconds > 0 ? sprintf('%dm %02ds', floor($session->duration_seconds / 60), $session->duration_seconds % 60) : 'Pending',
                        'amount' => '₹' . number_format($session->total_cost, 2),
                        'amount_value' => $session->total_cost,
                        'status' => $this->mapStatus($session->status),
                        'raw_status' => $session->status,
                        'created_at' => optional($session->created_at)->format('M d, Y'),
                        'created_at_raw' => $session->created_at,
                    ];
                });
        }

        $orders = $calls->merge($chats)
            ->sortByDesc('created_at_raw')
            ->values();

        $totalRevenue = $orders->sum('amount_value');

        return view('admin.orders.by-astrologer-provider', [
            'provider' => $provider,
            'orders' => $orders,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    protected function findOrder(string $type, int $id)
    {
        if ($type === 'call') {
            return Schema::hasTable('call_sessions') ? CallSession::find($id) : null;
        }

        return Schema::hasTable('chat_sessions') ? ChatSession::find($id) : null;
    }

    protected function buildOrderData($session, string $type): array
    {
        return [
            'id' => strtoupper($type) . '-' . $session->id,
            'user' => $session->consumer->name ?? 'N/A',
            'user_mail' => $session->consumer->email ?? 'N/A',
            'astro' => $session->provider->name ?? 'N/A',
            'type' => ucfirst($type),
            'duration' => $session->duration_seconds > 0 ? sprintf('%dm %02ds', floor($session->duration_seconds / 60), $session->duration_seconds % 60) : 'Pending',
            'amount' => '₹' . number_format($session->total_cost, 2),
            'status' => $this->mapStatus($session->status),
            'raw_status' => $session->status,
            'date' => optional($session->created_at)->format('M d, Y'),
            'started_at' => optional($session->started_at)->format('M d, Y H:i'),
            'ended_at' => optional($session->ended_at)->format('M d, Y H:i'),
        ];
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'Completed',
            'rejected' => 'Cancelled',
            'cancelled' => 'Cancelled',
            'failed' => 'Cancelled',
            default => 'Processing',
        };
    }
}
