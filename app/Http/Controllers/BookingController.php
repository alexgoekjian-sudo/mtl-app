<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $query = Booking::with(['lead', 'student', 'assignedTeacher']);
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('booking_type')) {
            $query->where('booking_type', $request->input('booking_type'));
        }
        
        $bookings = $query->orderBy('scheduled_at', 'desc')->paginate($perPage);
        return response()->json($bookings);
    }

    public function show($id)
    {
        $booking = Booking::with(['lead', 'student', 'assignedTeacher'])->findOrFail($id);
        return response()->json($booking);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'lead_id' => 'nullable|exists:leads,id',
            'student_id' => 'nullable|exists:students,id',
            'booking_provider' => 'sometimes|string|max:255',
            'external_booking_id' => 'nullable|string|max:255|unique:bookings',
            'booking_type' => 'sometimes|string|max:255',
            'scheduled_at' => 'nullable|date',
            'assigned_teacher_id' => 'nullable|exists:users,id',
            'assigned_level' => 'nullable|string|max:255',
            'teacher_notes' => 'nullable|string',
            'status' => 'sometimes|in:scheduled,completed,cancelled,no_show',
        ]);

        $booking = Booking::create($request->all());
        return response()->json($booking->load(['lead', 'student', 'assignedTeacher']), 201);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $this->validate($request, [
            'scheduled_at' => 'nullable|date',
            'assigned_teacher_id' => 'nullable|exists:users,id',
            'assigned_level' => 'nullable|string|max:255',
            'teacher_notes' => 'nullable|string',
            'status' => 'sometimes|in:scheduled,completed,cancelled,no_show',
        ]);

        $booking->update($request->all());
        return response()->json($booking->load(['lead', 'student', 'assignedTeacher']));
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();
        return response()->json(['status' => 'deleted']);
    }
}
