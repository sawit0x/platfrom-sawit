<?php

namespace App\Http\Controllers\Api\Front;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Http\Resources\TeamResource;
use App\Models\AccountProcess;
use App\Models\Blog;
use App\Models\Currency;
use App\Models\Feature;
use App\Models\Generalsetting;
use App\Models\Language;
use App\Models\Page;
use App\Models\Pagesetting;
use App\Models\Plan;
use App\Models\Review;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FrontendController extends Controller
{
    public function __construct()
    {
        $this->ps = Pagesetting::first();
        $this->gs = Generalsetting::first();
    }

    public function banner(){
        $data['title'] = $this->ps->hero_title;
        $data['subtitle'] = $this->ps->hero_subtitle;
        $data['button_url'] = $this->ps->hero_btn_url;
        $data['video_link'] = $this->ps->hero_link;

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function about(){
        $data['subtitle'] = $this->ps->about_title;
        $data['description'] = $this->ps->about_text;
        $data['read_more_link'] = $this->ps->about_link;
        $data['photo'] = $this->ps->about_photo != NULL ? url('/') . '/assets/images/' . $this->ps->about_photo : 'NULL';

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function profit_calculator(){
        $data['subtitle'] = $this->ps->profit_title;
        $data['description'] = $this->ps->profit_text;
        $data['plans'] = Plan::whereStatus(1)->orderBy('id','desc')->get();
        $data['banner'] = $this->ps->profit_banner != NULL ? url('/') . '/assets/images/' . $this->ps->profit_banner : 'NULL';

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function calculate(Request $request){
        $rules = [
            'plan_id'=>'required',
            'amount'=>'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
        }

        $plan = Plan::whereId($request->plan_id)->first();

        if($plan->invest_type == 'range'){
            if($request->amount < $plan->min_amount || $request->amount > $plan->max_amount){
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => 'Request amount should be between of '. $plan->min_amount.' minimum ' .$plan->max_amount. ' maximum amount']]);
            }
        }else{
            if($plan->fixed_amount != $request->amount){
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => 'Request amount should be '.$plan->fixed_amount]]);
            }
        }
        $percentage = ($request->amount * $plan->profit_percentage)/100;
        $profit = $request->amount + $percentage;

        $msg = showPrice($percentage);
        return response()->json(['status' => true, 'data' => $msg, 'error' => []]);
    }

    public function plans(){
        $data = Plan::whereStatus(1)
                     ->orderBy('id','desc')
                     ->take(6)
                     ->get();

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function allPlans(){
        $data = Plan::whereStatus(1)
                     ->orderBy('id','desc')
                     ->get();

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }


    public function partners(){
        $data = Partner::orderBy('id','desc')
                        ->get();
        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function transactions(){
        $data['deposits'] = getDeposits();
        $data['withdraws'] = getWithdraws();
        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function how_to_start(){
        $data['subtitle'] = $this->ps->start_title;
        $data['description'] = $this->ps->start_text;
        $data['banner'] = $this->ps->start_photo != NULL ? url('/') . '/assets/images/' . $this->ps->start_photo : 'NULL';
        $data['steps'] = AccountProcess::orderBy('id','desc')->get();

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function features(){
        $data['subtitle'] = $this->ps->feature_title;
        $data['description'] = $this->ps->feature_text;
        $data['features'] = Feature::orderBy('id','desc')->get();

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function referrals(){
        $data['subtitle'] = $this->ps->referral_title;
        $data['description'] = $this->ps->referral_text;
        $data['referral_percentages'] = $this->ps->referral_percentage != NULL ? json_decode($this->ps->referral_percentage,true) : 'NULL';

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function teams(){
        $data['subtitle'] = $this->ps->team_title;
        $data['description'] = $this->ps->team_text;
        $teams = Team::orderBy('id','desc')->get();

        return response()->json(['status' => true, 'data' =>['homepage'=>$data,'teams'=>TeamResource::collection($teams)], 'error' => []]);
    }

    public function testimonials(){
        $data = Review::orderBy('id','desc')->get();
        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function ctas(){
        $data['title'] = $this->ps->call_title;
        $data['subtitle'] = $this->ps->call_subtitle;
        $data['cta_link'] = $this->ps->call_link;
        $data['background_color'] = $this->ps->call_bg;

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function blogs(){
        $data['subtitle'] = $this->ps->blog_title;
        $data['description'] = $this->ps->blog_text;
        $blogs = Blog::orderBy('id','desc')->limit(3)->get();

        return response()->json(['status' => true, 'data' => ['homepage'=>$data,'blogs'=> BlogResource::collection($blogs)], 'error' => []]);
    }

    public function payment_gateways(){
        $data['subtitle'] = $this->ps->brand_title;
        $data['description'] = $this->ps->brand_text;
        $data['banner'] = $this->ps->brand_photo != NULL ? url('/') . '/assets/images/' . $this->ps->brand_photo : 'NULL';
        $data['payment_gateways'] = getBrands();

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function defaultLanguage() {
        try{
            $language = Language::where('is_default','=',1)->first();
            if(!$language){
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Language Found']]);
            }
            $data_results = file_get_contents(resource_path().'/lang/'.$language->file);
            $lang = json_decode($data_results);
            return response()->json(['status' => true, 'data' => ['basic' => $language ,'languages' => $lang], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function language($id) {
        try{
            $language = Language::find($id);
            if(!$language){
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Language Found']]);
            }
            $data_results = file_get_contents(resource_path().'/lang/'.$language->file);
            $lang = json_decode($data_results);
            return response()->json(['status' => true, 'data' => ['basic' => $language ,'languages' => $lang], 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function languages() {
        try{
            $languages = Language::all();
            return response()->json(['status' => true, 'data' => $languages, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function defaultCurrency() {
        try{
            $currency = Currency::where('is_default','=',1)->first();
            if(!$currency){
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Currency Found']]);
        }
            return response()->json(['status' => true, 'data' => $currency, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }


    public function currency($id) {
        try{
            $currency = Currency::find($id);
            if(!$currency){
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'No Currency Found']]);
        }
         return response()->json(['status' => true, 'data' => $currency, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function currencies() {
        try{
            $currencies = Currency::all();
            return response()->json(['status' => true, 'data' => $currencies, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function pages(){
        try{
            $pages = Page::whereStatus(1)->orderBy('id','desc')->get();
            return response()->json(['status' => true, 'data' => $pages, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function page($slug)
    {
        try{
            $page = Page::whereSlug($slug)->first();
            return response()->json(['status' => true, 'data' => $page, 'error' => []]);
        }
        catch(\Exception $e){
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function info(){
        $data['latitude'] = $this->gs->latitude;
        $data['longitude'] = $this->gs->longitude;
        $data['subtitle'] = $this->ps->side_title;
        $data['description'] = $this->ps->side_text;
        $data['address'] = $this->ps->street;
        $data['phone'] = $this->ps->phone;
        $data['email'] = $this->ps->email;

        return response()->json(['status' => true, 'data' => $data, 'error' => []]);
    }

    public function contact(Request $request)
    {
        try {
            $gs = Generalsetting::findOrFail(1);

            $subject = $request->subject;

            $to = $this->ps->contact_email;
            $name = $request->name;
            $phone = $request->phone;
            $from = $request->email;
            $msg = "Name: ".$name."\Phone: ".$phone."\nEmail: ".$from."\nMessage: ".$request->message;

            if($gs->is_smtp)
            {
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new GeniusMailer();
                $mailer->sendCustomMail($data);
            }
            else
            {
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);
            }

            return response()->json(['status' => true, 'data' => ['message' => 'Email Sent Successfully!'], 'error' => []]);
        } catch (\Throwable $th) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $th->getMessage()]]);
        }

    }

}
