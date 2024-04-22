<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Invest;
use App\Models\Deposit;
use App\Models\Withdraw;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ReferralBonus;
use App\Models\AdminUserConversation;

class DashboardController extends Controller
{
    public function dashboard(){
        try{
            $data['user'] = auth()->guard('api')->user();
            $data['transactions']       = Transaction::whereUserId(auth()->id())->orderBy('id','desc')->limit(5)->get();
            $data['total_deposits']     = showPrice(Deposit::whereUserId(auth()->id())->whereStatus('complete')->sum('amount'));
            $data['total_invests']      = showPrice(Invest::whereUserId(auth()->id())->whereStatus(1)->sum('amount'));
            $data['total_withdraws']    = showPrice(Withdraw::whereUserId(auth()->id())->whereStatus('completed')->sum('amount'));
            $data['total_transactions'] = showPrice(Transaction::whereUserId(auth()->id())->sum('amount'));
            $data['total_tickets'] = AdminUserConversation::whereUserId(auth()->id())->count();
            $data['total_referral_bonus'] = showPrice(ReferralBonus::wheretoUserId(auth()->id())->sum('amount'));

            return response()->json(['status' => true, 'data' => $data, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function transactions(Request $request){
        try{
            $transaction_type = ['all','Deposit','Payout','ReferralBonus','SendMoney','ReceiveMoney','Invest','InterestMoney','RequestMoney','PayoutRejected'];
            $transactions = Transaction::whereUserId(auth()->id())
                                    ->when($request->trx_no,function($query) use ($request){
                                        return $query->where('transaction_no', $request->trx_no);
                                    })
                                    ->when($request->type,function($query) use ($request){
                                        if($request->type != 'all'){
                                            return $query->where('type',$request->type);
                                        }else{

                                        }
                                    })
                                    ->whereUserId(auth()->id())->orderBy('id','desc')->paginate(20);
            return response()->json(['status' => true, 'data' => ['transaction_type'=>$transaction_type,'transactions'=> TransactionResource::collection($transactions)], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
