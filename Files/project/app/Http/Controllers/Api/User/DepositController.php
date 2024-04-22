<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepositResource;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    public function history(){
        try{
            $deposits = Deposit::orderby('id','desc')->whereUserId(auth()->id())->paginate(10);
            return response()->json(['status' => true, 'data' => DepositResource::collection($deposits), 'error' => []]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'data'=>[], 'error'=>$e->getMessage()]);
        }
    }

    public function deposit(Request $request){
        try{
            $currency = Currency::where('id',$request->currency_id)->first();
            $amountToAdd = $request->amount/$currency->value;

            $deposit = new Deposit();
            $deposit['deposit_number'] = Str::random(12);
            $deposit['user_id'] = $request->user_id;
            $deposit['currency_id'] = $request->currency_id;
            $deposit['amount'] = $amountToAdd;
            $deposit['status'] = "pending";
            $deposit->save();

            $route = route('api.user.deposit.confirm',$deposit->id);

            return response()->json(['status' => true, 'data' => ['deposit_data' => new DepositResource($deposit), 'deposit_route' => $route], 'error' => []]);
        }catch(\Exception $e){
            return response()->json(['status'=>false, 'data'=>[], 'error'=>$e->getMessage()]);
        }
    }

    public function confirm_deposit($id){
        $data['deposit'] = Deposit::findOrFail($id);
        $data['deposit_currency'] = Currency::findOrFail($data['deposit']->currency_id);
        $data['availableGatways'] = ['block.io.btc','block.io.ltc','block.io.dgc','flutterwave','authorize.net','paystack','razorpay','mollie','paytm','instamojo','stripe','paypal','mercadopago','skrill','perfectmoney','payeer'];
        $data['gateways'] = PaymentGateway::OrderBy('id','desc')->whereStatus(1)->get();

        return view('user.deposit.api_deposit',$data);
    }
}

