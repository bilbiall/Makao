<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\HouseReview;
use App\Models\User;

/**
 * Public "storefront" for an agent marketing short-stay houses - photo, bio,
 * aggregate rating, and every stay they manage. See Profile's "Your public
 * profile" section for how an agent gets/shares this link.
 */
class AgentProfileController extends Controller
{
    public function show(User $user)
    {
        abort_unless($user->role === 'agent', 404);

        $houses = $user->assignedHouses()
            ->bnbVisible()
            ->with(['location', 'photos', 'pricePackages'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();

        $houseIds = $houses->pluck('id');

        $reviews = HouseReview::whereIn('house_id', $houseIds)
            ->with(['house', 'booking'])
            ->latest()
            ->take(20)
            ->get();

        $averageRating = $reviews->isNotEmpty()
            ? round(HouseReview::whereIn('house_id', $houseIds)->avg('rating'), 1)
            : null;

        $reviewsCount = HouseReview::whereIn('house_id', $houseIds)->count();

        return view('agents.show', compact('user', 'houses', 'reviews', 'averageRating', 'reviewsCount'));
    }
}
