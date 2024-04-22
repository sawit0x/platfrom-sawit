<?php

namespace App\Http\Controllers\Admin;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Generalsetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Datatables;

class DepositController extends Controller
{
    public function datatables()
    {
        $datas = Deposit::orderBy('id','desc');

        return Datatables::of($datas)
                        ->editColumn('created_at', function(Deposit $data) {
                            $date = date('d-m-Y',strtotime($data->created_at));
                            return $date;
                        })
                        ->addColumn('customer_name',function(Deposit $data){
                            $data = User::where('id',$data->user_id)->first();
                            return $data->name;
                        })
                        ->addColumn('customer_email',function(Deposit $data){
                            $data = User::where('id',$data->user_id)->first();
                            return $data->email;
                        })
                        ->editColumn('amount', function(Deposit $data) {
                            return showAdminAmount($data->amount);
                        })
                        ->editColumn('status', function(Deposit $data) {
                            if($data->status == 'pending'){
                                $status  =  __('pending');
                                $status_sign = 'warning';
                            }elseif($data->status == 'complete'){
                                $status  =  __('completed');
                                $status_sign = 'success';
                            }else{
                                $status  =  __('rejected');
                                $status_sign = 'danger';
                            }

                            return '<div class="btn-group mb-1">
                                    <button type="button" class="btn btn-'.$status_sign.' btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    '.$status .'
                                    </button>
                                    <div class="dropdown-menu" x-placement="bottom-start">
                                        <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.deposits.status',['id1' => $data->id, 'id2' => 'pending']).'">'.__("Pending").'</a>
                                        <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.deposits.status',['id1' => $data->id, 'id2' => 'reject']).'">'.__("Reject").'</a>
                                        <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.deposits.status',['id1' => $data->id, 'id2' => 'complete']).'">'.__("Completed").'</a>
                                    </div>
                                </div>';
                        })
                        ->addColumn('action', function(Deposit $data) {
                            return '<div class="btn-group mb-1">
                                        <button type="button" class="btn btn-primary btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        '.'Actions' .'
                                        </button>
                                        <div class="dropdown-menu" x-placement="bottom-start">
                                            <a href="javascript:;" data-href="' . route('admin.deposit.show',$data->id) . '"  class="dropdown-item" id="applicationDetails" data-toggle="modal" data-target="#details">'.__("Details").'</a>
                                        </div>
                                    </div>';
                         })
                        ->rawColumns(['created_at','customer_name','customer_email','amount','status','action'])
                        ->toJson();
    }

    public function index(){
        return view('admin.deposit.index');
    }

    public function depositdetails($id)
    {
        $deposit = Deposit::findOrFail($id);
        return view('admin.deposit.details',compact('deposit'));
    }

    public function status($id1,$id2){
        $data = Deposit::findOrFail($id1);
        $user = User::findOrFail($data->user_id);

        if($data->status == 'complete'){
          $msg = 'Deposit already completed';
          return response()->json($msg);
        }

        if($data->status == 'reject'){
            $msg = 'Deposit already rejected';
            return response()->json($msg);
        }

        if($id2 == 'pending'){
            $msg = 'Deposit updated successfully';
            return response()->json($msg);
        }

        if($id2 == 'reject'){
            $data->update(['status' => $id2]);

            $trans = new Transaction();
            $trans->email = $user->email;
            $trans->amount = $data->amount;
            $trans->type = "Deposit Reject";
            $trans->profit = "minus";
            $trans->txnid = $data->deposit_number;
            $trans->user_id = $user->id;
            $trans->save();

            $msg = 'Deposit rejected successfully';
            return response()->json($msg);
        }

        $data->update(['status' => $id2]);

        $user->balance += $data->amount;
        $user->save();

        $trans = new Transaction();
        $trans->email = $user->email;
        $trans->amount = $data->amount;
        $trans->type = "Deposit";
        $trans->profit = "plus";
        $trans->txnid = $data->deposit_number;
        $trans->user_id = $user->id;
        $trans->save();

        $data->update(['status' => $id2]);

        if($data->status == 'complete'){
            $gs = Generalsetting::findOrFail(1);
            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $user->email,
                    'type' => "Deposit",
                    'cname' => $user->name,
                    'oamount' => $data->amount,
                    'aname' => "",
                    'aemail' => "",
                    'wtitle' => "",
                ];

                $mailer = new GeniusMailer();
                $mailer->sendAutoMail($data);
            }
            else
            {
                $to = $user->email;
                $subject = " You have deposited successfully.";
                $msg = "Hello ".$user->name."!\nYou have invested successfully.\nThank you.";
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);
            }
        }

        $msg = 'Data Updated Successfully.';
        return response()->json($msg);
    }
}
