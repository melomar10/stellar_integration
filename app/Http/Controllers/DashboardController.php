<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard
     */
    public function index()
    {
        $totalClients = Client::count();
        
        return view('admin.dashboard', compact('totalClients'));
    }
}

