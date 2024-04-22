<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Validator;
use Datatables;
use Illuminate\Http\Request;

class ManageCountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function datatables()
    {
         $datas = Country::orderBy('id','desc');

         return Datatables::of($datas)
                            ->addColumn('status', function(Country $data) {
                                $status      = $data->status == 1 ? __('Activated') : __('Deactivated');
                                $status_sign = $data->status == 1 ? 'success'   : 'danger';
    
                                return '<div class="btn-group mb-1">
                                <button type="button" class="btn btn-'.$status_sign.' btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                '.$status .'
                                </button>
                                <div class="dropdown-menu" x-placement="bottom-start">
                                <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.country.status',['id1' => $data->id, 'id2' => 1]).'">'.__("Activate").'</a>
                                <a href="javascript:;" data-toggle="modal" data-target="#statusModal" class="dropdown-item" data-href="'. route('admin.country.status',['id1' => $data->id, 'id2' => 0]).'">'.__("Deactivate").'</a>
                                </div>
                            </div>';
    
                            })  
                            ->addColumn('action', function(Country $data) {

                                return '<div class="btn-group mb-1">
                                  <button type="button" class="btn btn-primary btn-sm btn-rounded dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    '.'Actions' .'
                                  </button>
                                  <div class="dropdown-menu" x-placement="bottom-start">
                                    <a href="' . route('admin.country.edit',$data->id) . '"  class="dropdown-item">'.__("Edit").'</a>
                                  </div>
                                </div>';
  
                              })
                            ->rawColumns(['status','action'])
                            ->toJson();
    }


    public function index()
    {
        return view('admin.countries.index');
    }

    public function status($id1,$id2)
    {
        $data = Country::findOrFail($id1);
        $data->status = $id2;
        $data->update();

        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
    }

    public function edit($id){
        $data['data'] = Country::findOrFail($id);

        return view('admin.countries.edit',$data);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|unique:countries,name,'.$id,
            'phone_code' => 'required|unique:countries,phone_code,'.$id,
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }


        $data = Country::findOrFail($id);
        $input = $request->all();


        $data->update($input);

        $msg = 'Data Updated Successfully.'.' '.'<a href="'.route('admin.country.index').'"> '.__('View Lists.').'</a>';
        return response()->json($msg);
        
    }
}
