<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class NotificationController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function index()
    {
        $userId = Session::get('user_id');

        $notifications = $this->supabase->selectAdvanced('notifications', [
            'columns' => '*',
            'filters' => ['user_id' => 'eq.' . $userId],
            'order' => 'created_at.desc',
            'limit' => 50,
        ]);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id)
    {
        $userId = Session::get('user_id');

        $result = $this->supabase->update('notifications', [
            'id' => $id,
            'user_id' => $userId,
        ], ['is_read' => true]);

        if ($result['success']) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }

    public function count()
    {
        $userId = Session::get('user_id');

        $unread = $this->supabase->count('notifications', [
            'user_id' => $userId,
            'is_read' => 'false',
        ]);

        return response()->json(['count' => $unread]);
    }
}
