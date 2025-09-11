<?php
namespace App\Http\Controllers;

use App\Character;
use App\Vehicle;

class FindController extends Controller
{
    public function vehicle(Vehicle $vehicle)
    {
        $owner = $vehicle->character()->get()->first();

        if (!$owner) {
            abort(404);
        }

        return redirect('/players/' . $owner->license_identifier . '/characters/' . $owner->character_id);
    }

    public function character(Character $character)
    {
        return redirect('/players/' . $character->license_identifier . '/characters/' . $character->character_id);
    }
}
