<?php

namespace App\Http\Controllers\Api\User;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawMethodResource;
use App\Http\Resources\WithdrawResource;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\Models\WithdrawMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PayoutController extends Controller
{
    public function methods(){
        try {
            $methods = WithdrawMethod::whereStatus(1)->orderBy('id','desc')->get();
            $withdraws = Withdraw::whereUserId(auth()->id())->orderBy('id','desc')->limit(6)->get();
            return response()->json(['status' => true, 'data' => ['withdraws'=>WithdrawResource::collection($withdraws),'methods'=>WithdrawMethodResource::collection($methods)], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function history(Request $request){
        try{
            $transaction_type = ['all','pending','completed','rejected'];
            $available_type =
            $withdraws = Withdraw::when($request->trx_no,function($query) use ($request){
                                                return $query->where('txnid', $request->trx_no);
                                            })
                                            ->when($request->type,function($query) use ($request){
                                                if($request->type != 'all'){
                                                    return $query->where('status',$request->type);
                                                }else{

                                                }
                                            })
                                            ->whereUserId(auth()->id())->orderBy('id','desc')->paginate(10);

            return response()->json(['status' => true, 'data' => ['transaction_type'=>$transaction_type,'transactions'=> WithdrawResource::collection($withdraws)], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function store(Request $request){
        try{
            $rules = [
                'amount' => 'required|gt:0'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
            return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            $user = auth()->user();
            $method = WithdrawMethod::whereId($request->method_id)->first();

            if($method->min_amount > $request->amount){
                return response()->json(['status' => false, 'data' => [], 'error' => 'Min Amount is '.$method->min_amount]);
            }

            if($request->amount > $method->max_amount){
                return response()->json(['status' => false, 'data' => [], 'error' => 'Max Amount is '.$method->max_amount]);
            }

            $currency = Currency::whereId($method->currency_id)->first();
            $amountToDeduct = $request->amount/$currency->value;

            $fee = (($method->percentage / 100) * $request->amount) + $method->fixed;
            $fee = $fee/$currency->value;
            $finalamount = $amountToDeduct + $fee;

            if($request->withdraw_wallet == 'main_wallet'){
                if($finalamount > $user->balance){
                    return response()->json(['status' => false, 'data' => [], 'error' => 'Insufficient Balance.']);
                }
            }else{
                if($finalamount > $user->interest_balance){
                    return response()->json(['status' => false, 'data' => [], 'error' => 'Insufficient Balance.']);
                }
            }

            $finalamount = number_format((float)$finalamount,2,'.','');

            if($request->withdraw_wallet == 'main_wallet'){
                $user->balance = $user->balance - $finalamount;
            }else{
                $user->interest_balance = $user->interest_balance - $finalamount;
            }

            $user->update();

            $txnid = Str::random(12);
            $newwithdraw = new Withdraw();
            $newwithdraw['user_id'] = auth()->id();
            $newwithdraw['currency_id'] = $method->currency_id;
            $newwithdraw['method'] = $method->name;
            $newwithdraw['txnid'] = $txnid;
            $newwithdraw['amount'] = $amountToDeduct;
            $newwithdraw['fee'] = $fee;
            $newwithdraw['details'] = $request->details;
            $newwithdraw->save();

            $trans = new Transaction();
            $trans->email = $user->email;
            $trans->amount = $finalamount;
            $trans->type = "Payout";
            $trans->profit = "minus";
            $trans->txnid = $txnid;
            $trans->user_id = $user->id;
            $trans->save();

            $gs = Generalsetting::findOrFail(1);

            $to = $user->email;
            $subject = 'Withdraw';
            $msg = "Dear Customer,<br> Your withdraw in process.";

            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $user->email,
                    'type' => "Withdraw",
                    'cname' => $user->name,
                    'oamount' => $newwithdraw->amount,
                    'aname' => "",
                    'aemail' => "",
                    'wtitle' => "",
                ];

                $mailer = new GeniusMailer();
                $mailer->sendAutoMail($data);
            }
            else
            {
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);
            }
            return response()->json(['status' => true, 'data' => 'Withdraw Requesting Successfully', 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function details($id){
        try {
            $withdraw = Withdraw::findOrFail($id);
            return response()->json(['status' => true, 'data' => WithdrawResource::collection($withdraw), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
