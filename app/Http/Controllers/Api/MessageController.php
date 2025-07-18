<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{


    public function index()
    {
        $messages = Message::where('receiver_id', Auth::id())->with('sender')->get();
        return response()->json($messages, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
            'type' => 'required|in:absence,note,payment,general',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'type' => $request->type,
            'is_read' => false,
        ]);

        return response()->json($message, 201);
    }

    public function markAsRead($id)
    {
        $message = Message::where('receiver_id', Auth::id())->findOrFail($id);
        $message->update(['is_read' => true]);
        return response()->json($message, 200);
    }
}
