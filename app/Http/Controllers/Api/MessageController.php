<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin|teacher|parent|student')->only(['store', 'index', 'show']);
        $this->middleware('role:admin')->only(['update', 'destroy']);
    }

    private function checkMessageAccess(Message $message)
    {
        if (!auth()->user()->hasRole('admin') && 
            auth()->user()->id !== $message->sender_id && 
            auth()->user()->id !== $message->receiver_id) {
            abort(403, 'Accès non autorisé');
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!auth()->user()->hasRole('admin') && auth()->user()->id !== $request->user_id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $messages = Message::with('sender:id,name', 'receiver:id,name')
            ->where(function ($query) use ($request) {
                $query->where('sender_id', $request->user_id)
                      ->orWhere('receiver_id', $request->user_id);
            })
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['message' => 'Aucun message trouvé pour cet utilisateur'], 404);
        }

        return response()->json([
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name ?? 'Non assigné',
                    'receiver_id' => $message->receiver_id,
                    'receiver_name' => $message->receiver->name ?? 'Non assigné',
                    'subject' => $message->subject,
                    'content' => $message->content,
                    'sent_at' => $message->sent_at->format('d/m/Y H:i'),
                    'read_at' => $message->read_at ? $message->read_at->format('d/m/Y H:i') : null,
                    'created_at' => $message->created_at->format('d/m/Y H:i')
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'subject' => 'required|string|min:1|max:255',
            'content' => 'required|string|min:1',
            'sent_at' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = Message::create(array_merge($request->all(), ['sender_id' => auth()->id()]));
        return response()->json([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name ?? 'Non assigné',
            'receiver_id' => $message->receiver_id,
            'receiver_name' => $message->receiver->name ?? 'Non assigné',
            'subject' => $message->subject,
            'content' => $message->content,
            'sent_at' => $message->sent_at->format('d/m/Y H:i'),
            'read_at' => $message->read_at ? $message->read_at->format('d/m/Y H:i') : null,
            'created_at' => $message->created_at->format('d/m/Y H:i')
        ], 201);
    }

    public function show($id)
    {
        $message = Message::with('sender:id,name', 'receiver:id,name')->find($id);

        if (!$message) {
            return response()->json(['message' => 'Message non trouvé'], 404);
        }

        $this->checkMessageAccess($message);

        return response()->json([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name ?? 'Non assigné',
            'receiver_id' => $message->receiver_id,
            'receiver_name' => $message->receiver->name ?? 'Non assigné',
            'subject' => $message->subject,
            'content' => $message->content,
            'sent_at' => $message->sent_at->format('d/m/Y H:i'),
            'read_at' => $message->read_at ? $message->read_at->format('d/m/Y H:i') : null,
            'created_at' => $message->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json(['message' => 'Message non trouvé'], 404);
        }

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'sometimes|exists:users,id',
            'subject' => 'sometimes|string|min:1|max:255',
            'content' => 'sometimes|string|min:1',
            'sent_at' => 'sometimes|date',
            'read_at' => 'sometimes|nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message->update($request->all());
        return response()->json([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name ?? 'Non assigné',
            'receiver_id' => $message->receiver_id,
            'receiver_name' => $message->receiver->name ?? 'Non assigné',
            'subject' => $message->subject,
            'content' => $message->content,
            'sent_at' => $message->sent_at->format('d/m/Y H:i'),
            'read_at' => $message->read_at ? $message->read_at->format('d/m/Y H:i') : null,
            'created_at' => $message->created_at->format('d/m/Y H:i')
        ], 200);
    }

    public function destroy($id)
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json(['message' => 'Message non trouvé'], 404);
        }

        $message->delete();
        return response()->json(['message' => 'Message supprimé avec succès'], 200);
    }
}
