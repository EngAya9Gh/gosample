<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\User;

class ClientAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'password' => 'required'
        ]);

        // جيب الكلاينت
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $client = Client::find($user->client_id);

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found'], 404);
        }

        if (!$client->status == 2) {
            return response()->json(['success' => false, 'message' => 'Client is disabled'], 403);
        }


        // ولّد توكن جديد
        $client->api_token = Str::random(60);
        $client->save();

        return response()->json([
            'success' => true,
            'token'   => $client->api_token,
        ]);
    }
}
