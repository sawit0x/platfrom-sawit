<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Classes\GeniusMailer;
use App\Models\Invest;
use App\Models\PaymentGateway;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\ReferralBonus;
use App\Models\User;
use App\Models\Generalsetting;
use App\Models\Transaction;
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
        // dd($mercadoKey);
        $this->middleware('auth');
        $this->gs = Generalsetting::findOrFail(1);
    }

    public function mainWallet(Request $request){
        $amount = userBaseAmount($request->amount);
        if($amount>0){

            if($plan = Plan::whereId($request->investId)->first()){
                if($plan->invest_type == 'range'){
                    if($plan->min_amount > $amount ){
                        return redirect()->back()->with('warning','Amount Should be greater than this!');
                    }

                    if($amount > $plan->max_amount){
                        return redirect()->back()->with('warning','Amount Should be less than this.');
                    }
                }

                if($amount <= auth()->user()->balance){
                    $currency = Currency::first();

                    $invest = new Invest();
                    $invest->transaction_no = Str::random(12);
                    $invest->user_id = auth()->id();
                    $invest->plan_id = $plan->id;
                    $invest->currency_id = $currency->id;
                    $invest->method = 'Main Wallet';
                    $invest->amount = $amount;
                    $profitAmount = ($amount * $plan->profit_percentage)/100;
                    $invest->profit_amount = $profitAmount;

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
                    $user->balance = $user->balance - $amount;
                    $user->update();

                    $trans = new Transaction();
                    $trans->email = auth()->user()->email;
                    $trans->amount = $amount ;
                    $trans->type = "Invest";
                    $trans->txnid = $invest->transaction_no;
                    $trans->user_id = $invest->user_id;
                    $trans->save();

                    $this->refferalBonus($invest);

                    return redirect()->back()->with('message','Stake successful, profit in progress.');
                }else{
                    return redirect()->back()->with('warning','You don,t have sufficient balance.');
                }

                return redirect()->route('user.invest.checkout');
            }
        }else{
            return redirect()->route('front.index')->with('warning','Amount should be greater then 0!');
        }
    }

    public function interestWallet(Request $request){
        $amount = userBaseAmount($request->amount);
        if($amount>0){
            if($plan = Plan::whereId($request->investId)->first()){
                if($plan->invest_type == 'range'){
                    if($plan->min_amount > $amount){
                        return redirect()->back()->with('warning','Amount Should be greater than this!');
                    }

                    if($amount > $plan->max_amount){
                        return redirect()->back()->with('warning','Amount Should be less than this.');
                    }
                }

                if($amount < auth()->user()->interest_balance){
                    $currency = Currency::first();

                    $invest = new Invest();
                    $invest->transaction_no = Str::random(12);
                    $invest->user_id = auth()->id();
                    $invest->plan_id = $plan->id;
                    $invest->currency_id = $currency->id;
                    $invest->method = 'Interest Wallet';
                    $invest->amount = $amount;
                    $profitAmount = ($amount * $plan->profit_percentage)/100;
                    $invest->profit_amount = $profitAmount;

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
                    $user->interest_balance =$user->interest_balance - $amount;
                    $user->update();

                    $trans = new Transaction();
                    $trans->email = auth()->user()->email;
                    $trans->amount = $invest->amount;
                    $trans->type = "Invest";
                    $trans->txnid = $invest->transaction_no;
                    $trans->user_id = $invest->user_id;
                    $trans->save();

                    $this->refferalBonus($invest);
                    // $this->sendMail($invest);

                    return redirect()->back()->with('message','Stake successful, profit in progress.');
                }else{
                    return redirect()->back()->with('warning','You don,t have sufficient balance.');
                }

                return redirect()->route('user.invest.checkout');
            }
        }else{
            return redirect()->route('front.index')->with('warning','Amount should be greater than 0!');
        }
    }

    public function investAmount(Request $request){
        $currency = globalCurrency();
        if($request->amount>0){
            if($plan = Plan::whereId($request->investId)->first()){

                if($plan->invest_type == 'range'){
                    if(($plan->min_amount * $currency->value) > $request->amount ){
                        return back()->with('warning','Amount Should be greater than this!');
                    }

                    if($request->amount > ($plan->max_amount * $currency->value)){
                        return back()->with('warning','Amount Should be less than this');
                    }
                }

                session(['invest_amount'=>$request->amount,'currencyId'=>$currency->id,'investPlanId'=>$plan->id]);
                return redirect()->route('user.invest.checkout');
            }
        }else{
            return redirect()->route('front.index')->with('warning','Amount should be greater then 0!');
        }
    }

    public function planHistory(Request $request){
        $data['invests'] = Invest::whereUserId(auth()->id())
                                    ->when($request->trx_no,function($query) use ($request){
                                        return $query->where('transaction_no', $request->trx_no);
                                    })
                                    ->when($request->type,function($query) use ($request){
                                        if($request->type == 'pending'){
                                            return $query->where('status',0);
                                        }elseif($request->type == 'running'){
                                            return $query->where('status',1);
                                        }elseif($request->type == 'completed'){
                                            return $query->where('status',2);
                                        }else{

                                        }
                                    })
                                    ->orderBy('id','desc')->paginate(10);
        return view('user.invest.history',$data);
    }

    public function plans(){
        $data['plans'] = Plan::whereStatus(1)->orderBy('id','desc')->get();
        return view('user.invest.plans',$data);
    }

    public function checkout(){
        $data['invests'] = Invest::whereUserId(auth()->id())->orderBy('id','desc')->limit(5)->get();
        $data['gateways'] = PaymentGateway::where('status',1)->get();
        $mercado    = PaymentGateway::whereKeyword('mercadopago')->first();
        $mercado = $mercado->convertAutoData();
        $data['mercadoKey'] = $mercado['public_key'];
        
        return view('user.invest.create',$data);
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
