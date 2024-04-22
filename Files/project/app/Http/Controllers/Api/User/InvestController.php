<?php

namespace App\Http\Controllers\Api\User;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvestCheckoutResource;
use App\Http\Resources\InvestResource;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\ReferralBonus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class InvestController extends Controller
{
    public $gs;
    public  $allusers = [];
    public $referral_ids = [];

    public function __construct()
    {
        $this->gs = Generalsetting::findOrFail(1);
    }

    public function plans(){
        try{
            $data = Plan::whereStatus(1)
                        ->orderBy('id','desc')
                        ->get();

            return response()->json(['status' => true, 'data' => $data, 'error' => []]);
        }catch(\Exception $e){
            return response()->json(['status' => false, 'data'=> [], 'error'=> $e->getMessage()]);
        }
    }

    public function mainWallet(Request $request){
        try{
            if($request->amount>0){
                if($plan = Plan::whereId($request->invest_id)->first()){
                    if($plan->invest_type == 'range'){
                        if(rootPrice($plan->min_amount) > $request->amount ){
                            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be greater than this!']);
                        }

                        if($request->amount > rootPrice($plan->max_amount)){
                            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be less than this!']);
                        }
                    }else{
                        if(rootPrice($plan->fixed_amount) < $request->amount){
                            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be greater or equal to '.$plan->fixed_amount]);
                        }
                    }

                    if($request->amount <= rootPrice(auth()->user()->balance)){
                        $currency = Currency::first();

                        $invest = new Invest();
                        $invest->transaction_no = Str::random(12);
                        $invest->user_id = auth()->id();
                        $invest->plan_id = $plan->id;
                        $invest->currency_id = $currency->id;
                        $invest->method = 'Main Wallet';
                        $invest->amount = investCurrencyAmount($request->amount);
                        $profitAmount = ($request->amount * $plan->profit_percentage)/100;
                        $invest->profit_amount = investCurrencyAmount($profitAmount);

                        if($plan->lifetime_return){
                            $invest->lifetime_return = 1;
                        }

                        if($plan->captial_return){
                            $invest->capital_back = 1;
                            $invest->profit_repeat = 0;
                        }
                        $invest->status = 1;
                        $invest->payment_status = "completed";
                        $invest->profit_time = Carbon::now()->addHours($plan->schedule_hour);
                        $invest->save();

                        $user = auth()->user();
                        $user->balance = $user->balance - investCurrencyAmount($request->amount);
                        $user->update();

                        $trans = new Transaction();
                        $trans->email = auth()->user()->email;
                        $trans->amount = $invest->amount;
                        $trans->type = "Invest";
                        $trans->txnid = $invest->transaction_no;
                        $trans->user_id = $invest->user_id;
                        $trans->save();

                        $this->refferalBonus($invest);
                        return response()->json(['status' => true, 'data' => new InvestResource($invest), 'error'=>[]]);
                    }else{
                        return response()->json(['status' => false, 'data'=> [], 'error'=> 'You don,t have sufficient balance.']);
                    }
                    return response()->json(['status' => false, 'data'=> [], 'error'=> 'No plan exists.']);
                }
            }else{
                return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount should be greater then 0!']);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false, 'data'=> [], 'error'=> $e->getMessage()]);
        }
    }


    public function interestWallet(Request $request){
        try{
            if($request->amount>0){
                if($plan = Plan::whereId($request->invest_id)->first()){
                    if($plan->invest_type == 'range'){
                        if(rootPrice($plan->min_amount) > $request->amount ){
                            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be greater than this!']);
                        }

                        if($request->amount > rootPrice($plan->max_amount)){
                            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be less than this!']);
                        }
                    }else{
                        if(rootPrice($plan->fixed_amount) < $request->amount){
                            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be greater or equal to '.$plan->fixed_amount]);
                        }
                    }

                    if($request->amount < rootPrice(auth()->user()->interest_balance)){
                        $currency = Currency::first();

                        $invest = new Invest();
                        $invest->transaction_no = Str::random(12);
                        $invest->user_id = auth()->id();
                        $invest->plan_id = $plan->id;
                        $invest->currency_id = $currency->id;
                        $invest->method = 'Interest Wallet';
                        $invest->amount = investCurrencyAmount($request->amount);
                        $profitAmount = ($request->amount * $plan->profit_percentage)/100;
                        $invest->profit_amount = investCurrencyAmount($profitAmount);

                        if($plan->lifetime_return){
                            $invest->lifetime_return = 1;
                        }

                        if($plan->captial_return){
                            $invest->capital_back = 1;
                            $invest->profit_repeat = 0;
                        }
                        $invest->status = 1;
                        $invest->payment_status = "completed";
                        $invest->profit_time = Carbon::now()->addHours($plan->schedule_hour);
                        $invest->save();

                        $user = auth()->user();
                        $user->interest_balance =$user->interest_balance - investCurrencyAmount($request->amount);
                        $user->update();

                        $trans = new Transaction();
                        $trans->email = auth()->user()->email;
                        $trans->amount = $invest->amount;
                        $trans->type = "Invest";
                        $trans->txnid = $invest->transaction_no;
                        $trans->user_id = $invest->user_id;
                        $trans->save();

                        $this->refferalBonus($invest);
                        return response()->json(['status' => true, 'data' => new InvestResource($invest), 'error'=>[]]);
                    }else{
                        return response()->json(['status' => false, 'data'=> [], 'error'=> 'You don,t have sufficient balance.']);
                    }
                    return response()->json(['status' => false, 'data'=> [], 'error'=> 'No plan exists.']);
                }
            }else{
                return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount should be greater then 0!']);
            }
        }catch(\Exception $e){
             return response()->json(['status' => false, 'data'=> [], 'error'=> $e->getMessage()]);
        }
    }

    public function investAmount(Request $request){
        if($request->amount>0){
            if($plan = Plan::whereId($request->plan_id)->first()){
                if($plan->invest_type == 'range'){
                    if(rootPrice($plan->min_amount) > $request->amount ){
                        return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be greater than this!']);
                    }

                    if($request->amount > rootPrice($plan->max_amount)){
                        return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be less than this!']);
                    }
                }else{
                    if(rootPrice($plan->fixed_amount) > $request->amount){
                        return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount Should be greater or equal to '.$plan->fixed_amount]);
                    }
                }

                $invest = new Invest();
                $invest->transaction_no = Str::random(12);
                $invest->user_id = $request->user_id;
                $invest->plan_id = $request->plan_id;
                $invest->currency_id = $request->currency_id;
                $invest->amount = investCurrencyAmount($request->amount);
                $invest->profit_amount = investCurrencyAmount(($request->amount * $plan->profit_percentage)/100);

                if($plan->lifetime_return){
                    $invest->lifetime_return = 1;
                }

                if($plan->captial_return){
                    $invest->capital_back = 1;
                    $invest->profit_repeat = 0;
                }

                $invest->status = 0;
                $invest->payment_status = "pending";
                $invest->save();

                $route = route('api.user.invest.checkout',$invest->id);

                return response()->json(['status' => true, 'data' => ['invest_data' => new InvestCheckoutResource($invest), 'checkout_route' => $route], 'error'=>[]]);
            }
        }else{
            return response()->json(['status' => false, 'data'=> [], 'error'=> 'Amount should be greater then 0!']);
        }
    }

    public function checkout($id){
        $data['invest'] = Invest::findOrFail($id);
        $data['invest_currency'] = Currency::findOrFail($data['invest']->currency_id);
        $data['gateways'] = PaymentGateway::where('status',1)->get();

        return view('user.invest.api_checkout',$data);
    }

    public function refferalBonus($order){
        if($this->gs->is_affilate == 1){
            $referralUser = User::whereId($order->user_id)->first();
            if(Session::has('affilate') || ($referralUser != NULL && $referralUser->referral_id != 0)){

                if(Session::has('affilate')){
                    $this->referralUsers(Session::get('affilate'));
                }else{
                    if($referralUser->referral_id != 0){
                        $this->referralUsers($referralUser->referral_id);
                    }
                }

                $referral_ids = $this->allReferralId();

                if(count($this->allusers) >0){
                    $users = array_reverse($this->allusers);
                    foreach($users as $key=>$data){
                        $user = User::findOrFail($data);

                        if($referral = Referral::findOrFail($referral_ids[$key])){

                            $referralAmount = ($order->amount * $referral->percent)/100;

                            $bonus = new ReferralBonus();
                            $bonus->from_user_id = auth()->id();
                            $bonus->to_user_id = $user->id;
                            $bonus->percentage = $referral->percent;
                            $bonus->level = $referral->level;
                            $bonus->amount = $referralAmount;
                            $bonus->type = 'invest';
                            $bonus->save();

                            $to_user = User::findOrFail($bonus->to_user_id);
                            $trans = new Transaction();
                            $trans->email = $to_user->email;
                            $trans->amount = $referralAmount;
                            $trans->type = "Referral Bonus";
                            $trans->txnid = $order->transaction_no;
                            $trans->user_id = $to_user->id;
                            $trans->profit = 'plus';
                            $trans->save();

                            if($this->gs->is_smtp == 1)
                            {
                                $data = [
                                    'to' => $to_user->email,
                                    'type' => "referral bonus",
                                    'cname' => $to_user->name,
                                    'oamount' => $referralAmount,
                                    'aname' => "",
                                    'aemail' => "",
                                    'wtitle' => "",
                                ];

                                $mailer = new GeniusMailer();
                                $mailer->sendAutoMail($data);
                            }
                            else
                            {
                               $to = $to_user->email;
                               $subject = "Referral Bonus";
                               $msg = "Hello ".$to_user->name."!\nYou got bonus from referral.\nThank you.";
                               $headers = "From: ".$this->gs->from_name."<".$this->gs->from_email.">";
                               mail($to,$subject,$msg,$headers);
                            }

                            $user->increment('balance',$referralAmount);
                            $referralAmount = 0;
                        }

                    }
                }
            }
        }
    }

    public function allReferralId(){
        $referrals = Referral::where('commission_type','invest')->get();

        if(count($referrals)>0){
            foreach($referrals as $key=>$data){
                $this->referral_ids[] = $data->id;
            }
            return $this->referral_ids;
        }
    }

    public function referralUsers($id)
    {
        $referral = Referral::where('commission_type','invest')->get();

        for($i=1; $i<=count($referral); $i++){
            $user = User::findOrFail($id);
            $this->allusers[] = $user->id;

            if($user->referral_id){
                $id = $user->referral_id;
            }else{
                return false;
            }
        }
        return $this->allusers;
    }
}
