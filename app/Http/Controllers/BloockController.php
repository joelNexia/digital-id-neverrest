<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Helpers
use App\Http\Helpers\BloockHelper;

class BloockController extends Controller
{
    /**
     * Create a digital ID
     */
    public function create(Request $request)
    {
        // check if email is not empy
        if(!$request->email==''){
            $bloock = new BloockHelper();
            // Create a identity
            $identity = $bloock->createIdentity();

            $digitalId = $bloock->issuance($identity,$request->email);
            return $digitalId;
        }
    }
}
