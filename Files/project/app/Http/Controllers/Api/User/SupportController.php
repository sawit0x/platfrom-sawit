<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\TicketReplyResource;
use App\Models\AdminUserConversation;
use App\Models\AdminUserMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    public function allTickets(){
        try{
            $support_tickets = AdminUserConversation::whereUserId(auth()->id())->paginate(10);
            return response()->json(['status'=>true, 'data'=>SupportTicketResource::collection($support_tickets), 'error'=>[]]);

        }catch(\Exception $e){
            return response()->json(['status'=>false, 'data'=>[], 'error'=>$e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try{
            $rules = [
                'subject' => 'required',
                'message' => 'required',
                'attachment' => 'required|mimes:png,jpeg,gif',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }


            $ticket = new AdminUserConversation();
            $input = $request->all();

            $input['ticket_number'] = '#TKT'.random_int(100000, 999999);
            $input['user_id'] = auth()->id();

            if ($file = $request->file('attachment'))
            {
                $name = Str::random(8).time().'.'.$file->getClientOriginalExtension();
                $file->move('assets/images',$name);
                $input['attachment'] = $name;
            }
            $ticket->fill($input)->save();

            $conversation = new AdminUserMessage();
            $conversation->conversation_id = $ticket->id;
            $conversation->user_id = auth()->id();
            $conversation->message = $request->message;
            $conversation->photo = $ticket->attachment;
            $conversation->save();

            return response()->json(['status' => true, 'data' => new SupportTicketResource($ticket), 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function show($id){
        try{
            $data = AdminUserConversation::whereId($id)->first();
            $replies = $data->messages;
            return response()->json(['status' => true, 'data' => TicketReplyResource::collection($replies), 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function reply(Request $request, $id){
        try{
            $data = new AdminUserMessage();
            $data->conversation_id = $id;
            $data->user_id = auth()->id();
            $data->message = $request->message;

            if ($file = $request->file('photo'))
            {
                $name = Str::random(8).time().'.'.$file->getClientOriginalExtension();
                $file->move('assets/images',$name);
                $data->photo = $name;
            }
            $data->save();

            return response()->json(['status' => true, 'data' => new TicketReplyResource($data), 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }

    }
}
