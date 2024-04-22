<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\BalanceTransferResource;
use App\Models\BalanceTransfer;
use App\Models\Generalsetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    public function history(){
        try{
            $transfers = BalanceTransfer::whereUserId(auth()->id())->orderBy('id','desc')->paginate(10);
            return response()->json(['status' => true, 'data' => BalanceTransferResource::collection($transfers), 'error' => []]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'data'=>[], 'error'=>$e->getMessage()]);
        }
    }

    public function store(Request $request){
        try{
            $rules = [
                'email' => 'required',
                'name' => 'required',
                'amount' => 'required|gt:0',
                'wallet' => 'required',
                'password' => 'required'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $user = auth()->user();
            if(!Hash::check($request->password,$user->password)){
                return response()->json(['status' => false, 'data' => [], 'error' => 'Password does not match!']);
            }

            if($request->wallet == 'main_balance'){
                if($request->amount > $user->balance){
                    return response()->json(['status' => false, 'data' => [], 'error' => 'Insufficient Main Account Balance.']);
                }
            }else{
                if($request->amount > $user->interest_balance){
                    return response()->json(['status' => false, 'data' => [], 'error' => 'Insufficient Main Account Balance.']);
                }
            }

            $gs = Generalsetting::first();

            if($request->email == $user->email){
                return response()->json(['status' => false, 'data' => [], 'error' => 'You can not send money yourself!!']);
            }


            if($receiver = User::where('email',$request->email)->first()){
                $txnid = Str::random(4).time();
                $data = new BalanceTransfer();
                $data->user_id = $user->id;
                $data->receiver_id = $receiver->id;
                $data->transaction_no = $txnid;
                $data->cost = 0;
                $data->amount = $request->amount;
                $data->status = 1;
                $data->save();

                $receiver->increment('balance',$request->amount);
                if($request->wallet == 'main_balance'){
                    $user->decrement('balance',$request->amount);
                }else{
                    $user->decrement('interest_balance',$request->amount);
                }

                $trans = new Transaction();
                $trans->email = $user->email;
                $trans->amount = $request->amount;
                $trans->type = "Send Money";
                $trans->profit = "minus";
                $trans->txnid = $txnid;
                $trans->user_id = $user->id;
                $trans->save();

                $receivertrans = new Transaction();
                $receivertrans->email = $receiver->email;
                $receivertrans->amount = $request->amount;
                $receivertrans->type = "Receive Money";
                $receivertrans->profit = "plus";
                $receivertrans->txnid = $txnid;
                $receivertrans->user_id = $receiver->id;
                $receivertrans->save();

                return response()->json(['status' => true, 'data' => 'Money Send Successfully', 'error' => []]);

            }else{
                return response()->json(['status' => false, 'data' => [], 'error' => 'Sender not found!']);
            }

        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
