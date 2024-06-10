<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileInteraction;

class ProfileInteractionController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();
        $interactions = ProfileInteraction::where('user_id', $user->id)->get();
        $token = $user->createToken('myToken')->accessToken;
        return response()->json([$interactions, 'token' => $token  ] ,201);
    }

    public function store(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
        ]);

        $user = $request->user();
        $interaction = ProfileInteraction::create([
            'user_id' => $user->id,
            'action' => $request->action,
        ]);
        $token = $user->createToken('myToken')->accessToken;
        return response()->json([$interaction, 'token' => $token  ],201);
    }
}
