<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * 管理ダッシュボード
     */
    public function index()
    {
        $latestContacts = Contact::orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard.index', [
            'latestContacts' => $latestContacts,
        ]);
    }
}
