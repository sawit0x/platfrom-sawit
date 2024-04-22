<?php

namespace App\Http\Controllers\Admin;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Generalsetting;
use Datatables;
use App\Models\Invest;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\ReferralBonus;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class InvestController extends Controller
{
    public $gs;
    public  $allusers = [];
    public $referral_ids = [];

    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->gs = Generalsetting::findOrFail(1);
    }

    public function datatables(){
        $datas = Invest::orderBy('id','desc')->get();

        return Datatables::of($datas)
                        ->editColumn('user_id', function(Invest $data) {
                            return '<div>
                                    '.ucfirst($data->user->name).'
                                    <p>@'.$data->user->email.'</p>
                            </div>';
                        })
                        ->editColumn('plan_id', function(Invest $data) {
                            return  '<div>
                                        '.$data->plan->title.'
                                        <p>'.showAdminAmount($data->profit_amount).' / '.$data->plan->schedule->name.'</p>
                                </div>';
                        })
                        ->editColumn('amount', function(Invest $data){
                            return '<div>
                                        <strong>'.showAdminAmount($data->amount,2).'</strong>
                                    </div>';
                        })
                        ->editColumn('method', function(Invest $data) {
                            return '<div>
                                    '.ucfirst($data->method).'
                            </div>';
                        })
                        ->editColumn('status', function(Invest $data) {

                            if($data->status == 0){
                                $status = "Pending";
                                $status_sign = $data->status == 0 ? 'warning' : '';
                            }elseif($data->status == 1){
                                $status = "Running";
                                $status_sign = $data->status == 1 ? 'info' : '';
                            }else{
                                $status = "Completed";
                                $status_sign = $data->status == 2 ? 'success' : '';
                            }

                            return '<div class="btn-group mb-1">
                            <button type="button" class="btn btn-'.$status_sign.' btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              '.$status .'
                            </button>
                            <div class="dropdown-menu" x-placement="bottom-start">
                              <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.invests.status',['id1' => $data->id, 'id2' => 0]).'">'.__("Pending").'</a>
                              <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.invests.status',['id1' => $data->id, 'id2' => 1]).'">'.__("Running").'</a>
                              <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.invests.status',['id1' => $data->id, 'id2' => 2]).'">'.__("Completed").'</a>
                            </div>
                          </div>';
                        })

                        ->editColumn('profit_time', function(Invest $data){
                            return $data->profit_time ? $data->profit_time->toDateString() : '--';
                        })

                        ->addColumn('action', function(Invest $data) {
                            return '<div class="btn-group mb-1">
                            <button type="button" class="btn btn-primary btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              '.'Actions' .'
                            </button>
                            <div class="dropdown-menu" x-placement="bottom-start">
                              <a href="javascript:;" data-href="' . route('admin.invests.show',$data->id) . '"  class="dropdown-item" id="applicationDetails" data-toggle="modal" data-target="#details">'.__("Details").'</a>

                            </div>
                          </div>';
                         })
                        ->rawColumns(['user_id','plan_id','amount','method','status','profit_time','action'])
                        ->toJson();
    }

    public function index(){
        return view('admin.invest.index');
    }

    public function investdetails($id)
    {
        $invest = Invest::findOrFail($id);
        return view('admin.invest.details',compact('invest'));
    }

    public function status($id1,$id2){
        $data = Invest::findOrFail($id1);
        $user = User::whereId($data->user_id)->first();

        if($data->status == 2){
          $msg = 'Invest already completed';
          return response()->json($msg);
        }

        if($id2 == 2){
            $msg = 'Invest will completed automaticlly by the system';
            return response()->json($msg);
        }

        if($data->status == 1){
            $msg = 'Invest is running';
            return response()->json($msg);
        }

        $plan = Plan::whereId($data->plan_id)->first();

        $data->status = 1;
        $data->payment_status = 'completed';
        $data->profit_time = Carbon::now()->addHours($plan->schedule_hour);
        $data->update();

        $this->refferalBonus($data);

        $msg = 'Data Updated Successfully.';
        return response()->json($msg);
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
