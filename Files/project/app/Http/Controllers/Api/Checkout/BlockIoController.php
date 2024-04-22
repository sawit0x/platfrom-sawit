<?php

namespace App\Http\Controllers\Api\Checkout;

use App;
use URL;
use Auth;
use Hash;
use Config;
use Session;
use Redirect;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Classes\BlockIO;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Classes\GeniusMailer;
use App\Models\Generalsetting;
use App\Classes\CoinPaymentsAPI;
use App\Models\UserNotification;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Invest;
use Illuminate\Support\Facades\Input;
use App\Models\PaymentGateway;
use App\Models\Plan;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Facades\Session as FacadesSession;
use Illuminate\Support\Str;

class BlockIoController extends Controller
{
    public function blockioInvest()
    {
        return view('user.invest.blockio');
    }


    public function blockiocallback(Request $request)
    {
        $notifyID = $request['notification_id'];
        $amountRec = $request['data']['amount_received'];

            if (Invest::where('notify_id',$notifyID)->exists()){

                $order = Invest::where('notify_id',$notifyID)->where('payment_status','pending')->first();

                $data['txnid'] =  $request['data']['txid'];
                $data['payment_status'] = "completed";
                $order->update($data);

                $trans = new Transaction();
                $trans->email = auth()->user()->email;
                $trans->amount = $order->amount;
                $trans->type = "Invest";
                $trans->txnid = $order->transaction_no;
                $trans->user_id = $order->user_id;
                $trans->save();

                $gs =  Generalsetting::findOrFail(1);
                $user = User::whereId($order->user_id)->first();

                if($gs->is_smtp == 1)
                {
                    $data = [
                        'to' => $user->email,
                        'type' => "Invest",
                        'cname' => $user->name,
                        'oamount' => $order->amount,
                        'aname' => "",
                        'aemail' => "",
                        'wtitle' => "",
                    ];

                    $mailer = new GeniusMailer();
                    $mailer->sendAutoMail($data);
                }
                else
                {
                    $to = $order->customer_email;
                    $subject = " You have invested successfully.";
                    $msg = "Hello ".$order->customer_name."!\nYou have invested successfully.\nThank you.";
                    $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                    mail($to,$subject,$msg,$headers);
                }
            }


    }

    function curlGetCall($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec ($ch);
        curl_close ($ch);

        return $data;
    }


    public function deposit(Request $request)
    {
        $invest = Invest::findOrFail($request->invest_id);
        $user = User::findOrFail($invest->user_id);

        if($request->amount > 0){
            $methods = $request->method;
             $version = 2;
             $coin = "BTC";
             $my_api_key = '';

             if($methods == "block.io.ltc"){
                $blockinfo    = PaymentGateway::whereKeyword('block.io.ltc')->first();
                $blocksettings= $blockinfo->convertAutoData();
                $coin = "Litecoin";
                $my_api_key = $blocksettings['blockio_api_ltc'];

            }elseif($methods == "block.io.btc"){
                $blockinfo    = PaymentGateway::whereKeyword('block.io.btc')->first();
                $blocksettings= $blockinfo->convertAutoData();
                $coin = "Bitcoin";
                $my_api_key = $blocksettings['blockio_api_btc'];

            }elseif ($methods == "block.io.dgc"){
                $coin = "Dogecoin";
                $blockinfo    = PaymentGateway::whereKeyword('block.io.dgc')->first();
                $blocksettings= $blockinfo->convertAutoData();
                $my_api_key = $blocksettings['blockio_api_dgc'];

            }

            $acc = Auth::user()->id;
            $item_number = $invest->transaction_no;

            $item_amount = $request->amount;
            $currency_code = $request->currency_code;


            $secret = $blocksettings['secret_string'];

            $my_callback_url = route('api.checkout.blockio.notify');

            $block_io = new BlockIO($my_api_key, $secret, $version);

            $biorate = 1;

            $coin_amount = round($item_amount / $biorate, 8);


            $root_url = 'https://block.io/api/v2/';
            $addObject = $block_io->get_new_address(array());

            $address = $addObject->data->address;


            $notifyObject = $block_io->create_notification(array('type' => 'address', 'address' => $address, 'url' => $my_callback_url));


            $notifyID = $notifyObject->data->notification_id;

            $invest->method = $request->method;
            $invest->coin_amount = $coin_amount;
            $invest->notify_id = $notifyID;
            $invest->update();

            $qrcode_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=bitcoin:".$address."?amount=".$coin_amount."&choe=UTF-8";
            return redirect()->route('api.user.invest.checkout',$invest->id)->with('success',['address' => $address,'qrcode_url' => $qrcode_url,]);

        }
        return redirect()->route('api.user.invest.checkout',$invest->id)->with('error','Please enter a valid amount.')->withInput();

    }
}
