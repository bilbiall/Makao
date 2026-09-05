<?php

namespace App\Livewire;

use App\Models\ViewingRequest;
use App\Support\StaffScope;
use Livewire\Component;

/** Small pending-count pill dropped next to the "Viewing Requests" nav item -
 *  mirrors ChatUnreadBadge's pattern. */
class ViewingRequestsBadge extends Component
{
    public function render()
    {
        return view('livewire.viewing-requests-badge', [
            'count' => StaffScope::onTenant(ViewingRequest::query())->where('status', 'pending')->count(),
        ]);
    }
}
