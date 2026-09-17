<?php

namespace App\Http\Controllers;

use App\Models\DataPO;
use App\Models\FormApproval;
use App\Models\History;
use App\Models\Item;
use App\Models\ItemMaster;
use App\Models\ItemOutstanding;
use App\Models\KedatanganBarang;
use App\Models\UnregistrasiApproval;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display unified executive dashboard combining MARS and SATURNUS metrics.
     */
    public function index()
    {
        // 1. MARS Stock Metrics
        $totalItemMaster = ItemMaster::count();
        $totalDataPO = DataPO::count();
        $totalPoQty = DataPO::sum('scheduled_receipt_qty');
        
        // Item Minim: Ending balance <= Order point OR Ending balance <= Minimal stock
        $totalItemMinim = ItemMaster::where(function ($q) {
            $q->whereColumn('ending_balance', '<=', 'minimal_stock')
              ->orWhereColumn('ending_balance', '<=', 'order_point');
        })->count();

        $totalItemOutstanding = ItemOutstanding::count();
        $todayKedatanganCount = KedatanganBarang::whereDate('arrival_date', today())->count();
        $totalHistories = History::count();

        // 2. SATURNUS Registry & Approval Metrics
        $totalConsumables = Item::count();
        $registeredConsumables = Item::where('status', 'registered')->count();
        $unregisteredConsumables = Item::where('status', 'unregistered')->count();

        $formRegistrasiTotal = FormApproval::count();
        $formRegistrasiPendingStaff = FormApproval::where('status', 'like', '%STAFF%')->count();
        $formRegistrasiPendingAccounting = FormApproval::where('status', 'like', '%ACCOUNTING%')->count();
        $formRegistrasiPendingWarehouse = FormApproval::where('status', 'like', '%WAREHOUSE%')->count();
        $formRegistrasiCompleted = FormApproval::where('status', 'like', '%SELESAI%')->orWhere('status', 'like', '%REGISTERED%')->count();

        $formUnregTotal = UnregistrasiApproval::count();
        $formUnregPending = UnregistrasiApproval::where('status', 'not like', '%SELESAI%')
            ->where('status', 'not like', '%DISCONTINUE%')
            ->count();

        // 3. User stats
        $totalUsers = User::count();

        // 4. Recent Lists for Live Feeds
        $recentLowStock = ItemMaster::where(function ($q) {
            $q->whereColumn('ending_balance', '<=', 'minimal_stock')
              ->orWhereColumn('ending_balance', '<=', 'order_point');
        })->orderBy('ending_balance', 'asc')->limit(6)->get();

        $recentKedatangan = KedatanganBarang::orderBy('arrival_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $recentRegistrasiForms = FormApproval::orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $recentUnregistrasiForms = UnregistrasiApproval::orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        return view('dashboard.index', compact(
            'totalItemMaster',
            'totalDataPO',
            'totalPoQty',
            'totalItemMinim',
            'totalItemOutstanding',
            'todayKedatanganCount',
            'totalHistories',
            'totalConsumables',
            'registeredConsumables',
            'unregisteredConsumables',
            'formRegistrasiTotal',
            'formRegistrasiPendingStaff',
            'formRegistrasiPendingAccounting',
            'formRegistrasiPendingWarehouse',
            'formRegistrasiCompleted',
            'formUnregTotal',
            'formUnregPending',
            'totalUsers',
            'recentLowStock',
            'recentKedatangan',
            'recentRegistrasiForms',
            'recentUnregistrasiForms'
        ));
    }
}
