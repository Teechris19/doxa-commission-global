<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\PickupLocation;
use App\Models\Transport;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    /**
     * Store a new transport pickup request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'pickup_location_id' => 'nullable|integer|exists:pickup_locations,id',
            'pickup_location' => 'nullable|string|max:1000|required_without:pickup_location_id',
            'pickup-location' => 'nullable|string|max:1000',
            'pickup_time' => 'nullable|date_format:H:i',
            'chapter_id' => 'nullable|integer|exists:chapters,id',
            'user_address' => 'nullable|string|max:255',
            'user_latitude' => 'nullable|numeric|between:-90,90',
            'user_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $chapterId = $validated['chapter_id'] ?? null;

        if ($request->query('chapter')) {
            $chapterId = Chapter::where('name', e($request->query('chapter')))->value('id') ?? $chapterId;
        }

        if (!$chapterId && $request->user()) {
            $chapterId = $request->user()->chapter_id;
        }

        $pickupLocation = null;
        $pickupLocationText = $validated['pickup_location'] ?? $validated['pickup-location'] ?? null;
        $pickupTime = $validated['pickup_time'] ?? null;

        if (!empty($validated['pickup_location_id'])) {
            $pickupLocation = PickupLocation::find($validated['pickup_location_id']);

            if ($pickupLocation) {
                $pickupLocationText = $pickupLocation->address ?: $pickupLocation->name;
                $pickupTime = $pickupLocation->pickup_time ?: $pickupTime;
                $chapterId = $pickupLocation->chapter_id ?: $chapterId;
            }
        }

        $transport = Transport::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'pickup_location' => $pickupLocationText,
            'pickup_location_id' => $pickupLocation?->id,
            'pickup_time' => $pickupTime,
            'chapter_id' => $chapterId,
            'user_address' => $validated['user_address'] ?? null,
            'user_latitude' => $validated['user_latitude'] ?? null,
            'user_longitude' => $validated['user_longitude'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup request submitted successfully. We will contact you soon!',
            'data' => $transport->loadMissing('pickupLocation'),
        ], 201);
    }



    /**
     * Update transport request status
     */
    public function updateStatus(Request $request, Transport $transport)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string',
        ]);

        $transport->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'],
            'processed_at' => $validated['status'] === 'pending' ? null : now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transport request status updated successfully',
            'data' => $transport,
        ]);
    }

    /**
     * Delete transport request
     */
    public function destroy(Transport $transport)
    {
        $transport->delete();
        return response()->json([
            'success' => true,
            'message' => 'Transport request deleted successfully',
        ]);
    }
}
