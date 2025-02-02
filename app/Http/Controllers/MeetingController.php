<?php
namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    // Fungsi untuk mendapatkan semua data meeting
    public function index()
    {
        $meetings = Meeting::all();
        return response()->json($meetings);
    }

    // Fungsi untuk membuat meeting baru
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_time' => 'required|date',
            'room_id' => 'required|exists:rooms,id', // Validasi room_id
        ]);
    
        $meeting = Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'meeting_time' => $request->meeting_time,
            'room_id' => $request->room_id, // Simpan room_id
        ]);
    
        return response()->json($meeting, 201);
    }
}