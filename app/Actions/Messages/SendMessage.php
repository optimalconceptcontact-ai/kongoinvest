<?php

namespace App\Actions\Messages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SendMessage
{
    public function handle(User $sender, Conversation $conversation, string $body): Message
    {
        return DB::transaction(function () use ($sender, $conversation, $body) {
            // sender_id and last_message_at are deliberately outside
            // their models' $fillable — create()/update() would
            // silently drop them, so force* bypasses that protection
            // for this trusted, server-controlled call.
            $message = $conversation->messages()->forceCreate([
                'sender_id' => $sender->id,
                'body' => $body,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message;
        });
    }
}
