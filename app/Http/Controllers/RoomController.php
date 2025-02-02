<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // Fungsi untuk mendapatkan semua data room
    public function index()
    {
        $rooms = Room::all();
        return response()->json($rooms);
    }

    // Fungsi untuk membuat room baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $room = Room::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json($room, 201);
    }
}
