<?php

namespace App\Http\Controllers\Admin;

use App\Classes\GeniusMailer;
use Datatables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Follow;
use App\Models\Generalsetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
        {
            $this->middleware('auth:admin');
        }

        public function datatables()
        {
             $datas = User::orderBy('id','desc');

             return Datatables::of($datas)
                                ->addColumn('action', function(User $data) {
                                    return '<div class="btn-group mb-1">
                                        <button type="button" class="btn btn-primary btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        '.'Actions' .'
                                        </button>
                                        <div class="dropdown-menu" x-placement="bottom-start">
                                        <a href="' . route('admin-user-show',$data->id) . '"  class="dropdown-item">'.__("Details").'</a>
                                        <a href="' . route('admin-user-edit',$data->id) . '" class="dropdown-item" >'.__("Edit").'</a>
                                        <a href="javascript:;" class="dropdown-item send" data-email="'. $data->email .'" data-toggle="modal" data-target="#vendorform">'.__("Send").'</a>
                                        <a href="javascript:;" data-toggle="modal" data-target="#deleteModal" class="dropdown-item" data-href="'.  route('admin-user-delete',$data->id).'">'.__("Delete").'</a>
                                        </div>
                                    </div>';
                                })

                                ->addColumn('status', function(User $data) {
                                    $status      = $data->is_banned == 1 ? __('Block') : __('Unblock');
                                    $status_sign = $data->is_banned == 1 ? 'danger'   : 'success';

                                        return '<div class="btn-group mb-1">
                                        <button type="button" class="btn btn-'.$status_sign.' btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            '.$status .'
                                        </button>
                                        <div class="dropdown-menu" x-placement="bottom-start">
                                            <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin-user-ban',['id1' => $data->id, 'id2' => 0]).'">'.__("Unblock").'</a>
                                            <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin-user-ban',['id1' => $data->id, 'id2' => 1]).'">'.__("Block").'</a>
                                        </div>
                                        </div>';
                                })
                                ->rawColumns(['action','status'])
                                ->toJson();
        }

        public function index()
        {
            return view('admin.user.index');
        }

        public function image()
        {
            return view('admin.generalsetting.user_image');
        }

        public function show($id)
        {
            $data = User::findOrFail($id);
            $data['data'] = $data;
            return view('admin.user.show',$data);
        }

        public function ban($id1,$id2)
        {
            $user = User::findOrFail($id1);
            $user->is_banned = $id2;
            $user->update();
            $msg = 'Data Updated Successfully.';
            return response()->json($msg);
        }


        public function edit($id)
        {
            $data = User::findOrFail($id);
            return view('admin.user.edit',compact('data'));
        }


        public function update(Request $request, $id)
        {
            $rules = [
                   'photo' => 'mimes:jpeg,jpg,png,svg',
                    ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
              return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }

            $user = User::findOrFail($id);
            $data = $request->all();
            if ($file = $request->file('photo'))
            {
                $name = Str::random(8).time().'.'.$file->getClientOriginalExtension();
                $file->move('assets/images',$name);
                if($user->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/'.$user->photo)) {
                        unlink(public_path().'/assets/images/'.$user->photo);
                    }
                }
                $data['photo'] = $name;
            }
            $user->update($data);
            $msg = 'Customer Information Updated Successfully.';
            return response()->json($msg);
        }

        public function adddeduct(Request $request){
            $user = User::whereId($request->user_id)->first();
            $gs = Generalsetting::first();

            $amount = baseCurrencyAmount($request->amount);
            if($user){
                if($request->type == 'add'){
                    $user->increment('balance',$amount);
                    if($gs->is_smtp == 1)
                    {
                        $data = [
                            'to' => $user->email,
                            'type' => "credited",
                            'cname' => $user->name,
                            'oamount' => "",
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
                       $subject = "Your Account has been credited";
                       $msg = "Hello ".$user->name."!\nYour account has been credited by admin.\nThank you.";
                       $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                       mail($to,$subject,$msg,$headers);
                    }
                    return redirect()->back()->with('message','User balance added');
                }else{
                    if($user->balance>=$request->amount){
                        $user->decrement('balance',$amount);
                        if($gs->is_smtp == 1)
                        {
                            $data = [
                                'to' => $user->email,
                                'type' => "debited",
                                'cname' => $user->name,
                                'oamount' => "",
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
                           $subject = "Your Account has been debited";
                           $msg = "Hello ".$user->name."!\nYour account has been debited by admin.\nThank you.";
                           $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                           mail($to,$subject,$msg,$headers);
                        }
                        return redirect()->back()->with('message','User balance deduct!');
                    }else{
                        return redirect()->back()->with('warning','User don,t have sufficient balance!');
                    }
                }
            }else{
                return redirect()->back()->with('warning','User not found!');
            }
        }

        public function withdraws(){
            return view('admin.user.withdraws');
        }

          public function withdrawdatatables()
          {
               $datas = Withdraw::orderBy('id','desc');

               return Datatables::of($datas)
                                  ->addColumn('email', function(Withdraw $data) {
                                      $email = $data->user != NULL ? $data->user->email : __('Customer Deleted');
                                      return $email;
                                  })
                                  ->addColumn('phone', function(Withdraw $data) {
                                    $phone = $data->user != NULL ? $data->user->phone : __('Customer Deleted');
                                    return $phone;
                                })
                                ->editColumn('status', function(Withdraw $data) {
                                    $status = ucfirst($data->status);
                                    return $status;
                                })
                                ->editColumn('amount', function(Withdraw $data) {
                                    return showAdminAmount($data->amount);
                                })
                                ->editColumn('created_at', function(Withdraw $data) {
                                    $date = $data->created_at->diffForHumans();
                                    return $date;
                                })
                                ->editColumn('status', function(Withdraw $data) {
                                    if($data->status == 'pending'){
                                        $status  =  _('pending');
                                        $status_sign = 'warning';
                                    }elseif($data->status == 'completed'){
                                        $status  =  _('completed');
                                        $status_sign = 'success';
                                    }else{
                                        $status  =  _('rejected');
                                        $status_sign = 'danger';
                                    }

                                    return '<div class="btn-group mb-1">
                                            <button type="button" class="btn btn-'.$status_sign.' btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            '.$status .'
                                            </button>
                                            <div class="dropdown-menu" x-placement="bottom-start">
                                                <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.withdraw.status',['id1' => $data->id, 'id2' => 'pending']).'">'.__("Pending").'</a>
                                                <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.withdraw.status',['id1' => $data->id, 'id2' => 'rejected']).'">'.__("Reject").'</a>
                                                <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.withdraw.status',['id1' => $data->id, 'id2' => 'completed']).'">'.__("Completed").'</a>
                                            </div>
                                        </div>';
                                })
                                ->addColumn('action', function(Withdraw $data) {
                                    return '<div class="btn-group mb-1">
                                            <button type="button" class="btn btn-primary btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            '.'Actions' .'
                                            </button>
                                            <div class="dropdown-menu" x-placement="bottom-start">
                                                <a href="javascript:;" data-href="' . route('admin.withdraw.show',$data->id) . '"  class="dropdown-item" id="applicationDetails" data-toggle="modal" data-target="#details">'.__("Details").'</a>
                                            </div>
                                        </div>';
                                })
                            ->rawColumns(['name','email','amount','status','action'])
                            ->toJson();
          }


    public function withdrawdetails($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        return view('admin.user.withdraw-details',compact('withdraw'));
    }

    public function status($id1,$id2){
        $gs = Generalsetting::first();
        $admin = Admin::first();

        $withdraw = Withdraw::findOrFail($id1);
        $user = User::findOrFail($withdraw->user->id);

        if($withdraw->status == 'completed'){
          $msg = 'Withdraw already completed';
          return response()->json($msg);
        }

        if($withdraw->status == 'rejected'){
            $msg = 'Withdraw already rejected';
            return response()->json($msg);
        }

        if($id2 == 'pending'){
            $msg = 'Withdraw updated successfully';
            return response()->json($msg);
        }

        if($id2 == 'rejected'){
            $data['status'] = "rejected";
            $withdraw->update($data);

            $user->balance = $user->balance + $withdraw->amount + $withdraw->fee;
            $user->update();

            $trans = new Transaction();
            $trans->email = $user->email;
            $trans->amount = $withdraw->amount + $withdraw->fee;
            $trans->type = "Payout Rejected";
            $trans->profit = "plus";
            $trans->txnid = Str::random(12);
            $trans->user_id = $user->id;
            $trans->save();

            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $user->email,
                    'type' => "Withdraw",
                    'cname' => $user->name,
                    'oamount' => $withdraw->amount + $withdraw->fee,
                    'aname' => $admin->name,
                    'aemail' => $admin->email,
                    'wtitle' => "",
                ];

                $mailer = new GeniusMailer();
                $mailer->sendAutoMail($data);
            }
            else
            {
               $to = $user->email;
               $subject = "Withdraw Rejected Successfully.";
               $msg = "Hello ".$user->name."!\nYour withdraw rejected by admin.\nThank you.";
               $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
               mail($to,$subject,$msg,$headers);
            }


            $msg = __('Withdraw Rejected Successfully.');
            return response()->json($msg);
        }

        if($id2 == 'completed'){

            $data['status'] = "completed";
            $withdraw->update($data);

            $trans = new Transaction();
            $trans->email = $user->email;
            $trans->amount = $withdraw->amount + $withdraw->fee;
            $trans->type = "Payout Accepted";
            $trans->profit = "plus";
            $trans->txnid = $withdraw->txnid;
            $trans->user_id = $user->id;
            $trans->save();

            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $user->email,
                    'type' => "Withdraw",
                    'cname' => $user->name,
                    'oamount' => $withdraw->amount + $withdraw->fee,
                    'aname' => $admin->name,
                    'aemail' => $admin->email,
                    'wtitle' => "",
                ];

                $mailer = new GeniusMailer();
                $mailer->sendAutoMail($data);
            }
            else
            {
               $to = $user->email;
               $subject = "Withdraw Accepted Successfully.";
               $msg = "Hello ".$user->name."!\nYour withdraw accepted by admin.\nThank you.";
               $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
               mail($to,$subject,$msg,$headers);
            }

            $msg = __('Withdraw Accepted Successfully.');
            return response()->json($msg);
        }

    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if($user->transactions->count() > 0)
        {
            foreach ($user->transactions as $transaction) {
                $transaction->delete();
            }
        }

        if($user->withdraws->count() > 0)
        {
            foreach ($user->withdraws as $withdraw) {
                $withdraw->delete();
            }
        }

        if($user->deposits->count() > 0)
        {
            foreach ($user->deposits as $deposit) {
                $deposit->delete();
            }
        }

        if($user->balanceTransfers->count() > 0)
        {
            foreach ($user->balanceTransfers as $balanceTransfer) {
                $balanceTransfer->delete();
            }
        }

        @unlink('/assets/images/'.$user->photo);
        $user->delete();

        $msg = 'Data Deleted Successfully.';
        return response()->json($msg);
    }

}
