<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;
use Illuminate\Support\Carbon;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Chat $chat)
    {
        $messages = Message::where('chat_id', $chat->id)->get();

        return view('messages.index', compact('messages', 'chat'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('messages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Chat $chat, Request $request)
    {
        $message = $request->input('message');
        // $chat_id = $request->input('chat_id');

        $messageData = [
            'user_id' => Auth::user()->id,
            'chat_id' => $chat->id,
            'content' => $message,
        ];

        Message::create($messageData);

        return redirect()->route('chats.messages.index', $chat);
    }

    /**
     * Display the specified resource.
     */
    public function show(Chat $chat, Message $message)
    {
        return view('messages.show', compact('message'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chat $chat, Message $message)
    {
        return view('messages.edit', compact('message'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Chat $chat, Message $message, Request $request)
    {
        $message = Message::findOrFail($message);
        $message->update($request->all());

        return redirect()->route('chats.messages.show', ['message' => $message, 'chat' => $message->chat]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($message)
    {
        $message = Message::findOrFail($message);
        $message->delete();

        return redirect()
            ->route('chats.index');
    }

    public function getPreviewMessages(Chat $chat, Request $request)
    {
        // Получение даты из запроса
        $date = $request->input('date');

        // Если дата не передана, использовать текущую дату
        $selectedDate = $date ? Carbon::parse($date) : Carbon::now();

        // Определение предыдущего дня общения
        $lastDate = Message::where('chat_id', $chat->id)
            ->whereDate('created_at', '<=', $selectedDate)
            ->latest('created_at')
            ->value('created_at');

        // Определение предыдущего дня общения
        $previousDate = Message::where('chat_id', $chat->id)
            ->whereDate('created_at', '<', $lastDate)
            ->latest('created_at')
            ->value('created_at');

        // Получение всех сообщений за указанный день
        $messages = Message::where('chat_id', $chat->id)
            ->whereDate('created_at', $lastDate)
            ->get();

        $lastDate = Message::where('chat_id', $chat->id)
            ->latest('created_at')
            ->value('created_at');

        // Преобразуем дату в строку или устанавливаем "Нет сообщений", если дата не найдена
        $day = $previousDate ? Carbon::parse($previousDate)->toDateString() : null;

        return response()->json([
            'messagesHtml' => view('messages.components.list', compact('messages', 'day'))->render(),
            'previousDate' => $previousDate,
            'lastDate' => $lastDate,
            'selectedDate' => $selectedDate,
        ]);
    }

    public function getNewMessages(Chat $chat, Request $request)
    {
        // Получение даты из запроса
        $date = $request->input('date');

        // Если дата не передана, использовать текущую дату
        $currentDate = $date ? Carbon::parse($date) : Carbon::now();

        // Получение всех сообщений за указанный день
        $messages = Message::where('chat_id', $chat->id)
            ->where('created_at', '>', $currentDate)
            ->get();

        $lastDate = Message::where('chat_id', $chat->id)
            ->latest('created_at')
            ->value('created_at');

        // Преобразуем дату в строку или устанавливаем "Нет сообщений", если дата не найдена
        $day = $currentDate; //$date ? Carbon::parse($date)->toDateString() : "Нет сообщений";

        return response()->json([
            'messagesHtml' => view('messages.components.list', compact('messages', 'day'))->render(),
            'lastDate' => $lastDate,
        ]);
    }
}
