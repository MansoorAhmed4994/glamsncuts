<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client\ManualOrders;
use App\Models\Riders;
use App\Models\Inventory;
use App\Models\Orderpayments;
use App\Models\Order_details;
use App\Models\Client\Customers;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\MNPTraits;
use App\Traits\TraxTraits;
use App\Traits\ManualOrderTraits;

use Carbon\Carbon;
use DB;

class AccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     

    use ManualOrderTraits;
    use MNPTraits;
    use TraxTraits;
    
    protected $pagination;
     

    public function __construct() 
    {
        $this->pagination = '20';
    }
    public function OrderFieldList()
    {
        return array(
            'manual_orders.consignment_id',
            'manual_orders.id',
            'manual_orders.payment_status',
            'manual_orders.customers_id',
            'manual_orders.description',
            'manual_orders.receiver_number',
            'customers.first_name',
            'manual_orders.reciever_address',
            'customers.last_name',
            'customers.number',
            'customers.address',
            'manual_orders.price',
            'manual_orders.images',
            'manual_orders.total_pieces',
            'manual_orders.date_order_paid',
            'manual_orders.status',
            'manual_orders.created_at',
            'manual_orders.updated_at',
            'manual_orders.status_reason',
            'manual_orders.fare',
            'manual_orders.price',
            'manual_orders.advance_payment',
            'manual_orders.cod_amount',
            'manual_orders.payment_status',
            'manual_orders.shipment_tracking_status',
            
            
            
            'orderpayments.amount',
            'orderpayments.charges',
            'orderpayments.gst',
            'orderpayments.payment_id',
            'orderpayments.payable'); 

    }
    public function list_query()
    {
        $query = ManualOrders::query();
        $list = $query->leftJoin('orderpayments', 'orderpayments.order_id', '=', 'manual_orders.id')->
        leftJoin('customers', 'customers.id', '=', 'manual_orders.customers_id')->
        select($this->OrderFieldList());
        return $query;
    }
    
    public function index(Request $request)
    {
        $list = $this->list_query();
        if($request->date_from)
        {
            // dd($request->date_from);
            $from_date = $request->date_from;
            $to_date = $request->date_to;  
            $list->whereBetween('manual_orders.created_at', [$from_date, $to_date]); 
        }
        if($request->shipment_tracking_status)
        {
            // dd($request->date_from);
            $shipment_tracking_status = $request->shipment_tracking_status; 
            $list->where('manual_orders.shipment_tracking_status','=',$shipment_tracking_status); 
        }
        if($request->payment_status)
        {
            // dd($request->date_from);
            $payment_status = $request->payment_status; 
            $list->where('manual_orders.payment_status','=',$payment_status); 
        }
        if($request->order_by)
        { 
            $order_by = $request->order_by;  
            $list->orderBy($order_by,'ASC');
        }
        if(!$request->order_by)
        {  
            $list->orderBy('manual_orders.id','Desc');
        }
        if($request->search_order_id)
        {  
            $list->where('manual_orders.id','=',$request->search_order_id);
        }
        
        
        
        
        $list = $list->where([['manual_orders.consignment_id','>','0']])
        ->paginate($this->pagination);
        
        
        // dd($list);
        return view('admin.accounts.orders')->with('list',$list);  
        //
    }
     
     

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
     
     
     
     
    public function UpdateShipmentPaymentStatus(Request $request,$id,$order_id)
    {
        //dd($request->consignment_id);
        $data = $this->GetShipmentPaymentStatus($id);
        //return response()->json(['messege' => $data]);
        //dd($id);
        if($data->status == 0)
        {
            // if($data->current_payment_status == "Payment - Paid")
            // { 
                // dd($data);
                
                $matchThese = ['consignment_id' => $id, 'order_id' => $order_id, 'payment_id' => $data->payments[0]->id];
                $orderpayment = Orderpayments::where($matchThese)->first();
                //  dd()/
                if(!$orderpayment)
                { 
                    $Orderpayments = Orderpayments::create([
                    'order_id' => $order_id,
                    'consignment_id' => $id,
                    'cash_handling_charges' => (isset($data->charges->cash_handling_charges) ? $data->charges->cash_handling_charges : '0'),  
                    'fuel_surcharge' => $data->charges->fuel_surcharge,  
                    'weight_charges' => (isset($data->charges->weight_charges) ? $data->charges->weight_charges : '0') ,  
                    'current_payment_status' => (isset($data->current_payment_status) ? $data->current_payment_status  : '0'),  
                    'message' => $data->message,  
                    'amount' => $data->payments[0]->amount,  
                    'charges' => $data->payments[0]->charges,  
                    'datetime' => $data->payments[0]->datetime,  
                    'gst' => $data->payments[0]->gst,  
                    'payment_id' => $data->payments[0]->id,  
                    'payable' => $data->payments[0]->payable,  
                    'type' => $data->payments[0]->type,      
                    'created_by' => Auth::id(),  
                    'updated_by' => Auth::id(),  
                    'status' => 'active',
                    ]);
                    // dd($data->current_payment_status);
                    $ManualOrders = ManualOrders::find($order_id);
     
                    $ManualOrders->payment_status = $data->current_payment_status;
                     
                    $ManualOrders->save();
                }
            
            // }
            
            return response()->json(['messege' => $data]);
        }
        if($data->status == 1 && $data->message == "No Payments")
        {
            $ManualOrders = ManualOrders::find($order_id);

            $ManualOrders->payment_status = "No Payments";
             
            $ManualOrders->save();
        }
        return response()->json(['messege' => $data]);
        
    } 
     
    
    public function UpdateBulkShipmentPaymentStatus(Request $request)
    {
        
        // $order_action = $request->order_action;
        $order_action = $request->order_action;
        $order_ids = $request->order_ids;
       
        if($order_action == 'updateshipmentspayment')
        {
            $explode_id = explode(',', $order_ids);
            $ManualOrdersLists = ManualOrders::select('consignment_id','id')->whereIn('id',$explode_id)->get();
            foreach($ManualOrdersLists as $ManualOrdersList)
            {
            
            $id = $ManualOrdersList->consignment_id;
            $order_id = $ManualOrdersList->id;
            // echo $ManualOrdersList->id."<br>";
        
            $data = $this->GetShipmentPaymentStatus($id);
            //return response()->json(['messege' => $data]);
            //dd($id);
            echo $order_id.'<br>';
            if($data->status == 0)
            {
                // if($data->current_payment_status == "Payment - Paid")
                // { 
                    // dd($data);
                    
                    $matchThese = ['consignment_id' => $id, 'order_id' => $order_id, 'payment_id' => $data->payments[0]->id];
                    $orderpayment = Orderpayments::where($matchThese)->first();
                    //  dd()/
                    if(!$orderpayment)
                    { 
                        
                        // dd($data);
                        $Orderpayments = Orderpayments::create([
                        'order_id' => $order_id,
                        'consignment_id' => $id,
                        'cash_handling_charges' => (isset($data->charges->cash_handling_charges) ? $data->charges->cash_handling_charges : '0'),  
                        'fuel_surcharge' => $data->charges->fuel_surcharge,  
                        'weight_charges' => (isset($data->charges->weight_charges) ? $data->charges->weight_charges : '0') ,  
                        'current_payment_status' => (isset($data->current_payment_status) ? $data->current_payment_status  : '0'),  
                        'message' => $data->message,  
                        'amount' => $data->payments[0]->amount,  
                        'charges' => $data->payments[0]->charges,  
                        'datetime' => (string)$data->payments[0]->datetime,  
                        'gst' => $data->payments[0]->gst,  
                        'payment_id' => $data->payments[0]->id,  
                        'payable' => $data->payments[0]->payable,  
                        'type' => $data->payments[0]->type,      
                        'created_by' => Auth::id(),  
                        'updated_by' => Auth::id(),  
                        'status' => 'active',
                        ]);
                        
                        $ManualOrders = ManualOrders::find($order_id);
         
                        $ManualOrders->payment_status = $data->current_payment_status;
                         
                        $status_save = $ManualOrders->save();
                        //print_r($status_save)."<br>";
                    }
                
                
                // }
                //return response()->json(['messege' => $data]);
                }
                
                if($data->status == 1 && $data->message == "No Payments")
                {
                    $ManualOrders = ManualOrders::find($order_id);
     
                    $ManualOrders->payment_status = "No Payments";
                     
                    $ManualOrders->save();
                }
                
            }
        }
        elseif($order_action == 'dispatched')
        {
            $explode_id = explode(',', $order_ids);
            //dd($explode_id);
            $ManualOrder = ManualOrders::whereIn('id',$explode_id)->update(['status' => 'dispatched']);
            //dd($ManualOrder);
        }
        if($order_action == 'updateshipmentstracking')
        {
            // dd('working');
            $explode_id = explode(',', $order_ids);
            $ManualOrdersLists = ManualOrders::select('consignment_id','id')->whereIn('id',$explode_id)->get();
            // dd($trax_order_details);
            foreach($ManualOrdersLists as $ManualOrdersList)
            {
                $data = $this->TrackTraxOrder($ManualOrdersList->consignment_id,0);
                // dd($data);
                $order_id = $ManualOrdersList->id;
                if($data->status == 0)
                {
                    // dd($data->details->tracking_history);
                    if(isset($data->details->tracking_history))
                    { 
                        $tracking_status = $data->details->tracking_history[0]->status;
                        
                        
                        
                        $ManualOrders = ManualOrders::find($order_id);
         
                        $ManualOrders->shipment_tracking_status = $tracking_status;
                         
                        $status_save = $ManualOrders->save();
                        //print_r($status_save)."<br>";
                    }
                
                
                // }
                //return response()->json(['messege' => $data]);
                } 
                
            }
        }
        
        
        return back()->withInput();
        //dd($ManualOrdersLists);
    }
    
    
    public function CroneUpdateShipmentPaymentStatuss()
    { 
        $from_date= date('Y-m-01');
        $to_date = date('Y-m-t');
        $ManualOrdersLists = ManualOrders::select('consignment_id','id','payment_status','shipment_tracking_status')
        ->where([
            ['consignment_id','>','0'],
            ['payment_status','!=' ,'Payment - Paid'],
            ['payment_status','!=' ,'Charges - Deducted']
        ])
        ->paginate(200);
        
        
        foreach($ManualOrdersLists as $ManualOrdersList)
        {
            
            $id = $ManualOrdersList->consignment_id;
            $order_id = $ManualOrdersList->id;
            // echo $ManualOrdersList->id."<br>";
        
            $data = $this->GetShipmentPaymentStatus($id);
            //return response()->json(['messege' => $data]);
            //dd($id);
            // echo $order_id.'<br>';
            if($data->status == 0)
            {
                
                        echo $order_id.', ts: '.$ManualOrdersList->shipment_tracking_status.'<br>';
                 
                    $payment_id = $data->payments[0]->id;
                    $matchThese = ['consignment_id' => $id, 'order_id' => $order_id, 'payment_id' =>$payment_id];
                    $orderpayment = Orderpayments::where($matchThese)
                    ->where(function ($query) use ($payment_id) {
                        $query->where('payment_id','=',$payment_id)
                        ->orWhere('payment_id','=','0');
                    })
                    ->get();
                    
                    if($orderpayment->count() == 0)
                    { 
                        
                        // dd($data);
                        $Orderpayments = Orderpayments::create([
                        'order_id' => $order_id,
                        'consignment_id' => $id,
                        'cash_handling_charges' => (isset($data->charges->cash_handling_charges) ? $data->charges->cash_handling_charges : '0'),  
                        'fuel_surcharge' => $data->charges->fuel_surcharge,  
                        'weight_charges' => (isset($data->charges->weight_charges) ? $data->charges->weight_charges : '0') ,  
                        'current_payment_status' => (isset($data->current_payment_status) ? $data->current_payment_status  : '0'),  
                        'message' => $data->message,  
                        'amount' => $data->payments[0]->amount,  
                        'charges' => $data->payments[0]->charges,  
                        'datetime' => (string)$data->payments[0]->datetime,  
                        'gst' => $data->payments[0]->gst,  
                        'payment_id' => $data->payments[0]->id,  
                        'payable' => $data->payments[0]->payable,  
                        'type' => $data->payments[0]->type,      
                        'created_by' => Auth::id(),  
                        'updated_by' => Auth::id(),  
                        'status' => 'active',
                        ]);
                        
                        $ManualOrders = ManualOrders::find($order_id);
         
                        $ManualOrders->payment_status = $data->current_payment_status;
                         
                        $status_save = $ManualOrders->save();
                        
                        //print_r($status_save)."<br>";
                    }
                    else
                    {
                        foreach($orderpayment as $orderpaymentlist)
                        {
                            // dd($orderpaymentlist);
                            if($orderpaymentlist->payment_id == '0')
                            {
                                Orderpayments::where('id',$orderpaymentlist->id)
                                ->update([
                                    'order_id' => $order_id,
                                    'consignment_id' => $id,
                                    'cash_handling_charges' => (isset($data->charges->cash_handling_charges) ? $data->charges->cash_handling_charges : '0'),  
                                    'fuel_surcharge' => $data->charges->fuel_surcharge,  
                                    'weight_charges' => (isset($data->charges->weight_charges) ? $data->charges->weight_charges : '0') ,  
                                    'current_payment_status' => (isset($data->current_payment_status) ? $data->current_payment_status  : '0'),  
                                    'message' => $data->message,  
                                    'amount' => $data->payments[0]->amount,  
                                    'charges' => $data->payments[0]->charges,  
                                    'datetime' => (string)$data->payments[0]->datetime,  
                                    'gst' => $data->payments[0]->gst,  
                                    'payment_id' => $data->payments[0]->id,  
                                    'payable' => $data->payments[0]->payable,  
                                    'type' => $data->payments[0]->type,   
                                    'updated_by' => Auth::id(),  
                                    'status' => 'active',
                                    ]);
                                    // dd('entry done');
                            }
                            else
                            {
                                // dd('not intery');
                            }
                        }
                        
                    }
                
                
                // }
                //return response()->json(['messege' => $data]);
            }
            elseif($data->status == 1)
            {
                if($data->message == "No Payments")
                {
                    $matchThese = ['consignment_id' => $id, 'order_id' => $order_id];
                    $orderpayment = Orderpayments::where($matchThese)->first();
                    //  dd()/
                    if(!$orderpayment)
                    { 
                        $mytime = Carbon::now();
                        $current_time =  $mytime->toDateTimeString();
                        dd($current_time);
                        $Orderpayments = Orderpayments::create([
                        'order_id' => $order_id,
                        'consignment_id' => $id,
                        'cash_handling_charges' => 0,  
                        'fuel_surcharge' => "",  
                        'weight_charges' => 0,  
                        'current_payment_status' => 0,  
                        'message' => $data->message,  
                        'amount' => 0,  
                        'charges' => 0,  
                        'datetime' => $current_time,  
                        'gst' => 0,  
                        'payment_id' => 0,  
                        'payable' => 0,  
                        'type' => 0,      
                        'created_by' => Auth::id(),  
                        'updated_by' => Auth::id(),  
                        'status' => 'active',
                        ]); 
                        
                        //print_r($status_save)."<br>";
                    }
                }
            }
            
            if($data->status == 1 )
            {
                $ManualOrders = ManualOrders::find($order_id);
 
                $ManualOrders->payment_status = "No Payments";
                // echo '<pre>';
                // print_r($data);
                 echo $order_id.', ts: '.$ManualOrdersList->current_payment_status.'<br>';
                $ManualOrders->save();
            }
            
        }
        dd('work');
        // return response()->json(['messege' => $data]);
        
    }
    
    public function CroneUpdateFare()
    { 
        $from_date= date('Y-m-01');
        $to_date = date('Y-m-t');
        $ManualOrdersLists = ManualOrders::leftJoin('orderpayments', 'orderpayments.order_id', '=', 'manual_orders.id')->
        leftJoin('customers', 'customers.id', '=', 'manual_orders.customers_id')
        ->select(
            'shipment_tracking_status',
            'manual_orders.consignment_id',
            'manual_orders.id',
            'manual_orders.payment_status',
            'manual_orders.fare',
            'manual_orders.city',
            'manual_orders.weight',
            'manual_orders.city'
            )
        ->where([
            ['manual_orders.city','>','0'],
            ['manual_orders.fare','=',null]
        ])
        ->whereBetween('manual_orders.created_at', [$from_date, $to_date])
        ->paginate(20);
        
        foreach($ManualOrdersLists as $ManualOrdersList)
        {
        
            $best_fare = array();
            $data['service_type_id'] = 1;
            $data['origin_city_id'] = 202;
            $data['destination_city_id'] = $request->destination_city_id;
            $data['estimated_weight'] = $request->estimated_weight;
            $data['shipping_mode_id'] = $request->shipping_mode_id;
            $data['amount'] = $request->amount;
            $calculation =  $this->CalculateDestinationRates($data);
            // dd($data,$calculation);
            return response()->json(['data' => $calculation]);
            
        }
        dd($ManualOrdersLists);
    }
    
    public function CroneUpdateShipmentTrackingStatus()
    { 
        $ManualOrdersLists = ManualOrders::leftJoin('orderpayments', 'orderpayments.order_id', '=', 'manual_orders.id')->
        leftJoin('customers', 'customers.id', '=', 'manual_orders.customers_id')
        ->select('shipment_tracking_status','manual_orders.consignment_id','manual_orders.id','manual_orders.payment_status')
        ->where([
            ['manual_orders.consignment_id','>','0'],
            ['shipment_tracking_status','!=' ,'Return - Delivered to Shipper'],
            ['shipment_tracking_status','!=' ,'Shipment - Delivered']
        ])
        ->paginate(200);
        
        // dd($ManualOrdersLists);
        foreach($ManualOrdersLists as $ManualOrdersList)
        {
            
            $id = $ManualOrdersList->consignment_id;
            $order_id = $ManualOrdersList->id;
            // echo $ManualOrdersList->id."<br>";
         
            $data = $this->TrackTraxOrder($ManualOrdersList->consignment_id,0);
            $order_id = $ManualOrdersList->id;
            if($data->status == 0)
            {
                // dd($data->details->tracking_history);
                if(isset($data->details->tracking_history))
                { 
                    $tracking_status = $data->details->tracking_history[0]->status;
                    
                    
                    
                    $ManualOrders = ManualOrders::find($order_id);
     
                    $ManualOrders->shipment_tracking_status = $tracking_status;
                     
                    $status_save = $ManualOrders->save();
                    // echo $order_id.': '.$tracking_status.' <br>';
                    // dd($data);
                    // if(!$data->current_payment_status)
                    // {
                        
                    // }
                    
                    echo $order_id.': '.$ManualOrdersList->current_payment_status.', ts: '.$tracking_status.'<br>';
                }
            
            
            // }
            //return response()->json(['messege' => $data]);
            } 
                
            
            
        }
        dd('');
        // return response()->json(['messege' => $data]);
        
    }
    
    
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    
    public function ShipmentStatusList($status,$date_from,$date_to)
    {
         
        $list=$this->list_query()->where([['manual_orders.payment_status','=',$status]])
        ->whereBetween('manual_orders.created_at', [$date_from, $date_to])
        ->orderBy('manual_orders.id','Desc')
        ->paginate($this->pagination);
        // dd($list);
        return view('admin.accounts.orders')->with('list',$list); 
        // $list_order = 'DESC';
        // if($status == 'pending'  )
        // {
        //     $list_order = 'ASC';
        // }
        // $list = Customers::rightJoin('manual_orders', 'manual_orders.customers_id', '=', 'customers.id')->where('manual_orders.payment_status','=',$status.'%')
        //     ->orderBy('manual_orders.id', $list_order)
        //     ->select($this->OrderFieldList())
        //     ->paginate(20);
        //     //dd($list);
        //     //dd($list);
        //     //$list = $list->all();
        //     //dd($list->all());
        // return view('client.orders.manual-orders.list')->with('list',$list);
        // dd($status); 
    }
    
    public function TrackingStatusList($status,$date_from,$date_to)
    {
         
        $list=$this->list_query()->where([['manual_orders.shipment_tracking_status','=',$status]])
        ->whereBetween('manual_orders.created_at', [$date_from, $date_to])
        ->orderBy('manual_orders.id','Desc')
        ->paginate($this->pagination);
        // dd($list);
        return view('admin.accounts.orders')->with('list',$list); 
        // $list_order = 'DESC';
        // if($status == 'pending'  )
        // {
        //     $list_order = 'ASC';
        // }
        // $list = Customers::rightJoin('manual_orders', 'manual_orders.customers_id', '=', 'customers.id')->where('manual_orders.payment_status','=',$status.'%')
        //     ->orderBy('manual_orders.id', $list_order)
        //     ->select($this->OrderFieldList())
        //     ->paginate(20);
        //     //dd($list);
        //     //dd($list);
        //     //$list = $list->all();
        //     //dd($list->all());
        // return view('client.orders.manual-orders.list')->with('list',$list);
        // dd($status);
    }
    
    
    
    
    
}
